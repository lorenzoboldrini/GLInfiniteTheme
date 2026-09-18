#!/usr/bin/env bash
# Scaffold a Style Variation in styles/<slug>.json.
# Usage: scaffold-variation.sh <slug-kebab-case> "<Title>"
set -euo pipefail

SLUG="${1:-}"
TITLE="${2:-}"

[[ -n "$SLUG" && -n "$TITLE" ]] || { echo "Usage: scaffold-variation.sh <slug-kebab-case> \"<Title>\"" >&2; exit 1; }
[[ "$SLUG" =~ ^[a-z][a-z0-9]*(-[a-z0-9]+)*$ ]] || { echo "Error: slug must be kebab-case (a-z, 0-9, -)." >&2; exit 1; }
case "$TITLE" in
	*'"'* | *'\'* | *'&'* | *'|'*) echo "Error: title must not contain \" \\ & |" >&2; exit 1 ;;
esac

HERE="$(cd "$(dirname "$0")" && pwd)"
THEME_ROOT="$(cd "$HERE/../../.." && pwd)"
DEST="$THEME_ROOT/styles/$SLUG.json"

[[ ! -e "$DEST" ]] || { echo "Error: $DEST already exists." >&2; exit 1; }

mkdir -p "$THEME_ROOT/styles"
sed -e "s|{{title}}|$TITLE|g" "$HERE/snippets/variation.json.tpl" > "$DEST"

echo "Created styles/$SLUG.json."
echo "Next: redefine palette values (same slugs as theme.json), check contrast, run validate.sh."
