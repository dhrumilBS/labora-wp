#!/usr/bin/env bash
# Restore a dump made by db-backup.sh, then point the site at this environment's URL.
#
# Usage:  scripts/db-restore.sh backups/labora-wp-2026-10-08-1700.sql.gz [new-url]
#   new-url (optional): e.g. https://www.labora.com when restoring a local dump on the live server.
#   The current site URL is replaced everywhere, including serialized data, with wp search-replace.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WP="${WP_CLI:-/d/xampp/tools/wp}"
# mysqldump/mysql for WP-CLI: XAMPP's folder by default; set MYSQL_BIN elsewhere, or have them on PATH
MYSQL_BIN="${MYSQL_BIN:-/d/xampp/mysql/bin}"
[ -d "$MYSQL_BIN" ] && export PATH="$MYSQL_BIN:$PATH"
DUMP="${1:?Give the dump file to restore}"
NEW_URL="${2:-}"

cd "$ROOT"
OLD_URL="$("$WP" option get siteurl)"
echo "Restoring $DUMP into the database configured in wp-config.php ..."
read -r -p "This replaces the current database. Type RESTORE to continue: " ok
[ "$ok" = "RESTORE" ] || { echo "Cancelled."; exit 1; }

# Safety copy of what is there now
"$(dirname "$0")/db-backup.sh" >/dev/null && echo "Current database saved to backups/ first."

case "$DUMP" in
  *.gz) gunzip -c "$DUMP" > "$ROOT/backups/.restore.sql" ;;
  *)    cp "$DUMP" "$ROOT/backups/.restore.sql" ;;
esac
"$WP" db import "$ROOT/backups/.restore.sql"
rm -f "$ROOT/backups/.restore.sql"

if [ -n "$NEW_URL" ]; then
  DUMP_URL="$("$WP" option get siteurl)"
  "$WP" search-replace "$DUMP_URL" "$NEW_URL" --all-tables --precise --skip-columns=guid
  echo "URLs changed from $DUMP_URL to $NEW_URL"
fi
"$WP" cache flush >/dev/null 2>&1 || true
"$WP" rewrite flush
echo "Done. Site URL: $("$WP" option get siteurl) (was $OLD_URL before the restore)"
