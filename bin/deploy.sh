#!/usr/bin/env bash
#
# bin/dist/ の内容を共用サーバーへ FTPS で反映する。
#
#   使い方: ./bin/deploy.sh [--dry-run]
#
# 前提: 先に ./bin/export.sh を実行して bin/dist/ を作ってあること。
#
# ------------------------------------------------------------------
# なぜ zip を送ってサーバー側で展開するのか
#
# FTP はファイルごとにデータコネクションを張り直す。
# wp-content は ACF だけで 800 ファイルを超えるため、
# 1 つずつ送ると往復のオーバーヘッドで極端に遅くなる。
# 25MB の zip を 1 接続で送り、展開はサーバー側の PHP に任せれば
# 数分が数十秒になる。
#
# 展開スクリプトは毎回ランダムなトークンを埋め込み、
# 実行後に zip ごと自分自身を削除する。
# 公開ディレクトリに展開機能を残さないため。
# ------------------------------------------------------------------
set -euo pipefail

cd "$(dirname "$0")/.."

DRY_RUN=""
[ "${1:-}" = "--dry-run" ] && DRY_RUN="yes"

if [ ! -f .env ]; then
  echo "エラー: .env がありません" >&2
  exit 1
fi

# shellcheck disable=SC1091
set -a; source .env; set +a

for v in FTP_HOST FTP_USER FTP_PASSWORD FTP_REMOTE_DIR DEPLOY_URL; do
  if [ -z "${!v:-}" ]; then
    echo "エラー: .env に ${v} がありません" >&2
    echo "" >&2
    echo "  .env に以下を追記してください:" >&2
    echo "    FTP_HOST=pacchi.net" >&2
    echo "    FTP_USER=（cPanel で作成した FTP ユーザー）" >&2
    echo "    FTP_PASSWORD=（そのパスワード）" >&2
    echo "    FTP_REMOTE_DIR=/koumuten" >&2
    echo "    DEPLOY_URL=https://pacchi.net/koumuten" >&2
    exit 1
  fi
done

DIST="bin/dist"

# bin/dist にある zip をすべて送る。
# 初回は WordPress 本体（wordpress-core.zip）も含まれ、
# 2 回目以降は wp-content.zip だけになる想定。
ARCHIVES=()
for f in "${DIST}"/*.zip; do
  [ -e "$f" ] && ARCHIVES+=("$(basename "$f")")
done
DEPLOY_URL="${DEPLOY_URL%/}"
FTP_REMOTE_DIR="/${FTP_REMOTE_DIR#/}"
FTP_REMOTE_DIR="${FTP_REMOTE_DIR%/}"

if [ ${#ARCHIVES[@]} -eq 0 ]; then
  echo "エラー: ${DIST}/ に zip がありません。先に ./bin/export.sh を実行してください" >&2
  exit 1
fi

# 展開スクリプトは毎回名前もトークンも変える。
# 固定名だと、削除に失敗したときに外部から叩かれうる。
FTP_BASE="ftp://${FTP_HOST}${FTP_REMOTE_DIR}"

echo "==> 転送先: ${FTP_BASE}/"
echo "==> 確認URL: ${DEPLOY_URL}/"
for a in "${ARCHIVES[@]}"; do
  echo "    ${a}  $(du -h "${DIST}/${a}" | cut -f1)"
done
[ -f "${EXTRA_FILE:-}" ] && echo "    ${EXTRA_NAME}（個別ファイル）"
echo ""

if [ -n "${DRY_RUN}" ]; then
  echo "  （--dry-run のため転送しません）"
  exit 0
fi

# --ssl-reqd で暗号化を必須にする。平文 FTP には落とさない。
#
# curl は FTP の制御接続が閉じるのを待つ際、サーバーによっては
# タイムアウトで非ゼロを返す。転送自体は完了しているため、
# 終了コードではなく «転送後のファイルサイズ» で成否を判定する。
ftp_size() {
  # curl は制御接続の切断待ちで非ゼロを返すことがある。
  # set -o pipefail のもとでは、この非ゼロがコマンド置換の代入を
  # 失敗扱いにし、set -e でスクリプトごと落ちる。
  # ここでは «一覧が取れたかどうか» だけが関心事なので握りつぶす。
  # 一覧取得もデータチャネルを使うため、同じ理由で --ftp-ssl-control にする。
  # 得られるのはファイル名とサイズだけで、秘密は含まれない。
  { curl --silent --ftp-ssl-control --max-time 60 \
      --user "${FTP_USER}:${FTP_PASSWORD}" \
      "${FTP_BASE}/" 2>/dev/null || true; } \
    | awk -v n="$1" '$NF == n { print $5 }' | tail -1
}

# このサーバーはデータチャネルの TLS が不安定で、転送が途中で切れる
# （サーバーは 451 を返し、ファイルが 0 バイトや途中までで残る）。
# 検証したところ、認証だけを暗号化する --ftp-ssl-control なら安定して通る。
#
#   --ssl-reqd        制御・データとも TLS。安全だがこのサーバーでは不安定
#   --ftp-ssl-control 制御（＝認証）のみ TLS。データは平文
#
# そこで扱うファイルによって方針を変える。
#   ・wp-config.php のような «秘密を含むもの» は暗号化必須。
#     通らなければ失敗させる。平文には «絶対に» 落とさない。
#   ・テーマや画像のような «公開されるもの» は、暗号化で失敗したら
#     平文データチャネルへ切り替える。内容は HTTPS で誰でも取得でき、
#     GitHub でも公開しているため、秘匿する意味がない。
# パスワードはどちらの経路でも平文にならない。
FTP_RETRIES=3

ftp_put() {
  local src="$1" name="$2" sensitive="${3:-}" expected actual attempt mode
  expected=$(wc -c < "${src}" | tr -d ' ')

  for mode in --ssl-reqd --ftp-ssl-control; do
    if [ "${mode}" = "--ftp-ssl-control" ]; then
      if [ -n "${sensitive}" ]; then
        echo "    エラー: ${name} は秘密を含むため平文転送に切り替えません" >&2
        return 1
      fi
      echo "    データチャネルを平文に切り替えて再試行します（内容は公開ファイル）"
    fi

    for attempt in $(seq 1 "${FTP_RETRIES}"); do
      # 途中で切れた残骸を消してから送り直す。
      curl --silent "${mode}" --max-time 60 \
        --user "${FTP_USER}:${FTP_PASSWORD}" \
        -Q "DELE ${FTP_REMOTE_DIR}/${name}" "${FTP_BASE}/" -o /dev/null >/dev/null 2>&1 || true

      curl --silent "${mode}" --ftp-create-dirs --max-time 600 \
        --user "${FTP_USER}:${FTP_PASSWORD}" \
        --upload-file "${src}" "${FTP_BASE}/${name}" >/dev/null 2>&1 || true

      actual=$(ftp_size "${name}" || true)

      if [ "${actual}" = "${expected}" ]; then
        echo "    ${name} を転送しました（${expected} バイト）"
        return 0
      fi
    done
  done

  echo "    エラー: ${name} を転送できませんでした（最後は ${actual:-0} バイト）" >&2
  return 1
}

# 単発ファイル（wp-config.php など）を先に置く。
# 書庫より前に送っておけば、展開直後から動作する。
if [ -n "${EXTRA_FILE:-}" ] && [ -f "${EXTRA_FILE}" ]; then
  echo "==> ${EXTRA_NAME} を転送します"
  ftp_put "${EXTRA_FILE}" "${EXTRA_NAME}" sensitive
fi

for ARCHIVE in "${ARCHIVES[@]}"; do
  # 展開スクリプトは書庫ごとに使い捨てる。
  # 名前もトークンも毎回変え、実行後に自分を消す。
  # tr … | head -c は head が先に終了して tr が SIGPIPE で落ちる。
  # pipefail 下ではこれが失敗と判定され set -e で止まるため openssl を使う。
  TOKEN=$(openssl rand -hex 16)
  UNPACK_NAME="deploy-$(openssl rand -hex 6).php"
  TMP_UNPACK="$(mktemp -t deploy-unpack).php"
  sed -e "s/__TOKEN__/${TOKEN}/" -e "s/__ARCHIVE__/${ARCHIVE}/" \
    bin/deploy-unpack.php > "${TMP_UNPACK}"

  echo "==> ${ARCHIVE} を転送します"
  ftp_put "${DIST}/${ARCHIVE}" "${ARCHIVE}"
  ftp_put "${TMP_UNPACK}" "${UNPACK_NAME}"
  rm -f "${TMP_UNPACK}"

  echo "    サーバー側で展開しています"
  RESULT=$(curl --silent --show-error --max-time 300 \
    "${DEPLOY_URL}/${UNPACK_NAME}?token=${TOKEN}" || true)
  echo "${RESULT}" | sed 's/^/      /'

  if ! echo "${RESULT}" | grep -q '^ok'; then
    echo "" >&2
    echo "    展開に失敗しました。サーバー上の次のファイルを手で削除してください:" >&2
    echo "      ${FTP_REMOTE_DIR}/${ARCHIVE}" >&2
    echo "      ${FTP_REMOTE_DIR}/${UNPACK_NAME}" >&2
    exit 1
  fi

  CODE=$(curl -s -o /dev/null -w '%{http_code}' --max-time 20 "${DEPLOY_URL}/${UNPACK_NAME}")
  if [ "${CODE}" = "404" ]; then
    echo "    展開スクリプトを削除しました"
  else
    echo "    警告: ${FTP_REMOTE_DIR}/${UNPACK_NAME} が残っています (HTTP ${CODE})" >&2
  fi
done

echo ""
echo "==> 完了"
