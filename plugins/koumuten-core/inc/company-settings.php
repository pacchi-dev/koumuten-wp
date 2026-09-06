<?php
/**
 * 会社情報（サイト共通設定）。
 *
 * 電話番号・住所・営業時間はサイト全体で何度も出力する値なので、
 * テンプレートに直接書かず管理画面から変更できるようにする。
 *
 * なぜカスタマイザーか:
 *   ACF 無料版には Options Page（サイト全体設定）が無い。
 *   固定ページに紐付ける回避策もあるが、「会社の電話番号がトップページの
 *   編集画面にある」のは運営者にとって不自然。
 *   カスタマイザーはライブプレビューがあり、非エンジニアに説明しやすい。
 *
 * なぜプラグイン側か:
 *   会社情報はテーマを差し替えても残るべきデータのため。
 *   カスタマイザー API はプラグインからも利用できる。
 *
 * @package KoumutenCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 会社情報の項目定義。
 *
 * ここを唯一の定義元にして、カスタマイザーの登録と既定値の取得の
 * 両方から参照する（項目追加時の書き漏らしを防ぐ）。
 *
 * @return array<string, array{label: string, default: string, type: string}>
 */
function kc_company_fields(): array {
	return array(
		'name'          => array(
			'label'   => __( '会社名', 'koumuten-core' ),
			'default' => '株式会社ソラノワ工務店',
			'type'    => 'text',
		),
		'name_kana'     => array(
			'label'   => __( '会社名（英字）', 'koumuten-core' ),
			'default' => 'SORANOWA KOUMUTEN',
			'type'    => 'text',
		),
		'zip'           => array(
			'label'   => __( '郵便番号', 'koumuten-core' ),
			'default' => '670-0000',
			'type'    => 'text',
		),
		'address'       => array(
			'label'   => __( '住所', 'koumuten-core' ),
			'default' => '兵庫県姫路市ソラノワ町0-0-0',
			'type'    => 'text',
		),
		'tel'           => array(
			'label'   => __( '電話番号', 'koumuten-core' ),
			'default' => '079-000-0000',
			'type'    => 'text',
		),
		'fax'           => array(
			'label'   => __( 'FAX 番号', 'koumuten-core' ),
			'default' => '079-000-0001',
			'type'    => 'text',
		),
		'hours'         => array(
			'label'   => __( '営業時間', 'koumuten-core' ),
			'default' => '9:00〜18:00',
			'type'    => 'text',
		),
		'holiday'       => array(
			'label'   => __( '定休日', 'koumuten-core' ),
			'default' => '水曜・第2/第4火曜',
			'type'    => 'text',
		),
		'area'          => array(
			'label'   => __( '対応エリア', 'koumuten-core' ),
			'default' => '姫路市・たつの市・加古川市・高砂市・福崎町',
			'type'    => 'text',
		),
		'license'       => array(
			'label'   => __( '建設業許可番号', 'koumuten-core' ),
			'default' => '兵庫県知事許可（般-00）第00000号（ダミー）',
			'type'    => 'text',
		),
		'instagram_url' => array(
			'label'   => __( 'Instagram の URL', 'koumuten-core' ),
			'default' => 'https://www.instagram.com/',
			'type'    => 'url',
		),
		'map_embed'     => array(
			'label'   => __( 'Google マップの埋め込み URL', 'koumuten-core' ),
			'default' => 'https://maps.google.com/maps?q=%E5%A7%AB%E8%B7%AF%E9%A7%85&z=15&output=embed',
			'type'    => 'url',
		),
	);
}

/**
 * 会社情報を取得する。
 *
 * 返り値は生の値。出力側で必ずエスケープすること。
 *
 * @param string $key kc_company_fields() のキー。
 * @return string
 */
function kc_company( string $key ): string {
	$fields = kc_company_fields();

	if ( ! isset( $fields[ $key ] ) ) {
		return '';
	}

	return (string) get_theme_mod( 'kc_company_' . $key, $fields[ $key ]['default'] );
}

/**
 * 電話番号を tel: リンク用に整形する。
 *
 * ハイフンを除いた数字だけにする。表示用の値をそのまま href に入れると
 * 端末によってはダイヤルできない。
 *
 * @param string $tel 電話番号。
 * @return string
 */
function kc_tel_href( string $tel ): string {
	return 'tel:' . preg_replace( '/[^0-9+]/', '', $tel );
}

/**
 * カスタマイザーに会社情報セクションを登録する。
 *
 * @param WP_Customize_Manager $wp_customize カスタマイザー。
 */
add_action( 'customize_register', 'kc_customize_register' );
function kc_customize_register( WP_Customize_Manager $wp_customize ): void {

	$wp_customize->add_section(
		'kc_company',
		array(
			'title'       => __( '会社情報', 'koumuten-core' ),
			'priority'    => 20,
			'description' => __( 'サイト全体（ヘッダー・フッター・お問い合わせ・構造化データ）で使う会社情報です。', 'koumuten-core' ),
		)
	);

	foreach ( kc_company_fields() as $key => $field ) {
		$setting = 'kc_company_' . $key;

		$wp_customize->add_setting(
			$setting,
			array(
				'default'           => $field['default'],
				// 値の保存前に必ず通す。入力のサニタイズ漏れを防ぐ。
				'sanitize_callback' => 'url' === $field['type'] ? 'esc_url_raw' : 'sanitize_text_field',
				// 変更をプレビューへ即時反映する。
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			$setting,
			array(
				'label'   => $field['label'],
				'section' => 'kc_company',
				'type'    => 'url' === $field['type'] ? 'url' : 'text',
			)
		);
	}
}
