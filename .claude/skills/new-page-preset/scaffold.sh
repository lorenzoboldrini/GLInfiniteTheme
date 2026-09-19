#!/usr/bin/env bash
# Scaffold a page preset (block pattern) in patterns/page-<slug>.php.
# Usage: scaffold.sh <slug-kebab-case> "<Title>"
set -euo pipefail

NAMESPACE="glinf"
TEXT_DOMAIN="gl-infinite-theme"
CATEGORY="${NAMESPACE}-page-presets"

SLUG="${1:-}"
TITLE="${2:-}"

[[ -n "$SLUG" && -n "$TITLE" ]] || { echo "Usage: scaffold.sh <slug-kebab-case> \"<Title>\"" >&2; exit 1; }
[[ "$SLUG" =~ ^[a-z][a-z0-9]*(-[a-z0-9]+)*$ ]] || { echo "Error: slug must be kebab-case (a-z, 0-9, -)." >&2; exit 1; }
case "$TITLE" in
	*"'"* | *'"'* | *'\'* | *'&'* | *'|'*) echo "Error: title must not contain ' \" \\ & |" >&2; exit 1 ;;
esac

HERE="$(cd "$(dirname "$0")" && pwd)"
THEME_ROOT="$(cd "$HERE/../../.." && pwd)"
DEST="$THEME_ROOT/patterns/page-$SLUG.php"

[[ ! -e "$DEST" ]] || { echo "Error: $DEST already exists." >&2; exit 1; }

mkdir -p "$THEME_ROOT/patterns"
sed \
	-e "s|{{slug}}|$SLUG|g" \
	-e "s|{{title}}|$TITLE|g" \
	-e "s|{{namespace}}|$NAMESPACE|g" \
	-e "s|{{category}}|$CATEGORY|g" \
	-e "s|{{textdomain}}|$TEXT_DOMAIN|g" \
	"$HERE/templates/pattern.php.tpl" > "$DEST"

echo "Created patterns/page-$SLUG.php (slug '$NAMESPACE/page-$SLUG', category '$CATEGORY')."
grep -qs "$CATEGORY" "$THEME_ROOT"/inc/*.php "$THEME_ROOT/functions.php" 2>/dev/null \
	|| echo "Warning: pattern category '$CATEGORY' is not registered yet (register_block_pattern_category in inc/patterns.php)."
