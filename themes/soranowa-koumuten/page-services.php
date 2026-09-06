<?php
/**
 * 事業内容ページ。
 *
 * page-{slug}.php はテンプレート階層により、スラッグ services の
 * 固定ページで自動的に使われる。管理画面でテンプレートを選ぶ必要がない。
 *
 * ここの ACF がトップページの 3 カードの定義元にもなっている。
 *
 * @package SoranowaKoumuten
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$sk_id   = get_the_ID();
	$sk_lead = function_exists( 'get_field' ) ? (string) get_field( 'services_lead', $sk_id ) : '';

	sk_page_header( get_the_title(), 'Services' );
	sk_breadcrumb();
	?>

	<div class="l-section">
		<div class="l-container">

			<?php if ( '' !== $sk_lead ) : ?>
				<p class="c-heading__lead"><?php echo esc_html( $sk_lead ); ?></p>
			<?php endif; ?>

			<?php
			for ( $sk_i = 1; $sk_i <= 3; $sk_i++ ) :
				$sk_service = function_exists( 'get_field' ) ? get_field( 'service_' . $sk_i, $sk_id ) : null;

				if ( ! is_array( $sk_service ) || '' === (string) ( $sk_service['title'] ?? '' ) ) {
					continue;
				}

				$sk_points = function_exists( 'kc_parse_lines' )
					? kc_parse_lines( (string) ( $sk_service['points'] ?? '' ) )
					: array();
				?>
				<section class="p-service-detail" id="service-<?php echo esc_attr( (string) $sk_i ); ?>">

					<div class="p-service-detail__media">
						<?php
						sk_image(
							(int) ( $sk_service['image'] ?? 0 ),
							'sk-card-lg',
							array(
								'sizes' => '(min-width: 900px) 50vw, 100vw',
							)
						);
						?>
					</div>

					<div class="p-service-detail__body">
						<h2 class="p-service-card__title"><?php echo esc_html( (string) $sk_service['title'] ); ?></h2>

						<?php if ( '' !== (string) ( $sk_service['catch'] ?? '' ) ) : ?>
							<p class="p-service-card__catch"><?php echo esc_html( (string) $sk_service['catch'] ); ?></p>
						<?php endif; ?>

						<?php if ( '' !== (string) ( $sk_service['description'] ?? '' ) ) : ?>
							<p class="p-service-card__text"><?php echo esc_html( (string) $sk_service['description'] ); ?></p>
						<?php endif; ?>

						<?php if ( ! empty( $sk_points ) ) : ?>
							<ul class="p-service-detail__points">
								<?php foreach ( $sk_points as $sk_point ) : ?>
									<li><?php echo esc_html( $sk_point ); ?></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>

				</section>
			<?php endfor; ?>

			<div class="c-prose p-work__body"><?php the_content(); ?></div>

		</div>
	</div>

	<?php
endwhile;

get_footer();
