<?php
/**
 * 施工実績（CPT work）の投入。
 *
 *   実行: docker exec koumuten_wp_cli wp --path=/var/www/html eval-file /scripts/seed-works.php
 *
 * 冪等性: スラッグで既存投稿を探し、あれば更新・なければ作成する。
 * 画像も取り込み元ファイル名で重複を判定し、二重登録しない。
 *
 * @package KoumutenSeed
 */

defined( 'ABSPATH' ) || exit;

require_once '/scripts/seed-lib.php';

WP_CLI::log( '==> 施工実績を登録します' );

$sk_path = '/scripts/seed/works.json';

if ( ! file_exists( $sk_path ) ) {
	WP_CLI::error( "シードファイルが見つかりません: {$sk_path}" );
}

$sk_works = json_decode( (string) file_get_contents( $sk_path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

if ( ! is_array( $sk_works ) ) {
	WP_CLI::error( "JSON の解析に失敗しました: {$sk_path}" );
}

$sk_count = 0;

foreach ( $sk_works as $sk_item ) {

	$sk_slug  = (string) ( $sk_item['slug'] ?? '' );
	$sk_title = (string) ( $sk_item['title'] ?? '' );

	if ( '' === $sk_slug || '' === $sk_title ) {
		continue;
	}

	// get_page_by_path() は固定ページ以外（CPT）にも使える。
	$sk_existing = get_page_by_path( $sk_slug, OBJECT, 'work' );

	$sk_postarr = array(
		'post_type'    => 'work',
		'post_status'  => 'publish',
		'post_title'   => $sk_title,
		'post_name'    => $sk_slug,
		'post_content' => (string) ( $sk_item['body'] ?? '' ),
	);

	/*
	 * 投稿日を竣工年月に合わせる。
	 * 既定では投入した日時が入るため、全件が同じ日付になり
	 * 一覧の並び順が意味を持たなくなる。
	 */
	if ( preg_match( '/(\d{4})年(\d{1,2})月/u', (string) ( $sk_item['completed_at'] ?? '' ), $sk_m ) ) {
		$sk_postarr['post_date'] = sprintf( '%04d-%02d-15 10:00:00', (int) $sk_m[1], (int) $sk_m[2] );
	}

	if ( $sk_existing instanceof WP_Post ) {
		$sk_postarr['ID'] = $sk_existing->ID;
	}

	$sk_post_id = wp_insert_post( $sk_postarr, true );

	if ( is_wp_error( $sk_post_id ) ) {
		WP_CLI::warning( "投稿の作成に失敗しました: {$sk_slug} / " . $sk_post_id->get_error_message() );
		continue;
	}

	$sk_post_id = (int) $sk_post_id;

	// 物件概要。
	foreach ( array( 'location', 'completed_at', 'structure', 'site_area', 'floor_area', 'family', 'duration', 'floor_plan_label', 'floor_plan', 'floor_plan_note' ) as $sk_field ) {
		update_field( $sk_field, (string) ( $sk_item[ $sk_field ] ?? '' ), $sk_post_id );
	}

	// タクソノミー。append を false にして毎回同期する。
	if ( '' !== (string) ( $sk_item['category'] ?? '' ) ) {
		wp_set_object_terms( $sk_post_id, (string) $sk_item['category'], 'work_category', false );
	}

	if ( '' !== (string) ( $sk_item['area'] ?? '' ) ) {
		wp_set_object_terms( $sk_post_id, (string) $sk_item['area'], 'work_area', false );
	}

	// アイキャッチ画像。
	$sk_image = (string) ( $sk_item['image'] ?? '' );

	if ( '' !== $sk_image ) {
		$sk_attachment_id = sk_seed_image(
			$sk_image,
			(string) ( $sk_item['imageAlt'] ?? $sk_title ),
			$sk_post_id
		);

		if ( $sk_attachment_id > 0 ) {
			set_post_thumbnail( $sk_post_id, $sk_attachment_id );
		}
	}

	++$sk_count;
	WP_CLI::log( "    {$sk_title}" );
}

WP_CLI::log( "    {$sk_count} 件を登録しました" );
