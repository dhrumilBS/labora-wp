#!/usr/bin/env bash
# Export the WordPress database to backups/ (git-ignored: dumps contain form leads and user data).
# Credentials are read from wp-config.php by WP-CLI, so none are stored here.
#
# Usage:  scripts/db-backup.sh            -> backups/labora-wp-YYYY-MM-DD-HHMM.sql.gz
#         KEEP=30 scripts/db-backup.sh    -> keep the newest 30 dumps (default 14)
# Needs:  WP-CLI on PATH, or WP_CLI pointing at it (default below is the local XAMPP setup).
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WP="${WP_CLI:-/d/xampp/tools/wp}"
KEEP="${KEEP:-14}"
OUT_DIR="$ROOT/backups"
STAMP="$(date +%Y-%m-%d-%H%M)"
FILE="$OUT_DIR/labora-wp-$STAMP.sql"

mkdir -p "$OUT_DIR"
cd "$ROOT"
"$WP" db export "$FILE" --add-drop-table --quiet
gzip -9 "$FILE"
echo "Saved $FILE.gz ($(du -h "$FILE.gz" | cut -f1))"

# Rotate: keep the newest $KEEP dumps
ls -1t "$OUT_DIR"/labora-wp-*.sql.gz 2>/dev/null | tail -n +"$((KEEP + 1))" | xargs -r rm -f
