# gl-infinite-theme — contesto di progetto

Tema WordPress **universale**, a blocchi (Full Site Editing), per progetti personali e per la **rivendita online**.
Deve funzionare subito anche vuoto, essere personalizzabile via Style Variations e non dipendere da plugin di terze parti.

Lingua: documentazione e comunicazione in **italiano**; codice, commenti, docblock e stringhe sorgente in **inglese** (il tema è destinato a un mercato internazionale).

## Stack tecnico

- **PHP 8.1+** (`Requires PHP: 8.1` nell'header). Usa type hint e return type; `declare(strict_types=1)` nei file di `inc/`.
- **WordPress**: `Requires at least: 6.7` (minimo per `register_block_template()`, scelta deliberata e non l'ultima major); `Tested up to` = ultima stabile verificata.
- **Block Theme FSE**: `theme.json` v3, `templates/*.html`, `parts/*.html`, `patterns/*.php`, `styles/*.json`.
- **Build**: `@wordpress/scripts` (`wp-scripts`, Node ≥ 20) per i blocchi in `src/blocks/<slug>/` → output in `build/blocks/<slug>/` (non versionato: si builda in CI o dopo l'installazione con `npm ci && npm run build`).
  Comandi: `npm run start` (watch), `npm run build`, `npm run lint:js`, `npm run lint:css`, `npm run format`. `package-lock.json` si versiona.
- **CSS**: nativo + custom properties generate da `theme.json`. SCSS ammesso solo dentro i blocchi (compilato da `wp-scripts`).
  **Nessun framework CSS pesante in produzione** (Bootstrap, Tailwind runtime, ecc.).
- **JS front-end**: zero per default. Se serve interattività, Interactivity API (`viewScriptModule`). Nessuna dipendenza da jQuery.
- **Tooling di sviluppo** (mai in produzione): WPCS/PHPCS, ESLint e Stylelint via `wp-scripts lint-*`, Composer (solo `require-dev`), WP-CLI.

## Struttura

```
style.css  theme.json  functions.php
inc/         logica PHP: setup.php, blocks/register.php (registra i blocchi compilati), entities/ (CPT builder: config, capabilities, register, templates, admin, admin-taxonomies); previsti patterns, security
             functions.php fa solo bootstrap: costanti (GLINF_VERSION, GLINF_DIR) e require_once da inc/
templates/   template FSE (.html)
parts/       template part (header, footer, …)
patterns/    block pattern e preset di pagina (.php)
styles/      Style Variations (.json)
src/blocks/  sorgenti dei blocchi custom (una cartella per blocco)
build/       output di wp-scripts, build/blocks/<slug>/ (git-ignored)
assets/      font self-hosted, immagini
languages/   .pot / .po / .mo
docs/        documentazione di progetto
.claude/     contesto per Claude Code (agents, skills, settings)
```

## Convenzioni di naming

| Elemento | Convenzione | Esempio |
|---|---|---|
| Funzioni, hook, opzioni, transient, meta key | prefisso `glinf_` | `glinf_enqueue_assets()` |
| Costanti | prefisso `GLINF_` | `GLINF_VERSION` |
| Classi PHP | `GLINF_Nome` | `GLINF_Assets` |
| Blocchi | `glinf/nome-kebab` | `glinf/hero` → classe `wp-block-glinf-hero` |
| Pattern | slug `glinf/nome` | `glinf/page-landing` |
| Categorie pattern | `glinf-nome` | `glinf-page-presets` |
| CPT / tassonomie | `glinf_nome` (max 20 caratteri) | `glinf_event` |
| Classi CSS custom | `glinf-nome` (BEM leggero) | `glinf-card__title` |
| Slug preset theme.json | semantici, non descrittivi del valore | `surface`, non `light-gray` |
| Text domain | slug del tema: `gl-infinite-theme` | `__( 'Read more', 'gl-infinite-theme' )` |

> Il prefisso `glinf` (da *GL Infinite*, 5 lettere) è definito qui e come variabile all'inizio di ogni `scaffold.sh` nelle skill. Se cambia, aggiornare entrambi. Nei post type pesa sul limite di 20 caratteri: `glinf_` + slug entità ≤ 14. Rinominarlo dopo aver creato contenuti richiede una migrazione del DB (post type, option, meta, nome dei blocchi nei contenuti).

Regole di i18n: ogni stringa visibile è traducibile. I testi traducibili **non** vanno nei file `.html` di template/parts (non eseguono PHP): vanno in pattern PHP richiamati con `<!-- wp:pattern {"slug":"glinf/…"} /-->`.

## Sicurezza (obbligatorie, nessuna eccezione)

1. **Ogni file PHP** inizia con `defined( 'ABSPATH' ) || exit;`.
2. **Sanitizza in ingresso, escapa in uscita** (late escaping): `sanitize_text_field()`, `absint()`, `sanitize_key()`, `wp_kses_post()` in input; `esc_html()`, `esc_attr()`, `esc_url()`, `esc_js()`, `wp_kses()` in output. Mai `echo` di dati non escapati.
3. **Nonce** su ogni form/azione che modifica stato: `wp_nonce_field()` + `check_admin_referer()`; AJAX con `check_ajax_referer()`.
4. **Capability check** con `current_user_can()` prima di ogni operazione privilegiata. Mai basarsi su `is_admin()` come controllo di sicurezza.
5. **REST API**: `permission_callback` sempre esplicito (mai `__return_true` su endpoint che scrivono o espongono dati privati); `sanitize_callback`/`validate_callback` su ogni argomento.
6. **Database**: solo `$wpdb->prepare()` (o API WP_Query/WP_Meta/Options). Mai concatenare input in SQL.
7. Meta registrati con `register_post_meta()` con `sanitize_callback` e `auth_callback`.
8. Niente `eval()`, `unserialize()` su input utente, `extract()`, `$_REQUEST` grezzo; upload solo via `wp_handle_upload()`.
9. Nessun segreto nel repo (chiavi, token, `.env`, `wp-config.php`). Nessuna chiamata a servizi esterni non dichiarata (privacy/GDPR).
10. Dipendenze: `npm audit` e `composer audit` prima di ogni release.

## Performance

- Asset caricati **solo dove servono**: per i blocchi via `block.json` (`style`, `viewScriptModule`), per il resto `wp_enqueue_block_style()` o enqueue condizionali.
- Script con `strategy => 'defer'` o moduli; nessuno script bloccante nel `<head>`.
- Font **self-hosted** in `woff2`, `font-display: swap`, subset e preload solo dei pesi above-the-fold. Nessun CDN o font esterno.
- Immagini: sempre `width`/`height`, `loading="lazy"` sotto il fold, `fetchpriority="high"` sull'immagine LCP, formati moderni (WebP/AVIF) tramite API core.
- Query: mai query dentro un loop; `no_found_rows => true` se non serve la paginazione; transient/object cache per query costose.
- Budget indicativi: CSS globale ≤ 20 KB minificato; JS front-end di default 0 KB; LCP < 2.5 s, CLS < 0.1, INP < 200 ms.
- Non rimuovere feature core in modo aggressivo (compatibilità con plugin).

## Accessibilità — WCAG 2.1 AA

- Contrasto ≥ 4.5:1 (testo) e ≥ 3:1 (testo grande, componenti UI) **per ogni coppia colore di ogni Style Variation**.
- Focus sempre visibile, mai `outline: none` senza alternativa; ordine di tab logico; tutto operabile da tastiera.
- Skip link "Vai al contenuto"; landmark corretti (`header`, `nav`, `main`, `footer`; un solo `main`); gerarchia heading senza salti, un solo `h1`.
- `alt` corretto sulle immagini (vuoto se decorative); label associate a ogni campo; nessuna informazione affidata solo al colore.
- Reflow a 320 px senza scroll orizzontale; testo ridimensionabile al 200%; rispettare `prefers-reduced-motion`.
- ARIA solo quando l'HTML semantico non basta; niente `role` ridondanti.

## Standard di codice

- **PHP**: WordPress Coding Standards (WPCS) + PHPCompatibilityWP (8.1+). Verifica con `vendor/bin/phpcs`.
- **JS/CSS**: `@wordpress/eslint-plugin` e Stylelint via `npm run lint:js` / `npm run lint:css`. Non disattivare regole per far passare il lint: correggi il codice o dichiara la dipendenza mancante.
- **Indentazione**: tab per PHP/JS/CSS (come WPCS), 2 spazi per JSON/YAML/MD.
- Ogni funzione/classe/hook custom ha docblock. Commenti in inglese, spiegano il *perché*.
- `theme.json`: sempre valido rispetto a `https://schemas.wp.org/trunk/theme.json`; colori/spaziature nei template **sempre tramite preset**, mai valori hardcoded.

## Blocchi custom

- Sorgenti in `src/blocks/<slug>/`, nome `glinf/<slug>`, `apiVersion: 3`, `textdomain: gl-infinite-theme`. Si creano con la skill `new-block` e l'agent `blocks-gutenberg`.
- **Registrazione**: `inc/blocks/register.php` fa `register_block_type()` su ogni `build/blocks/*/block.json` (il block.json *compilato*). Senza build non registra nulla e non genera errori. Nessun enqueue manuale: WordPress carica `style` solo dove il blocco è presente e `editorScript`/`editorStyle` solo nell'editor.
- **Preferisci blocchi dinamici** (`render.php`, `save: () => null`). Gli attributi RichText di un blocco dinamico sono attributi semplici (`type: string`, senza `source`) e si stampano con `wp_kses_post()`; testi semplici con `esc_html()`, URL con `esc_url()`; livelli heading e allineamenti con whitelist.
- **Eredita da `theme.json`**: usa i `supports` di core (colori, spaziature, tipografia, `align`) invece di controlli custom; i bottoni usano la classe `wp-element-button` per prendere gli stili di `elements.button`. Negli stili del blocco solo variabili `--wp--preset--*` / `--wp--custom--*`, mai valori hardcoded; i default vanno in `:where()` (bassa specificità) così le scelte dell'utente vincono.
- **Attenzione ai `supports` inerti**: `spacing.blockGap` funziona solo con il `layout` support; senza, il controllo non fa nulla. Non dichiarare supports che il blocco non onora davvero.
- Ogni blocco è verificato dall'utente in editor (inserimento, controlli, salvataggio/ricarica senza errori di validazione) e in frontend prima del commit; **un blocco/CPT/preset alla volta**.
- Le dipendenze `@wordpress/*` sono `devDependencies` solo per la risoluzione ESLint: nel bundle restano external (le fornisce WordPress).

## Style Variations

Dettagli e contrasti misurati in `docs/design-tokens.md`; guida per l'utente in `docs/guida-utente.md`.

- **Token-first**: la *struttura* di header, footer, card dei post, paginazione, tabelle e ricerca sta **una sola volta** nel `theme.json` base (`styles.blocks.<blocco>.css`, solo token `--wp--custom--*`/preset). Le variation cambiano **solo i valori** dei token (`settings.custom`) e le proprietà native. Nuovo elemento grafico = struttura nel base + valori in *tutte* le variation.
- **Copertura uniforme**: le quattro variation hanno le stesse chiavi (`settings.custom`, `styles.blocks`, `styles.elements`) e ridefiniscono per intero le liste del base (palette, fontSizes, `spacingSizes` espliciti, ombre, `fontFamilies`): nel merge le liste si **sostituiscono**. Slug identici al base (Fumetto aggiunge solo `display`).
- **Le stringhe si sostituiscono**: una variation che definisce `styles.css` o il `css` di un blocco già presente nel base **cancella** quello del base. Oggi solo Fumetto ha `styles.css` e ripete verbatim focus ring e `prefers-reduced-motion`. Le altre non lo definiscono.
- **Le variation applicate sono una copia nel DB**: scegliere una variation nel Site Editor la salva negli stili utente; modificare `styles/*.json` non aggiorna i siti che l'hanno già applicata (serve riselezionarla). Il `css` per-blocco del base si aggiorna subito.
- **Compatibilità WP 6.7** (`Requires at least`): niente feature solo-7.0 (pseudo-selettori di blocco, `css` su elements). Il titolo di `default.json` è "Soft" (non "Default": esiste già la card "Default" del base).
- **Verifica**: `validate.sh`, contrasti (≥4.5 testo, ≥3 UI/bordi dei campi) e matrice di copertura per ogni variation; un colore nuovo va misurato su tutte le coppie che lo usano (card, header, footer, campi).

## Entità (CPT builder)

Codice in `inc/entities/`; guida utente in `docs/guida-utente.md`; note tecniche (dove vivono i template, versione minima, etichette nel Site Editor) in `docs/guida-sviluppatore.md`. Due schermate sotto il menu "Entity Manager": **Entities** e **Taxonomies**.

- **Storage**: un'unica option `glinf_entities` (autoload), **versione 2**: `{version, items:{<entity_slug>:{…}}, taxonomies:{<tax_slug>:{slug, singular, plural, hierarchical, entities:[<entity_slug>…], url_base?}}}` (`url_base` opzionale anche sulle entità, vedi sotto). L'associazione tassonomia↔entità vive **solo sul lato tassonomia** (`entities`): una entità = tassonomia *specifica*, più entità = *condivisa*, zero = *non collegata* (non registrata, termini conservati). Max 20 entità e 20 tassonomie, nessun limite di tassonomie per entità. Salvataggio atomico con `glinf_save_config( $items, $taxonomies )`; ogni lettura passa da `glinf_normalize_stored_config()` (che migra la v1: la chiave registrata `glinf_<entità>_<nome>` resta identica, quindi i termini non si perdono). Nessun CPT di sistema (costerebbe una query per request).
- **Post type** `glinf_<slug>` (slug entità `^[a-z][a-z0-9_]{1,12}[a-z0-9]$`, 3–14 caratteri, per il limite di 20 del post type). **Tassonomia** `glinf_<slug>` (slug 3–26 caratteri, chiave ≤ 32), rewrite piatto `/<base>/<termine>/`. Slug di entità e tassonomie **immutabili** dopo la creazione (identificano contenuti/termini nel DB), univoci tra entrambi i tipi, con blacklist filtrabile `glinf_reserved_entity_slugs` (vale per entrambi).
- **Base dell'URL (`url_base`)**: chiave opzionale (stringa) su ogni entità e tassonomia, **modificabile anche dopo la creazione**, indipendente dallo slug. Vuota/assente = derivata dallo slug (`_` → `-`): per questo non serve migrazione né cambio di `GLINF_ENTITIES_VERSION` (additiva, resta v2). Base effettiva: `glinf_entity_url_base()` / `glinf_taxonomy_url_base()` (usate da `register.php` per `rewrite.slug`). Formato: un segmento, `[a-z0-9-]`, inizia/finisce con lettera o cifra, almeno una lettera, max 40. Si valida la base *effettiva* (anche quella derivata) con `glinf_validate_url_base()`, in creazione e in modifica: non deve coincidere con un'altra entità/tassonomia (incluse quelle non collegate), con la blacklist `glinf_reserved_entity_slugs` (dopo la normalizzazione `-`↔`_`), con rotte registrate né con pagine/articoli esistenti; se la base effettiva non cambia non si ricontrolla nulla. **Nessun redirect** dai vecchi indirizzi: il modulo avvisa che i vecchi link smettono di funzionare. Il flush è quello già esistente (flag `glinf_entities_flush`).
- **Registrazione (`init`)**: prima i post type, poi le tassonomie (`register_taxonomy( key, [post types] )`); una tassonomia con 0 entità non si registra.
- **Eliminazioni**: entità → rimuove solo la config e, nello stesso salvataggio, ne stacca lo slug da ogni tassonomia (le tassonomie restano). Tassonomia → rimuove solo la config. In nessun caso si cancellano post o termini.
- **Capability `manage_theme_entities`**: solo user meta, **mai** sul ruolo `administrator`, ed *effettiva* solo insieme a `manage_options` (filtro `map_meta_cap`: un utente retrocesso perde l'accesso). Assegnata **solo alla prima attivazione** (all'utente che attiva il tema) o, per i siti già attivi, con migrazione una tantum all'admin con ID più basso; poi la concede solo chi la ha già, dal profilo utente. Guard: option `glinf_entities_caps_version`. Ogni handler (`glinf_save_entity`/`glinf_delete_entity`/`glinf_save_taxonomy`/`glinf_delete_taxonomy`): cap → POST → nonce → sanitize → validate → save → redirect.
- **Rewrite flush**: eccezione deliberata alla regola "solo `after_switch_theme`": salvataggio/eliminazione impostano il flag `glinf_entities_flush`, `wp_loaded` fa `flush_rewrite_rules( false )` solo se il flag è attivo. Allo switch del tema flusha già il core (`check_theme_switched()`): nessun flush extra.
- **Template e pattern** generati dalla config, senza scritture su disco né post `wp_template`: per ogni entità `single-glinf_<slug>` (con `post-terms` per le tassonomie collegate) e, se ha archivio, `archive-glinf_<slug>`; per ogni tassonomia registrata `taxonomy-glinf_<slug>`, che usa la card dell'entità se è specifica e la card generica `glinf/entity-card` se è condivisa. `register_block_template()` esiste solo da WP 6.7, che è quindi il `Requires at least` (il core blocca l'attivazione su versioni inferiori: nessun guard `function_exists`). `single.html`/`archive.html` restano il ripiego per Articoli, categorie/tag, autori, date e CPT di plugin. Le card ereditano le Style Variation dai token `core/post-template` del `theme.json`: nessun CSS dedicato. Nel Site Editor questi template sono marcati dal core come *plugin* (`source = 'plugin'`) e compaiono sotto il filtro "gl-infinite-theme" con l'icona della spina, accanto a "GL Infinite Theme" (template dei file): è solo estetica, **decisione: si lascia così** (cambiare `GLINF_ENTITIES_TEMPLATE_NAMESPACE` farebbe perdere i template già salvati). Un template salvato dal Site Editor diventa una riga `wp_template` che prevale sul generato dal codice. Dettagli in `docs/guida-sviluppatore.md`.
- **Cambio tema**: config, cap e contenuti restano nel DB, CPT e template spariscono finché il tema non è riattivato.
- **Non incluso (possibile passo futuro)**: collegare Categorie/Tag nativi di WordPress o tassonomie a Articoli e Pagine.

## Workflow

- **Branch**: `main` è l'unico branch di lavoro e **si lavora direttamente lì** (decisione dell'utente). Altri branch (es. per un lavoro sperimentale o un rilascio) si creano **solo su richiesta esplicita dell'utente**, e a fine lavoro vanno fusi in `main` ed eliminati. Claude non crea branch e non fa merge di sua iniziativa; il push lo fa l'utente. Per un merge richiesto usa `git merge --no-ff -m` (`git merge` non accetta `-F -`).
- **Manutenzione di questo file**: Claude tiene `CLAUDE.md` aggiornato quando cambiano convenzioni, struttura, comandi, decisioni o roadmap (autorizzazione permanente dell'utente). La modifica al file segue comunque la regola dei commit qui sotto.
- **Commit — regola ferrea**: Claude esegue i commit, **l'utente fa i `git push`** (Claude non pusha mai). **Prima di OGNI commit chiedi la verifica all'utente**: proponi (1) file modificati, (2) messaggio in **Conventional Commits** (`feat:`, `fix:`, `chore:`, `docs:`, `refactor:`, `perf:`, `test:`, `style:`), (3) mini-changelog, e **attendi l'OK esplicito**. Nessun trailer `Co-Authored-By` né riga "Generated with Claude Code". Usa `git commit -F -` con heredoc (titolo, riga vuota, corpo). Mai `--no-verify`.
- **Checkpoint**: il lavoro procede per fasi; a fine fase riepiloga cosa è stato fatto, proponi il passo successivo e **fermati per conferma**.
- Uso dei subagent (`.claude/agents/`): implementazione → `backend-php`, `blocks-gutenberg`, `ux-ui`; verifica → `performance`, `accessibility`, `qa-reviewer` (sola lettura).
- Uso delle skill (`.claude/skills/`): `new-block`, `new-cpt`, `new-page-preset`, `theme-json-editor` — preferirle allo scaffolding manuale.
- **Definition of Done** di ogni modifica: phpcs/lint puliti, build ok, checklist sicurezza/a11y/performance di questo file rispettata, nessun errore PHP/JS in console con `WP_DEBUG` attivo.

## Roadmap

- [x] **Fase 1** — struttura di contesto (cartelle, CLAUDE.md, agents, skills, sicurezza, git)
- [x] **Fase 2** — fondamenta: `style.css`, `theme.json` v3, template minimi, header/footer; tema attivabile (attivazione verificata dall'utente)
- [x] **Fase 3** — pipeline `wp-scripts` + primo blocco `glinf/call-to-action` (dinamico), registrazione in `inc/blocks/register.php`; verificato dall'utente in editor e frontend
- [ ] **Fase 4** — Style Variations (`styles/`: Soft, Minimal, Fumetto, Dark) con token di design in `theme.json`; copertura uniforme e contrasti AA verificati con script, anteprima desktop verificata con screenshot. **Da verificare dall'utente**: editor e frontend, hover/"pressione" dei pulsanti, reflow a 320 px, anteprime nel Site Editor
- [x] **Fase 5** — CPT builder ("Entity Manager") in `inc/entities/`: pagina admin top-level con cap dedicata `manage_theme_entities`, config in option `glinf_entities` (v2), CPT registrati su `init` e tassonomie come oggetti a sé collegabili a una o più entità (schermata Taxonomies), template archive/single/tassonomia (`register_block_template`, WP ≥ 6.7) e pattern card generati dalla config. Verificata dall'utente in WordPress (entità, tassonomie specifiche/condivise e gerarchiche, archivi dei termini con le variation, permessi). Vedi sezione "Entità (CPT builder)"
- [ ] **Fase 5.5** — blocchi trasversali riutilizzabili in ogni preset e Style Variation (nessun valore visivo hardcoded: solo `supports` e token), uno alla volta con verifica su almeno due variation. Slug in **inglese** (regola del codice): `cpt-grid` ✔ (committato), `card` (implementato, da verificare in editor), `slider`, `timeline`, `faq-accordion`, `stats-counter`. Scelte: gli item di `cpt-grid` usano i token `--wp--custom--card--*`; l'hover del titolo è solo sottolineato (nessun token `card--link-hover*`, rivalutabile); `cpt-grid` andrà ridotto al layout delegando la card a `glinf/card` solo su richiesta esplicita. Verifica finale incrociata su Fumetto e Minimal (e Soft, Dark).
- [ ] Fasi successive (da definire dopo conferma): tooling qualità (composer, `phpcs.xml.dist`, `npm run lint` unico), `search.html`, blocco dinamico `glinf/entity-list` per elencare le entità, preset di pagina (Fase 6), preload del font di Fumetto solo con la variation attiva

Da fare/rivalutare (Fase 5, emersi dalla review): (1) `phpcs.xml.dist` con `customSanitizingFunctions` per `glinf_normalize_slug_input` (altrimenti `InputNotSanitized` sulle letture di `$_POST`); (2) il rinomino `tu/call-to-action` → `glinf/call-to-action` invalida i blocchi già salvati (nessuna deprecation, tema non rilasciato); (3) la migrazione config v1→v2 tronca a 20 tassonomie (caso solo teorico, v1 non è mai stata rilasciata); (4) rifiniture: prefisso `': '` di `post-terms` non traducibile, `&mdash;` letto dagli screen reader nelle tabelle admin, conteggi `wp_count_posts`/`wp_count_terms` per riga (solo admin); (5) opzioni possibili per le tassonomie: Categorie/Tag nativi collegabili alle entità (la base dell'URL indipendente dallo slug è fatta: vedi `url_base`).

Da fare/rivalutare: `npm audit` mostra 12 vulnerabilità nelle sole dipendenze di sviluppo (0 in produzione): rivalutare prima di ogni release. Il blocco `call-to-action` non ha `example` in `block.json` (anteprima vuota nell'inserter).

Non generare altri blocchi, CPT o preset di pagina finché l'utente non conferma quello precedente.
