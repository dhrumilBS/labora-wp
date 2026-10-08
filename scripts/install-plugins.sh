#!/usr/bin/env bash
# Install the third-party plugins listed in plugins.txt (they are not committed to git).
# Each line: <slug> <version|latest> <active|inactive>   Lines starting with # are ignored.
# Premium plugins are not on WordPress.org: put their .zip in backups/plugins/ (git-ignored) and list
# them with version "zip"; this script installs backups/plugins/<slug>.zip.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WP="${WP_CLI:-/d/xampp/tools/wp}"
cd "$ROOT"

sed 's/#.*//' plugins.txt | grep -v -E '^\s*$' | while read -r slug version state; do
  if [ "$version" = "zip" ]; then
    src="backups/plugins/$slug.zip"
    [ -f "$src" ] || { echo "Skipping $slug: put its zip at $src"; continue; }
  elif [ "$version" = "latest" ]; then
    src="$slug"
  else
    src="$slug --version=$version"
  fi
  flag=""; [ "$state" = "active" ] && flag="--activate"
  # shellcheck disable=SC2086
  "$WP" plugin install $src --force $flag
done
"$(dirname "$0")/apply-patches.sh"
"$WP" plugin list --fields=name,status,version
