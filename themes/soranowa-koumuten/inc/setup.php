<?php
/**
 * テーマの基本設定。
 *
 * after_setup_theme は「テーマが読み込まれた直後」に走る hook。
 * ここで add_theme_support() を呼ばないと、アイキャッチ画像の項目自体が
 * 管理画面に出てこない。
 *
 * @package SoranowaKoumuten
 */

defined( 'ABSPATH' ) || exit;

/**
 * テーマサポートとメニューを登録する。
 */
add_action( 'after_setup_theme', 'sk_setup' );
function sk_setup(): void {

	// <title> を WordPress に生成させる。テンプレートに直接書かない。
	add_theme_support( 'title-tag' );

	// アイキャッチ画像。これが無いと施工実績に写真を設定できない。
	add_theme_support( 'post-thumbnails' );

	// 出力を HTML5 にする。指定しないと XHTML 風の古いマークアップが混ざる。
	add_theme_support(
		'html5',
		array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
	);

	// 埋め込み（Google マップ等）を親要素の幅に追従させる。
	add_theme_support( 'responsive-embeds' );

	add_theme_support( 'automatic-feed-links' );

	add_theme_support(
		'custom-logo',
		array(
			'height'      => 64,
			'width'       => 240,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	register_nav_menus(
		array(
			'primary' => __( 'ヘッダーメニュー', 'soranowa-koumuten' ),
			'footer'  => __( 'フッターメニュー', 'soranowa-koumuten' ),
		)
	);

	/*
	 * 用途別の画像サイズ。
	 *
	 * WordPress は登録済みのサイズのうち「元画像と同じ縦横比」のものを
	 * 自動で srcset に載せる。そのため、同じ縦横比で大中小を揃えておくと
	 * ブラウザが画面幅に応じて最適な 1 枚を選べる。
	 * サイズを 1 つしか定義しないと srcset が育たず、スマホにも
	 * 1920px の画像が落ちてくる。
	 *
	 * 第 4 引数 true は「切り抜く」指定。false だと長辺基準の縮小になり、
	 * カードの高さが画像ごとにばらつく。
	 */
	add_image_size( 'sk-hero-sm', 800, 450, true );    // 16:9
	add_image_size( 'sk-hero-md', 1280, 720, true );
	add_image_size( 'sk-hero', 1920, 1080, true );

	add_image_size( 'sk-thumb', 400, 300, true );      // 4:3
	add_image_size( 'sk-card', 800, 600, true );
	add_image_size( 'sk-card-lg', 1200, 900, true );

	add_image_size( 'sk-detail-md', 900, 600, true );  // 3:2
	add_image_size( 'sk-detail', 1400, 933, true );
}

/**
 * 抜粋の長さと末尾記号を日本語向けにする。
 *
 * 既定は「単語数」で数えるため、空白で区切らない日本語では効かない。
 *
 * @param string $excerpt 抜粋。
 * @return string
 */
add_filter( 'get_the_excerpt', 'sk_trim_excerpt' );
function sk_trim_excerpt( string $excerpt ): string {
	return mb_strimwidth( wp_strip_all_tags( $excerpt ), 0, 120, '…' );
}

/**
 * 不要な自動出力を止める。
 *
 * 既定では絵文字用の JS/CSS や、使っていない機能のメタタグが
 * 全ページに出力される。転送量とリクエストを減らすため外す。
 * 「写真が主役のサイトで帯域は写真に使う」という本サイトの方針に沿う。
 */
add_action( 'init', 'sk_dequeue_defaults' );
function sk_dequeue_defaults(): void {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
}

/**
 * ブロックエディタの既定 CSS を読み込まない。
 *
 * 本テーマはブロックのスタイルを使っていないため、
 * wp-block-library（約 100KB）は無駄なリクエストになる。
 */
add_action( 'wp_enqueue_scripts', 'sk_dequeue_block_styles', 100 );
function sk_dequeue_block_styles(): void {
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_style( 'classic-theme-styles' );
}
