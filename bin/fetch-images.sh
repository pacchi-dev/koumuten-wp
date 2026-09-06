#!/usr/bin/env bash
#
# 写真素材を取得する。
#
#   使い方: ./bin/fetch-images.sh
#
# 素材そのものは Git 管理しない（再配布しないため）。
# このスクリプトで同じ状態を再現できるようにしてある。
#
# 出典はすべて Pexels / Unsplash。
#   - 商用利用可・クレジット表記不要・枚数制限なし
#   - 出典は配信元 URL で記録する（README の一覧を参照）。
#     クレジット表記が必須ではなく、URL で写真を一意に特定できるため、
#     撮影者名は記載していない。
#
set -euo pipefail
cd "$(dirname "$0")/.."
DEST="bin/seed/images"
mkdir -p "$DEST"

# ファイル名<TAB>URL
# Unsplash: https://images.unsplash.com/{photo-id}
# Pexels  : https://images.pexels.com/photos/{id}/pexels-photo-{id}.jpeg
IMAGES=$(cat <<'LIST'
hero-main.jpg	https://images.unsplash.com/photo-1600047509807-ba8f99d2cdde?w=1920&q=80
service-newbuild.jpg	https://images.unsplash.com/photo-1600585154526-990dced4db0d?w=1600&q=80
service-reform.jpg	https://images.pexels.com/photos/1743227/pexels-photo-1743227.jpeg?w=1600
service-exterior.jpg	https://images.unsplash.com/photo-1558904541-efa843a96f01?w=1600&q=80
work-01.jpg	https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=1600&q=80
work-02.jpg	https://images.unsplash.com/photo-1523217582562-09d0def993a6?w=1600&q=80
work-03.jpg	https://images.unsplash.com/photo-1600585152220-90363fe7e115?w=1600&q=80
work-04.jpg	https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=1600&q=80
work-05.jpg	https://images.unsplash.com/photo-1600573472550-8090b5e0745e?w=1600&q=80
work-06.jpg	https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=1600&q=80
company-office.jpg	https://images.pexels.com/photos/323780/pexels-photo-323780.jpeg?w=1600
recruit-site.jpg	https://images.unsplash.com/photo-1541888946425-d81bb19240f5?w=1600&q=80
LIST
)

count=0
while IFS=$'\t' read -r name url; do
  [ -z "$name" ] && continue
  if [ -s "${DEST}/${name}" ]; then
    echo "    スキップ（取得済み）: ${name}"
    count=$((count+1))
    continue
  fi
  echo "==> ${name}"
  if curl -sfL --max-time 60 -o "${DEST}/${name}" "$url"; then
    count=$((count+1))
  else
    echo "    取得に失敗しました: ${url}" >&2
    rm -f "${DEST}/${name}"
  fi
done <<< "$IMAGES"

echo "完了: ${count} 枚 → ${DEST}"
