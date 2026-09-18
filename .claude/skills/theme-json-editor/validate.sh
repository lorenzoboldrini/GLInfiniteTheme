#!/usr/bin/env bash
# Validate theme.json and styles/*.json.
# Usage: validate.sh
set -uo pipefail

command -v jq >/dev/null 2>&1 || { echo "Error: jq is required." >&2; exit 2; }

HERE="$(cd "$(dirname "$0")" && pwd)"
THEME_ROOT="$(cd "$HERE/../../.." && pwd)"
status=0

fail() { echo "FAIL  $1"; status=1; }
ok()   { echo "ok    $1"; }

files=("$THEME_ROOT/theme.json")
for f in "$THEME_ROOT"/styles/*.json; do
	[[ -e "$f" ]] && files+=("$f")
done

for f in "${files[@]}"; do
	rel="${f#"$THEME_ROOT"/}"
	if [[ ! -f "$f" ]]; then fail "$rel: file not found"; continue; fi
	if ! jq empty "$f" 2>/dev/null; then fail "$rel: invalid JSON"; continue; fi
	ok "$rel: valid JSON"

	[[ "$(jq '.version' "$f")" == "3" ]] && ok "$rel: version 3" || fail "$rel: version must be 3"

	if [[ "$rel" == styles/* ]]; then
		[[ "$(jq -r '.title // empty' "$f")" != "" ]] && ok "$rel: title present" || fail "$rel: missing title"
	fi

	# Duplicate slugs inside each preset list.
	for path in '.settings.color.palette' '.settings.color.gradients' '.settings.typography.fontSizes' \
		'.settings.typography.fontFamilies' '.settings.spacing.spacingSizes' '.settings.shadow.presets'; do
		dups="$(jq -r "($path // []) | group_by(.slug) | map(select(length > 1) | .[0].slug) | .[]" "$f" 2>/dev/null)"
		[[ -z "$dups" ]] || fail "$rel: duplicate slug in $path: $(echo "$dups" | tr '\n' ' ')"
	done
done

# Hardcoded hex colours in markup (warning only: may include false positives such as anchors).
hits="$(grep -rnE '#[0-9a-fA-F]{6}\b|#[0-9a-fA-F]{3}\b' "$THEME_ROOT/templates" "$THEME_ROOT/parts" "$THEME_ROOT/patterns" 2>/dev/null | grep -vE 'href="#' || true)"
if [[ -n "$hits" ]]; then
	echo "WARN  hardcoded hex colours found (use theme.json presets):"
	echo "$hits" | sed 's/^/        /'
fi

exit "$status"
