<?php
/**
 * トップページの ACF フィールド。
 *
 * ロケーションに page_type == front_page を使う。
 * 特定のページ ID を直接指定すると環境ごとに ID が変わって壊れるため。
 *
 * 事業内容の 3 件はここでは持たない。事業内容ページ（/services/）の
 * フィールドを唯一の定義元にして、トップは同じデータを読んで表示する。
 * 同じ内容を 2 箇所で編集させると必ず食い違うため。
 *
 * @package KoumutenCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * トップページのフィールドグループを登録する。
 */
add_action( 'acf/init', 'kc_register_front_page_fields' );
function kc_register_front_page_fields(): void {

	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		array(
			'key'            => 'group_kc_front_page',
			'title'          => __( 'トップページの内容', 'koumuten-core' ),
			'menu_order'     => 0,
			'position'       => 'normal',
			'style'          => 'default',
			'active'         => true,
			'hide_on_screen' => array( 'the_content', 'comments', 'discussion', 'author', 'excerpt' ),
			'location'       => array(
				array(
					array(
						'param'    => 'page_type',
						'operator' => '==',
						'value'    => 'front_page',
					),
				),
			),
			'fields'         => array(

				// ---------------------------------------------------------- ヒーロー
				array(
					'key'   => 'field_kc_fp_tab_hero',
					'label' => __( 'ヒーロー', 'koumuten-core' ),
					'type'  => 'tab',
				),
				array(
					'key'           => 'field_kc_fp_hero_image',
					'label'         => __( '背景写真', 'koumuten-core' ),
					'name'          => 'hero_image',
					'type'          => 'image',
					// ID で返すと wp_get_attachment_image() が使え、srcset が自動で付く。
					'return_format' => 'id',
					'preview_size'  => 'medium',
					'instructions'  => __( '文字を重ねるため、横長で余白のある構図を選んでください。', 'koumuten-core' ),
				),
				array(
					'key'           => 'field_kc_fp_hero_eyebrow',
					'label'         => __( '小見出し', 'koumuten-core' ),
					'name'          => 'hero_eyebrow',
					'type'          => 'text',
					'default_value' => '姫路の注文住宅・リノベーション',
				),
				array(
					'key'           => 'field_kc_fp_hero_title',
					'label'         => __( 'キャッチコピー', 'koumuten-core' ),
					'name'          => 'hero_title',
					'type'          => 'textarea',
					'rows'          => 3,
					'instructions'  => __( '改行すると、そこで行が分かれます。', 'koumuten-core' ),
					'default_value' => "暮らしに、\n余白をつくる。",
				),
				array(
					'key'           => 'field_kc_fp_hero_text',
					'label'         => __( 'リード文', 'koumuten-core' ),
					'name'          => 'hero_text',
					'type'          => 'textarea',
					'rows'          => 3,
					'default_value' => '土地の風景と、家族の時間。その両方に無理のない住まいを、設計から施工まで一貫してつくります。',
				),

				// ---------------------------------------------------------- 事業内容
				array(
					'key'   => 'field_kc_fp_tab_services',
					'label' => __( '事業内容', 'koumuten-core' ),
					'type'  => 'tab',
				),
				array(
					'key'           => 'field_kc_fp_services_heading',
					'label'         => __( '見出し', 'koumuten-core' ),
					'name'          => 'services_heading',
					'type'          => 'text',
					'default_value' => '事業内容',
				),
				array(
					'key'           => 'field_kc_fp_services_lead',
					'label'         => __( 'リード文', 'koumuten-core' ),
					'name'          => 'services_lead',
					'type'          => 'textarea',
					'rows'          => 2,
					'default_value' => '新築から、住みながらのリフォーム、庭まわりまで。住まいに関わることを一社で受けられます。',
				),
				array(
					'key'          => 'field_kc_fp_services_note',
					'label'        => __( '補足', 'koumuten-core' ),
					'name'         => 'services_note',
					'type'         => 'message',
					'message'      => __( '3 件の内容は「事業内容」ページで編集します。ここではトップに出す見出しとリード文だけを設定します。', 'koumuten-core' ),
				),

				// ---------------------------------------------------------- 施工実績
				array(
					'key'   => 'field_kc_fp_tab_works',
					'label' => __( '施工実績', 'koumuten-core' ),
					'type'  => 'tab',
				),
				array(
					'key'           => 'field_kc_fp_works_heading',
					'label'         => __( '見出し', 'koumuten-core' ),
					'name'          => 'works_heading',
					'type'          => 'text',
					'default_value' => '施工実績',
				),
				array(
					'key'           => 'field_kc_fp_works_lead',
					'label'         => __( 'リード文', 'koumuten-core' ),
					'name'          => 'works_lead',
					'type'          => 'textarea',
					'rows'          => 2,
					'default_value' => '完成したお住まいの一部をご紹介します。',
				),
				array(
					'key'           => 'field_kc_fp_works_count',
					'label'         => __( '表示件数', 'koumuten-core' ),
					'name'          => 'works_count',
					'type'          => 'number',
					'default_value' => 6,
					'min'           => 1,
					'max'           => 12,
				),

				// ---------------------------------------------------------- お知らせ
				array(
					'key'   => 'field_kc_fp_tab_news',
					'label' => __( 'お知らせ', 'koumuten-core' ),
					'type'  => 'tab',
				),
				array(
					'key'           => 'field_kc_fp_news_heading',
					'label'         => __( '見出し', 'koumuten-core' ),
					'name'          => 'news_heading',
					'type'          => 'text',
					'default_value' => 'お知らせ',
				),
				array(
					'key'           => 'field_kc_fp_news_count',
					'label'         => __( '表示件数', 'koumuten-core' ),
					'name'          => 'news_count',
					'type'          => 'number',
					'default_value' => 4,
					'min'           => 1,
					'max'           => 10,
				),

				// ---------------------------------------------------------- Instagram
				array(
					'key'   => 'field_kc_fp_tab_instagram',
					'label' => __( 'Instagram', 'koumuten-core' ),
					'type'  => 'tab',
				),
				array(
					'key'     => 'field_kc_fp_instagram_note',
					'label'   => __( '補足', 'koumuten-core' ),
					'name'    => 'instagram_note',
					'type'    => 'message',
					'message' => __( 'Instagram の公式 API は 2024 年 12 月に提供が終了したため、写真は手動で登録します。アカウントの URL は「外観 > 会社情報」で設定します。', 'koumuten-core' ),
				),
				array(
					'key'           => 'field_kc_fp_instagram_heading',
					'label'         => __( '見出し', 'koumuten-core' ),
					'name'          => 'instagram_heading',
					'type'          => 'text',
					'default_value' => 'Instagram',
				),
				array(
					'key'           => 'field_kc_fp_instagram_lead',
					'label'         => __( 'リード文', 'koumuten-core' ),
					'name'          => 'instagram_lead',
					'type'          => 'text',
					'default_value' => '現場の様子や完成したお住まいを日々投稿しています。',
				),
				/*
				 * Repeater は ACF Pro 機能のため使えない。
				 * 枚数が 6 で固定のグリッドなので group に固定個のサブフィールドを持たせる。
				 */
				array(
					'key'        => 'field_kc_fp_instagram',
					'label'      => __( '写真（6枚）', 'koumuten-core' ),
					'name'       => 'instagram',
					'type'       => 'group',
					'layout'     => 'block',
					'sub_fields' => array(
						array(
							'key'           => 'field_kc_fp_ig_1',
							'label'         => __( '1枚目', 'koumuten-core' ),
							'name'          => 'image_1',
							'type'          => 'image',
							'return_format' => 'id',
							'preview_size'  => 'thumbnail',
							'wrapper'       => array( 'width' => '16' ),
						),
						array(
							'key'           => 'field_kc_fp_ig_2',
							'label'         => __( '2枚目', 'koumuten-core' ),
							'name'          => 'image_2',
							'type'          => 'image',
							'return_format' => 'id',
							'preview_size'  => 'thumbnail',
							'wrapper'       => array( 'width' => '16' ),
						),
						array(
							'key'           => 'field_kc_fp_ig_3',
							'label'         => __( '3枚目', 'koumuten-core' ),
							'name'          => 'image_3',
							'type'          => 'image',
							'return_format' => 'id',
							'preview_size'  => 'thumbnail',
							'wrapper'       => array( 'width' => '16' ),
						),
						array(
							'key'           => 'field_kc_fp_ig_4',
							'label'         => __( '4枚目', 'koumuten-core' ),
							'name'          => 'image_4',
							'type'          => 'image',
							'return_format' => 'id',
							'preview_size'  => 'thumbnail',
							'wrapper'       => array( 'width' => '16' ),
						),
						array(
							'key'           => 'field_kc_fp_ig_5',
							'label'         => __( '5枚目', 'koumuten-core' ),
							'name'          => 'image_5',
							'type'          => 'image',
							'return_format' => 'id',
							'preview_size'  => 'thumbnail',
							'wrapper'       => array( 'width' => '16' ),
						),
						array(
							'key'           => 'field_kc_fp_ig_6',
							'label'         => __( '6枚目', 'koumuten-core' ),
							'name'          => 'image_6',
							'type'          => 'image',
							'return_format' => 'id',
							'preview_size'  => 'thumbnail',
							'wrapper'       => array( 'width' => '16' ),
						),
					),
				),

				// ---------------------------------------------------------- 採用
				array(
					'key'   => 'field_kc_fp_tab_recruit',
					'label' => __( '採用バナー', 'koumuten-core' ),
					'type'  => 'tab',
				),
				array(
					'key'           => 'field_kc_fp_recruit_heading',
					'label'         => __( '見出し', 'koumuten-core' ),
					'name'          => 'recruit_heading',
					'type'          => 'text',
					'default_value' => '一緒に家をつくる仲間を募集しています',
				),
				array(
					'key'           => 'field_kc_fp_recruit_text',
					'label'         => __( '本文', 'koumuten-core' ),
					'name'          => 'recruit_text',
					'type'          => 'textarea',
					'rows'          => 2,
					'default_value' => '設計・施工管理・大工職。経験者も、これから覚えたい方も歓迎します。',
				),
				array(
					'key'           => 'field_kc_fp_recruit_image',
					'label'         => __( '背景写真', 'koumuten-core' ),
					'name'          => 'recruit_image',
					'type'          => 'image',
					'return_format' => 'id',
					'preview_size'  => 'medium',
				),

				// ---------------------------------------------------------- CTA
				array(
					'key'   => 'field_kc_fp_tab_cta',
					'label' => __( 'CTA帯', 'koumuten-core' ),
					'type'  => 'tab',
				),
				array(
					'key'           => 'field_kc_fp_cta_heading',
					'label'         => __( '見出し', 'koumuten-core' ),
					'name'          => 'cta_heading',
					'type'          => 'text',
					'default_value' => 'まずはお気軽にご相談ください',
				),
				array(
					'key'           => 'field_kc_fp_cta_text',
					'label'         => __( '本文', 'koumuten-core' ),
					'name'          => 'cta_text',
					'type'          => 'textarea',
					'rows'          => 2,
					'default_value' => '土地探しの段階でも、リフォームの小さなご相談でも構いません。ご予算の目安だけでもお伝えできます。',
				),

				// ---------------------------------------------------------- アクセス
				array(
					'key'   => 'field_kc_fp_tab_access',
					'label' => __( 'アクセス', 'koumuten-core' ),
					'type'  => 'tab',
				),
				array(
					'key'           => 'field_kc_fp_access_heading',
					'label'         => __( '見出し', 'koumuten-core' ),
					'name'          => 'access_heading',
					'type'          => 'text',
					'default_value' => 'アクセス',
				),
				array(
					'key'           => 'field_kc_fp_access_note',
					'label'         => __( '補足', 'koumuten-core' ),
					'name'          => 'access_note',
					'type'          => 'textarea',
					'rows'          => 2,
					'default_value' => 'ご来社の際は事前にご連絡ください。駐車場をご用意します。',
				),
			),
		)
	);
}
