<?php
/**
 * 下層固定ページの ACF フィールド（事業内容 / 会社概要 / 採用情報 / お問い合わせ）。
 *
 * ロケーションは固定ページ ID で指定する仕様だが、ID は環境ごとに変わるため
 * スラッグから kc_page_id() で毎回引く。ページが未作成のうちは登録しない。
 *
 * 可変長の表は Repeater（Pro 機能）が使えないため
 * textarea に 1 行 1 レコード「項目,値」で入力し、テーマ側の kc_parse_rows() で読む。
 *
 * @package KoumutenCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 下層ページのフィールドグループをまとめて登録する。
 */
add_action( 'acf/init', 'kc_register_page_fields' );
function kc_register_page_fields(): void {

	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	kc_register_services_fields();
	kc_register_company_fields();
	kc_register_recruit_fields();
	kc_register_contact_fields();
}

/**
 * 固定ページ 1 枚に紐づくロケーション定義を作る。
 *
 * @param string $slug 固定ページのスラッグ。
 * @return array<int, array<int, array<string, mixed>>>|null ページが無ければ null。
 */
function kc_page_location( string $slug ): ?array {
	$id = kc_page_id( $slug );

	if ( 0 === $id ) {
		return null;
	}

	return array(
		array(
			array(
				'param'    => 'page',
				'operator' => '==',
				'value'    => (string) $id,
			),
		),
	);
}

/**
 * サービス 1 件分のサブフィールド定義を作る。
 *
 * 3 件とも同じ構造なので関数で生成する。手で 3 回書くと必ずずれる。
 *
 * @param int    $index 1〜3。
 * @param string $title 既定のタイトル。
 * @param string $catch 既定のキャッチコピー。
 * @return array<string, mixed>
 */
function kc_service_group_field( int $index, string $title, string $catch ): array {
	return array(
		'key'        => "field_kc_service_{$index}",
		'label'      => sprintf( /* translators: %d: 表示順 */ __( '事業 %d', 'koumuten-core' ), $index ),
		'name'       => "service_{$index}",
		'type'       => 'group',
		'layout'     => 'block',
		'sub_fields' => array(
			array(
				'key'           => "field_kc_service_{$index}_title",
				'label'         => __( '名称', 'koumuten-core' ),
				'name'          => 'title',
				'type'          => 'text',
				'default_value' => $title,
				'wrapper'       => array( 'width' => '40' ),
			),
			array(
				'key'           => "field_kc_service_{$index}_catch",
				'label'         => __( 'キャッチコピー', 'koumuten-core' ),
				'name'          => 'catch',
				'type'          => 'text',
				'default_value' => $catch,
				'wrapper'       => array( 'width' => '60' ),
			),
			array(
				'key'           => "field_kc_service_{$index}_image",
				'label'         => __( '写真', 'koumuten-core' ),
				'name'          => 'image',
				'type'          => 'image',
				'return_format' => 'id',
				'preview_size'  => 'medium',
			),
			array(
				'key'   => "field_kc_service_{$index}_description",
				'label' => __( '説明', 'koumuten-core' ),
				'name'  => 'description',
				'type'  => 'textarea',
				'rows'  => 4,
			),
			array(
				'key'          => "field_kc_service_{$index}_points",
				'label'        => __( '対応内容', 'koumuten-core' ),
				'name'         => 'points',
				'type'         => 'textarea',
				'rows'         => 5,
				'instructions' => __( '1 行に 1 項目を入力します。箇条書きとして表示されます。', 'koumuten-core' ),
			),
		),
	);
}

/**
 * 事業内容ページのフィールド。
 */
function kc_register_services_fields(): void {
	$location = kc_page_location( 'services' );

	if ( null === $location ) {
		return;
	}

	acf_add_local_field_group(
		array(
			'key'            => 'group_kc_services',
			'title'          => __( '事業内容', 'koumuten-core' ),
			'location'       => $location,
			'hide_on_screen' => array( 'the_content', 'comments', 'discussion', 'author' ),
			'fields'         => array(
				array(
					'key'           => 'field_kc_services_lead',
					'label'         => __( 'リード文', 'koumuten-core' ),
					'name'          => 'services_lead',
					'type'          => 'textarea',
					'rows'          => 3,
					'default_value' => '設計・施工・アフターまでを自社で担当します。窓口が一つなので、要望と現場のあいだで話がずれません。',
				),
				kc_service_group_field( 1, '新築・注文住宅', '土地の見え方から考える、一邸ずつの設計' ),
				kc_service_group_field( 2, 'リフォーム・リノベーション', '住みながらでも進められる、暮らしの更新' ),
				kc_service_group_field( 3, '外構・エクステリア', '建物と庭を、ひと続きで考える' ),
			),
		)
	);
}

/**
 * 会社概要ページのフィールド。
 */
function kc_register_company_fields(): void {
	$location = kc_page_location( 'company' );

	if ( null === $location ) {
		return;
	}

	acf_add_local_field_group(
		array(
			'key'            => 'group_kc_company_page',
			'title'          => __( '会社概要', 'koumuten-core' ),
			'location'       => $location,
			'hide_on_screen' => array( 'the_content', 'comments', 'discussion', 'author' ),
			'fields'         => array(
				array(
					'key'           => 'field_kc_company_lead',
					'label'         => __( 'リード文', 'koumuten-core' ),
					'name'          => 'company_lead',
					'type'          => 'textarea',
					'rows'          => 3,
					'default_value' => '姫路を中心に、注文住宅とリノベーションを手がけています。設計から施工まで自社で担当し、引き渡し後の点検も同じ担当者が伺います。',
				),
				array(
					'key'           => 'field_kc_company_image',
					'label'         => __( '写真', 'koumuten-core' ),
					'name'          => 'company_image',
					'type'          => 'image',
					'return_format' => 'id',
					'preview_size'  => 'medium',
				),
				array(
					'key'           => 'field_kc_company_table',
					'label'         => __( '会社情報', 'koumuten-core' ),
					'name'          => 'company_table',
					'type'          => 'textarea',
					'rows'          => 10,
					'instructions'  => __( '1 行に 1 項目を「項目名,内容」の形式で入力します。例: 設立,1998年4月', 'koumuten-core' ),
					'default_value' => "設立,1998年4月\n資本金,2,000万円\n代表者,代表取締役 空野 和真\n従業員数,18名（うち一級建築士2名）\n事業内容,注文住宅の設計・施工／リフォーム・リノベーション／外構工事",
				),
				array(
					'key'           => 'field_kc_company_history',
					'label'         => __( '沿革', 'koumuten-core' ),
					'name'          => 'company_history',
					'type'          => 'textarea',
					'rows'          => 8,
					'instructions'  => __( '1 行に 1 件を「年,内容」の形式で入力します。例: 1998年,姫路市にて創業', 'koumuten-core' ),
					'default_value' => "1998年,姫路市にて創業。木造住宅の新築を中心に事業を開始\n2006年,設計部門を新設。設計から施工までの一貫体制へ\n2014年,リノベーション事業を開始\n2021年,外構・造園部門を新設\n2024年,累計施工 500 棟を達成",
				),
				array(
					'key'           => 'field_kc_company_access_note',
					'label'         => __( 'アクセスの補足', 'koumuten-core' ),
					'name'          => 'company_access_note',
					'type'          => 'textarea',
					'rows'          => 2,
					'default_value' => 'ご来社の際は事前にご連絡ください。駐車場をご用意します。',
				),
			),
		)
	);
}

/**
 * 採用情報ページのフィールド。
 */
function kc_register_recruit_fields(): void {
	$location = kc_page_location( 'recruit' );

	if ( null === $location ) {
		return;
	}

	acf_add_local_field_group(
		array(
			'key'            => 'group_kc_recruit',
			'title'          => __( '採用情報', 'koumuten-core' ),
			'location'       => $location,
			'hide_on_screen' => array( 'the_content', 'comments', 'discussion', 'author' ),
			'fields'         => array(
				array(
					'key'           => 'field_kc_recruit_lead',
					'label'         => __( 'リード文', 'koumuten-core' ),
					'name'          => 'recruit_lead',
					'type'          => 'textarea',
					'rows'          => 3,
					'default_value' => '図面を引く人、現場を回す人、手を動かす人。どの役割も、家が建つまでの同じ一本の流れの上にあります。',
				),
				array(
					'key'           => 'field_kc_recruit_image',
					'label'         => __( '写真', 'koumuten-core' ),
					'name'          => 'recruit_image',
					'type'          => 'image',
					'return_format' => 'id',
					'preview_size'  => 'medium',
				),
				array(
					'key'           => 'field_kc_recruit_message',
					'label'         => __( 'メッセージ', 'koumuten-core' ),
					'name'          => 'recruit_message',
					'type'          => 'textarea',
					'rows'          => 5,
					'default_value' => "当社は年間 20 棟ほどの規模で、一人が一邸に長く関わります。分業で流していく作り方ではないぶん、覚えることは多いですが、引き渡しの日に立ち会えます。\n未経験の方は、まず現場の補助から始めていただきます。資格取得の費用は会社が負担します。",
				),
				array(
					'key'           => 'field_kc_recruit_table',
					'label'         => __( '募集要項', 'koumuten-core' ),
					'name'          => 'recruit_table',
					'type'          => 'textarea',
					'rows'          => 12,
					'instructions'  => __( '1 行に 1 項目を「項目名,内容」の形式で入力します。例: 雇用形態,正社員', 'koumuten-core' ),
					'default_value' => "募集職種,設計・施工管理・大工職\n雇用形態,正社員（試用期間3か月）\n勤務地,兵庫県姫路市ソラノワ町0-0-0（本社）\n勤務時間,8:00〜17:00（休憩60分）\n休日休暇,水曜・第2/第4火曜・夏季・年末年始・有給休暇\n給与,月給 22万円〜38万円（経験・資格を考慮）\n諸手当,通勤手当・資格手当・住宅手当・家族手当\n待遇,社会保険完備・資格取得支援・退職金制度\n応募方法,下記フォームよりご応募ください。追ってご連絡します",
				),
				array(
					'key'           => 'field_kc_recruit_form',
					'label'         => __( '応募フォームのショートコード', 'koumuten-core' ),
					'name'          => 'recruit_form',
					'type'          => 'text',
					'instructions'  => __( 'Contact Form 7 のショートコードを貼り付けます。例: [contact-form-7 id="2" title="採用応募"]', 'koumuten-core' ),
				),
			),
		)
	);
}

/**
 * お問い合わせページのフィールド。
 */
function kc_register_contact_fields(): void {
	$location = kc_page_location( 'contact' );

	if ( null === $location ) {
		return;
	}

	acf_add_local_field_group(
		array(
			'key'            => 'group_kc_contact',
			'title'          => __( 'お問い合わせ', 'koumuten-core' ),
			'location'       => $location,
			'hide_on_screen' => array( 'the_content', 'comments', 'discussion', 'author' ),
			'fields'         => array(
				array(
					'key'           => 'field_kc_contact_lead',
					'label'         => __( 'リード文', 'koumuten-core' ),
					'name'          => 'contact_lead',
					'type'          => 'textarea',
					'rows'          => 3,
					'default_value' => '土地探しの段階でも、リフォームの小さなご相談でも構いません。内容を確認のうえ、2〜3営業日以内にご返信します。',
				),
				array(
					'key'          => 'field_kc_contact_form',
					'label'        => __( 'フォームのショートコード', 'koumuten-core' ),
					'name'         => 'contact_form',
					'type'         => 'text',
					'instructions' => __( 'Contact Form 7 のショートコードを貼り付けます。例: [contact-form-7 id="1" title="お問い合わせ"]', 'koumuten-core' ),
				),
			),
		)
	);
}
