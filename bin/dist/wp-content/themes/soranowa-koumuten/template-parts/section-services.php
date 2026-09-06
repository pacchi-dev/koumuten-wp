<?php
/**
 * 事業内容（トップの 3 カード）。
 *
 * 内容は事業内容ページ（/services/）の ACF を唯一の定義元として読む。
 * 同じ文言をトップと下層の 2 箇所で編集させると必ず食い違うため。
 *
 * @package SoranowaKoumuten
 */

defined( 'ABSPATH' ) || exit;

$sk_front_id   = get_the_ID();
$sk_heading    = function_exists( 'get_field' ) ? (string) get_field( 'services_heading', $sk_front_id ) : '';
$sk_lead       = function_exists( 'get_field' ) ? (string) get_field( 'services_lead', $sk_front_id ) : '';
$sk_services_id = function_exists( 'kc_page_id' ) ? kc_page_id( 'services' ) : 0;

$sk_services = array();

if ( $sk_services_id > 0 && function_exists( 'get_field' ) ) {
	for ( $i = 1; $i <= 3; $i++ ) {
		$service = get_field( 'service_' . $i, $sk_services_id );

		if ( is_array( $service ) && '' !== (string) ( $service['title'] ?? '' ) ) {
			$sk_services[] = $service;
		}
	}
}

if ( empty( $sk_services ) ) {
	return;
}
?>
<section class="l-section">
	<div class="l-container">

		<header class="c-heading c-heading--split">
			<div>
				<span class="c-heading__sub">Services</span>
				<h2 class="c-heading__title"><?php echo esc_html( '' !== $sk_heading ? $sk_heading : __( '事業内容', 'soranowa-koumuten' ) ); ?></h2>
			</div>
			<?php if ( '' !== $sk_lead ) : ?>
				<p class="c-heading__lead"><?php echo esc_html( $sk_lead ); ?></p>
			<?php endif; ?>
		</header>

		<div class="c-grid c-grid--3">
			<?php foreach ( $sk_services as $sk_service ) : ?>
				<article class="p-service-card">
					<a class="c-card" href="<?php echo esc_url( (string) get_permalink( $sk_services_id ) ); ?>">
						<div class="p-service-card__media">
							<?php
							sk_image(
								(int) ( $sk_service['image'] ?? 0 ),
								'sk-card',
								array(
									'sizes' => '(min-width: 1180px) 366px, (min-width: 900px) 33vw, (min-width: 600px) 50vw, 100vw',
								)
							);
							?>
						</div>
						<h3 class="p-service-card__title"><?php echo esc_html( (string) ( $sk_service['title'] ?? '' ) ); ?></h3>
						<?php if ( '' !== (string) ( $sk_service['catch'] ?? '' ) ) : ?>
							<p class="p-service-card__catch"><?php echo esc_html( (string) $sk_service['catch'] ); ?></p>
						<?php endif; ?>
						<?php if ( '' !== (string) ( $sk_service['description'] ?? '' ) ) : ?>
							<p class="p-service-card__text"><?php echo esc_html( mb_strimwidth( (string) $sk_service['description'], 0, 90, '…' ) ); ?></p>
						<?php endif; ?>
					</a>
				</article>
			<?php endforeach; ?>
		</div>

	</div>
</section>
