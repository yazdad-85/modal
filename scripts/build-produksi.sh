#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUTPUT="$ROOT/produksi/modalcalc"
ZIP="$ROOT/produksi/modalcalc.zip"

echo "==> ModalCalc — Build paket produksi"
cd "$ROOT"

echo "==> Membersihkan folder output..."
rm -rf "$OUTPUT"
mkdir -p "$OUTPUT"

RSYNC_EXCLUDES=(
    --exclude '.git'
    --exclude '.env'
    --exclude '.env.*'
    --exclude '.DS_Store'
    --exclude 'vendor'
    --exclude 'tests'
    --exclude 'docs'
    --exclude 'build'
    --exclude 'produksi'
    --exclude 'scripts'
    --exclude '.idea'
    --exclude '.vscode'
    --exclude 'phpunit'
    --exclude 'phpunit.dist.xml'
    --exclude 'README.md'
    --exclude 'writable/cache/*'
    --exclude 'writable/logs/*'
    --exclude 'writable/session/*'
    --exclude 'writable/debugbar/*'
    --exclude 'writable/uploads/*'
    --exclude 'writable/install.lock'
    --exclude 'writable/.installing'
)

echo "==> Menyalin file aplikasi..."
rsync -a "${RSYNC_EXCLUDES[@]}" "$ROOT/" "$OUTPUT/"

echo "==> Memastikan folder writable bersih..."
for dir in cache logs session debugbar uploads; do
    mkdir -p "$OUTPUT/writable/$dir"
    if [[ -f "$ROOT/writable/$dir/index.html" ]]; then
        cp "$ROOT/writable/$dir/index.html" "$OUTPUT/writable/$dir/index.html"
    fi
done

echo "==> Composer install di paket produksi (tanpa dev)..."
cd "$OUTPUT"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Menyalin panduan pengguna..."
cp "$ROOT/produksi/README.md" "$OUTPUT/README.md"

echo "==> Membuat arsip ZIP (isi langsung di root, tanpa folder pembungkus)..."
rm -f "$ZIP"
(cd "$OUTPUT" && zip -rq "$ZIP" . -x '.DS_Store')

echo ""
echo "Selesai!"
echo "  Folder : $OUTPUT"
echo "  ZIP    : $ZIP"
echo ""
echo "Upload isi folder modalcalc/ atau modalcalc.zip ke public_html hosting Anda."
