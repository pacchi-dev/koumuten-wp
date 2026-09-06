<?php
/**
 * ヒーロー。
 *
 * 背景写真の上に白文字を置くため、暗幕（スクリム）でコントラストを担保する。
 * 詳細は style.css の .p-hero__scrim のコメントを参照。
 *
 * @package SoranowaKoumuten
 */

defined( 'ABSPATH' ) || exit;

$sk_front_id = get_the_ID();
$sk_image    = function_exists( 'get_field' ) ? (int) get_field( 'hero_image', $sk_front_id ) : 0;
$sk_eyebrow  = function_exists( 'get_field' ) ? (string) get_field( 'hero_eyebrow', $sk_front_id ) : '';
$sk_title    = function_exists( 'get_field' ) ? (string) get_field( 'hero_title', $sk_front_id ) : '';
$sk_text     = function_exists( 'get_field' ) ? (string) get_field( 'hero_text', $sk_front_id ) : '';
?>
<section class="p-hero">

	<div class="p-hero__media">
		<?php
		/*
		 * ヒーローは LCP（最大要素の描画）になる画像。
		 * ここだけ eager + fetchpriority="high" にする。
		 * すべて lazy にすると、かえって表示開始が遅くなる。
		 */
		sk_image(
			$sk_image,
			'sk-hero',
			array(
				'sizes' => '100vw',
				'eager' => true,
				'alt'   => '',
			)
		);
		?>
	</div>

	<div class="p-hero__scrim" aria-hidden="true"></div>

	<div class="p-hero__body">
		<?php if ( '' !== $sk_eyebrow ) : ?>
			<p class="p-hero__eyebrow"><?php echo esc_html( $sk_eyebrow ); ?></p>
		<?php endif; ?>

		<?php if ( '' !== $sk_title ) : ?>
			<h1 class="p-hero__title">
				<?php
				// 改行を <br> にする。wp_kses で <br> だけを許可し、他のタグは通さない。
				echo wp_kses( nl2br( esc_html( $sk_title ) ), array( 'br' => array() ) );
				?>
			</h1>
		<?php endif; ?>

		<?php if ( '' !== $sk_text ) : ?>
			<p class="p-hero__text"><?php echo esc_html( $sk_text ); ?></p>
		<?php endif; ?>

		<p class="p-hero__actions">
			<a class="c-button c-button--primary" href="<?php echo esc_url( (string) get_post_type_archive_link( 'work' ) ); ?>">
				<?php esc_html_e( '施工実績を見る', 'soranowa-koumuten' ); ?>
			</a>
			<a class="c-button c-button--on-photo" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">
				<?php esc_html_e( '相談してみる', 'soranowa-koumuten' ); ?>
			</a>
		</p>
	</div>
</section>
