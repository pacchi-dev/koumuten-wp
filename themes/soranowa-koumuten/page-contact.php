<?php
/**
 * お問い合わせページ。
 *
 * フォームは Contact Form 7。実サイト 50 件で最多のプラグイン（42%）であり、
 * 発注者が管理画面から項目を変更できることが採用理由。
 *
 * 電話番号はここにも置く（ヘッダーには置かない方針）。
 * フォームより電話が早い層が一定数いるため。
 *
 * @package SoranowaKoumuten
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$sk_id   = get_the_ID();
	$sk_lead = function_exists( 'get_field' ) ? (string) get_field( 'contact_lead', $sk_id ) : '';
	$sk_form = function_exists( 'get_field' ) ? (string) get_field( 'contact_form', $sk_id ) : '';
	$sk_tel  = sk_company( 'tel', '' );

	sk_page_header( get_the_title(), 'Contact' );
	sk_breadcrumb();
	?>

	<div class="l-section">
		<div class="l-container">

			<?php if ( '' !== $sk_lead ) : ?>
				<p class="c-heading__lead"><?php echo esc_html( $sk_lead ); ?></p>
			<?php endif; ?>

			<?php if ( '' !== $sk_tel ) : ?>
				<section class="l-section l-section--tight">
					<h2 class="p-work__spec-title"><?php esc_html_e( 'お電話でのお問い合わせ', 'soranowa-koumuten' ); ?></h2>
					<p>
						<a class="p-contact__tel" href="<?php echo esc_url( kc_tel_href( $sk_tel ) ); ?>"><?php echo esc_html( $sk_tel ); ?></a>
					</p>
					<p class="p-access__info">
						<?php echo esc_html( sk_company( 'hours', '' ) ); ?>
						／<?php esc_html_e( '定休日', 'soranowa-koumuten' ); ?> <?php echo esc_html( sk_company( 'holiday', '' ) ); ?>
					</p>
				</section>
			<?php endif; ?>

			<section class="l-section l-section--tight">
				<h2 class="p-work__spec-title"><?php esc_html_e( 'フォームでのお問い合わせ', 'soranowa-koumuten' ); ?></h2>

				<div class="p-form">
					<p class="p-form__note">
						<?php esc_html_e( '※ は必須項目です。内容を確認のうえ、2〜3営業日以内にご返信します。', 'soranowa-koumuten' ); ?>
					</p>

					<?php if ( '' !== trim( $sk_form ) ) : ?>
						<?php echo do_shortcode( $sk_form ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php else : ?>
						<p><?php esc_html_e( 'フォームは準備中です。お電話にてお問い合わせください。', 'soranowa-koumuten' ); ?></p>
					<?php endif; ?>
				</div>
			</section>

			<div class="c-prose"><?php the_content(); ?></div>

		</div>
	</div>

	<?php
endwhile;

get_footer();
