---
name: new-block
description: Crea lo scaffolding di un nuovo blocco Gutenberg custom `tu/<nome>` in src/blocks/ (block.json, edit, save o render.php, stili). Usala quando l'utente chiede un nuovo blocco.
---

# new-block

Genera un blocco in `src/blocks/<slug>/` dai template in `templates/`. Prima di iniziare leggi `.claude/CLAUDE.md` (naming, sicurezza, a11y).

## 1. Raccogli i dati (chiedi se mancano)
- **slug** kebab-case (`hero`, `pricing-table`) → nome blocco `tu/<slug>`
- **titolo** leggibile (senza `"`, `\`, `&`, `|`)
- **tipo**: `dynamic` (default; `render.php`, dipende da dati/query) oppure `static` (solo markup salvato)
- **icona** dashicon (opzionale, default `smiley`)

## 2. Esegui lo scaffolding
```bash
bash .claude/skills/new-block/scaffold.sh <slug> "<Titolo>" [static|dynamic] [icona]
```
Lo script rifiuta di sovrascrivere un blocco esistente e non tocca altri file.

## 3. Completa il blocco
1. Definisci `attributes` e `supports` in `block.json`. **Preferisci `supports` di core** (colori, spaziature, tipografia, bordi) ai controlli custom.
2. Implementa `edit.js` (UI editor) e, se `dynamic`, `render.php` — con **escaping di ogni attributo** in output.
3. Stili in `style.scss` (front + editor) ed `editor.scss` (solo editor): usa i preset di `theme.json` (`var(--wp--preset--…)`), niente valori hardcoded.
4. Se serve JS front-end, aggiungi `view.js` e in `block.json` `"viewScriptModule": "file:./view.js"` (Interactivity API); altrimenti nessun JS front-end.

## 4. Verifica
- `package.json` con `@wordpress/scripts` deve esistere; se manca, **segnalalo all'utente** (non crearlo di tua iniziativa).
- `npm run build` → il blocco compare in `build/blocks/<slug>/`.
- La registrazione avviene in `inc/blocks/register.php` scorrendo `build/blocks/*/block.json` con `register_block_type()`. Se il file non esiste ancora, segnalalo.
- Inserisci il blocco nell'editor, salva, ricarica: nessun "block validation error".
- Chiedi una revisione ad `accessibility` (markup/controlli) se il blocco è interattivo.

## Note
- Modificare il markup di un blocco **static** già rilasciato richiede una `deprecated` in `index.js`.
- Namespace, text domain e prefisso sono variabili in testa a `scaffold.sh` (`NAMESPACE`, `TEXT_DOMAIN`).
