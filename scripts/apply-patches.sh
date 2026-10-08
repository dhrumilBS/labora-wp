#!/usr/bin/env bash
# Re-apply local fixes to third-party plugins (run after updating any of them). See scripts/patches/.
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PHP="${PHP_BIN:-/d/xampp/php/php.exe}"
command -v "$PHP" >/dev/null 2>&1 || PHP=php
for p in "$ROOT"/scripts/patches/*.php; do
  echo "$(basename "$p" .php):"
  "$PHP" "$p"
done
