---
name: theme-json-editor
description: Modifica sicura di theme.json (v3) e creazione di Style Variations in styles/ — palette, tipografia, spaziature, radius, ombre, layout, stili per blocco — con validazione. Usala per qualsiasi cambio ai design token.
---

# theme-json-editor

`theme.json` è la fonte unica dei design token. Prima di iniziare leggi `.claude/CLAUDE.md` (naming, a11y).

## Regole
1. `version: 3` e `$schema: https://schemas.wp.org/trunk/theme.json` sempre presenti; JSON valido (tab per l'indentazione, come nel resto del progetto — 2 spazi solo se il file già li usa).
2. **Slug semantici e stabili** (`surface`, `contrast`, `accent`, `base`), mai descrittivi del valore (`light-gray`): le Style Variations ridefiniscono i valori, non gli slug. **Non rinominare mai uno slug esistente** senza cercarne gli usi (`grep -r "preset|color|<slug>\|--preset--color--<slug>" templates parts patterns src styles`).
3. Ogni variation ridefinisce **lo stesso set di slug** del tema base; slug mancanti = fallback silenzioso, slug in più = inutilizzabili altrove.
4. **Contrasto WCAG AA** per ogni coppia testo/sfondo e per gli stati dei componenti, **per ciascuna variation**: calcola i rapporti (≥ 4.5:1 testo, ≥ 3:1 UI/testo grande) e riportali.
5. Tipografia con `clamp()` per `fluid` o `fontSizes` fluidi; font **self-hosted** dichiarati in `settings.typography.fontFamilies[].fontFace` con `src` in `file:./assets/fonts/…` (woff2), `fontDisplay: "swap"`.
6. Preferisci `settings` + `styles.elements`/`styles.blocks` a CSS custom in `style.css`. Disattiva ciò che non serve (`custom` off, palette/gradient di default off) per non gonfiare il CSS generato.
7. Nei template/pattern usa sempre i preset, mai valori hardcoded.

## Snippet riutilizzabili (`snippets/`)
`color-palette.json`, `typography.json`, `spacing.json` sono frammenti da **unire** in `settings` (non file completi). Adatta valori e slug al progetto.

## Creare una Style Variation
```bash
bash .claude/skills/theme-json-editor/scaffold-variation.sh <slug> "<Titolo>"
```
Crea `styles/<slug>.json` da `snippets/variation.json.tpl`. Poi: ridefinisci i valori della palette (stessi slug del base), verifica i contrasti, valida.

## Validare (sempre dopo ogni modifica)
```bash
bash .claude/skills/theme-json-editor/validate.sh
```
Controlla: JSON valido, `version: 3`, slug duplicati nelle palette, `title` presente nelle variation, e segnala colori hex hardcoded in `templates/`, `parts/`, `patterns/`. Richiede `jq`.

## Dopo la modifica
- Se cambiano i token: elenca template/pattern/blocchi impattati e passa la palette all'agente `accessibility` per il controllo dei contrasti.
- Verifica nell'editor Site Editor → Stili che la variation compaia e si applichi.
