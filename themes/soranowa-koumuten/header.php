<?php
/**
 * ヘッダー。
 *
 * 実サイト 50 件の調査では、ヘッダー内に電話番号を置いているサイトが 0 件だった。
 * ヘッダーは「お問い合わせ」ボタンに寄せ、電話番号はフッター・CTA帯・
 * お問い合わせページに置く。
 *
 * @package SoranowaKoumuten
 */

defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="c-skip-link" href="#main"><?php esc_html_e( '本文へスキップ', 'soranowa-koumuten' ); ?></a>

<header class="l-header" id="header">
	<div class="l-header__inner">

		<p class="l-header__brand">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="l-header__brand-link">
				<?php if ( has_custom_logo() ) : ?>
					<?php the_custom_logo(); ?>
				<?php else : ?>
					<span class="l-header__brand-name"><?php echo esc_html( sk_company( 'name', (string) get_bloginfo( 'name' ) ) ); ?></span>
					<span class="l-header__brand-sub"><?php echo esc_html( sk_company( 'name_kana', '' ) ); ?></span>
				<?php endif; ?>
			</a>
		</p>

		<?php
		/*
		 * aria-expanded と aria-controls を付けるのは、
		 * 開閉状態をスクリーンリーダーに伝えるため。
		 * 見た目だけ切り替える実装だと、閉じているメニューが読み上げられてしまう。
		 */
		?>
		<button class="l-header__toggle" type="button"
			aria-expanded="false"
			aria-controls="global-nav"
			data-nav-toggle>
			<span class="l-header__toggle-bar" aria-hidden="true"></span>
			<span class="u-visually-hidden"><?php esc_html_e( 'メニューを開く', 'soranowa-koumuten' ); ?></span>
		</button>

		<nav class="l-nav" id="global-nav" aria-label="<?php esc_attr_e( 'メインメニュー', 'soranowa-koumuten' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'l-nav__list',
					'depth'          => 1,
					'fallback_cb'    => 'sk_nav_fallback',
				)
			);
			?>

			<p class="l-nav__cta">
				<a class="c-button c-button--primary" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">
					<?php esc_html_e( 'お問い合わせ', 'soranowa-koumuten' ); ?>
				</a>
			</p>
		</nav>

	</div>
</header>

<main id="main" class="l-main">
