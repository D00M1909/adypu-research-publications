#!/usr/bin/env bash
# Stages the files an upload needs into upload/, keeping their paths, so the
# whole tree can be dragged onto the server's htdocs in one drop.
#
#   tools/stage-upload.sh           changed since the last commit (incl. uncommitted)
#   tools/stage-upload.sh HEAD~3    changed over the last 3 commits
#   tools/stage-upload.sh --all     every deployable file, for a fresh install
#
# Never staged: data/ (the live accounts, publications and proof files the
# server wrote itself), tools/, tests/, and includes/config.local.php, which
# lives only on the server. Copying a local one up would swap the live admin
# login for a dev one.
set -euo pipefail
cd "$(dirname "$0")/.."

OUT=upload
rm -rf "$OUT"

PAGES="index.php login.php mine.php add.php view.php proof.php account.php admin.php school.php list.php accounts.php export.php"

if [ "${1:-}" = "--all" ]; then
  files=$(printf '%s\n' $PAGES .htaccess; find includes css js img -type f)
else
  # Diffs the ref against the working tree, so an edited but uncommitted file
  # still goes up, and adds untracked files, which git diff never lists.
  files=$( { git diff --name-only --diff-filter=d "${1:-HEAD~1}"; git ls-files --others --exclude-standard; } | sort -u )
fi

n=0
while IFS= read -r f; do
  [ -n "$f" ] || continue
  # An allowlist: exactly what the server serves, so a README, a test or a
  # dotfile can never ride along by accident.
  case " $PAGES " in
    *" $f "*) ;;
    *) case "$f" in .htaccess|includes/*|css/*|js/*|img/*) ;; *) continue ;; esac ;;
  esac
  case "$f" in includes/config.local.php|includes/config.local.example.php) continue ;; esac
  [ -f "$f" ] || continue
  mkdir -p "$OUT/$(dirname "$f")"
  cp "$f" "$OUT/$f"
  echo "  $f"
  n=$((n + 1))
done <<< "$files"

echo
if [ "$n" -eq 0 ]; then
  echo "nothing to upload."
  rmdir "$OUT" 2>/dev/null || true
  exit
fi
echo "$n file(s) staged in $OUT/"
echo "Open that folder, select all, drop it on the server's htdocs root."
if [ -f "$OUT/.htaccess" ]; then
  echo
  echo "NOTE: .htaccess is in there. It is a dotfile, so turn on hidden files"
  echo "      before selecting all, or it gets left behind."
fi
