#!/usr/bin/env bash
#
# WordPress の初期セットアップを WP-CLI で自動化する。
# 何度実行しても同じ結果になるよう冪等に書いてある。
#
#   使い方: ./bin/setup.sh
#
# 注意:
#   コンテナ内でのコマンド実行に `docker compose exec` ではなく `docker exec` を使う。
#   Compose の一部バージョン（2.0.0-beta.1 で確認）では `exec -T` が標準出力を
#   返さず、バージョン取得などの結果が空になるため。
#
set -euo pipefail

cd "$(dirname "$0")/.."

if [ ! -f .env ]; then
  echo "==> .env が無いので .env.example からコピーします"
  cp .env.example .env
fi

# shellcheck disable=SC1091
set -a; source .env; set +a

WP_CONTAINER="koumuten_wp"
CLI_CONTAINER="koumuten_wp_cli"
DB_CONTAINER="koumuten_wp_db"

wp() {
  docker exec "${CLI_CONTAINER}" wp --path=/var/www/html "$@"
}

echo "==> 写真素材を用意します"
./bin/fetch-images.sh

echo "==> コンテナを起動します"
docker compose up -d

echo "==> データベースの起動を待ちます"
# mysqladmin ping は初期化の途中でも成功してしまう。
# WordPress が使うユーザーで実際に接続できるまで待たないと、
# 直後の wp core install が「データベース接続確立エラー」で落ちる。
for i in $(seq 1 90); do
  if docker exec "${DB_CONTAINER}" \
      mysql -u"${DB_USER}" -p"${DB_PASSWORD}" -D "${DB_NAME}" -e 'SELECT 1' >/dev/null 2>&1; then
    echo "    データベース接続を確認"
    break
  fi
  if [ "$i" -eq 90 ]; then
    echo "    データベースに接続できませんでした" >&2
    exit 1
  fi
  sleep 2
done

echo "==> WordPress 本体の展開を待ちます"
for i in $(seq 1 60); do
  if docker exec "${CLI_CONTAINER}" test -f /var/www/html/wp-settings.php >/dev/null 2>&1; then
    echo "    本体を確認"
    break
  fi
  if [ "$i" -eq 60 ]; then
    echo "    WordPress 本体が展開されませんでした" >&2
    exit 1
  fi
  sleep 2
done

# ---------------------------------------------------------------------------
# WordPress インストール
# ---------------------------------------------------------------------------
if wp core is-installed >/dev/null 2>&1; then
  echo "==> WordPress はインストール済みです（スキップ）"
else
  echo "==> WordPress をインストールします"
  wp core install \
    --url="${WP_URL}" \
    --title="${WP_TITLE}" \
    --admin_user="${WP_ADMIN_USER}" \
    --admin_password="${WP_ADMIN_PASSWORD}" \
    --admin_email="${WP_ADMIN_EMAIL}" \
    --skip-email
fi

# ---------------------------------------------------------------------------
# wp-config.php の定数
#   WP_ENVIRONMENT_TYPE=local は mu-plugins/local-smtp.php が
#   SMTP を Mailpit に向ける条件になっている。これが無いとメールが飛ばない。
# ---------------------------------------------------------------------------
echo "==> wp-config.php の定数を設定します"
wp config set WP_DEBUG true --raw --type=constant >/dev/null
wp config set WP_DEBUG_LOG true --raw --type=constant >/dev/null
wp config set WP_DEBUG_DISPLAY false --raw --type=constant >/dev/null
wp config set DISALLOW_FILE_EDIT true --raw --type=constant >/dev/null
wp config set WP_ENVIRONMENT_TYPE local --type=constant >/dev/null

echo "==> 基本設定を適用します"

# パーマリンク構造。
#   デフォルトは ?p=123 形式で、この状態では /works/{slug}/ が機能しない。
#   CPT を扱う案件では最初に必ず確認する項目。
wp rewrite structure '/%postname%/' >/dev/null
wp rewrite flush >/dev/null

wp language core install ja --activate >/dev/null 2>&1 || true
wp option update timezone_string 'Asia/Tokyo'
wp option update date_format 'Y.m.d'
wp option update time_format 'H:i'
wp option update start_of_week 1

# ローカルでは検索エンジンに拾わせない
wp option update blog_public 0

# コメント機能はこのサイトでは使わない
wp option update default_comment_status closed
wp option update default_ping_status closed

# アイキャッチのトリミング設定（中サイズは切り抜かない）
wp option update thumbnail_crop 1

echo "==> 初期状態の不要コンテンツを削除します"
sample_post="$(wp post list --post_type=post --name=hello-world --format=ids 2>/dev/null || true)"
if [ -n "${sample_post}" ]; then
  wp post delete "${sample_post}" --force >/dev/null 2>&1 || true
fi
for slug in sample-page privacy-policy; do
  id="$(wp post list --post_type=page --name="${slug}" --format=ids 2>/dev/null || true)"
  if [ -n "${id}" ]; then
    wp post delete "${id}" --force >/dev/null 2>&1 || true
  fi
done

echo "==> 不要な同梱プラグインを削除します"
# Akismet / Hello Dolly は使わない。「プラグインは最小限」の方針に沿う。
wp plugin delete akismet hello >/dev/null 2>&1 || true

# ---------------------------------------------------------------------------
# プラグイン
#   ACF     … カスタムフィールド（無料版で完結させる）
#   CF7     … 実サイト 50 件で最多（42%）だったフォームプラグイン
#   自作 koumuten-core … CPT / タクソノミー / フィールド定義 / 会社情報
# ---------------------------------------------------------------------------
echo "==> プラグインを用意します"
wp plugin is-installed advanced-custom-fields >/dev/null 2>&1 || wp plugin install advanced-custom-fields >/dev/null
wp plugin is-installed contact-form-7 >/dev/null 2>&1 || wp plugin install contact-form-7 >/dev/null
wp plugin activate advanced-custom-fields contact-form-7 koumuten-core >/dev/null

echo "==> テーマを有効化します"
wp theme activate soranowa-koumuten >/dev/null

# CPT のリライトルールを確実に反映させる
wp rewrite flush >/dev/null

# ---------------------------------------------------------------------------
# コンテンツ投入
#   スクリプトを分けているのは ACF のフィールドグループ登録タイミングのため。
#   詳細は bin/seed-fields.php の冒頭コメントを参照。
# ---------------------------------------------------------------------------
echo "==> コンテンツを投入します"
wp eval-file /scripts/seed-pages.php
wp eval-file /scripts/seed-works.php
wp eval-file /scripts/seed-news.php
wp eval-file /scripts/seed-forms.php
wp eval-file /scripts/seed-fields.php

wp rewrite flush >/dev/null

# ---------------------------------------------------------------------------
# 静的アセットのキャッシュ設定
# ---------------------------------------------------------------------------
echo "==> 静的アセットのキャッシュ設定を適用します"
if ! docker exec "${WP_CONTAINER}" grep -q "mod_expires" /var/www/html/.htaccess 2>/dev/null; then
  docker exec -i "${WP_CONTAINER}" sh -c 'cat >> /var/www/html/.htaccess' < config/htaccess-cache.conf
  docker exec "${WP_CONTAINER}" sh -c 'a2enmod expires headers >/dev/null 2>&1 && apache2ctl graceful >/dev/null 2>&1' || true
  echo "    適用しました"
else
  echo "    適用済みです（スキップ）"
fi

echo ""
echo "======================================================"
echo " セットアップ完了"
echo ""
echo "  サイト        : ${WP_URL}"
echo "  管理画面      : ${WP_URL}/wp-admin/"
echo "  ユーザー      : ${WP_ADMIN_USER}"
echo "  パスワード    : ${WP_ADMIN_PASSWORD}"
echo "  メール確認    : http://localhost:${MAILPIT_PORT}"
echo ""
echo "  WordPress     : $(wp core version)"
echo "  PHP           : $(docker exec "${WP_CONTAINER}" php -r 'echo PHP_VERSION;')"
echo "======================================================"
