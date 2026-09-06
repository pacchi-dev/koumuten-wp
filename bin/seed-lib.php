<?php
/**
 * シードスクリプト共通の処理。
 *
 * 各 seed-*.php から require_once して使う。
 *
 * @package KoumutenSeed
 */

defined( 'ABSPATH' ) || exit;

/*
 * メディア関連の関数はフロント・管理画面のどちらでも自動では読み込まれない。
 * WP-CLI から使う場合は明示的に require する必要がある。
 * 移行スクリプトで最頻出のつまずきどころ。
 */
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

const SK_SEED_IMAGE_DIR = '/scripts/seed/images/';

/**
 * 画像をメディアライブラリへ取り込み、添付ファイル ID を返す。
 *
 * 同じファイルを二重に登録しないよう、取り込み元のファイル名を
 * メタデータに残して冪等性を確保する。
 *
 * @param string $file_name seed/images/ 配下のファイル名。
 * @param string $alt       代替テキスト。
 * @param int    $post_id   紐付ける投稿 ID（0 なら未紐付け）。
 * @return int 添付ファイル ID。失敗時は 0。
 */
function sk_seed_image( string $file_name, string $alt = '', int $post_id = 0 ): int {

	$path = SK_SEED_IMAGE_DIR . $file_name;

	if ( ! file_exists( $path ) ) {
		WP_CLI::warning( "画像が見つかりません: {$path}（bin/fetch-images.sh を実行してください）" );
		return 0;
	}

	$existing = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_sk_seed_source',   // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => $file_name,          // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);

	if ( ! empty( $existing ) ) {
		return (int) $existing[0];
	}

	$upload = wp_upload_bits( $file_name, null, (string) file_get_contents( $path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

	if ( ! empty( $upload['error'] ) ) {
		WP_CLI::warning( "アップロードに失敗しました: {$upload['error']}" );
		return 0;
	}

	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => (string) wp_check_filetype( $upload['file'] )['type'],
			'post_title'     => sanitize_file_name( pathinfo( $file_name, PATHINFO_FILENAME ) ),
			'post_status'    => 'inherit',
		),
		$upload['file'],
		$post_id
	);

	if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
		WP_CLI::warning( "添付ファイルの作成に失敗しました: {$file_name}" );
		return 0;
	}

	/*
	 * サムネイル各サイズの生成。
	 * これを呼ばないと add_image_size() で定義したサイズが作られず、
	 * srcset が育たないまま原寸だけが配信される。
	 */
	wp_update_attachment_metadata(
		(int) $attachment_id,
		wp_generate_attachment_metadata( (int) $attachment_id, $upload['file'] )
	);

	update_post_meta( (int) $attachment_id, '_wp_attachment_image_alt', $alt );
	update_post_meta( (int) $attachment_id, '_sk_seed_source', $file_name );

	return (int) $attachment_id;
}

/**
 * スラッグで固定ページを取得または作成する。
 *
 * @param string $slug    スラッグ。
 * @param string $title   タイトル。
 * @param string $content 本文。
 * @return int 固定ページ ID。
 */
function sk_seed_page( string $slug, string $title, string $content = '' ): int {

	$existing = get_page_by_path( $slug );

	$postarr = array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => $title,
		'post_name'    => $slug,
		'post_content' => $content,
	);

	if ( $existing instanceof WP_Post ) {
		$postarr['ID'] = $existing->ID;
	}

	$id = wp_insert_post( $postarr, true );

	if ( is_wp_error( $id ) ) {
		WP_CLI::warning( "固定ページの作成に失敗しました: {$slug} / " . $id->get_error_message() );
		return 0;
	}

	return (int) $id;
}
