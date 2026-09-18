# Design tokens

`theme.json` (v3) è la fonte unica dei token. Le Style Variations in `styles/*.json` ridefiniscono i **valori**, mai gli **slug**.

## Contratto per le Style Variations
- Ogni variation ridefinisce **tutti** gli slug di `settings.color.palette` (`base`, `surface`, `contrast`, `muted`, `accent`, `accent-contrast`).
- Template, parts e pattern usano solo preset (`var(--wp--preset--color--…)`), mai valori hardcoded: cambiando i valori cambia l'intero tema.
- Ogni variation deve rispettare i contrasti minimi qui sotto (verificati con `accessibility`).

## Palette del tema base — contrasti WCAG
| Testo | Sfondo | Rapporto | Requisito |
|---|---|---|---|
| `contrast` | `base` | 17.74:1 | ≥ 4.5 |
| `contrast` | `surface` | 16.26:1 | ≥ 4.5 |
| `muted` | `base` | 7.56:1 | ≥ 4.5 |
| `muted` | `surface` | 6.93:1 | ≥ 4.5 |
| `accent` | `base` | 6.70:1 | ≥ 4.5 (link) |
| `accent` | `surface` | 6.14:1 | ≥ 4.5 (link) |
| `accent-contrast` | `accent` | 6.70:1 | ≥ 4.5 (pulsanti) |
| `base` | `contrast` | 17.74:1 | ≥ 4.5 (hover pulsanti) |

Il focus ring usa `accent` (≥ 3:1 contro `base`/`surface`).

## Altri token
- **Spaziature**: `20`…`80` (0.5rem → 6rem; `60`–`80` fluide con `clamp()`).
- **Font size**: `small`, `medium`, `large`, `x-large`, `xx-large` (fluide).
- **Font family**: `system-sans`, `system-serif`, `system-mono` (stack di sistema; font self-hosted solo dove serve: `display` in Fumetto).
- **Ombre**: `sm`, `md`. **Radius** (custom): `--wp--custom--radius--{sm,md,lg,pill}`.
- **Layout**: contenuto `45rem`, wide `75rem`.

## Style Variations (`styles/*.json`)

| File | Titolo | Carattere |
|---|---|---|
| `default.json` | Soft | Neutra, radius morbidi, ombre leggere, accent indaco. Il titolo NON è "Default": il Site Editor mostra già una card "Default" per il theme.json base. |
| `minimal.json` | Minimal | Monocromatica, radius 0 (compreso `pill`), nessuna ombra. |
| `fumetto.json` | Fumetto | Bordi 3px `contrast`, ombre offset senza blur, titoli e pulsanti in Bangers (`display`, OFL, self-hosted in `assets/fonts/`), pulsanti che si "premono". |
| `dark.json` | Dark | Palette scura, stessa struttura di Soft. |

Ogni variation ridefinisce **per intero** le liste del base (i valori si sostituiscono, non si fondono): palette (6 slug), `fontSizes` (5), `spacingSizes` (`20`–`80`, espliciti, non `spacingScale`), `shadow.presets` (`sm`, `md`), `custom.radius` (`sm`, `md`, `lg`, `pill`), `fontFamilies` (3 di sistema; Fumetto aggiunge `display`). Nessun gradient (il base non ne usa).

**Attenzione a `styles.css`**: nel merge le stringhe vengono *sostituite*, non concatenate. Il base mette in `styles.css` il focus ring e `prefers-reduced-motion`; una variation che definisce `styles.css` (oggi solo Fumetto) deve **ripetere quelle due regole** verbatim, altrimenti le perde. Le altre non definiscono `styles.css` e le ereditano.

### Contrasti WCAG per variation (misurati)
| Coppia | Requisito | Soft | Minimal | Fumetto | Dark |
|---|---|---|---|---|---|
| `contrast` / `base` | ≥ 4.5 | 17.85 | 18.88 | 17.45 | 16.30 |
| `contrast` / `surface` | ≥ 4.5 | 16.30 | 17.32 | 14.20 | 13.35 |
| `muted` / `base` | ≥ 4.5 | 7.58 | 7.00 | 8.05 | 6.96 |
| `muted` / `surface` | ≥ 4.5 | 6.92 | 6.42 | 6.55 | 5.71 |
| `accent` / `base` (link, focus ring) | ≥ 4.5 | 7.90 | 15.13 | 7.09 | 8.96 |
| `accent` / `surface` (link, focus ring) | ≥ 4.5 | 7.21 | 13.88 | 5.77 | 7.34 |
| `accent-contrast` / `accent` (pulsante) | ≥ 4.5 | 7.90 | 15.13 | 7.51 | 8.96 |
| hover pulsante (`base` su `contrast`; Minimal: `contrast` su `base`) | ≥ 4.5 | 17.85 | 18.88 | 17.45 | 16.30 |
| bordo `contrast` / `base` | ≥ 3 | 17.85 | 18.88 | 17.45 | 16.30 |

Il caso più stretto è `accent`/`surface` in Fumetto (5.77) e `muted`/`surface` in Dark (5.71): entrambi sopra soglia. In Minimal `accent` è quasi uguale a `contrast`: i link si distinguono da sottolineatura e hover (`muted`), non dal colore.

### Token di design (`settings.custom`)
La **struttura** di header, footer, card dei post, paginazione, tabelle e campi di ricerca è definita **una sola volta** nel `theme.json` base (`styles.blocks.<blocco>.css`, che usa solo token). Ogni variation cambia **solo i valori** dei token: per questo ogni elemento segue automaticamente la variation attiva.

| Token (variabile CSS) | Cosa governa |
|---|---|
| `--wp--custom--radius--{sm,md,lg,pill}` | Angoli |
| `--wp--custom--line` | Linea sotto l'header e sopra il footer (shorthand `border`, o `none`) |
| `--wp--custom--card--{background,border,radius,padding,shadow}` | Card dei post nel loop, tabelle, paginazione, card del blocco `tu/call-to-action` |
| `--wp--custom--card--hover-shadow`, `--card--hover-transform` | Stato hover delle card |
| `--wp--custom--input--border` | Bordo di campi di ricerca e celle delle tabelle |
| `--wp--custom--header--background` | Sfondo dell'header |
| `--wp--custom--footer--{background,color}` | Sfondo e colore del testo del footer |

Il base definisce valori neutri (nessun bordo/ombra), quindi il tema senza variation resta piano.

### Contratto di copertura (verificato con script)
Le quattro variation devono avere **le stesse chiavi**: gli stessi 16 token in `settings.custom`, gli stessi 14 blocchi in `styles.blocks` e gli stessi 11 elementi in `styles.elements`. Una variation **non deve** ridefinire il `css` di un blocco già definito nel base (la stringa sostituirebbe la struttura). Aggiungendo un nuovo elemento grafico: struttura nel base con i token, valori nelle variation.

### Come si applica una variation (importante)
Scegliere una variation nel Site Editor **copia** i suoi valori negli stili utente nel database. Modificare in seguito `styles/*.json` non cambia un sito che l'ha già applicata: bisogna riselezionarla (o **Reimposta stili**). Il `css` per-blocco del base, invece, si aggiorna subito.

### Limiti noti
- **Fumetto**: con `font-display: swap` il fallback `Impact` può causare un piccolo CLS sul titolo; preload del woff2 solo con la variation attiva (logica PHP, fase successiva).
- **Tabelle**: l'intestazione mantiene il bordo inferiore del core (3px); il resto della griglia segue `--input--border`.
- **Minimal**: `accent` è quasi uguale a `contrast` (link distinti da sottolineatura e hover).
- **Anteprima verificata solo su desktop** (1280px) con screenshot di Chrome headless; il reflow a 320px va controllato nel browser.

## CSS del tema
`style.css` contiene solo l'header: gli stili sono generati da `theme.json` (compreso il focus ring e `prefers-reduced-motion`, in `styles.css`), così non c'è una richiesta CSS aggiuntiva.
