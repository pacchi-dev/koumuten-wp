<?php
/**
 * 採用バナー。
 *
 * 実サイト 50 件のナビ出現率で採用情報が 2 位（28%）だった。
 * 建設業は人手不足で、採用が主要導線になっている。
 * ここでもトップから 1 クリックで到達できるようにする。
 *
 * @package SoranowaKoumuten
 */

defined( 'ABSPATH' ) || exit;

$sk_front_id = get_the_ID();
$sk_heading  = function_exists( 'get_field' ) ? (string) get_field( 'recruit_heading', $sk_front_id ) : '';
$sk_text     = function_exists( 'get_field' ) ? (string) get_field( 'recruit_text', $sk_front_id ) : '';
$sk_image    = function_exists( 'get_field' ) ? (int) get_field( 'recruit_image', $sk_front_id ) : 0;

if ( '' === $sk_heading ) {
	return;
}
?>
<section class="p-recruit-banner">
	<?php if ( $sk_image > 0 ) : ?>
		<div class="p-recruit-banner__media">
			<?php
			sk_image(
				$sk_image,
				'sk-hero-md',
				array(
					'sizes' => '100vw',
				)
			);
			?>
		</div>
		<div class="p-recruit-banner__scrim" aria-hidden="true"></div>
	<?php endif; ?>

	<div class="l-container">
		<div class="p-recruit-banner__body">
			<h2 class="p-recruit-banner__title"><?php echo esc_html( $sk_heading ); ?></h2>

			<?php if ( '' !== $sk_text ) : ?>
				<p class="p-recruit-banner__text"><?php echo esc_html( $sk_text ); ?></p>
			<?php endif; ?>

			<p class="p-recruit-banner__actions">
				<a class="c-button c-button--on-photo" href="<?php echo esc_url( home_url( '/recruit/' ) ); ?>">
					<?php esc_html_e( '採用情報を見る', 'soranowa-koumuten' ); ?>
				</a>
			</p>
		</div>
	</div>
</section>
