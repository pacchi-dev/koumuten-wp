<?php
/**
 * ACF フィールド値の投入（トップページと下層ページ）。
 *
 *   実行: docker exec koumuten_wp_cli wp --path=/var/www/html eval-file /scripts/seed-fields.php
 *
 * seed-pages.php とプロセスを分けているのは、下層ページのフィールドグループが
 * 「そのページが存在すること」を条件に登録されるため。
 * ページを作った直後の同一プロセスではまだ登録されておらず、
 * update_field() が ACF のフィールドとしてではなく素のメタデータとして
 * 保存されてしまう。
 *
 * 実行順: seed-pages → seed-works → seed-news → seed-forms → seed-fields
 *
 * @package KoumutenSeed
 */

defined( 'ABSPATH' ) || exit;

require_once '/scripts/seed-lib.php';

if ( ! function_exists( 'update_field' ) ) {
	WP_CLI::error( 'Advanced Custom Fields が有効ではありません。' );
}

WP_CLI::log( '==> ページの内容を登録します' );

$sk_front_id = (int) get_option( 'page_on_front' );

if ( $sk_front_id <= 0 ) {
	WP_CLI::error( 'フロントページが設定されていません。先に seed-pages.php を実行してください。' );
}

// ---------------------------------------------------------------------------
// 画像の取り込み
// ---------------------------------------------------------------------------
$sk_hero    = sk_seed_image( 'hero-main.jpg', '木と白い外壁の現代的な住宅の外観' );
$sk_recruit = sk_seed_image( 'recruit-site.jpg', '建設現場で作業する職人たち' );
$sk_office  = sk_seed_image( 'company-office.jpg', '事務所の外観' );

$sk_service_images = array(
	1 => sk_seed_image( 'service-newbuild.jpg', '木と黒を組み合わせた新築住宅の外観' ),
	2 => sk_seed_image( 'service-reform.jpg', 'リフォーム後の明るいリビングダイニング' ),
	3 => sk_seed_image( 'service-exterior.jpg', '手入れされた芝生と植栽' ),
);

// ---------------------------------------------------------------------------
// トップページ
// ---------------------------------------------------------------------------
$sk_front_fields = array(
	'hero_image'        => $sk_hero,
	'hero_eyebrow'      => '姫路の注文住宅・リノベーション',
	'hero_title'        => "暮らしに、\n余白をつくる。",
	'hero_text'         => '土地の風景と、家族の時間。その両方に無理のない住まいを、設計から施工まで一貫してつくります。',
	'services_heading'  => '事業内容',
	'services_lead'     => '新築から、住みながらのリフォーム、庭まわりまで。住まいに関わることを一社で受けられます。',
	'works_heading'     => '施工実績',
	'works_lead'        => '完成したお住まいの一部をご紹介します。間取り図と物件概要をあわせて掲載しています。',
	'works_count'       => 6,
	'news_heading'      => 'お知らせ',
	'news_count'        => 4,
	'instagram_heading' => 'Instagram',
	'instagram_lead'    => '現場の様子や完成したお住まいを日々投稿しています。',
	'recruit_heading'   => '一緒に家をつくる仲間を募集しています',
	'recruit_text'      => '設計・施工管理・大工職。経験者も、これから覚えたい方も歓迎します。',
	'recruit_image'     => $sk_recruit,
	'cta_heading'       => 'まずはお気軽にご相談ください',
	'cta_text'          => '土地探しの段階でも、リフォームの小さなご相談でも構いません。ご予算の目安だけでもお伝えできます。',
	'access_heading'    => 'アクセス',
	'access_note'       => 'ご来社の際は事前にご連絡ください。駐車場をご用意します。',
);

foreach ( $sk_front_fields as $sk_name => $sk_value ) {
	update_field( $sk_name, $sk_value, $sk_front_id );
}

/*
 * Instagram グリッドの 6 枚は施工実績のアイキャッチを流用する。
 * 素材の追加ダウンロードを避けるため。運営者は管理画面から差し替えられる。
 */
$sk_work_ids = get_posts(
	array(
		'post_type'      => 'work',
		'posts_per_page' => 6,
		'post_status'    => 'publish',
		'fields'         => 'ids',
		'orderby'        => 'date',
		'order'          => 'DESC',
	)
);

$sk_instagram = array();
$sk_index     = 0;

foreach ( $sk_work_ids as $sk_work_id ) {
	++$sk_index;
	$sk_instagram[ 'image_' . $sk_index ] = (int) get_post_thumbnail_id( (int) $sk_work_id );
}

if ( ! empty( $sk_instagram ) ) {
	update_field( 'instagram', $sk_instagram, $sk_front_id );
}

WP_CLI::log( '    トップページ' );

// ---------------------------------------------------------------------------
// 事業内容
// ---------------------------------------------------------------------------
$sk_services_id = kc_page_id( 'services' );

if ( $sk_services_id > 0 ) {

	update_field( 'services_lead', '設計・施工・アフターまでを自社で担当します。窓口が一つなので、要望と現場のあいだで話がずれません。', $sk_services_id );

	$sk_services = array(
		1 => array(
			'title'       => '新築・注文住宅',
			'catch'       => '土地の見え方から考える、一邸ずつの設計',
			'description' => '同じ間取りでも、土地の向きと周りの建ち方で住み心地は変わります。まず敷地に立ち、光と風の入り方を確認してから図面を引きます。工事費用の目安は、初回のご相談時にお伝えします。',
			'points'      => "敷地調査・法規チェック\n間取りのご提案（何度でも）\n資金計画・住宅ローンのご相談\n工事中の現場ご案内\n引き渡し後の定期点検",
		),
		2 => array(
			'title'       => 'リフォーム・リノベーション',
			'catch'       => '住みながらでも進められる、暮らしの更新',
			'description' => '水回りの入れ替えのような小さな工事から、間取りを変える全面改修まで対応します。既存の構造と断熱の状態を先に確認し、どこまで手を入れると効果が出るかをご説明します。',
			'points'      => "キッチン・浴室・洗面の入れ替え\n間取り変更をともなう改修\n断熱・耐震の補強\nマンションの内装改修\n住みながらの工程調整",
		),
		3 => array(
			'title'       => '外構・エクステリア',
			'catch'       => '建物と庭を、ひと続きで考える',
			'description' => '駐車場・アプローチ・植栽・目隠しまで、建物の設計と同じ担当が計画します。建物が完成してから外構を別会社に頼むと、高さや素材が合わないことがあります。',
			'points'      => "駐車場・土間コンクリート\nアプローチ・門まわり\n植栽・芝張り\nウッドデッキ・テラス\n目隠しフェンス・カーポート",
		),
	);

	foreach ( $sk_services as $sk_i => $sk_service ) {
		$sk_service['image'] = $sk_service_images[ $sk_i ] ?? 0;
		update_field( 'service_' . $sk_i, $sk_service, $sk_services_id );
	}

	WP_CLI::log( '    事業内容' );
}

// ---------------------------------------------------------------------------
// 会社概要
// ---------------------------------------------------------------------------
$sk_company_id = kc_page_id( 'company' );

if ( $sk_company_id > 0 ) {
	update_field( 'company_lead', '姫路を中心に、注文住宅とリノベーションを手がけています。設計から施工まで自社で担当し、引き渡し後の点検も同じ担当者が伺います。', $sk_company_id );
	update_field( 'company_image', $sk_office, $sk_company_id );
	update_field(
		'company_table',
		"設立,1998年4月\n資本金,2,000万円\n代表者,代表取締役 空野 和真\n従業員数,18名（うち一級建築士2名・二級建築士4名）\n事業内容,注文住宅の設計・施工／リフォーム・リノベーション／外構工事\n加盟団体,兵庫県建築士会（ダミー）",
		$sk_company_id
	);
	update_field(
		'company_history',
		"1998年,姫路市にて創業。木造住宅の新築を中心に事業を開始\n2006年,設計部門を新設。設計から施工までの一貫体制へ\n2014年,リノベーション事業を開始\n2021年,外構・造園部門を新設\n2024年,累計施工 500 棟を達成",
		$sk_company_id
	);
	update_field( 'company_access_note', 'ご来社の際は事前にご連絡ください。駐車場をご用意します。', $sk_company_id );

	WP_CLI::log( '    会社概要' );
}

// ---------------------------------------------------------------------------
// 採用情報
// ---------------------------------------------------------------------------
$sk_recruit_id = kc_page_id( 'recruit' );

if ( $sk_recruit_id > 0 ) {
	update_field( 'recruit_lead', '図面を引く人、現場を回す人、手を動かす人。どの役割も、家が建つまでの同じ一本の流れの上にあります。', $sk_recruit_id );
	update_field( 'recruit_image', $sk_recruit, $sk_recruit_id );
	update_field(
		'recruit_message',
		"当社は年間 20 棟ほどの規模で、一人が一邸に長く関わります。分業で流していく作り方ではないぶん覚えることは多いですが、引き渡しの日に立ち会えます。\n未経験の方は、まず現場の補助から始めていただきます。資格取得の費用は会社が負担します。",
		$sk_recruit_id
	);
	update_field(
		'recruit_table',
		"募集職種,設計・施工管理・大工職\n雇用形態,正社員（試用期間3か月）\n勤務地,兵庫県姫路市ソラノワ町0-0-0（本社）\n勤務時間,8:00〜17:00（休憩60分）\n休日休暇,水曜・第2/第4火曜・夏季・年末年始・有給休暇\n給与,月給 22万円〜38万円（経験・資格を考慮）\n諸手当,通勤手当・資格手当・住宅手当・家族手当\n待遇,社会保険完備・資格取得支援・退職金制度\n応募方法,下記フォームよりご応募ください。追ってご連絡します",
		$sk_recruit_id
	);
	update_field( 'recruit_form', (string) get_option( 'sk_form_recruit_shortcode', '' ), $sk_recruit_id );

	WP_CLI::log( '    採用情報' );
}

// ---------------------------------------------------------------------------
// お問い合わせ
// ---------------------------------------------------------------------------
$sk_contact_id = kc_page_id( 'contact' );

if ( $sk_contact_id > 0 ) {
	update_field( 'contact_lead', '土地探しの段階でも、リフォームの小さなご相談でも構いません。内容を確認のうえ、2〜3営業日以内にご返信します。', $sk_contact_id );
	update_field( 'contact_form', (string) get_option( 'sk_form_contact_shortcode', '' ), $sk_contact_id );

	WP_CLI::log( '    お問い合わせ' );
}

// ---------------------------------------------------------------------------
// サイトのキャッチフレーズ（description に使われる）
// ---------------------------------------------------------------------------
update_option( 'blogdescription', '姫路の注文住宅・リノベーション。設計から施工まで自社で担当します。' );

WP_CLI::log( '    完了' );
