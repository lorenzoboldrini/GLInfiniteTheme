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
- **Font family**: `system-sans`, `system-serif`, `system-mono` (stack di sistema; font self-hosted in una fase successiva).
- **Ombre**: `sm`, `md`. **Radius** (custom): `--wp--custom--radius--{sm,md,lg,pill}`.
- **Layout**: contenuto `45rem`, wide `75rem`.

## CSS del tema
`style.css` contiene solo l'header: gli stili sono generati da `theme.json` (compreso il focus ring e `prefers-reduced-motion`, in `styles.css`), così non c'è una richiesta CSS aggiuntiva.
