#!/usr/bin/env bash
set -euo pipefail

if [[ $# -gt 0 ]]; then
  echo "[test] running targeted tests: $*"
  php artisan test --compact "$@"
else
  echo "[test] running full suite"
  php artisan test --compact
fi

echo "[test] done"

