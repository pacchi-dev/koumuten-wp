<?php
/**
 * 汎用アーカイブ（カテゴリ・月別など）。
 *
 * テンプレート階層では category.php → archive.php → index.php の順に探される。
 * category.php を作らず archive.php で受けているのは、
 * カテゴリと月別で表示を変える必要がないため。
 *
 * @package SoranowaKoumuten
 */

defined( 'ABSPATH' ) || exit;

get_header();

sk_page_header( wp_strip_all_tags( get_the_archive_title() ), 'News' );
sk_breadcrumb();
?>

<div class="l-section">
	<div class="l-container">

		<?php if ( have_posts() ) : ?>

			<ul class="p-news-list">
				<?php
				while ( have_posts() ) :
					the_post();
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
				?>
			</ul>

			<?php
			the_posts_pagination(
				array(
					'class'     => 'c-pagination',
					'mid_size'  => 1,
					'prev_text' => __( '前へ', 'soranowa-koumuten' ),
					'next_text' => __( '次へ', 'soranowa-koumuten' ),
				)
			);
			?>

		<?php else : ?>
			<p><?php esc_html_e( '該当する記事はありません。', 'soranowa-koumuten' ); ?></p>
		<?php endif; ?>

	</div>
</div>

<?php
get_footer();
