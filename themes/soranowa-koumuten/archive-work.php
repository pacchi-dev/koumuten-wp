<?php
/**
 * 施工実績の一覧（CPT アーカイブ）。
 *
 * テンプレート階層により /works/ で自動的にこのファイルが使われる。
 * 種別・エリアのタクソノミーアーカイブからも、
 * taxonomy-work_category.php / taxonomy-work_area.php 経由で同じ表示を使う。
 *
 * 表示件数と並び順はプラグイン側の pre_get_posts で設定している。
 * ここで WP_Query を作り直すと、ページネーションがメインクエリとずれる。
 *
 * @package SoranowaKoumuten
 */

defined( 'ABSPATH' ) || exit;

get_header();

$sk_is_tax = is_tax( array( 'work_category', 'work_area' ) );
$sk_term   = $sk_is_tax ? get_queried_object() : null;
$sk_title  = $sk_term instanceof WP_Term ? $sk_term->name : __( '施工実績', 'soranowa-koumuten' );

sk_page_header( $sk_title, 'Works' );
sk_breadcrumb();
?>

<div class="l-section">
	<div class="l-container">

		<?php sk_sample_notice( 'works' ); ?>

		<div class="l-works-tabs">
			<?php sk_work_category_tabs(); ?>
		</div>

		<?php if ( have_posts() ) : ?>

			<div class="c-grid c-grid--3">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/card-work' );
				endwhile;
				?>
			</div>

			<?php
			the_posts_pagination(
				array(
					'class'              => 'c-pagination',
					'mid_size'           => 1,
					'prev_text'          => __( '前へ', 'soranowa-koumuten' ),
					'next_text'          => __( '次へ', 'soranowa-koumuten' ),
					'screen_reader_text' => __( '施工実績のページ送り', 'soranowa-koumuten' ),
				)
			);
			?>

		<?php else : ?>

			<p><?php esc_html_e( '該当する施工実績はまだありません。', 'soranowa-koumuten' ); ?></p>

		<?php endif; ?>

	</div>
</div>

<?php
get_footer();
