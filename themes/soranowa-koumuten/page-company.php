<?php
/**
 * 会社概要ページ。
 *
 * 会社情報テーブルと沿革は、ACF 無料版に Repeater が無いため
 * textarea に「項目,値」形式で入力してもらい、kc_parse_rows() でパースする。
 * 不正な行は捨てるので、入力ミスで画面が壊れることはない。
 *
 * @package SoranowaKoumuten
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$sk_id      = get_the_ID();
	$sk_lead    = function_exists( 'get_field' ) ? (string) get_field( 'company_lead', $sk_id ) : '';
	$sk_image   = function_exists( 'get_field' ) ? (int) get_field( 'company_image', $sk_id ) : 0;
	$sk_note    = function_exists( 'get_field' ) ? (string) get_field( 'company_access_note', $sk_id ) : '';
	$sk_table   = function_exists( 'get_field' ) ? (string) get_field( 'company_table', $sk_id ) : '';
	$sk_history = function_exists( 'get_field' ) ? (string) get_field( 'company_history', $sk_id ) : '';

	$sk_rows    = function_exists( 'kc_parse_rows' ) ? kc_parse_rows( $sk_table ) : array();
	$sk_events  = function_exists( 'kc_parse_rows' ) ? kc_parse_rows( $sk_history ) : array();
	$sk_map     = sk_company( 'map_embed', '' );

	sk_page_header( get_the_title(), 'Company' );
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

			<?php if ( ! empty( $sk_rows ) ) : ?>
				<section class="l-section l-section--tight">
					<h2 class="p-work__spec-title"><?php esc_html_e( '会社情報', 'soranowa-koumuten' ); ?></h2>
					<table class="c-table">
						<tbody>
							<?php foreach ( $sk_rows as $sk_row ) : ?>
								<tr>
									<th scope="row"><?php echo esc_html( $sk_row[0] ); ?></th>
									<td><?php echo esc_html( $sk_row[1] ); ?></td>
								</tr>
							<?php endforeach; ?>
							<tr>
								<th scope="row"><?php esc_html_e( '所在地', 'soranowa-koumuten' ); ?></th>
								<td>〒<?php echo esc_html( sk_company( 'zip', '' ) ); ?> <?php echo esc_html( sk_company( 'address', '' ) ); ?></td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( '連絡先', 'soranowa-koumuten' ); ?></th>
								<td>
									TEL <?php echo esc_html( sk_company( 'tel', '' ) ); ?>
									／ FAX <?php echo esc_html( sk_company( 'fax', '' ) ); ?>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( '建設業許可', 'soranowa-koumuten' ); ?></th>
								<td><?php echo esc_html( sk_company( 'license', '' ) ); ?></td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( '対応エリア', 'soranowa-koumuten' ); ?></th>
								<td><?php echo esc_html( sk_company( 'area', '' ) ); ?></td>
							</tr>
						</tbody>
					</table>
				</section>
			<?php endif; ?>

			<?php if ( ! empty( $sk_events ) ) : ?>
				<section class="l-section l-section--tight">
					<h2 class="p-work__spec-title"><?php esc_html_e( '沿革', 'soranowa-koumuten' ); ?></h2>
					<dl class="c-history">
						<?php foreach ( $sk_events as $sk_event ) : ?>
							<dt><?php echo esc_html( $sk_event[0] ); ?></dt>
							<dd><?php echo esc_html( $sk_event[1] ); ?></dd>
						<?php endforeach; ?>
					</dl>
				</section>
			<?php endif; ?>

			<?php if ( '' !== $sk_map ) : ?>
				<section class="l-section l-section--tight">
					<h2 class="p-work__spec-title"><?php esc_html_e( 'アクセス', 'soranowa-koumuten' ); ?></h2>
					<div class="p-access__map">
						<iframe
							src="<?php echo esc_url( $sk_map ); ?>"
							title="<?php esc_attr_e( '所在地の地図', 'soranowa-koumuten' ); ?>"
							loading="lazy"
							referrerpolicy="no-referrer-when-downgrade"
							allowfullscreen></iframe>
					</div>
					<div class="p-access__info">
						<?php if ( '' !== $sk_note ) : ?>
							<p><?php echo esc_html( $sk_note ); ?></p>
						<?php endif; ?>
						<p><?php esc_html_e( '※ 住所はダミーのため、地図は姫路駅周辺を表示しています。', 'soranowa-koumuten' ); ?></p>
					</div>
				</section>
			<?php endif; ?>

			<div class="c-prose"><?php the_content(); ?></div>

		</div>
	</div>

	<?php
endwhile;

get_footer();
