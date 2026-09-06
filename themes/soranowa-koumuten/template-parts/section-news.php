<?php
/**
 * お知らせ（トップの新着）。
 *
 * お知らせは CPT を作らず標準の投稿を使っている。
 * カテゴリ・アーカイブ・RSS が標準機能のまま動くため。
 *
 * @package SoranowaKoumuten
 */

defined( 'ABSPATH' ) || exit;

$sk_front_id = get_the_ID();
$sk_heading  = function_exists( 'get_field' ) ? (string) get_field( 'news_heading', $sk_front_id ) : '';
$sk_count    = function_exists( 'get_field' ) ? (int) get_field( 'news_count', $sk_front_id ) : 4;
$sk_count    = $sk_count > 0 ? $sk_count : 4;

$sk_news = new WP_Query(
	array(
		'post_type'      => 'post',
		'posts_per_page' => $sk_count,
		'post_status'    => 'publish',
		'no_found_rows'  => true,
	)
);

if ( ! $sk_news->have_posts() ) {
	wp_reset_postdata();
	return;
}

$sk_posts_page_id = (int) get_option( 'page_for_posts' );
?>
<section class="l-section">
	<div class="l-container">

		<header class="c-heading c-heading--split">
			<div>
				<span class="c-heading__sub">News</span>
				<h2 class="c-heading__title"><?php echo esc_html( '' !== $sk_heading ? $sk_heading : __( 'お知らせ', 'soranowa-koumuten' ) ); ?></h2>
			</div>
			<?php if ( $sk_posts_page_id > 0 ) : ?>
				<p class="c-heading__lead">
					<a href="<?php echo esc_url( (string) get_permalink( $sk_posts_page_id ) ); ?>">
						<?php esc_html_e( 'お知らせ一覧へ', 'soranowa-koumuten' ); ?>
					</a>
				</p>
			<?php endif; ?>
		</header>

		<ul class="p-news-list">
			<?php
			while ( $sk_news->have_posts() ) :
				$sk_news->the_post();
				$sk_cats = get_the_category();
				?>
				<li class="p-news-item">
					<a class="p-news-item__link" href="<?php the_permalink(); ?>">
						<time class="p-news-item__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
						<span>
							<?php if ( ! empty( $sk_cats ) ) : ?>
								<span class="p-news-item__cat"><?php echo esc_html( $sk_cats[0]->name ); ?></span>
							<?php endif; ?>
						</span>
						<span class="p-news-item__title"><?php the_title(); ?></span>
					</a>
				</li>
				<?php
			endwhile;
			wp_reset_postdata();
			?>
		</ul>

	</div>
</section>
