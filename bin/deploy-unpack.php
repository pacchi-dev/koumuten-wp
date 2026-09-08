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

const EXPECTED_TOKEN = '__TOKEN__';
const ARCHIVE        = __DIR__ . '/__ARCHIVE__';
const DEST           = __DIR__;

header( 'Content-Type: text/plain; charset=UTF-8' );

// 推測での実行を防ぐ。トークンは deploy.sh が毎回生成する。
if ( ( $_GET['token'] ?? '' ) !== EXPECTED_TOKEN ) {
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
