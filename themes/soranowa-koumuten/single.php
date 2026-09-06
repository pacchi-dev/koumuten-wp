<?php
/**
 * お知らせ詳細（通常投稿）。
 *
 * @package SoranowaKoumuten
 */

defined( 'ABSPATH' ) || exit;

get_header();

sk_breadcrumb();

while ( have_posts() ) :
	the_post();
	$sk_cats = get_the_category();
	?>

	<article class="l-section">
		<div class="l-container">

			<header class="c-heading">
				<p class="c-card__meta">
					<?php if ( ! empty( $sk_cats ) ) : ?>
						<span class="c-card__cat"><?php echo esc_html( $sk_cats[0]->name ); ?></span>
					<?php endif; ?>
					<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
				</p>
				<h1 class="c-heading__title"><?php the_title(); ?></h1>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<div class="p-work__hero">
					<?php
					sk_post_image(
						get_the_ID(),
						'sk-detail',
						array( 'sizes' => '(min-width: 1180px) 1116px, 100vw' )
					);
					?>
				</div>
			<?php endif; ?>

			<div class="c-prose p-work__body"><?php the_content(); ?></div>

			<nav class="p-work__nav" aria-label="<?php esc_attr_e( '記事の前後', 'soranowa-koumuten' ); ?>">
				<?php
				previous_post_link( '%link', '← ' . esc_html__( '前の記事', 'soranowa-koumuten' ) );
				$sk_posts_page_id = (int) get_option( 'page_for_posts' );
				if ( $sk_posts_page_id > 0 ) {
					echo '<a href="' . esc_url( (string) get_permalink( $sk_posts_page_id ) ) . '">' . esc_html__( '一覧へ戻る', 'soranowa-koumuten' ) . '</a>';
				}
				next_post_link( '%link', esc_html__( '次の記事', 'soranowa-koumuten' ) . ' →' );
				?>
			</nav>

		</div>
	</article>

	<?php
endwhile;

get_footer();
