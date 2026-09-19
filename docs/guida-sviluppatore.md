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
