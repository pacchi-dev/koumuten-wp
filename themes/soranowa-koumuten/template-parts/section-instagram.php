<?php
/**
 * Instagram セクション。
 *
 * 公式 API は使わない。
 *   1. Instagram Basic Display API は 2024 年 12 月に提供終了
 *   2. oEmbed は Meta のアプリ審査が必要
 *   3. 本サイトは架空企業のため実アカウントが存在しない
 *
 * ACF で登録した 6 枚のグリッドと、アカウントへの外部リンクで構成する。
 * 実アカウント運用時の差し替え手順は README に記載。
 *
 * ACF 無料版に Repeater が無いため、group に 6 個のサブフィールドを持たせている。
 *
 * @package SoranowaKoumuten
 */

defined( 'ABSPATH' ) || exit;

$sk_front_id = get_the_ID();
$sk_heading  = function_exists( 'get_field' ) ? (string) get_field( 'instagram_heading', $sk_front_id ) : '';
$sk_lead     = function_exists( 'get_field' ) ? (string) get_field( 'instagram_lead', $sk_front_id ) : '';
$sk_group    = function_exists( 'get_field' ) ? get_field( 'instagram', $sk_front_id ) : array();
$sk_profile  = function_exists( 'kc_company' ) ? kc_company( 'instagram_url' ) : '';

$sk_images = array();

if ( is_array( $sk_group ) ) {
	for ( $i = 1; $i <= 6; $i++ ) {
		$id = (int) ( $sk_group[ 'image_' . $i ] ?? 0 );

		if ( $id > 0 ) {
			$sk_images[] = $id;
		}
	}
}

if ( empty( $sk_images ) ) {
	return;
}
?>
<section class="l-section l-section--tight">
	<div class="l-container">

		<header class="c-heading">
			<span class="c-heading__sub">Instagram</span>
			<h2 class="c-heading__title"><?php echo esc_html( '' !== $sk_heading ? $sk_heading : 'Instagram' ); ?></h2>
			<?php if ( '' !== $sk_lead ) : ?>
				<p class="c-heading__lead"><?php echo esc_html( $sk_lead ); ?></p>
			<?php endif; ?>
		</header>

		<ul class="p-instagram__grid">
			<?php foreach ( $sk_images as $sk_image_id ) : ?>
				<li class="p-instagram__item">
					<?php
					sk_image(
						$sk_image_id,
						'sk-thumb',
						array(
							'sizes' => '(min-width: 900px) 180px, 33vw',
							'alt'   => '',
						)
					);
					?>
				</li>
			<?php endforeach; ?>
		</ul>

		<?php if ( '' !== $sk_profile ) : ?>
			<p>
				<a class="p-instagram__link" href="<?php echo esc_url( $sk_profile ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'アカウントを見る', 'soranowa-koumuten' ); ?>
					<span class="u-visually-hidden"><?php esc_html_e( '（新しいタブで開きます）', 'soranowa-koumuten' ); ?></span>
				</a>
			</p>
		<?php endif; ?>

	</div>
</section>
