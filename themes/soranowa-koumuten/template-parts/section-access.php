<?php
/**
 * アクセス（Google マップ埋め込み）。
 *
 * 埋め込み URL はカスタマイザーで設定する。
 * iframe には loading="lazy" を付ける。地図はページ下部にあり、
 * 初期表示では見えないことが多いため、先に読み込む必要が無い。
 *
 * @package SoranowaKoumuten
 */

defined( 'ABSPATH' ) || exit;

$sk_front_id = get_the_ID();
$sk_heading  = function_exists( 'get_field' ) ? (string) get_field( 'access_heading', $sk_front_id ) : '';
$sk_note     = function_exists( 'get_field' ) ? (string) get_field( 'access_note', $sk_front_id ) : '';
$sk_map      = sk_company( 'map_embed', '' );

if ( '' === $sk_map ) {
	return;
}
?>
<section class="l-section l-section--surface">
	<div class="l-container">

		<header class="c-heading">
			<span class="c-heading__sub">Access</span>
			<h2 class="c-heading__title"><?php echo esc_html( '' !== $sk_heading ? $sk_heading : __( 'アクセス', 'soranowa-koumuten' ) ); ?></h2>
		</header>

		<div class="p-access__map">
			<iframe
				src="<?php echo esc_url( $sk_map ); ?>"
				title="<?php esc_attr_e( '所在地の地図', 'soranowa-koumuten' ); ?>"
				loading="lazy"
				referrerpolicy="no-referrer-when-downgrade"
				allowfullscreen></iframe>
		</div>

		<div class="p-access__info">
			<p>
				〒<?php echo esc_html( sk_company( 'zip', '' ) ); ?>
				<?php echo esc_html( sk_company( 'address', '' ) ); ?>
			</p>
			<?php if ( '' !== $sk_note ) : ?>
				<p><?php echo esc_html( $sk_note ); ?></p>
			<?php endif; ?>
			<p><?php esc_html_e( '※ 住所はダミーのため、地図は姫路駅周辺を表示しています。', 'soranowa-koumuten' ); ?></p>
		</div>

	</div>
</section>
