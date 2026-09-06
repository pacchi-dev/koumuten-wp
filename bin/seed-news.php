<?php
/**
 * お知らせ（通常投稿）の投入。
 *
 *   実行: docker exec koumuten_wp_cli wp --path=/var/www/html eval-file /scripts/seed-news.php
 *
 * お知らせは CPT を作らず標準の投稿を使っている。
 * カテゴリ・アーカイブ・RSS が標準機能のまま動くため。
 *
 * @package KoumutenSeed
 */

defined( 'ABSPATH' ) || exit;

require_once '/scripts/seed-lib.php';

WP_CLI::log( '==> お知らせを登録します' );

// 既定の「未分類」は使わないので、実際に使うカテゴリを用意する。
$sk_categories = array( 'お知らせ', '施工事例', 'イベント', '採用' );

foreach ( $sk_categories as $sk_cat ) {
	if ( ! term_exists( $sk_cat, 'category' ) ) {
		wp_insert_term( $sk_cat, 'category' );
	}
}

$sk_posts = array(
	array(
		'slug'     => 'summer-holiday-2025',
		'title'    => '夏季休業のお知らせ',
		'category' => 'お知らせ',
		'date'     => '2025-07-28 10:00:00',
		'body'     => "誠に勝手ながら、下記の期間を夏季休業とさせていただきます。\n\n休業期間: 8月10日（日）〜8月17日（日）\n\n期間中にいただいたお問い合わせへのご返信は、8月18日（月）以降に順次対応いたします。ご不便をおかけしますが、よろしくお願いいたします。",
	),
	array(
		'slug'     => 'open-house-himeji-0906',
		'title'    => '完成見学会を開催します（姫路市・9月6日〜7日）',
		'category' => 'イベント',
		'date'     => '2025-08-20 09:00:00',
		'body'     => "姫路市内で完成したお住まいの見学会を開催します。\n\n日時: 9月6日（土）・7日（日） 10:00〜16:00\n場所: 兵庫県姫路市（詳細はご予約時にお伝えします）\n\n完全予約制です。お問い合わせフォームまたはお電話にてご連絡ください。実際に建てられた方の工夫を、間取り図と合わせてご覧いただけます。",
	),
	array(
		'slug'     => 'works-updated-2025-08',
		'title'    => '施工実績に「余白の多い家」を追加しました',
		'category' => '施工事例',
		'date'     => '2025-08-05 14:00:00',
		'body'     => "施工実績のページに、姫路市で竣工したお住まいを追加しました。\n\n南に大きく開いた LDK と、来客用にも使える和室を並べた住まいです。間取り図と物件概要を掲載していますので、あわせてご覧ください。",
	),
	array(
		'slug'     => 'recruit-2026',
		'title'    => '2026年度の新卒採用を開始しました',
		'category' => '採用',
		'date'     => '2025-06-10 11:00:00',
		'body'     => "設計・施工管理職の新卒採用を開始しました。\n\n当社は年間 20 棟ほどの規模で、一人が一邸に長く関わります。実際の現場を見ていただく機会も用意していますので、まずは採用ページのフォームよりご連絡ください。",
	),
	array(
		'slug'     => 'aftercare-inspection',
		'title'    => '定期点検のご案内について',
		'category' => 'お知らせ',
		'date'     => '2025-05-15 13:00:00',
		'body'     => "お引き渡し後の定期点検について、担当者よりご連絡を差し上げています。\n\n点検は 6 か月・1 年・2 年・5 年・10 年のタイミングで実施しています。ご都合が合わない場合は日程を調整しますので、遠慮なくお申し付けください。",
	),
);

$sk_count = 0;

foreach ( $sk_posts as $sk_item ) {

	$sk_existing = get_page_by_path( $sk_item['slug'], OBJECT, 'post' );

	$sk_postarr = array(
		'post_type'    => 'post',
		'post_status'  => 'publish',
		'post_title'   => $sk_item['title'],
		'post_name'    => $sk_item['slug'],
		'post_content' => $sk_item['body'],
		'post_date'    => $sk_item['date'],
	);

	if ( $sk_existing instanceof WP_Post ) {
		$sk_postarr['ID'] = $sk_existing->ID;
	}

	$sk_id = wp_insert_post( $sk_postarr, true );

	if ( is_wp_error( $sk_id ) ) {
		WP_CLI::warning( '投稿の作成に失敗しました: ' . $sk_item['slug'] );
		continue;
	}

	wp_set_object_terms( (int) $sk_id, $sk_item['category'], 'category', false );

	++$sk_count;
	WP_CLI::log( '    ' . $sk_item['title'] );
}

WP_CLI::log( "    {$sk_count} 件を登録しました" );
