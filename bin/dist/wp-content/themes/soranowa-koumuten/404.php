<?php
/**
 * 404 ページ。
 *
 * 行き止まりにしない。工務店サイトでは施工実績とお問い合わせが
 * 主要導線なので、そこへ戻れる出口を置く。
 *
 * @package SoranowaKoumuten
 */

defined( 'ABSPATH' ) || exit;

get_header();
sk_breadcrumb();
?>

<div class="l-container">
	<div class="p-404">
		<p class="p-404__code">404</p>
		<h1 class="p-404__title"><?php esc_html_e( 'ページが見つかりませんでした', 'soranowa-koumuten' ); ?></h1>
		<p class="p-404__text">
			<?php esc_html_e( 'お探しのページは移動または削除された可能性があります。', 'soranowa-koumuten' ); ?>
		</p>
		<p class="p-404__actions">
			<a class="c-button c-button--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<?php esc_html_e( 'トップページへ', 'soranowa-koumuten' ); ?>
			</a>
			<a class="c-button c-button--ghost" href="<?php echo esc_url( (string) get_post_type_archive_link( 'work' ) ); ?>">
				<?php esc_html_e( '施工実績を見る', 'soranowa-koumuten' ); ?>
			</a>
		</p>
	</div>
</div>

<?php
get_footer();
