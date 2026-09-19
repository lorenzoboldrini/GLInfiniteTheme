---
name: new-cpt
description: Crea lo scaffolding di un nuovo Custom Post Type `glinf_<nome>` in inc/post-types/ (registrazione, REST, rewrite, flush al cambio tema) con template FSE single/archive opzionali. Usala quando l'utente chiede un nuovo CPT.
---

# new-cpt

Genera `inc/post-types/<slug>.php` dai template in `templates/`. Prima di iniziare leggi `.claude/CLAUDE.md` (sicurezza, naming).

## 0. Avvertenza da comunicare all'utente
I CPT registrati nel **tema** spariscono dall'admin al cambio tema (i contenuti restano nel DB ma non sono più raggiungibili). Per progetti dove i contenuti devono sopravvivere al tema, valuta un plugin companion. Se l'utente vuole procedere col tema, continua.

## 1. Raccogli i dati (chiedi se mancano)
- **slug** in minuscolo, `a-z0-9_` (es. `event`) → post type `glinf_event` (**max 20 caratteri in totale**, prefisso incluso)
- **singolare** e **plurale** in inglese (senza `'`, `"`, `\`, `&`, `|`), es. `Event` / `Events`
- **template FSE**: creare anche `templates/single-glinf_<slug>.html` e `templates/archive-glinf_<slug>.html`? (default: sì)

## 2. Esegui lo scaffolding
```bash
bash .claude/skills/new-cpt/scaffold.sh <slug> "<Singular>" "<Plural>" [--with-templates]
```
Non sovrascrive file esistenti.

## 3. Completa
1. In `inc/post-types/<slug>.php` regola `supports`, `menu_icon`, `rewrite` e capability. Lascia `show_in_rest => true` (necessario per l'editor a blocchi).
2. **Meta e tassonomie**: usa i blocchi commentati nel file. Ogni meta con `register_post_meta()` deve avere `type`, `sanitize_callback`, `auth_callback` (con `current_user_can()`) e `show_in_rest`.
3. Il file deve essere caricato: `functions.php` (o `inc/setup.php`) include `inc/post-types/*.php`. Se il loader non esiste ancora, **segnalalo** e propone di aggiungerlo tramite `backend-php`.
4. I testi traducibili dei template vanno in un pattern (`wp:pattern`), non nell'`.html`.

## 4. Verifica
- `php -l inc/post-types/<slug>.php`
- Con WP-CLI: `wp post-type list` mostra `glinf_<slug>`; poi `wp rewrite flush`.
- Il CPT compare nell'admin, si crea/salva un elemento, `single` e `archive` rispondono 200.
- Chiedi una revisione a `qa-reviewer`.
