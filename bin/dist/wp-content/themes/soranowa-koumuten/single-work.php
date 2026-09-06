<?php
/**
 * 施工実績の詳細。
 *
 * 構成は「松尾工務店型」を採用している。
 * 実サイト調査では詳細ページの画像枚数に大きな幅があり
 * （ネイエ設計 230 枚 / ボックス・ワン 15 枚 / 松尾工務店 6 枚）、
 * 松尾工務店は物件概要テーブルが情報の主役だった。
 *
 * フリー素材では「同一物件の別カット」を揃えられず、
 * 枚数を増やすと別々の家の写真が並んで破綻するため、この型が素材制約にも合う。
 *
 * 構成: メイン写真 + 間取り図（インライン SVG） + 物件概要テーブル + 本文
 *
 * @package SoranowaKoumuten
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$sk_id       = get_the_ID();
	$sk_category = sk_first_term_name( $sk_id, 'work_category' );
	$sk_area     = sk_first_term_name( $sk_id, 'work_area' );
	$sk_specs    = sk_work_spec_rows( $sk_id );
	$sk_plan     = function_exists( 'get_field' ) ? (string) get_field( 'floor_plan', $sk_id ) : '';
	$sk_plan_note = function_exists( 'get_field' ) ? (string) get_field( 'floor_plan_note', $sk_id ) : '';
	$sk_plan_label = function_exists( 'get_field' ) ? (string) get_field( 'floor_plan_label', $sk_id ) : '';

	sk_breadcrumb();
	?>

	<article class="p-work">

		<div class="p-work__hero">
			<?php
			/*
			 * 詳細のメイン写真。一覧より大きく出すため sk-detail を使う。
			 * sizes は本文カラムの実幅に合わせて指定する。
			 */
			sk_post_image(
				$sk_id,
				'sk-detail',
				array(
					'sizes' => '(min-width: 1180px) 1116px, 100vw',
					'eager' => true,
				)
			);
			?>
		</div>

		<div class="l-container">
			<div class="p-work__layout">

				<div class="p-work__main">
					<p class="p-work__meta">
						<?php if ( '' !== $sk_category ) : ?>
							<span class="c-card__cat"><?php echo esc_html( $sk_category ); ?></span>
						<?php endif; ?>
						<?php if ( '' !== $sk_area ) : ?>
							<span><?php echo esc_html( $sk_area ); ?></span>
						<?php endif; ?>
					</p>

					<h1 class="p-work__title"><?php the_title(); ?></h1>

					<div class="c-prose p-work__body">
						<?php the_content(); ?>
					</div>

					<?php sk_sample_notice( 'works' ); ?>
				</div>

				<aside class="p-work__aside">

					<?php if ( '' !== trim( $sk_plan ) ) : ?>
						<div>
							<h2 class="p-work__plan-title"><?php esc_html_e( '間取り図', 'soranowa-koumuten' ); ?></h2>
							<?php sk_render_floor_plan( $sk_plan, $sk_plan_note, $sk_plan_label ); ?>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $sk_specs ) ) : ?>
						<div>
							<h2 class="p-work__spec-title"><?php esc_html_e( '物件概要', 'soranowa-koumuten' ); ?></h2>
							<table class="c-table">
								<tbody>
									<?php foreach ( $sk_specs as $sk_label => $sk_value ) : ?>
										<tr>
											<th scope="row"><?php echo esc_html( $sk_label ); ?></th>
											<td><?php echo esc_html( $sk_value ); ?></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php endif; ?>

				</aside>

			</div>

			<nav class="p-work__nav" aria-label="<?php esc_attr_e( '施工実績の前後', 'soranowa-koumuten' ); ?>">
				<?php
				previous_post_link( '%link', '← ' . esc_html__( '前の実績', 'soranowa-koumuten' ) );
				echo '<a href="' . esc_url( (string) get_post_type_archive_link( 'work' ) ) . '">' . esc_html__( '一覧へ戻る', 'soranowa-koumuten' ) . '</a>';
				next_post_link( '%link', esc_html__( '次の実績', 'soranowa-koumuten' ) . ' →' );
				?>
			</nav>
		</div>

	</article>

	<?php
endwhile;

get_footer();
