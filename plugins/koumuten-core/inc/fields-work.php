<?php
/**
 * 施工実績（CPT work）の ACF フィールド。
 *
 * ACF のフィールド定義は管理画面の GUI でも作れるが、それだと定義が DB に入り
 * Git 管理できず、環境間で差分が出る。acf_add_local_field_group() で
 * コードとして持つのが実案件での定石（バージョン管理・レビュー・移設ができる）。
 *
 * 物件概要の項目は、実サイト（松尾工務店 / ネイエ設計）の
 * 物件概要テーブルの実測に準拠している。
 *
 * @package KoumutenCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 施工実績のフィールドグループを登録する。
 */
add_action( 'acf/init', 'kc_register_work_fields' );
function kc_register_work_fields(): void {

	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		array(
			'key'            => 'group_kc_work',
			'title'          => __( '物件情報', 'koumuten-core' ),
			'menu_order'     => 0,
			'position'       => 'normal',
			'style'          => 'default',
			'active'         => true,
			'location'       => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'work',
					),
				),
			),
			'fields'         => array(

				array(
					'key'   => 'field_kc_work_tab_spec',
					'label' => __( '物件概要', 'koumuten-core' ),
					'type'  => 'tab',
				),
				array(
					'key'          => 'field_kc_work_location',
					'label'        => __( '所在地', 'koumuten-core' ),
					'name'         => 'location',
					'type'         => 'text',
					'instructions' => __( '例: 兵庫県姫路市', 'koumuten-core' ),
					'wrapper'      => array( 'width' => '50' ),
				),
				array(
					'key'          => 'field_kc_work_completed_at',
					'label'        => __( '竣工年月', 'koumuten-core' ),
					'name'         => 'completed_at',
					'type'         => 'text',
					'instructions' => __( '例: 2024年3月', 'koumuten-core' ),
					'wrapper'      => array( 'width' => '50' ),
				),
				array(
					'key'          => 'field_kc_work_structure',
					'label'        => __( '構造・規模', 'koumuten-core' ),
					'name'         => 'structure',
					'type'         => 'text',
					'instructions' => __( '例: 木造2階建て', 'koumuten-core' ),
					'wrapper'      => array( 'width' => '50' ),
				),
				array(
					'key'          => 'field_kc_work_duration',
					'label'        => __( '工期', 'koumuten-core' ),
					'name'         => 'duration',
					'type'         => 'text',
					'instructions' => __( '例: 約5か月', 'koumuten-core' ),
					'wrapper'      => array( 'width' => '50' ),
				),
				array(
					'key'          => 'field_kc_work_site_area',
					'label'        => __( '敷地面積', 'koumuten-core' ),
					'name'         => 'site_area',
					'type'         => 'text',
					'instructions' => __( '例: 165.29㎡（50.0坪）', 'koumuten-core' ),
					'wrapper'      => array( 'width' => '50' ),
				),
				array(
					'key'          => 'field_kc_work_floor_area',
					'label'        => __( '延床面積', 'koumuten-core' ),
					'name'         => 'floor_area',
					'type'         => 'text',
					'instructions' => __( '例: 112.60㎡（34.1坪）', 'koumuten-core' ),
					'wrapper'      => array( 'width' => '50' ),
				),
				array(
					'key'          => 'field_kc_work_family',
					'label'        => __( '家族構成', 'koumuten-core' ),
					'name'         => 'family',
					'type'         => 'text',
					'instructions' => __( '例: ご夫婦＋お子様2人（任意）', 'koumuten-core' ),
					'wrapper'      => array( 'width' => '50' ),
				),
				array(
					'key'          => 'field_kc_work_floor_plan_label',
					'label'        => __( '間取り', 'koumuten-core' ),
					'name'         => 'floor_plan_label',
					'type'         => 'text',
					'instructions' => __( '例: 3LDK+WIC', 'koumuten-core' ),
					'wrapper'      => array( 'width' => '50' ),
				),

				array(
					'key'   => 'field_kc_work_tab_plan',
					'label' => __( '間取り図', 'koumuten-core' ),
					'type'  => 'tab',
				),
				array(
					'key'          => 'field_kc_work_floor_plan',
					'label'        => __( '間取り図データ', 'koumuten-core' ),
					'name'         => 'floor_plan',
					'type'         => 'textarea',
					'rows'         => 10,
					'instructions' => __(
						'1 行に 1 部屋を「室名,X,Y,幅,高さ」の形式で入力します（数値はマス目単位）。'
						. '左上が原点（0,0）で、右方向が X、下方向が Y です。図は自動で組み立てられます。' . "\n\n"
						. '入力例:' . "\n"
						. 'LDK,0,0,6,4' . "\n"
						. '洋室,6,0,3,3' . "\n"
						. '和室,6,3,3,3' . "\n"
						. '玄関,0,4,2,2',
						'koumuten-core'
					),
					'placeholder'  => "LDK,0,0,6,4\n洋室,6,0,3,3",
				),
				array(
					'key'          => 'field_kc_work_floor_plan_note',
					'label'        => __( '間取り図の注記', 'koumuten-core' ),
					'name'         => 'floor_plan_note',
					'type'         => 'text',
					'instructions' => __( '例: 1階平面図（寸法は概略です）', 'koumuten-core' ),
				),
			),
			'hide_on_screen' => array( 'comments', 'discussion', 'author', 'trackbacks' ),
		)
	);
}
