# Guida sviluppatore

Note tecniche per chi lavora sul codice del tema. Per l'uso da parte di chi gestisce il sito vedi `guida-utente.md`; per i colori e i token vedi `design-tokens.md`.

## Entità: dove vivono i template

Riguarda `inc/entities/templates.php`. Le convenzioni e le decisioni di progetto sono in `.claude/CLAUDE.md`, sezione "Entità (CPT builder)".

### I template di un'entità non sono file

In `templates/` ci sono solo i template generici (`404`, `archive`, `index`, `page`, `single`). Per ogni entità non esistono file `archive-glinf_<slug>.html` o `single-glinf_<slug>.html`, né altrove su disco: il markup è **generato da PHP a ogni richiesta** e tenuto solo in memoria.

| Cosa | Dove sta |
|---|---|
| La "ricetta" (markup dei blocchi) | `inc/entities/templates.php`: `glinf_entity_single_template_markup()`, `glinf_entity_archive_template_markup()`, `glinf_taxonomy_template_markup()` |
| La registrazione | `glinf_register_entity_templates()`, agganciata a `init`: fa un ciclo sulle entità e chiama `register_block_template()` |
| Gli ingredienti (slug, nomi, archivio sì/no, tassonomie collegate) | option `glinf_entities` nella tabella `wp_options`, modificabile da Entity Manager |
| Una copia salvata | riga `wp_template` in `wp_posts`, **solo** se il template è stato modificato e salvato nel Site Editor |

Conseguenza: cambiando la configurazione di un'entità (per esempio una tassonomia collegata) il template si aggiorna alla richiesta successiva, senza rigenerare nulla. Eliminando l'entità il template sparisce.

### Copia salvata dal Site Editor

Quando si salva un template dal Site Editor, WordPress crea una riga `wp_template` con lo stesso slug (es. `archive-glinf_giocatore`) e da quel momento usa **quella**, non più la versione generata dal codice. Le modifiche successive al codice non la aggiornano: per riprendere la versione generata bisogna ripristinarla dal Site Editor ("Ripristina" o "Cancella personalizzazioni").

Per vedere quali template sono stati salvati (sola lettura):

```sql
SELECT ID, post_name, post_status FROM wp_posts WHERE post_type = 'wp_template';
```

### Perché due entità hanno archivi separati

Due meccanismi lavorano insieme:

1. **La query è già filtrata dal core.** Su `/giocatore/` WordPress imposta la query principale sul post type `glinf_giocatore`. Il blocco Query con `inherit: true` (come nel generico `archive.html`) mostra quindi giocatori su quell'URL e alunni su `/alunno/`, anche con un unico template.
2. **Ogni entità con archivio ha il proprio template.** La gerarchia per l'archivio di un CPT è:

   ```
   archive-{post_type}  →  archive  →  index
   ```

   e per il dettaglio:

   ```
   single-{post_type}  →  single  →  singular  →  index
   ```

   WordPress prende il primo che esiste. Quelli dedicati vengono registrati da `glinf_register_entity_templates()` con gli slug della gerarchia, quindi hanno la precedenza sui generici.

### A cosa servono `archive.html` e `single.html`

Sono il **ripiego** per tutto ciò che non ha un template più specifico. Non si possono togliere: il tema deve funzionare anche vuoto e con plugin che registrano contenuti propri.

- `single.html`: Articoli (`page.html` è per le Pagine), CPT senza `single-` dedicato (per esempio quelli di plugin di terze parti).
- `archive.html`: categorie e tag nativi, archivi per autore e per data, archivi di CPT senza `archive-` dedicato.
- `index.html` è obbligatorio per i temi a blocchi: è l'ultimo ripiego.

Le tassonomie registrate dalle entità hanno `taxonomy-glinf_<slug>` e non passano dal generico.

## Entità: la base dell'URL (`url_base`)

Riguarda `inc/entities/config.php` (dati e validazione), `register.php` (registrazione) e `admin.php` / `admin-taxonomies.php` (campo nel modulo). Le decisioni di progetto sono in `.claude/CLAUDE.md`, sezione "Entità (CPT builder)".

### Dove sta

Ogni entità e ogni tassonomia della option `glinf_entities` ha una chiave opzionale `url_base` (stringa). È il segmento unico dell'indirizzo: archivio `/<base>/`, singoli `/<base>/<post>/`, termini `/<base>/<termine>/`. Lo slug interno, il post type e la chiave della tassonomia (`glinf_<slug>`) e i template (`single-glinf_<slug>` ecc.) **non** dipendono da essa.

| Funzione | Cosa fa |
|---|---|
| `glinf_default_url_base( $slug )` | Base derivata dallo slug (`_` diventa `-`) |
| `glinf_entity_url_base( $entity )` / `glinf_taxonomy_url_base( $taxonomy )` | Base **effettiva**: `url_base` se non vuoto, altrimenti la derivata. Usate da `register.php` per `rewrite.slug` |
| `glinf_is_valid_url_base_format( $base )` | Formato: `[a-z0-9-]`, inizia e finisce con lettera o cifra, almeno una lettera, max `GLINF_URL_BASE_MAX` (40) |
| `glinf_normalize_url_base_input( $value, $slug )` | Stessa pipeline degli slug (`sanitize_text_field`, trim, minuscolo; niente riparazioni). Un valore uguale alla base derivata diventa `''` |
| `glinf_validate_url_base( $kind, $slug, $url_base, $items, $taxonomies )` | Restituisce `''` oppure un codice: `url_base_invalid`, `url_base_reserved`, `url_base_exists`, `url_base_conflict` |

### Niente migrazione, versione invariata

`GLINF_ENTITIES_VERSION` resta 2. La chiave è additiva e, se manca, vale `''` (base derivata dallo slug): è esattamente il comportamento di prima, quindi le configurazioni già salvate producono gli stessi indirizzi (`/giocatore/`, `/alunno/`) senza toccare nulla. La chiave compare nella option al primo salvataggio successivo. Il normalizzatore non scarta mai un'entità per un `url_base` sbagliato: lo riporta a `''` (formato non valido, parola riservata, già preso da un elemento precedente); i gestori non salvano mai un valore del genere, la difesa serve solo contro una option manomessa.

### Come si valida

Il controllo avviene nei gestori dopo `glinf_validate_*_fields()` e vale sia in creazione sia in modifica. Si valida la base **effettiva**, quindi anche il campo vuoto (base derivata dallo slug) passa dagli stessi controlli. Ordine: formato (solo se digitato) → parola riservata, confrontata dopo la normalizzazione trattini/underscore come per gli slug (`glinf_reserved_entity_slugs`) → base già usata da un'altra entità o tassonomia della configurazione, **incluse le tassonomie non collegate** (non registrate, quindi invisibili al controllo sulle rotte) → rotte registrate e pagine/articoli con lo stesso path (`glinf_url_base_collides_with_site()`, estratta da `glinf_validate_new_slug()`, che ora controlla solo formato, riservate, unicità e chiave).

Se la base effettiva **non cambia** rispetto a quella salvata, la validazione non fa nulla: un'entità esistente resta modificabile anche se nel frattempo una pagina ha preso il suo path o un plugin ha riservato la parola.

### Perché non c'è un redirect dai vecchi indirizzi

Decisione di progetto: il tema non salva la cronologia delle basi né registra redirect (servirebbero una lista per elemento, regole di rewrite aggiuntive e una politica di scadenza, e un redirect 301 sbagliato è difficile da annullare per i motori di ricerca). Il modulo avvisa in modo esplicito che i vecchi link smettono di funzionare. Chi ha bisogno di redirect può usarli lato server o con un plugin dedicato.

### Flush delle rewrite

Nessun flush aggiuntivo. `glinf_save_config()` normalizza e scrive l'option; se il valore cambia (e cambiare `url_base` lo cambia) imposta il flag `glinf_entities_flush`, e `wp_loaded` della richiesta successiva (il redirect dopo il salvataggio) lo consuma con `flush_rewrite_rules( false )`, quando `init` ha già registrato con la nuova base. Se non cambia nulla il flag non viene impostato.

## Entity Manager: le schermate di elenco (`WP_List_Table`)

Riguarda `inc/entities/admin.php`, `admin-taxonomies.php`, `admin-common.php`, `list-data.php`, `list-tables.php` e `assets/css/admin-entities.css`. Le decisioni di progetto sono in `.claude/CLAUDE.md`, sezione "Entità (CPT builder)".

### Chi fa cosa

| File | Responsabilità |
|---|---|
| `list-data.php` | Logica **pura** (nessuna superglobale, nessuna query, nessuna scrittura): costruzione delle righe dalla config, lettura whitelisted della query string (`glinf_list_parse_query()`), ricerca, filtri, viste con conteggi, ordinamento, paginazione, scelta degli elementi di un'azione di gruppo (`glinf_list_sanitize_selection()`) e configurazione risultante da un'eliminazione multipla (`glinf_list_bulk_delete_config()`). Testabile senza disegnare nulla. |
| `list-tables.php` | Le classi `GLINF_Entities_List_Table` e `GLINF_Taxonomies_List_Table`, che estendono `GLINF_Entity_Manager_List_Table` (che estende `WP_List_Table`). Solo disegno: colonne, azioni di riga, badge, viste, ricerca, paginazione. |
| `admin-common.php` | Cose condivise dalle due schermate: intestazione, schede, riquadro "stato vuoto", schermata di conferma, accodamento del CSS, Screen Options, caricamento lazy delle classi, instradamento dell'azione di gruppo. |
| `admin.php` / `admin-taxonomies.php` | Menu, messaggi, modulo add/edit, schermata di conferma specifica, handler `admin-post.php`. |

### Caricamento lazy di `WP_List_Table`

`WP_List_Table` esiste solo in wp-admin. `list-tables.php` **non** è nel bootstrap (`functions.php`): lo carica `glinf_entities_get_list_table( $kind )`, che prima fa `require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php'` e poi `require_once` del file delle classi, e tiene l'istanza in una variabile statica. Sul front-end quindi non viene mai letto. La funzione va chiamata presto (nell'hook `load-{schermata}`, prima dell'output): il costruttore di `WP_List_Table` registra il filtro `manage_{screen}_columns`, che Screen Options usa per offrire le colonne da nascondere.

Il costruttore ricava la schermata dalla globale `$hook_suffix` (non da `get_current_screen()`): in wp-admin coincidono, in uno script di test va impostata a mano.

### Hook della schermata

Le due schermate registrano `load-{hook}` con il valore **restituito** da `add_menu_page()` / `add_submenu_page()`: il nome dell'hook di una sotto-pagina contiene il titolo del menu tradotto (`entity-manager_page_glinf-taxonomies`), quindi non si può scrivere a mano. La callback (`glinf_entities_load_screen()`) fa, nell'ordine: capability, accodamento del CSS (`admin_enqueue_scripts`), e, solo per la lista (non per `action=new|edit|confirm-delete`), creazione della tabella, `add_screen_option( 'per_page' )`, intestazioni per gli screen reader e l'eventuale azione di gruppo. Il CSS è quindi caricato **solo** sulle due schermate; JS: nessuno (le azioni di riga e la conferma sono link e form normali).

### Stato dell'elenco: dalla query string alla riga

```
$_GET → glinf_list_parse_query()  (whitelist)  → state {view, filter, s, orderby, order, paged}
config → glinf_list_build_*_rows() → righe
righe  → glinf_list_count_views()     (conteggi delle viste, sull'insieme intero)
righe  → glinf_list_filter_rows()     (vista AND filtro AND ricerca)
righe  → glinf_list_sort_rows()       (spareggio sempre per plurale, poi slug)
righe  → glinf_list_paginate()        (pagina forzata nel range)
```

Tutto in memoria (max 20 + 20 elementi): nessuna query per filtrare o ordinare. Le uniche query sono i contatori per riga (`glinf_entities_count_items()`, `glinf_taxonomies_count_terms()`), fatti solo per le righe della pagina corrente. Regole del parser: `view`/`orderby`/`order` devono essere valori noti; `filter` deve essere lo slug di una cosa che esiste (una tassonomia nella lista entità, un'entità nella lista tassonomie); `s` è testo semplice (`sanitize_text_field`) di massimo 100 caratteri; `paged` un intero; array e valori sconosciuti diventano il default. Il confronto della ricerca è `mb_stripos` se c'è mbstring, `stripos` altrimenti; l'ordinamento per testo ignora maiuscole e accenti (`remove_accents()` + `strnatcasecmp()`).

`glinf_entities_list_url()` ricostruisce gli URL dallo stato **whitelisted** (mai da `REQUEST_URI`) e codifica a mano la ricerca con `rawurlencode()`: `add_query_arg()` non codifica i valori, e un `&` non codificato inietterebbe un parametro.

### Due form, non uno

Il core mette ricerca, filtri e tabella in un solo `<form method="get">`. Qui l'azione di gruppo deve essere **POST** con nonce, mentre ricerca e filtro devono essere **GET** (link condivisibili, preservati da ordinamento e paginazione). Soluzione in `GLINF_Entity_Manager_List_Table::render()`:

- `<form id="glinf-list-filter" method="get">` contiene i campi nascosti (`page`, `view`, `orderby`, `order`) e la casella di ricerca;
- la tabella sta in `<form id="glinf-list-bulk" method="post">` (action = URL della lista con lo stato, così un redirect torna alla stessa vista);
- il menu a tendina del filtro e il suo pulsante, che nel markup stanno **dentro** la tabella (`extra_tablenav()`), hanno `form="glinf-list-filter"`: il browser li invia con il form GET e mai con quello POST. Ricerca e filtro si combinano perché condividono lo stesso form.

Altre due differenze dal core, volute: la paginazione è disegnata da `pagination()` (link dallo stato whitelisted; la pagina corrente è testo, non c'è la casella "vai a pagina", che invierebbe un `paged` al form sbagliato) e `get_table_classes()` non include `fixed`. Le intestazioni ordinabili restano quelle del core (link da `REQUEST_URI`, sempre passati da `esc_url()`).

### Eliminazione: due passi, mai un click

```
lista ──(link riga)──────────────► GET  ?action=confirm-delete&entity=<slug> ─┐
lista ──(Bulk actions + Apply)───► POST alla stessa pagina + nonce bulk-…     ─┤ conferma (non cambia nulla)
                                                                              ▼
                                            POST admin-post.php  glinf_bulk_delete_{entities|taxonomies}
                                            cap → POST → nonce → sanitize → validate → save → redirect
```

- Il **link di riga** porta alla conferma con una GET: vedere la schermata non cambia niente. Uno slug sconosciuto dà il messaggio `not_found` e la lista.
- L'**azione di gruppo** è un POST alla pagina con il nonce `bulk-glinf-entities` / `bulk-glinf-taxonomies` (lo stampa il core in `display_tablenav()`). `glinf_entities_process_bulk_request()`, dal `load-{hook}`, verifica il nonce, legge il selettore (`action` sopra la tabella, `action2` sotto: vale quello con un'azione nota, oggi solo `delete`), sanifica gli slug e li ricorda in `glinf_entities_pending_selection()`; il render mostra la conferma. Nessuna azione o nessun elemento valido: redirect alla lista (con `nothing_selected` nel secondo caso). Un POST senza `action`/`action2` non è il form di gruppo (per esempio un Screen Options non reindirizzato dal core) e viene ignorato.
- La conferma invia gli slug (`glinf_slugs[]`) e un **secondo nonce** (`glinf_bulk_delete_entities` / `glinf_bulk_delete_taxonomies`) a `admin-post.php`. L'handler non si fida di nulla di ciò che ha visto l'utente: `glinf_list_bulk_delete_config()` prende al massimo `GLINF_*_MAX` elementi dell'array, scarta ciò che non è una stringa, normalizza ogni slug con `glinf_normalize_slug_input()`, tiene solo quelli presenti nella config, toglie i duplicati e restituisce la nuova config con il numero di rimossi. Per le entità stacca ognuna da tutte le tassonomie nello stesso risultato. Un'unica `glinf_save_config()` (atomica), poi redirect con codice whitelisted e `glinf_count` come **intero** (letto con `absint()` e limitato da `glinf_entities_read_count_arg()`): nessun testo dell'utente finisce nell'URL o nel messaggio.
- Gli handler singoli `glinf_delete_entity` / `glinf_delete_taxonomy` (form con `confirm()` inline) sono stati **rimossi**: una riga è una selezione di 1, e non resta un endpoint che cancella senza passare dalla conferma.

Screen Options: l'opzione utente è `glinf_entities_per_page` / `glinf_taxonomies_per_page` (default 20). Il filtro `set_screen_option_{option}` (`glinf_entities_filter_screen_option()`, registrato a livello di file perché il core lo esegue in `wp-admin/admin.php`, prima del `load-`) accetta solo numeri e li limita a 1-100; qualsiasi altro valore non viene salvato.

### CSS admin

`assets/css/admin-entities.css`, versione `GLINF_VERSION`, dipendenze `common`, `forms`, `list-tables`. Usa solo le variabili dello schema colore dell'admin (`--wp-admin-theme-color`, `--wp-admin-theme-color-darker-20`, `--wp-admin-border-width-focus`: le definisce `body.admin-color-<schema>`), i grigi neutri che il core usa in ogni schema e proprietà logiche (`margin-inline-start`, ecc.), quindi segue lo schema scelto e le lingue RTL senza un secondo file. Le tinte dei badge usano `color-mix()` con un ripiego grigio dove non è supportato. I badge hanno sempre del testo; gli stati "spento" hanno anche il bordo tratteggiato. Il testo dei badge è un colore fisso scuro su una tinta chiara, così il contrasto non dipende dallo schema.

Sotto i 783 px il core nasconde tutte le azioni in alto (`.tablenav.top .actions`) e lascia solo quelle sotto la tabella: il CSS riattiva il filtro (`.glinf-em-filter`), altrimenti su telefono non si potrebbe filtrare.

### Punto riservato per la dashboard (fase C)

`glinf_entities_render_dashboard( $section )` è chiamata da `glinf_entities_render_page_start()` subito sotto le schede, prima di avvisi, lista e moduli, su tutte le schermate. Oggi non stampa nulla.

## Versione minima di WordPress: 6.7

Il tema dichiara `Requires at least: 6.7` perché `register_block_template()`, con cui si registrano i template di entità e tassonomie, esiste solo da 6.7. `glinf_register_entity_templates()` la chiama direttamente, senza controllare che esista.

Non serve un guard: `switch_theme()` del core (`wp-includes/theme.php`) blocca l'attivazione con `wp_die()` se la versione di WordPress è inferiore a `Requires at least`.

Fino alla 6.6 il tema aveva un guard `function_exists()` e le entità ripiegavano su `single.html` e `archive.html` generici, senza griglia di card né `post-terms`. Il supporto alla 6.6 è stato tolto quando `Requires at least` è passato a 6.7, per evitare comportamenti diversi tra versioni. Le feature solo-7.0 restano comunque escluse (vedi "Compatibilità" in `CLAUDE.md`).

## Site Editor: le due voci con lo stesso nome

In Aspetto → Editor → Modelli il filtro per autore mostra due voci simili:

- **"GL Infinite Theme"**: i template dei file del tema (`404`, `archive`, `index`, `page`, `single`). Il nome viene da `Theme Name` in `style.css`.
- **"gl-infinite-theme" con l'icona della spina**: i template registrati con `register_block_template()`, cioè quelli generati per entità e tassonomie.

Il core marca ogni template registrato così con `source = 'plugin'` (`class-wp-block-templates-registry.php`), e l'etichetta è la parte prima di `//` del nome, cioè `GLINF_ENTITIES_TEMPLATE_NAMESPACE` (`'gl-infinite-theme'`). Il Site Editor non trova un plugin con quel nome e mostra lo slug grezzo. È un dato solo estetico: i template funzionano.

**Decisione: si lascia così.** Il filtro ha anche un vantaggio, perché isola i template generati dalle entità. Alternative valutate e scartate:

- **Cambiare il namespace**: le due voci resterebbero due, con un nome diverso, e il nuovo namespace farebbe perdere i template già salvati dal Site Editor.
- **Usare il filtro `get_block_templates`** al posto di `register_block_template()` per farli apparire come "del tema": non è un'API pensata per questo ed è fragile rispetto agli aggiornamenti del core.

L'etichetta è stata letta dal codice del core e dal comportamento osservato nel Site Editor; la logica JavaScript che sceglie il testo del filtro non è stata verificata.
