<?php
/**
 * 採用情報ページ。
 *
 * 実サイト 50 件のナビ出現率で採用情報は 2 位（28%）。
 * 建設業は人手不足で、採用は問い合わせと並ぶ主要導線になっている。
 *
 * 応募フォームは Contact Form 7。ショートコードは ACF で差し替えられるので、
 * フォームを作り直しても ID をテンプレートに書き換えに行く必要がない。
 *
 * @package SoranowaKoumuten
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$sk_id      = get_the_ID();
	$sk_lead    = function_exists( 'get_field' ) ? (string) get_field( 'recruit_lead', $sk_id ) : '';
	$sk_image   = function_exists( 'get_field' ) ? (int) get_field( 'recruit_image', $sk_id ) : 0;
	$sk_message = function_exists( 'get_field' ) ? (string) get_field( 'recruit_message', $sk_id ) : '';
	$sk_table   = function_exists( 'get_field' ) ? (string) get_field( 'recruit_table', $sk_id ) : '';
	$sk_form    = function_exists( 'get_field' ) ? (string) get_field( 'recruit_form', $sk_id ) : '';
	$sk_rows    = function_exists( 'kc_parse_rows' ) ? kc_parse_rows( $sk_table ) : array();

	sk_page_header( get_the_title(), 'Recruit' );
	sk_breadcrumb();
	?>

	<div class="l-section">
		<div class="l-container">

			<?php if ( $sk_image > 0 ) : ?>
				<div class="p-work__hero">
					<?php
					sk_image(
						$sk_image,
						'sk-detail',
						array(
							'sizes' => '(min-width: 1180px) 1116px, 100vw',
						)
					);
					?>
				</div>
			<?php endif; ?>

			<?php if ( '' !== $sk_lead ) : ?>
				<p class="c-heading__lead p-work__body"><?php echo esc_html( $sk_lead ); ?></p>
			<?php endif; ?>

			<?php if ( '' !== $sk_message ) : ?>
				<div class="c-prose p-work__body">
					<?php
					// 改行を段落に変換する。wpautop は WordPress 標準の整形関数。
					echo wp_kses_post( wpautop( esc_html( $sk_message ) ) );
					?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $sk_rows ) ) : ?>
				<section class="l-section l-section--tight">
					<h2 class="p-work__spec-title"><?php esc_html_e( '募集要項', 'soranowa-koumuten' ); ?></h2>
					<table class="c-table">
						<tbody>
							<?php foreach ( $sk_rows as $sk_row ) : ?>
								<tr>
									<th scope="row"><?php echo esc_html( $sk_row[0] ); ?></th>
									<td><?php echo esc_html( $sk_row[1] ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</section>
			<?php endif; ?>

			<section class="l-section l-section--tight" id="entry">
				<h2 class="p-work__spec-title"><?php esc_html_e( '応募フォーム', 'soranowa-koumuten' ); ?></h2>

				<div class="p-form">
					<?php if ( '' !== trim( $sk_form ) ) : ?>
						<?php
						/*
						 * ショートコードの実行結果は Contact Form 7 が生成した HTML。
						 * do_shortcode() の戻り値をそのまま出す必要があるため、
						 * ここでは追加のエスケープをしない（フォーム要素が壊れるため）。
						 */
						echo do_shortcode( $sk_form ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					<?php else : ?>
						<p><?php esc_html_e( '応募フォームは準備中です。お電話にてお問い合わせください。', 'soranowa-koumuten' ); ?></p>
					<?php endif; ?>
				</div>
			</section>

			<div class="c-prose"><?php the_content(); ?></div>

		</div>
	</div>

	<?php
endwhile;

get_footer();
