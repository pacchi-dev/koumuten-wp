<?php
/**
 * スタイル・スクリプトの読み込み。
 *
 * テーマ内のファイルは必ず get_template_directory_uri() で参照する。
 * パスを直接書くと、サブディレクトリ設置やドメイン変更で壊れる。
 * レンタルサーバーへの移設を前提にしているため、この点は徹底する。
 *
 * @package SoranowaKoumuten
 */

defined( 'ABSPATH' ) || exit;

/**
 * フロント側のアセットを登録する。
 */
add_action( 'wp_enqueue_scripts', 'sk_enqueue_assets' );
function sk_enqueue_assets(): void {

	$style_path = SK_DIR . '/style.css';

	/*
	 * バージョンにファイルの更新時刻を使う。
	 * 固定値にすると CSS を直してもブラウザキャッシュが残り、
	 * 「直したのに反映されない」という問い合わせにつながる。
	 */
	wp_enqueue_style(
		'sk-style',
		get_stylesheet_uri(),
		array(),
		file_exists( $style_path ) ? (string) filemtime( $style_path ) : SK_VERSION
	);

	$script_path = SK_DIR . '/assets/js/main.js';

	if ( file_exists( $script_path ) ) {
		wp_enqueue_script(
			'sk-main',
			get_template_directory_uri() . '/assets/js/main.js',
			array(),
			(string) filemtime( $script_path ),
			// フッターで読み込む。head だと描画をブロックする。
			true
		);
	}
}
