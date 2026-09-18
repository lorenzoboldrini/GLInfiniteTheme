# Guida utente — Stili del tema

Il tema include quattro **stili** (in WordPress si chiamano *Style Variations*): cambiano in un clic colori, caratteri, angoli arrotondati, ombre e aspetto dei pulsanti di tutto il sito, **senza toccare i contenuti**.

| Stile | Come appare |
|---|---|
| **Soft** | Chiaro e amichevole: sfondo bianco, card grigio-azzurro con angoli morbidi e ombra leggera, pulsanti a pillola, un solo colore d'accento (indaco). |
| **Minimal** | Editoriale: carattere con le grazie (serif), colonna di lettura più stretta, spazi ampi, riquadri a filo sottile con angoli netti, pulsanti "vuoti" con bordo, menu in maiuscolo piccolo. Nessuna ombra. |
| **Fumetto** | Pop-art: fondo crema, header giallo, titoli e menu in carattere comic, bordi neri spessi, ombre "piene" senza sfumatura, pulsanti che si "premono" quando li tocchi. |
| **Dark** | Scuro e tecnico: fondo blu notte, card con bordo sottile e leggero bagliore, pulsanti a pillola chiari. Anche i campi di ricerca sono scuri. |

Ogni stile cambia **tutto insieme**: header, footer, menu, elenco degli articoli, pulsanti, citazioni, immagini, tabelle, campi di ricerca e il blocco "Call to Action".

---

## 1. Come attivare uno stile

1. Nella bacheca di WordPress vai su **Aspetto → Editor**.
2. Nel menu a sinistra scegli **Stili** (l'icona a forma di mezzo cerchio).
3. Clicca **Sfoglia stili** (*Browse styles*): vedrai le anteprime di tutti gli stili.
4. Clicca lo stile che ti piace: l'anteprima si aggiorna subito.
5. Se sei soddisfatto, clicca **Salva** in alto a destra. Fino a quando non salvi, il sito pubblico non cambia.

> **Non ti piace il risultato?** Prima di salvare basta chiudere l'editor senza salvare. Dopo il salvataggio puoi tornare a un altro stile ripetendo gli stessi passi, oppure usare i tre puntini in alto a destra dentro *Stili* → **Reimposta stili** per tornare ai valori originali del tema.

> **Attenzione se aggiorni il tema o modifichi un file di stile.** Quando scegli uno stile e salvi, WordPress ne conserva una **copia** nel sito. Se in seguito il file cambia (per esempio dopo un aggiornamento del tema), il tuo sito non se ne accorge: per vedere la versione nuova scegli di nuovo lo stile in *Sfoglia stili* e salva.

### Ritocchi senza file
Dopo aver scelto uno stile puoi modificarlo a piacere dallo stesso pannello *Stili*: **Colori**, **Tipografia**, **Layout**, **Blocchi**. Le tue modifiche si salvano sul sito e non alterano i file del tema.

---

## 2. Come creare un tuo stile

Uno stile è un **file di testo** nella cartella `styles/` del tema. Crearne uno nuovo significa copiarne uno esistente e cambiare qualche valore. Non serve saper programmare, ma serve accesso ai file del sito (Gestione file del tuo hosting, FTP oppure il computer, se lavori in locale).

### Prima una raccomandazione importante
Se aggiorni il tema, i file dentro la sua cartella vengono **sostituiti**. Per non perdere il tuo stile, crea un **tema figlio** (*child theme*) e metti il file nella *sua* cartella `styles/`. Se non sai come fare, chiedi a chi gestisce il sito: è un'operazione di pochi minuti.

### Passo per passo
1. Apri la cartella `styles/` del tema (o del tema figlio).
2. **Duplica** il file dello stile più simile a quello che vuoi ottenere, per esempio `default.json` (è lo stile *Soft*), e rinominalo con un nome semplice, tutto minuscolo, senza spazi né accenti: `il-mio-stile.json`.
3. Apri il nuovo file con un editor di testo semplice (va bene anche Blocco Note; **non** usare Word).
4. In cima al file trova la riga `"title"` e cambia il nome che vedrai in WordPress:

   ```json
   "title": "Il mio stile",
   ```
5. Cambia i **colori**. Trova la lista `"palette"`: ogni riga ha un nome interno (`"slug"`) e un colore (`"color"`):

   ```json
   { "slug": "accent", "name": "Accent", "color": "#1d4ed8" },
   ```

   Cambia **solo il codice del colore** (`#1d4ed8`, un valore tra virgolette che inizia con `#`). Puoi ricavare i codici da un qualsiasi selettore di colori online.
6. Salva il file e ricarica **Aspetto → Editor → Stili → Sfoglia stili**: il tuo stile compare accanto agli altri.

### Cosa NON cambiare
- **I nomi interni (`"slug"`)**: `base`, `surface`, `contrast`, `muted`, `accent`, `accent-contrast`, `small`, `large`… Sono le "etichette" che il tema usa per trovare i colori e le misure. Cambia i valori, mai le etichette.
- **Non cancellare righe** delle liste (palette, dimensioni dei caratteri, spaziature, ombre): se ne manca una, quel colore o quella misura torna al valore predefinito e lo stile risulta incoerente.
- **Virgole e virgolette**: ogni riga di una lista, tranne l'ultima, finisce con una virgola. Se dopo una modifica lo stile non compare, quasi sempre manca una virgola o una virgoletta. Puoi controllare il file incollandolo in un validatore JSON online.

### Significato dei colori
| Nome interno | A cosa serve |
|---|---|
| `base` | Sfondo della pagina |
| `surface` | Sfondo di riquadri e sezioni |
| `contrast` | Testo principale e bordi |
| `muted` | Testo secondario |
| `accent` | Link e pulsanti |
| `accent-contrast` | Testo scritto **sopra** un pulsante `accent` |

### Controlla la leggibilità
Testo e sfondo devono avere abbastanza contrasto, altrimenti alcune persone non riescono a leggere. Prima di pubblicare, inserisci le coppie in un "contrast checker" online (per esempio cercando *WebAIM contrast checker*):

- `contrast` su `base` e su `surface`: almeno **4,5**
- `muted` su `base` e su `surface`: almeno **4,5**
- `accent` su `base` e su `surface` (sono i link): almeno **4,5**
- `accent-contrast` su `accent` (testo dei pulsanti): almeno **4,5**

### Altre cose facili da cambiare
- **Angoli arrotondati**: cerca `"radius"`. `0` = angoli netti; `0.5rem` = morbidi; `999px` = completamente tondi.
- **Ombre**: cerca `"shadow"`. Per togliere le ombre metti il valore `none`.
- **Carattere dei titoli**: cerca `"fontFamily"` dentro `"heading"`. Puoi scegliere tra i caratteri elencati in `"fontFamilies"`.

---

## 3. Domande frequenti

**Ho scelto uno stile ma il sito non è cambiato.**
Ricordati di cliccare **Salva**. Se usi un plugin di cache, svuota la cache.

**Lo stile che ho creato non compare.**
Controlla che il file sia nella cartella `styles/`, che finisca per `.json`, che abbia la riga `"title"` e che non ci siano virgole o virgolette mancanti.

**Posso avere più stili e cambiarli quando voglio?**
Sì. Puoi tenere tutti gli stili che vuoi e passare dall'uno all'altro senza perdere i contenuti.

**Cambiando stile ho perso le modifiche fatte a mano?**
Le modifiche fatte da *Stili* si applicano allo stile attivo. Scegliendone un altro le personalizzazioni precedenti possono essere sostituite: se ci tieni, salvale prima creando un tuo file come descritto sopra.

---

## Crediti

Il carattere per i titoli dello stile *Fumetto*, **Bangers**, è distribuito con licenza SIL Open Font License 1.1 (copia in `assets/fonts/bangers-OFL.txt`). È incluso nel tema: non viene scaricato da servizi esterni.
