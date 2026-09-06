<?php
/**
 * トップページ。
 *
 * front-page.php はテンプレート階層の最優先で、
 * 「設定 > 表示設定」でフロントページに指定した固定ページに使われる。
 * 各セクションは template-parts に分けている。
 * 1 枚に書くと、後から「この帯を下層でも使いたい」となったときに複製が発生する。
 *
 * @package SoranowaKoumuten
 */

defined( 'ABSPATH' ) || exit;

get_header();

/*
 * ACF の値は「現在の投稿」に紐づく。
 * front-page.php ではループを回して現在の投稿をフロントページに固定してから
 * 各セクションを呼ぶ。こうすると各パートで ID を引き回さずに済む。
 */
while ( have_posts() ) :
	the_post();

	get_template_part( 'template-parts/section-hero' );
	get_template_part( 'template-parts/section-services' );
	get_template_part( 'template-parts/section-works' );
	get_template_part( 'template-parts/section-news' );
	get_template_part( 'template-parts/section-instagram' );
	get_template_part( 'template-parts/section-recruit' );
	get_template_part( 'template-parts/section-cta' );
	get_template_part( 'template-parts/section-access' );

endwhile;

get_footer();
