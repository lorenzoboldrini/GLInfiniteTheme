---
name: new-page-preset
description: Crea un preset di pagina (block pattern PHP in patterns/) che compare nel selettore "scegli un pattern" alla creazione di una nuova pagina e nell'inserter. Usala quando l'utente chiede un layout/preset di pagina (landing, about, contatti, …).
---

# new-page-preset

Un preset di pagina è un **block pattern** in `patterns/<slug>.php`, con header che lo rende disponibile alla creazione di una nuova pagina. Prima di iniziare leggi `.claude/CLAUDE.md` (i18n, a11y).

## 1. Raccogli i dati (chiedi se mancano)
- **slug** kebab-case (`landing`, `about`) → pattern `glinf/page-<slug>`
- **titolo** leggibile (senza `"`, `\`, `&`, `|`, `'`)
- **struttura desiderata** (sezioni: hero, feature, testimonianze, CTA, …)

## 2. Esegui lo scaffolding
```bash
bash .claude/skills/new-page-preset/scaffold.sh <slug> "<Titolo>"
```
Crea `patterns/page-<slug>.php` con una struttura di partenza (hero + sezione testo + CTA). Non sovrascrive file esistenti.

## 3. Costruisci il layout
1. Sostituisci la struttura di esempio con le sezioni richieste, usando **solo blocchi core** e i **preset di `theme.json`** (`"backgroundColor":"surface"`, `var:preset|spacing|60`). Verifica in `theme.json` che gli slug usati esistano.
2. **Un solo `h1`**; le sezioni sono `section`/`div`, **mai un secondo `main`** (il `main` è nel template della pagina). Heading in ordine gerarchico.
3. Ogni testo visibile passa da `esc_html__()` / `esc_html_x()` / `esc_attr__()` con text domain `gl-infinite-theme`. Il markup dei blocchi deve essere **serializzato esattamente** come lo produce l'editor (se in dubbio, costruiscilo nell'editor e copia il codice).
4. Immagini: placeholder con `alt` significativo o vuoto se decorative; nessuna immagine remota.
5. Il pattern deve reggere tutte le Style Variations (nessun colore hardcoded).

## 4. Verifica
- La categoria `glinf-page-presets` deve essere registrata in `inc/patterns.php` con `register_block_pattern_category()`; lo script avvisa se manca.
- `php -l patterns/page-<slug>.php`.
- Nell'editor: nuova pagina → il preset compare nel selettore; inseriscilo, salva, ricarica senza "block validation error".
- Chiedi una revisione ad `accessibility` (heading, contrasto, landmark).

## Header pattern (riferimento)
`Title`, `Slug`, `Description`, `Categories`, `Keywords`, `Viewport Width`, `Block Types: core/post-content` + `Post Types: page` (fanno comparire il pattern alla creazione di una pagina), `Inserter: true|false`.
