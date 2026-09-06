<?php
/**
 * 最終フォールバック。
 *
 * テンプレート階層で他に該当が無かった場合に使われる。
 * WordPress のテーマは index.php と style.css があれば成立する。
 *
 * @package SoranowaKoumuten
 */

defined( 'ABSPATH' ) || exit;

get_header();

sk_page_header( wp_strip_all_tags( get_the_archive_title() ) );
sk_breadcrumb();
?>

<div class="l-section">
	<div class="l-container">
		<?php if ( have_posts() ) : ?>
			<ul class="p-news-list">
				<?php
				while ( have_posts() ) :
					the_post();
					?>
					<li class="p-news-item">
						<a class="p-news-item__link" href="<?php the_permalink(); ?>">
							<time class="p-news-item__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
							<span></span>
							<span class="p-news-item__title"><?php the_title(); ?></span>
						</a>
					</li>
					<?php
				endwhile;
				?>
			</ul>
			<?php the_posts_pagination( array( 'class' => 'c-pagination' ) ); ?>
		<?php else : ?>
			<p><?php esc_html_e( '記事がありません。', 'soranowa-koumuten' ); ?></p>
		<?php endif; ?>
	</div>
</div>

<?php
get_footer();
