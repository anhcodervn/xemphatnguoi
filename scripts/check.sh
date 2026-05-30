#!/usr/bin/env bash
set -euo pipefail

echo "[check] php format"
vendor/bin/pint --dirty --format agent

echo "[check] frontend format check"
npm run format:check

echo "[check] frontend lint"
npx eslint .

echo "[check] build"
npm run build

echo "[check] done"
