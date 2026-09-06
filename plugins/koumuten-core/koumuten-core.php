<?php
/**
 * Plugin Name: Koumuten Core
 * Plugin URI:  https://example.test/
 * Description: 施工実績（カスタム投稿タイプ）・タクソノミー・ACF フィールド定義・会社情報設定を提供する。表示はテーマ側の責務。
 * Version:     1.0.0
 * Author:      pacchi
 * Requires PHP: 8.1
 * Text Domain: koumuten-core
 *
 * このプラグインは「データ構造」だけを持つ。
 * テーマを差し替えても施工実績と会社情報が失われないようにするため、
 * CPT / タクソノミー / カスタムフィールド / サイト共通設定はすべてここに置く。
 *
 * @package KoumutenCore
 */

defined( 'ABSPATH' ) || exit;

define( 'KC_VERSION', '1.0.0' );
define( 'KC_PATH', plugin_dir_path( __FILE__ ) );

require_once KC_PATH . 'inc/post-types.php';

/**
 * 有効化時の処理。
 *
 * CPT の URL（/works/{slug}/）は、リライトルールが DB に保存されて初めて機能する。
 * register_post_type() を書いただけでは 404 になる。
 * flush_rewrite_rules() は重い処理なので毎回は呼ばず、有効化時に一度だけ実行する。
 * 「CPT を作ったのに 404 になる」は実案件で最頻出のつまずきどころ。
 */
register_activation_hook( __FILE__, 'kc_activate' );
function kc_activate(): void {
	kc_register_post_types();
	kc_register_taxonomies();
	kc_insert_default_terms();
	flush_rewrite_rules();
}

/**
 * 無効化時にリライトルールを掃除する。
 */
register_deactivation_hook( __FILE__, 'kc_deactivate' );
function kc_deactivate(): void {
	flush_rewrite_rules();
}

/**
 * ACF が無い場合に管理画面で警告する。
 *
 * 必須プラグインが欠けたまま気づかず運用されるのを防ぐ。
 * 実案件では引き継ぎ時に「なぜか項目が出ない」の原因になりやすい。
 */
add_action( 'admin_notices', 'kc_admin_notice_acf_missing' );
function kc_admin_notice_acf_missing(): void {
	if ( function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html__( 'Koumuten Core: Advanced Custom Fields が有効になっていません。施工実績や各ページのカスタム項目が表示されません。', 'koumuten-core' )
	);
}

/**
 * 固定ページ ID をスラッグから引く。
 *
 * ACF のロケーション条件「特定の固定ページ」はページ ID で指定する仕様だが、
 * ID は環境（ローカル / 本番）ごとに変わるため直接書くと壊れる。
 * スラッグから毎回引くことで環境非依存にする。
 *
 * @param string $slug 固定ページのスラッグ。
 * @return int 見つからない場合は 0。
 */
function kc_page_id( string $slug ): int {
	$cache_key = 'kc_page_id_' . $slug;
	$cached    = wp_cache_get( $cache_key, 'koumuten-core' );

	if ( false !== $cached ) {
		return (int) $cached;
	}

	$page = get_page_by_path( $slug );
	$id   = $page ? (int) $page->ID : 0;

	wp_cache_set( $cache_key, $id, 'koumuten-core', HOUR_IN_SECONDS );

	return $id;
}

/**
 * 「項目,値」形式のテキストを配列に変換する。
 *
 * ACF 無料版には Repeater が無いため、可変長の表は
 * textarea に 1 行 1 レコードで入力してもらい、ここでパースする。
 * 入力ミスで画面が壊れないよう、不正な行は黙って捨てる。
 *
 * @param string $raw   textarea の生の値。
 * @param int    $cols  1 行あたりの列数。
 * @return array<int, array<int, string>> 行の配列。
 */
function kc_parse_rows( string $raw, int $cols = 2 ): array {
	/*
	 * 改行の分割に \R は使わない。
	 * \R は u 修飾子が無いとバイト 0x85（NEL）1 文字にもマッチする。
	 * UTF-8 の日本語には 0x85 を含む文字が多く（者 = E8 80 85、
	 * 入 = E5 85 A5、内 = E5 86 85）、文字の途中で行が割れて文字化けする。
	 * \r\n / \r / \n を明示すれば、UTF-8 は自己同期符号なので安全に分割できる。
	 */
	$rows = array();

	foreach ( preg_split( '/\r\n|\r|\n/', $raw ) ?: array() as $line ) {
		$line = trim( $line );

		if ( '' === $line ) {
			continue;
		}

		// 値そのものにカンマが入りうるので、区切りは列数-1 回までとする。
		$parts = array_map( 'trim', explode( ',', $line, $cols ) );

		if ( count( $parts ) < $cols ) {
			continue;
		}

		$rows[] = $parts;
	}

	return $rows;
}

/**
 * 改行区切りのテキストを配列に変換する（箇条書き用）。
 *
 * @param string $raw textarea の生の値。
 * @return array<int, string>
 */
function kc_parse_lines( string $raw ): array {
	/*
	 * 改行の分割に \R は使わない。
	 * \R は u 修飾子が無いとバイト 0x85（NEL）1 文字にもマッチする。
	 * UTF-8 の日本語には 0x85 を含む文字が多く（者 = E8 80 85、
	 * 入 = E5 85 A5、内 = E5 86 85）、文字の途中で行が割れて文字化けする。
	 * \r\n / \r / \n を明示すれば、UTF-8 は自己同期符号なので安全に分割できる。
	 */
	$lines = array_map( 'trim', preg_split( '/\r\n|\r|\n/', $raw ) ?: array() );

	return array_values( array_filter( $lines, static fn( string $l ): bool => '' !== $l ) );
}
