<?php
/**
 * お知らせ一覧（投稿ページ）。
 *
 * home.php は「投稿一覧」に使われるテンプレート。
 * 「設定 > 表示設定」で投稿ページに固定ページ /news/ を割り当てているため、
 * /news/ でこのファイルが使われる（front-page.php があるのでトップとは競合しない）。
 *
 * @package SoranowaKoumuten
 */

defined( 'ABSPATH' ) || exit;

get_header();

$sk_posts_page_id = (int) get_option( 'page_for_posts' );
$sk_title         = $sk_posts_page_id > 0 ? get_the_title( $sk_posts_page_id ) : __( 'お知らせ', 'soranowa-koumuten' );

sk_page_header( $sk_title, 'News' );
sk_breadcrumb();
?>

<div class="l-section">
	<div class="l-container">

		<?php
		$sk_categories = get_categories( array( 'hide_empty' => true ) );

		if ( ! empty( $sk_categories ) ) :
			?>
			<nav class="c-tabs" aria-label="<?php esc_attr_e( 'カテゴリで絞り込む', 'soranowa-koumuten' ); ?>">
				<ul class="c-tabs__list">
					<li>
						<a class="c-tabs__item is-current" href="<?php echo esc_url( $sk_posts_page_id > 0 ? (string) get_permalink( $sk_posts_page_id ) : home_url( '/' ) ); ?>" aria-current="page">
							<?php esc_html_e( 'すべて', 'soranowa-koumuten' ); ?>
						</a>
					</li>
					<?php foreach ( $sk_categories as $sk_cat ) : ?>
						<li>
							<a class="c-tabs__item" href="<?php echo esc_url( (string) get_category_link( $sk_cat->term_id ) ); ?>">
								<?php echo esc_html( $sk_cat->name ); ?>
								<span class="c-tabs__count"><?php echo esc_html( (string) $sk_cat->count ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</nav>
		<?php endif; ?>

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
					'class'              => 'c-pagination',
					'mid_size'           => 1,
					'prev_text'          => __( '前へ', 'soranowa-koumuten' ),
					'next_text'          => __( '次へ', 'soranowa-koumuten' ),
					'screen_reader_text' => __( 'お知らせのページ送り', 'soranowa-koumuten' ),
				)
			);
			?>

		<?php else : ?>
			<p><?php esc_html_e( 'お知らせはまだありません。', 'soranowa-koumuten' ); ?></p>
		<?php endif; ?>

	</div>
</div>

<?php
get_footer();
