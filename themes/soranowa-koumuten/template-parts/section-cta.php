<?php
/**
 * CTA 帯（電話番号 + お問い合わせ）。
 *
 * ヘッダーには電話番号を置かない（実サイト 50 件で 0%）。
 * 代わりに CTA 帯・フッター・お問い合わせページに置く。
 *
 * 電話番号はカスタマイザーの「会社情報」から取得する。
 * 番号が変わったときにテンプレートを触らずに済ませるため。
 *
 * @package SoranowaKoumuten
 */

defined( 'ABSPATH' ) || exit;

$sk_front_id = get_the_ID();
$sk_heading  = function_exists( 'get_field' ) ? (string) get_field( 'cta_heading', $sk_front_id ) : '';
$sk_text     = function_exists( 'get_field' ) ? (string) get_field( 'cta_text', $sk_front_id ) : '';
$sk_tel      = sk_company( 'tel', '' );
$sk_hours    = sk_company( 'hours', '' );
$sk_holiday  = sk_company( 'holiday', '' );
?>
<section class="p-cta">
	<div class="l-container">
		<div class="p-cta__inner">

			<div>
				<h2 class="p-cta__title"><?php echo esc_html( '' !== $sk_heading ? $sk_heading : __( 'まずはお気軽にご相談ください', 'soranowa-koumuten' ) ); ?></h2>
				<?php if ( '' !== $sk_text ) : ?>
					<p class="p-cta__text"><?php echo esc_html( $sk_text ); ?></p>
				<?php endif; ?>
			</div>

			<div class="p-cta__contact">
				<?php if ( '' !== $sk_tel ) : ?>
					<p class="p-cta__tel-label"><?php esc_html_e( 'お電話でのご相談', 'soranowa-koumuten' ); ?></p>
					<p>
						<a class="p-cta__tel" href="<?php echo esc_url( kc_tel_href( $sk_tel ) ); ?>"><?php echo esc_html( $sk_tel ); ?></a>
					</p>
					<p class="p-cta__hours">
						<?php echo esc_html( $sk_hours ); ?>
						<?php if ( '' !== $sk_holiday ) : ?>
							／<?php esc_html_e( '定休日', 'soranowa-koumuten' ); ?> <?php echo esc_html( $sk_holiday ); ?>
						<?php endif; ?>
					</p>
				<?php endif; ?>

				<p>
					<a class="c-button p-cta__button" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">
						<?php esc_html_e( 'フォームで問い合わせる', 'soranowa-koumuten' ); ?>
					</a>
				</p>
			</div>

		</div>
	</div>
</section>
