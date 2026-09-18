#!/usr/bin/env bash
# Scaffold a custom post type in inc/post-types/<slug>.php.
# Usage: scaffold.sh <slug> "<Singular>" "<Plural>" [--with-templates]
set -euo pipefail

PREFIX="tu"
TEXT_DOMAIN="gl-infinite-theme"

SLUG="${1:-}"
SINGULAR="${2:-}"
PLURAL="${3:-}"
WITH_TEMPLATES="${4:-}"

usage() {
	echo "Usage: scaffold.sh <slug> \"<Singular>\" \"<Plural>\" [--with-templates]" >&2
	exit 1
}

[[ -n "$SLUG" && -n "$SINGULAR" && -n "$PLURAL" ]] || usage
[[ -z "$WITH_TEMPLATES" || "$WITH_TEMPLATES" == "--with-templates" ]] || usage
[[ "$SLUG" =~ ^[a-z][a-z0-9_]*$ ]] || { echo "Error: slug must match [a-z][a-z0-9_]*." >&2; exit 1; }

POST_TYPE="${PREFIX}_${SLUG}"
(( ${#POST_TYPE} <= 20 )) || { echo "Error: post type '$POST_TYPE' exceeds 20 characters." >&2; exit 1; }

for value in "$SINGULAR" "$PLURAL"; do
	case "$value" in
		*"'"* | *'"'* | *'\'* | *'&'* | *'|'*) echo "Error: labels must not contain ' \" \\ & |" >&2; exit 1 ;;
	esac
done

HERE="$(cd "$(dirname "$0")" && pwd)"
THEME_ROOT="$(cd "$HERE/../../.." && pwd)"
TPL="$HERE/templates"
DEST_DIR="$THEME_ROOT/inc/post-types"
DEST="$DEST_DIR/$SLUG.php"
REWRITE_SLUG="${SLUG//_/-}"

[[ ! -e "$DEST" ]] || { echo "Error: $DEST already exists." >&2; exit 1; }

render() { # render <template> <destination>
	sed \
		-e "s|{{prefix}}|$PREFIX|g" \
		-e "s|{{slug}}|$SLUG|g" \
		-e "s|{{post_type}}|$POST_TYPE|g" \
		-e "s|{{rewrite_slug}}|$REWRITE_SLUG|g" \
		-e "s|{{singular}}|$SINGULAR|g" \
		-e "s|{{plural}}|$PLURAL|g" \
		-e "s|{{textdomain}}|$TEXT_DOMAIN|g" \
		"$1" > "$2"
}

mkdir -p "$DEST_DIR"
render "$TPL/cpt.php.tpl" "$DEST"
created=("inc/post-types/$SLUG.php")

if [[ "$WITH_TEMPLATES" == "--with-templates" ]]; then
	for kind in single archive; do
		target="$THEME_ROOT/templates/$kind-$POST_TYPE.html"
		if [[ -e "$target" ]]; then
			echo "Skipped: templates/$kind-$POST_TYPE.html already exists." >&2
		else
			render "$TPL/$kind.html.tpl" "$target"
			created+=("templates/$kind-$POST_TYPE.html")
		fi
	done
fi

echo "Created CPT '$POST_TYPE':"
printf '  - %s\n' "${created[@]}"
echo
echo "Next: review supports/rewrite, then 'php -l' the file and 'wp rewrite flush'."
grep -qs "post-types" "$THEME_ROOT/functions.php" "$THEME_ROOT"/inc/*.php 2>/dev/null \
	|| echo "Warning: no loader for inc/post-types/*.php found in functions.php or inc/."
