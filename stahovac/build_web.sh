#!/usr/bin/env bash
# Zostaví verziu pre PHP webhosting (nahratie cez FTP) do dist/stahovac/ a dist/stahovac-web.zip
set -euo pipefail
cd "$(dirname "$0")"
OUT=dist/stahovac
rm -rf dist && mkdir -p "$OUT"
cp static/styles.css static/app.js static/manifest.webmanifest static/icon.svg static/sw.js "$OUT/"
cp php/api.php php/common.php php/setup.php "$OUT/"
cp php/NAVOD.txt "$OUT/NAVOD.txt"
# index.html: rozhranie volá api.php namiesto /api/…
sed 's#<script src="app.js"></script>#<script>window.STAHOVAC_API = "api.php";</script>\n  <script src="app.js"></script>#' \
  static/index.html > "$OUT/index.html"
grep -q 'STAHOVAC_API = "api.php"' "$OUT/index.html"
cat > "$OUT/.htaccess" <<'HT'
Options -Indexes
DirectoryIndex index.html
<FilesMatch "^(common\.php|NAVOD\.txt)$">
  Require all denied
</FilesMatch>
HT
mkdir -p "$OUT/data" "$OUT/bin"
printf 'Require all denied\nDeny from all\n' > "$OUT/data/.htaccess"
printf 'Require all denied\nDeny from all\n' > "$OUT/bin/.htaccess"
(cd dist && zip -qr stahovac-web.zip stahovac)
echo "Hotovo: $(pwd)/dist/stahovac-web.zip"
