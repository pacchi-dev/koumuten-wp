#!/usr/bin/env bash
#
# 共用レンタルサーバーへ移設するためのファイル一式を書き出す。
#
#   使い方: ./bin/export.sh <公開URL> [テーブル接頭辞]
#
#   例（サブディレクトリに設置し、DB を別サイトと共有する場合）:
#     ./bin/export.sh https://pacchi.dev/koumuten km_
#
#   例（独立したサイトとして設置する場合）:
#     ./bin/export.sh https://example.com
#
# 出力先: bin/dist/
#   database.sql  URL とテーブル接頭辞を書き換え済みの DB ダンプ
#   wp-content/   テーマ・プラグイン・アップロード画像
#   README.txt    アップロード手順
#
# ------------------------------------------------------------------
# なぜテーブル接頭辞を書き換えるのか
#
# 無料プランなどで MySQL データベースを 1 つしか作れない場合、
# 2 つの WordPress を同じ DB に同居させる必要がある。
# WordPress は wp-config.php の $table_prefix でテーブル名の接頭辞を
# 変えられるため、片方を wp_、もう片方を km_ にすれば共存できる。
#
# 注意すべきは、接頭辞がテーブル名だけでなく
# 一部のレコードの「値」にも埋め込まれている点。
#   wp_options   の option_name = 'wp_user_roles'
#   wp_usermeta  の meta_key    = 'wp_capabilities' / 'wp_user_level' ほか
# ここを書き換え忘れると、移設先で「権限が無い」状態になり
# 管理画面に入れなくなる。接頭辞変更で最も多い失敗。
# ------------------------------------------------------------------
set -euo pipefail

cd "$(dirname "$0")/.."

TARGET_URL="${1:-}"
NEW_PREFIX="${2:-wp_}"

if [ -z "${TARGET_URL}" ]; then
  echo "エラー: 公開 URL を指定してください" >&2
  echo "  例: ./bin/export.sh https://pacchi.dev/koumuten km_" >&2
  exit 1
fi

case "${NEW_PREFIX}" in
  *_) : ;;
  *)  echo "エラー: テーブル接頭辞はアンダースコアで終わらせてください（例: km_）" >&2; exit 1 ;;
esac

TARGET_URL="${TARGET_URL%/}"

if [ ! -f .env ]; then
  echo "エラー: .env がありません" >&2
  exit 1
fi

# shellcheck disable=SC1091
set -a; source .env; set +a

SOURCE_URL="${WP_URL%/}"
OLD_PREFIX="wp_"
CLI_CONTAINER="koumuten_wp_cli"
DIST="bin/dist"

wp() {
  docker exec "${CLI_CONTAINER}" wp --path=/var/www/html "$@"
}

echo "==> 移設元: ${SOURCE_URL}  (接頭辞 ${OLD_PREFIX})"
echo "==> 移設先: ${TARGET_URL}  (接頭辞 ${NEW_PREFIX})"
echo ""

rm -rf "${DIST}"
mkdir -p "${DIST}/wp-content/themes" "${DIST}/wp-content/plugins" "${DIST}/wp-content/uploads"

# ---------------------------------------------------------------------------
# 1. DB ダンプ（URL 置換済み）
#
#    wp search-replace はシリアライズされた値を展開してから置換し、
#    文字列長を再計算する。sed や SQL の REPLACE では値が壊れる。
#    --export を付けるとローカルの DB は書き換わらない。
# ---------------------------------------------------------------------------
echo "==> DB をエクスポートします（URL を置換）"
wp search-replace "${SOURCE_URL}" "${TARGET_URL}" \
  --all-tables --export=/scripts/dist/database.sql --quiet

# ---------------------------------------------------------------------------
# 2. テーブル接頭辞の書き換え
# ---------------------------------------------------------------------------
if [ "${NEW_PREFIX}" != "${OLD_PREFIX}" ]; then
  echo "==> テーブル接頭辞を ${OLD_PREFIX} から ${NEW_PREFIX} へ書き換えます"

  python3 - "${DIST}/database.sql" "${OLD_PREFIX}" "${NEW_PREFIX}" <<'PY'
import re, sys
path, old, new = sys.argv[1], sys.argv[2], sys.argv[3]
sql = open(path, encoding='utf-8', errors='surrogateescape').read()

# (a) テーブル名。SQL 中では必ずバッククォートで囲まれているため、
#     `wp_ を目印にすれば本文中の "wp_head" などを巻き込まない。
sql, n_tables = re.subn(r'`' + re.escape(old), '`' + new, sql)

# (b) 接頭辞が「値」として入っているレコード。
#     ここを書き換えないと移設先で管理者権限を失う。
keys = [
    'user_roles', 'capabilities', 'user_level',
    'dashboard_quick_press_last_post_id',
    'user-settings', 'user-settings-time',
]
n_values = 0
for k in keys:
    sql, c = re.subn(r"'" + re.escape(old + k) + r"'", "'" + new + k + "'", sql)
    n_values += c

open(path, 'w', encoding='utf-8', errors='surrogateescape').write(sql)
print(f'    テーブル名 {n_tables} 箇所 / 権限まわりの値 {n_values} 箇所を書き換え')
PY
fi

REPLACED=$(grep -c "${TARGET_URL}" "${DIST}/database.sql" || true)
echo "    database.sql に移設先 URL が ${REPLACED} 箇所"

if grep -q "${SOURCE_URL}" "${DIST}/database.sql"; then
  echo "    警告: 移設元 URL が残っています" >&2
fi

# ---------------------------------------------------------------------------
# 3. wp-content
# ---------------------------------------------------------------------------
echo "==> wp-content を収集します"
cp -R themes/soranowa-koumuten "${DIST}/wp-content/themes/"
cp -R plugins/koumuten-core "${DIST}/wp-content/plugins/"

if [ -d uploads ] && [ -n "$(ls -A uploads 2>/dev/null)" ]; then
  cp -R uploads/. "${DIST}/wp-content/uploads/"
fi

echo "    mu-plugins は移設対象外（ローカル専用のため）"
echo "    ACF / Contact Form 7 は移設先で管理画面からインストールする"

# ---------------------------------------------------------------------------
# 4. 手順書
# ---------------------------------------------------------------------------
PREFIX_NOTE=""
if [ "${NEW_PREFIX}" != "${OLD_PREFIX}" ]; then
  PREFIX_NOTE="
【重要】このダンプはテーブル接頭辞を ${NEW_PREFIX} に変更してあります。
設置先の wp-config.php で必ず次のように設定してください。

    \$table_prefix = '${NEW_PREFIX}';

これを忘れると、WordPress が自分のテーブルを見つけられず
インストール画面が表示されます。
"
fi

cat > "${DIST}/README.txt" <<EOS
移設手順（${TARGET_URL}）
${PREFIX_NOTE}
前提: 設置先に WordPress がインストール済みであること。

1. プラグイン Advanced Custom Fields と Contact Form 7 を
   インストールして有効化する。
   DB ダンプ側で有効化済みの状態になっているため、
   ファイルが無いと管理画面でエラーになる。先に入れておく。

2. FTP で wp-content/ の中身をアップロードする。
     wp-content/themes/soranowa-koumuten/
     wp-content/plugins/koumuten-core/
     wp-content/uploads/

3. phpMyAdmin で database.sql をインポートする。

4. 管理画面にログインし、「設定 > パーマリンク」を開いて保存する。
   カスタム投稿タイプ /works/ のリライトルールを再生成するために必要。

5. 動作確認
     ${TARGET_URL}/           トップ
     ${TARGET_URL}/works/     施工実績の一覧
     ${TARGET_URL}/contact/   お問い合わせ
   404 が出る場合は 4 をやり直す。

6. お問い合わせフォームの送信を試す。

注意
- 管理者のパスワードはローカルと同じものが入っている。
  ログイン後すぐに変更すること。
- mu-plugins（ローカル SMTP）は含めていない。
EOS

echo ""
echo "==> 完了"
du -sh "${DIST}" | sed 's/^/    /'
echo ""
echo "    手順は ${DIST}/README.txt を参照してください。"
