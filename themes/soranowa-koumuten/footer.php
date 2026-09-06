<?php
/**
 * フッター。
 *
 * 電話番号・住所・営業時間はここに置く（ヘッダーには置かない）。
 * 値はカスタマイザーの「会社情報」から取得する。
 *
 * @package SoranowaKoumuten
 */

defined( 'ABSPATH' ) || exit;

$sk_tel = sk_company( 'tel', '' );
?>
</main><!-- /.l-main -->

<footer class="l-footer">
	<div class="l-container">

		<div class="l-footer__grid">

			<div class="l-footer__company">
				<p class="l-footer__name"><?php echo esc_html( sk_company( 'name', (string) get_bloginfo( 'name' ) ) ); ?></p>

				<address class="l-footer__address">
					<span class="l-footer__zip">〒<?php echo esc_html( sk_company( 'zip', '' ) ); ?></span>
					<?php echo esc_html( sk_company( 'address', '' ) ); ?>
				</address>

				<dl class="l-footer__meta">
					<?php if ( '' !== $sk_tel ) : ?>
						<dt><?php esc_html_e( 'TEL', 'soranowa-koumuten' ); ?></dt>
						<dd>
							<a class="l-footer__tel" href="<?php echo esc_url( kc_tel_href( $sk_tel ) ); ?>"><?php echo esc_html( $sk_tel ); ?></a>
						</dd>
					<?php endif; ?>

					<dt><?php esc_html_e( '営業時間', 'soranowa-koumuten' ); ?></dt>
					<dd><?php echo esc_html( sk_company( 'hours', '' ) ); ?>／<?php esc_html_e( '定休日', 'soranowa-koumuten' ); ?> <?php echo esc_html( sk_company( 'holiday', '' ) ); ?></dd>

					<dt><?php esc_html_e( '対応エリア', 'soranowa-koumuten' ); ?></dt>
					<dd><?php echo esc_html( sk_company( 'area', '' ) ); ?></dd>
				</dl>

				<?php $sk_instagram = sk_company( 'instagram_url', '' ); ?>
				<?php if ( '' !== $sk_instagram ) : ?>
					<p class="l-footer__sns">
						<a href="<?php echo esc_url( $sk_instagram ); ?>" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Instagram', 'soranowa-koumuten' ); ?>
							<span class="u-visually-hidden"><?php esc_html_e( '（新しいタブで開きます）', 'soranowa-koumuten' ); ?></span>
						</a>
					</p>
				<?php endif; ?>
			</div>

			<nav class="l-footer__nav" aria-label="<?php esc_attr_e( 'フッターメニュー', 'soranowa-koumuten' ); ?>">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'footer',
						'container'      => false,
						'menu_class'     => 'l-footer__list',
						'depth'          => 1,
						'fallback_cb'    => 'sk_nav_fallback',
					)
				);
				?>
			</nav>

		</div>

		<?php
		/*
		 * 架空であることの明示（必須 4 項目のうちの 1 つ）。
		 * 運営者が消せないよう、意図的にハードコードしている。
		 */
		?>
		<p class="l-footer__disclaimer">
			<?php esc_html_e( '本サイトは架空の企業を想定した制作サンプルです。社名・住所・電話番号・施工実績はすべて実在しません。', 'soranowa-koumuten' ); ?>
		</p>

		<p class="l-footer__copyright">
			&copy; <?php echo esc_html( (string) gmdate( 'Y' ) ); ?> <?php echo esc_html( sk_company( 'name', (string) get_bloginfo( 'name' ) ) ); ?>
		</p>

	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
