<?php
/**
 * テーマの読み込み口。
 *
 * このテーマは「表示」だけを担当する。
 * 施工実績（CPT）・カスタムフィールド・会社情報はプラグイン koumuten-core が持つ。
 * テーマを差し替えてもデータが失われない構成にするため。
 *
 * @package SoranowaKoumuten
 */

defined( 'ABSPATH' ) || exit;

define( 'SK_VERSION', '1.0.0' );
define( 'SK_DIR', get_template_directory() );

require_once SK_DIR . '/inc/setup.php';
require_once SK_DIR . '/inc/enqueue.php';
require_once SK_DIR . '/inc/template-tags.php';
require_once SK_DIR . '/inc/floor-plan.php';
