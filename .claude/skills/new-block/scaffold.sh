#!/usr/bin/env bash
# Scaffold a custom block in src/blocks/<slug>/.
# Usage: scaffold.sh <slug-kebab-case> "<Title>" [static|dynamic] [dashicon]
set -euo pipefail

NAMESPACE="tu"
TEXT_DOMAIN="gl-infinite-theme"

SLUG="${1:-}"
TITLE="${2:-}"
MODE="${3:-dynamic}"
ICON="${4:-smiley}"

usage() {
	echo "Usage: scaffold.sh <slug-kebab-case> \"<Title>\" [static|dynamic] [dashicon]" >&2
	exit 1
}

[[ -n "$SLUG" && -n "$TITLE" ]] || usage
[[ "$SLUG" =~ ^[a-z][a-z0-9]*(-[a-z0-9]+)*$ ]] || { echo "Error: slug must be kebab-case (a-z, 0-9, -)." >&2; exit 1; }
[[ "$MODE" == "static" || "$MODE" == "dynamic" ]] || { echo "Error: mode must be 'static' or 'dynamic'." >&2; exit 1; }
[[ "$ICON" =~ ^[a-z0-9-]+$ ]] || { echo "Error: icon must be a dashicon slug." >&2; exit 1; }
case "$TITLE" in
	*'"'* | *'\'* | *'&'* | *'|'*) echo "Error: title must not contain \" \\ & |" >&2; exit 1 ;;
esac

HERE="$(cd "$(dirname "$0")" && pwd)"
THEME_ROOT="$(cd "$HERE/../../.." && pwd)"
TPL="$HERE/templates"
DEST="$THEME_ROOT/src/blocks/$SLUG"

[[ ! -e "$DEST" ]] || { echo "Error: $DEST already exists." >&2; exit 1; }

render() { # render <template> <destination>
	sed \
		-e "s|{{slug}}|$SLUG|g" \
		-e "s|{{title}}|$TITLE|g" \
		-e "s|{{namespace}}|$NAMESPACE|g" \
		-e "s|{{textdomain}}|$TEXT_DOMAIN|g" \
		-e "s|{{icon}}|$ICON|g" \
		"$1" > "$2"
}

mkdir -p "$DEST"

# block.json: keep the "render" line only for dynamic blocks.
if [[ "$MODE" == "dynamic" ]]; then
	sed -e 's/^#DYNAMIC#//' "$TPL/block.json.tpl" > "$DEST/block.json.tmp"
else
	sed -e '/^#DYNAMIC#/d' "$TPL/block.json.tpl" > "$DEST/block.json.tmp"
fi
render "$DEST/block.json.tmp" "$DEST/block.json"
rm "$DEST/block.json.tmp"

render "$TPL/index.js.tpl"      "$DEST/index.js"
render "$TPL/edit.js.tpl"       "$DEST/edit.js"
render "$TPL/style.scss.tpl"    "$DEST/style.scss"
render "$TPL/editor.scss.tpl"   "$DEST/editor.scss"
render "$TPL/save.$MODE.js.tpl" "$DEST/save.js"
if [[ "$MODE" == "dynamic" ]]; then
	render "$TPL/render.php.tpl" "$DEST/render.php"
fi

echo "Created $MODE block '$NAMESPACE/$SLUG' in src/blocks/$SLUG:"
ls -1 "$DEST" | sed 's/^/  - /'
echo
echo "Next: edit block.json (attributes/supports), then run 'npm run build'."
[[ -f "$THEME_ROOT/package.json" ]] || echo "Warning: package.json not found - @wordpress/scripts is not set up yet."
[[ -f "$THEME_ROOT/inc/blocks.php" ]] || echo "Warning: inc/blocks.php not found - blocks are not registered yet."
