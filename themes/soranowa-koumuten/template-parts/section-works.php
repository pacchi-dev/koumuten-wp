<?php
/**
 * 施工実績（トップの新着）。
 *
 * WP_Query を新規に作るのは、メインクエリ（= 固定ページ 1 件）とは別に
 * 施工実績を取りたいため。取得後は必ず wp_reset_postdata() で
 * グローバルの $post を戻す。戻し忘れると以降のテンプレートで
 * タイトルや ID がずれる（実案件で頻出の不具合）。
 *
 * @package SoranowaKoumuten
 */

defined( 'ABSPATH' ) || exit;

$sk_front_id = get_the_ID();
$sk_heading  = function_exists( 'get_field' ) ? (string) get_field( 'works_heading', $sk_front_id ) : '';
$sk_lead     = function_exists( 'get_field' ) ? (string) get_field( 'works_lead', $sk_front_id ) : '';
$sk_count    = function_exists( 'get_field' ) ? (int) get_field( 'works_count', $sk_front_id ) : 6;
$sk_count    = $sk_count > 0 ? $sk_count : 6;

$sk_works = new WP_Query(
	array(
		'post_type'              => 'work',
		'posts_per_page'         => $sk_count,
		'post_status'            => 'publish',
		'ignore_sticky_posts'    => true,
		// 件数だけ欲しいわけではないので、不要なクエリを止めて軽くする。
		'no_found_rows'          => true,
		'update_post_term_cache' => true,
	)
);

if ( ! $sk_works->have_posts() ) {
	wp_reset_postdata();
	return;
}
?>
<section class="l-section l-section--surface">
	<div class="l-container">

		<header class="c-heading c-heading--split">
			<div>
				<span class="c-heading__sub">Works</span>
				<h2 class="c-heading__title"><?php echo esc_html( '' !== $sk_heading ? $sk_heading : __( '施工実績', 'soranowa-koumuten' ) ); ?></h2>
			</div>
			<?php if ( '' !== $sk_lead ) : ?>
				<p class="c-heading__lead"><?php echo esc_html( $sk_lead ); ?></p>
			<?php endif; ?>
		</header>

		<div class="c-grid c-grid--3">
			<?php
			while ( $sk_works->have_posts() ) :
				$sk_works->the_post();
				get_template_part( 'template-parts/card-work' );
			endwhile;
			wp_reset_postdata();
			?>
		</div>

		<p class="c-more">
			<a class="c-button c-button--ghost" href="<?php echo esc_url( (string) get_post_type_archive_link( 'work' ) ); ?>">
				<?php esc_html_e( '施工実績をすべて見る', 'soranowa-koumuten' ); ?>
			</a>
		</p>

	</div>
</section>
