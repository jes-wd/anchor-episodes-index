#!/usr/bin/env bash
# Stage this plugin into the wordpress.org SVN checkout.
#
# The version is read from the plugin header, so the tag directory can never
# drift from what is actually being released. The previous version of this
# script had the tag hardcoded, which meant a release would be copied into an
# old tag directory and the new one left empty.
set -euo pipefail

cd "$(dirname "$0")/.."

SLUG="anchor-episodes-index"
SVN="${SVN_PATH:-$HOME/dev/svn/$SLUG}"
VERSION=$(sed -n 's/^[[:space:]]*\*[[:space:]]*Version:[[:space:]]*\(.*\)$/\1/p' "$SLUG.php" | head -1 | tr -d '[:space:]')
STABLE=$(sed -n 's/^Stable tag:[[:space:]]*\(.*\)$/\1/p' readme.txt | head -1 | tr -d '[:space:]')

if [ -z "$VERSION" ]; then
  echo "Could not read Version from $SLUG.php" >&2; exit 1
fi

if [ "$VERSION" != "$STABLE" ]; then
  echo "Version header ($VERSION) and readme Stable tag ($STABLE) disagree." >&2
  echo "wordpress.org serves whatever Stable tag names, so fix this first." >&2
  exit 1
fi

if [ ! -d "$SVN/trunk" ]; then
  echo "No SVN checkout at $SVN. Run:" >&2
  echo "  svn co https://plugins.svn.wordpress.org/$SLUG $SVN" >&2
  echo "or set SVN_PATH." >&2
  exit 1
fi

echo "Staging $VERSION into $SVN"
rsync -a --delete --exclude-from=rsync-excludes.txt ./ "$SVN/trunk/"
mkdir -p "$SVN/tags/$VERSION"
rsync -a --delete --exclude-from=rsync-excludes.txt ./ "$SVN/tags/$VERSION/"

cat <<MSG

Staged. Now, from $SVN:
  svn status                      # check for ! and ? entries
  svn add --force .               # pick up new files
  svn ci -m "Release $VERSION"

trunk/readme.txt must say "Stable tag: $VERSION" - it is what decides
which tag wordpress.org actually serves.
MSG
