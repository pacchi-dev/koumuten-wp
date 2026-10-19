<?php
/**
 * 使い捨ての展開スクリプト。
 *
 * FTP で zip を 1 個送り、これを HTTPS から 1 回だけ叩いて展開する。
 * 数百のファイルを 1 つずつ FTP 転送すると、ファイルごとに
 * データコネクションを張り直すため極端に遅くなる。
 * zip なら 1 接続で済み、展開はサーバー側で完結する。
 *
 * 実行後は zip と自分自身を必ず削除する。
 * 公開ディレクトリに展開機能を残すのは危険なため。
 */
declare( strict_types=1 );

const EXPECTED_TOKEN  = '__TOKEN__';
const EXPECTED_SHA256 = '__SHA256__';
const ARCHIVE         = __DIR__ . '/__ARCHIVE__';
const DEST            = __DIR__;

header( 'Content-Type: text/plain; charset=UTF-8' );

/*
 * 推測での実行を防ぐ。トークンは deploy.sh が毎回生成する。
 * hash_equals() はタイミング攻撃を避けるための定数時間比較。
 * 文字列長が異なる場合も false を返す。
 */
if ( ! hash_equals( EXPECTED_TOKEN, (string) ( $_GET['token'] ?? '' ) ) ) {
	http_response_code( 403 );
	echo "forbidden\n";
	exit;
}

/** zip と自分自身を消す。処理の成否にかかわらず必ず通す。 */
function cleanup(): void {
	@unlink( ARCHIVE );
	@unlink( __FILE__ );
}

if ( ! class_exists( 'ZipArchive' ) ) {
	cleanup();
	http_response_code( 500 );
	echo "error: ZipArchive が利用できません\n";
	exit;
}

if ( ! is_readable( ARCHIVE ) ) {
	cleanup();
	http_response_code( 500 );
	echo 'error: ' . basename( ARCHIVE ) . " が見つかりません\n";
	exit;
}

/*
 * 書庫の完全性を確認する。
 *
 * FTP のデータチャネルは TLS が不安定なため平文へフォールバックすることがある。
 * そこで失われるのは機密性だけではなく «完全性» も同じで、
 * この zip は Web ルートへ展開されるため、差し替えられれば任意コード実行になる。
 * deploy.sh が埋め込んだ sha256 と一致しない限り展開しない。
 */
$actual_sha256 = hash_file( 'sha256', ARCHIVE );

if ( ! is_string( $actual_sha256 ) || ! hash_equals( EXPECTED_SHA256, $actual_sha256 ) ) {
	cleanup();
	http_response_code( 409 );
	echo "error: 書庫の sha256 が一致しません（改ざんまたは転送破損）\n";
	exit;
}

$zip = new ZipArchive();
$opened = $zip->open( ARCHIVE );

if ( true !== $opened ) {
	cleanup();
	http_response_code( 500 );
	echo "error: zip を開けません (code {$opened})\n";
	exit;
}

$count = $zip->numFiles;

if ( ! $zip->extractTo( DEST ) ) {
	$zip->close();
	cleanup();
	http_response_code( 500 );
	echo "error: 展開に失敗しました\n";
	exit;
}

$zip->close();
cleanup();

echo "ok\n";
echo "extracted: {$count}\n";
echo 'dest: ' . DEST . "\n";
