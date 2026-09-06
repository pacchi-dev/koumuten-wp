<?php
/**
 * Plugin Name: Local SMTP (development only)
 * Description: ローカル開発時に wp_mail() の送信先を Mailpit へ向ける。本番環境では何もしない。
 *
 * mu-plugins（must-use plugins）は管理画面から無効化できず自動で読み込まれる。
 * 「環境固有の設定で、絶対に外れてほしくないもの」を置く場所として使う。
 *
 * なぜ必要か:
 *   素の WordPress コンテナには MTA が無く mail() が外に出ない。
 *   Contact Form 7 は「送信しました」と表示するが、実際にはメールが生成されない。
 *   これでは「フォームが送信できる」を検証できないため、SMTP を Mailpit に向ける。
 *
 * 本番への移設:
 *   wp_get_environment_type() が 'local' のときだけ動く。
 *   レンタルサーバーへ上げてもこのファイルを消す必要はなく、標準の mail() に戻る。
 *
 * @package SoranowaKoumuten
 */

defined( 'ABSPATH' ) || exit;

/**
 * PHPMailer を SMTP モードに切り替える。
 *
 * wp_mail() は内部で PHPMailer を使っており、phpmailer_init は
 * その設定を差し替えるための action hook。WP Mail SMTP 等の
 * プラグインも同じ仕組みで動いている。だからプラグインを増やさずに済む。
 *
 * @param PHPMailer\PHPMailer\PHPMailer $phpmailer PHPMailer インスタンス。
 */
add_action( 'phpmailer_init', 'koumuten_local_smtp' );
function koumuten_local_smtp( $phpmailer ): void {
	if ( 'local' !== wp_get_environment_type() ) {
		return;
	}

	$phpmailer->isSMTP();
	$phpmailer->Host        = 'mailpit';
	$phpmailer->Port        = 1025;
	$phpmailer->SMTPAuth    = false;
	$phpmailer->SMTPSecure  = '';
	$phpmailer->SMTPAutoTLS = false;
}

/**
 * ローカルでの送信元アドレスを妥当なドメインにする。
 *
 * WordPress の既定は wordpress@{ホスト名} で、ローカルでは wordpress@localhost になる。
 * PHPMailer は TLD を持たないドメインを拒否するため、そのままだと送信自体が失敗する。
 *
 * @param string $from 既定の送信元アドレス。
 * @return string
 */
add_filter( 'wp_mail_from', 'koumuten_local_mail_from' );
function koumuten_local_mail_from( string $from ): string {
	if ( 'local' !== wp_get_environment_type() ) {
		return $from;
	}

	if ( str_ends_with( $from, '@localhost' ) ) {
		return 'wordpress@localhost.test';
	}

	return $from;
}
