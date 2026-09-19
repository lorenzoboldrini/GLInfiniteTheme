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
