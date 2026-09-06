<?php
/**
 * 施工実績の絞り込み一覧（work_area）。
 *
 * 表示は施工実績一覧と同一のため archive-work.php を読み込む。
 * 同じ HTML を 2 箇所に書くと、片方だけ直して食い違う。
 *
 * @package SoranowaKoumuten
 */

defined( 'ABSPATH' ) || exit;

require get_template_directory() . '/archive-work.php';
