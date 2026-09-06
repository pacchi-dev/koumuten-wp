<?php
/**
 * 固定ページとメニューの作成。
 *
 *   実行: docker exec koumuten_wp_cli wp --path=/var/www/html eval-file /scripts/seed-pages.php
 *
 * ACF のフィールド値は別スクリプト（seed-fields.php）で入れる。
 * 下層ページのフィールドグループは「そのページが存在すること」を条件に
 * 登録しているため、ページ作成と同じプロセスではまだ登録されていない。
 * プロセスを分けることで、値が ACF のフィールドとして正しく保存される。
 *
 * @package KoumutenSeed
 */

defined( 'ABSPATH' ) || exit;

require_once '/scripts/seed-lib.php';

WP_CLI::log( '==> 固定ページを作成します' );

$sk_defs = array(
	array( 'home', 'ホーム', '' ),
	array( 'services', '事業内容', '' ),
	array( 'company', '会社概要', '' ),
	array( 'recruit', '採用情報', '' ),
	array( 'news', 'お知らせ', '' ),
	array( 'contact', 'お問い合わせ', '' ),
	array(
		'privacy',
		'プライバシーポリシー',
		"<h2>個人情報の取り扱いについて</h2>\n<p>当社は、お客様からお預かりした個人情報を適切に管理し、以下の目的の範囲内で利用します。</p>\n<ul><li>お問い合わせへの回答および資料の送付</li><li>ご依頼いただいた工事の見積・設計・施工およびアフターサービス</li><li>採用選考に関するご連絡</li></ul>\n<h2>第三者への提供</h2>\n<p>法令に基づく場合を除き、ご本人の同意なく第三者へ提供することはありません。</p>\n<h2>開示・訂正・削除</h2>\n<p>ご本人からの開示・訂正・削除のご請求には、速やかに対応します。</p>\n<h2>お問い合わせ窓口</h2>\n<p>本ポリシーに関するお問い合わせは、お問い合わせフォームよりご連絡ください。</p>\n<p><small>本サイトは架空の企業を想定した制作サンプルであり、記載の内容は実在の事業者のものではありません。</small></p>",
	),
);

$sk_ids = array();

foreach ( $sk_defs as [ $sk_slug, $sk_title, $sk_content ] ) {
	$sk_id = sk_seed_page( $sk_slug, $sk_title, $sk_content );

	if ( $sk_id > 0 ) {
		$sk_ids[ $sk_slug ] = $sk_id;
		WP_CLI::log( "    {$sk_title}（/{$sk_slug}/）" );
	}
}

// ---------------------------------------------------------------------------
// 表示設定
//   フロントページに固定ページ「ホーム」、投稿ページに「お知らせ」を割り当てる。
//   これで front-page.php と home.php がそれぞれ使われるようになる。
// ---------------------------------------------------------------------------
if ( isset( $sk_ids['home'], $sk_ids['news'] ) ) {
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $sk_ids['home'] );
	update_option( 'page_for_posts', $sk_ids['news'] );
	WP_CLI::log( '    表示設定: フロント=ホーム / 投稿ページ=お知らせ' );
}

if ( isset( $sk_ids['privacy'] ) ) {
	update_option( 'wp_page_for_privacy_policy', $sk_ids['privacy'] );
}

// ---------------------------------------------------------------------------
// メニュー
//   ヘッダーには電話番号を置かない方針（実サイト 50 件で 0%）。
//   ナビ出現率の実測（お問い合わせ 36% / 採用情報 28% / 会社概要 24% /
//   お知らせ 16% / 施工事例 10%）に沿った並びにする。
// ---------------------------------------------------------------------------
WP_CLI::log( '==> メニューを作成します' );

/**
 * メニューを作り、指定の項目を登録する。
 *
 * @param string                   $name     メニュー名。
 * @param string                   $location theme_location。
 * @param array<int, array{type: string, object: string, id?: int, url?: string, title: string}> $items 項目。
 */
function sk_seed_menu( string $name, string $location, array $items ): void {

	$menu = wp_get_nav_menu_object( $name );

	if ( ! $menu ) {
		$menu_id = wp_create_nav_menu( $name );

		if ( is_wp_error( $menu_id ) ) {
			WP_CLI::warning( "メニューの作成に失敗しました: {$name}" );
			return;
		}
	} else {
		$menu_id = (int) $menu->term_id;

		// 冪等性のため、既存の項目を一度消してから入れ直す。
		foreach ( (array) wp_get_nav_menu_items( $menu_id ) as $item ) {
			wp_delete_post( (int) $item->ID, true );
		}
	}

	$order = 0;

	foreach ( $items as $item ) {
		++$order;

		$args = array(
			'menu-item-title'     => $item['title'],
			'menu-item-status'    => 'publish',
			'menu-item-position'  => $order,
			'menu-item-type'      => $item['type'],
			'menu-item-object'    => $item['object'],
		);

		if ( 'custom' === $item['type'] ) {
			$args['menu-item-url'] = $item['url'] ?? '';
		} else {
			$args['menu-item-object-id'] = (int) ( $item['id'] ?? 0 );
		}

		wp_update_nav_menu_item( (int) $menu_id, 0, $args );
	}

	// theme_location への割り当て。これをしないとメニューが表示されない。
	$locations              = (array) get_theme_mod( 'nav_menu_locations', array() );
	$locations[ $location ] = (int) $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );

	WP_CLI::log( "    {$name} → {$location}" );
}

$sk_primary = array(
	array(
		'type'   => 'custom',
		'object' => 'custom',
		'url'    => (string) get_post_type_archive_link( 'work' ),
		'title'  => '施工実績',
	),
);

foreach ( array( 'services' => '事業内容', 'company' => '会社概要', 'recruit' => '採用情報', 'news' => 'お知らせ' ) as $sk_slug => $sk_label ) {
	if ( isset( $sk_ids[ $sk_slug ] ) ) {
		$sk_primary[] = array(
			'type'   => 'post_type',
			'object' => 'page',
			'id'     => $sk_ids[ $sk_slug ],
			'title'  => $sk_label,
		);
	}
}

sk_seed_menu( 'ヘッダーメニュー', 'primary', $sk_primary );

$sk_footer = $sk_primary;

foreach ( array( 'contact' => 'お問い合わせ', 'privacy' => 'プライバシーポリシー' ) as $sk_slug => $sk_label ) {
	if ( isset( $sk_ids[ $sk_slug ] ) ) {
		$sk_footer[] = array(
			'type'   => 'post_type',
			'object' => 'page',
			'id'     => $sk_ids[ $sk_slug ],
			'title'  => $sk_label,
		);
	}
}

sk_seed_menu( 'フッターメニュー', 'footer', $sk_footer );

WP_CLI::log( '    完了' );
