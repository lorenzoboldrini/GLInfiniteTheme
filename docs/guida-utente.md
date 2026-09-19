# Guida utente — Stili del tema

> Questa guida ha due parti: **Stili del tema** (qui sotto) e **Entità: creare nuovi tipi di contenuto** (dopo le domande frequenti).

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

# Guida utente — Entità: creare nuovi tipi di contenuto

Oltre a *Articoli* e *Pagine*, puoi creare **nuovi tipi di contenuto** con nome e voce di menu tutti tuoi: per esempio **Progetti**, **Eventi**, **Ricette**, **Membri del team**. In questo tema si chiamano **entità** (in WordPress il nome tecnico è *Custom Post Type*).

Per ogni entità che crei, il tema prepara **da solo**:

- una voce nel menu della bacheca, con l'icona che scegli;
- una **pagina elenco** pubblica (l'*archivio*), per esempio `iltuosito.it/progetti/`;
- una **pagina di dettaglio** per ogni contenuto (il *single*);
- una **card** pronta da usare nelle griglie e nell'editor.

Le **tassonomie** (categorie, etichette e simili) si creano a parte, in una schermata dedicata, e poi si **collegano a una o più entità**: vedi il punto 3.

Tutto segue lo **stile attivo** del sito (Soft, Minimal, Fumetto, Dark): se cambi stile, cambiano anche l'elenco e le pagine delle tue entità. Non devi scrivere codice.

> **Nota sui nomi.** Il tema non è ancora tradotto in italiano, quindi nella bacheca le voci compaiono in inglese. In questa guida trovi il nome inglese **in grassetto** (quello che vedrai sullo schermo) e la spiegazione in italiano.

---

## 1. Prima di iniziare: serve un permesso speciale

La pagina di gestione è protetta da un permesso dedicato, chiamato *manage_theme_entities*. **Non basta essere amministratori**: solo chi ha questo permesso vede la voce di menu.

- Se hai attivato tu il tema **per la prima volta**, il permesso ce l'hai già. Nei siti dove il tema era già attivo, lo riceve l'amministratore più "anziano" (il primo creato). Riattivare il tema in seguito **non** dà il permesso a nessun altro.
- Il permesso funziona **solo per gli amministratori**: se un utente viene retrocesso (per esempio a Editor) perde subito l'accesso, anche se in passato lo aveva.
- Se **non vedi** la voce **Entity Manager** nel menu a sinistra, non hai il permesso. Chiedilo a chi ce l'ha (vedi il punto 7).
- Chi non ha il permesso, anche scrivendo l'indirizzo della pagina a mano, vede solo un messaggio di accesso negato.

---

## 2. Creare una nuova entità, passo per passo

Come esempio creiamo l'entità **Progetto**.

1. Nella bacheca clicca **Entity Manager** nel menu a sinistra.
2. Se non hai ancora nessuna entità, vedi il messaggio *"No entities yet"*. Clicca **Add New Entity** (*Aggiungi nuova entità*).
3. Compila il modulo:

   | Campo | Cosa scrivere | Esempio |
   |---|---|---|
   | **Slug** | L'"identificativo" dell'entità: da 3 a 14 caratteri, solo **lettere minuscole, numeri e trattino basso**, deve **iniziare con una lettera** e non può finire con il trattino basso. Compare nell'indirizzo dell'elenco. | `progetto` |
   | **Singular name** | Il nome al singolare | `Progetto` |
   | **Plural name** | Il nome al plurale | `Progetti` |
   | **Menu icon** | L'icona del menu, scelta da una lista | *Portfolio* |
   | **Features** | Cosa può contenere ogni elemento (vedi sotto) | Title, Editor, Featured image, Excerpt |
   | **Archive** | Se vuoi una pagina pubblica che elenca tutti gli elementi | spuntato |

4. Clicca **Create Entity** (*Crea entità*).
5. Compare il messaggio **"Entity saved."** e la nuova entità appare nell'elenco. Dopo un istante trovi anche la nuova voce **Progetti** nel menu della bacheca.

Nel modulo, sotto ai campi, vedi la riga **Taxonomies**: è solo informativa e mostra le tassonomie già collegate all'entità (all'inizio *None yet*). Per collegarne una usa il link **Manage taxonomies**, come spiegato al punto 3.

> **Attenzione allo slug.** Una volta creata l'entità, lo **slug non si può più cambiare**. Serve a collegare i contenuti all'entità: cambiarlo li farebbe "perdere". Scegli con calma. Se lo slug è già usato (da un'altra entità, da una pagina con lo stesso indirizzo, da una parola riservata di WordPress come `post`, `page`, `category`…) vedrai un messaggio che ti chiede di sceglierne un altro.

### Le "Features": cosa può avere ogni elemento
Spunta solo ciò che ti serve (serve almeno una voce):

- **Title**: il titolo.
- **Editor (content)**: l'editor a blocchi per scrivere il contenuto.
- **Featured image**: l'immagine in evidenza (compare nelle card).
- **Excerpt**: un breve riassunto (compare nelle card).
- **Custom fields**: i campi personalizzati, per chi li usa.
- **Comments**: i commenti dei visitatori.

---

## 3. Le tassonomie: raggruppare gli elementi

Una **tassonomia** è un modo per raggruppare i contenuti, come le *Categorie* per gli articoli. Per esempio, per i Progetti puoi avere **Settore** (Web, Stampa, Video…).

Si creano nella schermata **Taxonomies**, separata da quella delle entità, e si **collegano** a una o più entità:

- collegata a **una sola entità** → tassonomia **specifica** (per esempio *Settore* solo per i Progetti);
- collegata a **più entità** → tassonomia **condivisa** (per esempio *Argomento* usata sia da Progetti sia da Eventi, con gli **stessi termini**);
- collegata a **nessuna** → **non collegata**: non compare sul sito, ma i termini restano salvati.

### Creare una tassonomia, passo per passo
Come esempio creiamo **Settore** per i Progetti.

1. Nella bacheca vai su **Entity Manager → Taxonomies** e clicca **Add New Taxonomy**.
2. Compila il modulo:

   | Campo | Cosa scrivere | Esempio |
   |---|---|---|
   | **Slug** | L'identificativo: da 3 a 26 caratteri, solo **lettere minuscole, numeri e trattino basso**, deve **iniziare con una lettera** e non può finire con il trattino basso. Compare nell'indirizzo dei termini (il trattino basso diventa trattino). | `settore` |
   | **Singular name** | Il nome al singolare | `Settore` |
   | **Plural name** | Il nome al plurale | `Settori` |
   | **Structure** | **Hierarchical (like categories)** è **spuntata di default**: ogni termine può avere un **padre**, scelto da un elenco (vedi sotto). Toglila solo se vuoi semplici etichette piatte, come i tag. | spuntata |
   | **Attach to** | Le entità a cui collegarla, una casella per entità | *Progetti* |

3. Clicca **Create Taxonomy**. Compare **"Taxonomy saved."**

> **Attenzione allo slug.** Come per le entità, **non si può più cambiare** dopo la creazione: serve a riconoscere i termini già assegnati. Non può coincidere con lo slug di un'entità né con una parola riservata di WordPress (`category`, `post_tag`…): in quel caso vedrai un messaggio che ti chiede di sceglierne un altro.

Se non hai ancora nessuna entità, la sezione **Attach to** ti avvisa e ti propone di crearne una.

### Aggiungere i termini e assegnarli
1. Nel menu della bacheca, sotto l'entità (per esempio **Progetti**), trovi la voce con il nome della tassonomia (**Settori**). Cliccala per creare i **termini** (Web, Stampa, Video…). Puoi scrivere anche una **descrizione**: comparirà nella pagina del termine.
2. Quando modifichi un contenuto (un Progetto), nella barra laterale trovi il pannello **Settori**: scegli i termini. Nell'elenco dei contenuti compare anche una colonna con i termini.

### Termini con un padre (gerarchia)
Se la tassonomia è **gerarchica** (la casella è spuntata di default), ogni termine può avere un **termine padre**, come le sottocategorie. Per esempio: **Web** → *Siti vetrina*, *E-commerce*.

1. Vai su **Progetti → Settori** (la voce con il nome della tassonomia).
2. Per creare un termine compila **Name** (e, se vuoi, **Slug**) e, nel campo **Parent Settore** (*Settore padre*), scegli dall'elenco il padre. Lascia **None** per un termine di primo livello.
3. Per cambiare il padre di un termine già creato, clicca **Edit** su quel termine e cambia lo stesso campo.

Cosa succede poi:

- Nell'elenco dei termini i figli compaiono sotto il padre, rientrati.
- Quando modifichi un contenuto (un Progetto), nel pannello **Settori** vedi i termini ad **albero**, con le caselle da spuntare. Puoi anche crearne uno nuovo da lì, scegliendo il padre.
- L'indirizzo dei figli include il padre: `iltuosito.it/settore/web/siti-vetrina/`.
- La pagina di un **padre** mostra anche gli elementi dei suoi **figli** (per esempio *Web* mostra anche quelli assegnati a *Siti vetrina*).
- Lo **slug del termine** (la parte dell'indirizzo, per esempio `siti-vetrina`) si crea in automatico dal nome e lo puoi modificare. Due termini con lo stesso nome ma padri diversi sono permessi: WordPress aggiunge da solo un suffisso allo slug per distinguerli.

Se togli la spunta **Hierarchical** da una tassonomia già esistente, il campo del padre sparisce e i termini vengono mostrati in elenco piatto. I padri già impostati non vengono cancellati: rispuntando la casella riappaiono.

### La pagina di ogni termine
Ogni termine ha una **pagina pubblica**, per esempio `iltuosito.it/settore/web/`, con titolo, descrizione e l'elenco degli elementi con quel termine, a **card in griglia**:

- se la tassonomia è **specifica**, usa la card dell'entità (per esempio quella dei Progetti);
- se è **condivisa**, mostra insieme gli elementi di tutte le entità collegate, con una card **generica** (immagine, titolo, riassunto e un link **Read more**). Ogni card resta cliccabile.

Puoi cambiare l'aspetto di queste pagine, come spiegato al punto 5.

### Modificare o eliminare una tassonomia
Nell'elenco **Taxonomies** vedi per ogni riga il **Type** (*Specific*, *Shared* o *Not attached*) e le entità collegate.

- **Edit**: puoi cambiare nomi, struttura e **entità collegate**. Togliere la spunta a un'entità **scollega** la tassonomia da quella entità: i termini restano salvati. **Non** puoi cambiare lo slug.
- **Delete**: compare una richiesta di conferma. Viene cancellata **solo la configurazione**: termini e assegnazioni restano nel database e riappaiono se ricrei una tassonomia con **lo stesso slug**.

---

## 4. Aggiungere i contenuti

1. Nel menu della bacheca clicca il nome della tua entità (per esempio **Progetti**).
2. Clicca **Aggiungi nuovo**, scrivi titolo e contenuto, scegli l'immagine in evidenza e i termini delle tassonomie collegate.
3. Clicca **Pubblica**.
4. Apri l'indirizzo dell'elenco (per esempio `iltuosito.it/progetti/`): vedrai gli elementi come **card in griglia**. Cliccando una card si apre la pagina di dettaglio.

> Se nelle *Impostazioni → Permalink* hai scelto la struttura "Semplice" (`?p=123`), l'elenco si apre come `iltuosito.it/?post_type=glinf_progetto`. Per indirizzi più leggibili scegli un'altra struttura, per esempio "Nome articolo".

---

## 5. Cambiare l'aspetto dell'elenco e del dettaglio

Il tema crea da solo due **modelli** per ogni entità. Li trovi in **Aspetto → Editor → Modelli** con questi nomi:

- **Archive: Progetti** (l'elenco);
- **Single: Progetto** (la pagina di dettaglio);
- **Taxonomy: Settori** (la pagina di ogni termine, uno per ogni tassonomia collegata).

Puoi modificarli come qualsiasi modello: cambiare l'ordine dei blocchi, aggiungerne altri, togliere l'immagine. Le tue modifiche vengono salvate sul sito.

La **card** è un *pattern* (un blocco pronto da inserire). Per usarla in una tua pagina: nell'editor clicca **+**, scheda **Pattern**, categoria **Entities**, scegli **Progetti card**. Nella stessa categoria trovi **Entity card (generic)**, la card usata dalle tassonomie condivise.

---

## 6. Modificare o eliminare un'entità

Vai in **Entity Manager**: per ogni riga trovi **Edit** e **Delete**. La colonna **Content** mostra quanti contenuti ci sono; cliccando il numero li vedi.

- **Edit** (*Modifica*): puoi cambiare nomi, icona, features e archivio. **Non** puoi cambiare lo slug. Clicca **Update Entity** per salvare. Le tassonomie si collegano dalla schermata **Taxonomies**.
- **Delete** (*Elimina*): compare una richiesta di conferma. Viene cancellata **solo la configurazione**: i **contenuti non vengono cancellati** e restano nel database. Se in futuro crei di nuovo un'entità con **lo stesso slug**, i vecchi contenuti riappaiono. Le **tassonomie** collegate restano, semplicemente **scollegate** da quell'entità.

Ricorda: tolta una feature (per esempio l'immagine in evidenza) non sparisce il dato già salvato, semplicemente non viene più mostrato.

---

## 7. Dare il permesso a un altro utente

Solo chi ha già il permesso può darlo ad altri, e solo ad altri **amministratori**.

1. Vai su **Utenti** e clicca **Modifica** sull'utente scelto.
2. Scorri fino alla sezione **Entity Manager**, riga **Permissions**.
3. Spunta **Can manage entities** e clicca **Aggiorna utente**.

Per togliere il permesso, togli la spunta. **Sul proprio profilo** la casella è bloccata: non puoi toglierti il permesso da solo (così non resti mai fuori dalla pagina).

> **Attenzione:** se elimini l'ultimo utente che ha il permesso, nessuno potrà più gestire le entità dalla bacheca (i siti già creati continuano a funzionare). Prima di eliminare un amministratore, assicurati che almeno un altro utente abbia il permesso.

---

## 8. Limiti da conoscere

- **Massimo 20 entità** e **20 tassonomie** in totale. Non c'è un limite di tassonomie per singola entità.
- **Lo slug non si cambia** dopo la creazione, né per le entità (da 3 a 14 caratteri) né per le tassonomie (da 3 a 26).
- **Categorie e tag di WordPress** restano degli Articoli: non si possono collegare alle entità (per ora).
- **Se cambi tema** le entità spariscono dalla bacheca e dal sito (i contenuti restano nel database). Tornando a questo tema riappaiono come prima. Se i contenuti devono sopravvivere a un cambio di tema, chiedi a chi gestisce il sito di valutare un plugin apposito.
- **Con WordPress 6.6** i modelli dedicati alle entità non vengono creati: l'elenco e il dettaglio usano i modelli generici del tema (funzionano, ma senza la griglia di card). La card resta disponibile tra i pattern. Con WordPress 6.7 o successivi tutto è automatico.

---

## 9. Domande frequenti sulle entità

**Non vedo la voce "Entity Manager".**
Non hai il permesso (punto 1). Chiedilo a chi ce l'ha.

**Ho creato l'entità ma l'indirizzo dell'elenco dà "pagina non trovata".**
Vai in *Impostazioni → Permalink* e clicca **Salva le modifiche** (senza cambiare nulla): WordPress aggiorna gli indirizzi. Controlla anche di aver spuntato **Archive** nell'entità.

**La card è vuota o non mostra l'immagine.**
Controlla che l'entità abbia **Featured image** ed **Excerpt** tra le Features e che il contenuto abbia davvero un'immagine e un testo.

**Posso rinominare un'entità o una tassonomia?**
Sì, i nomi (singolare e plurale) si cambiano quando vuoi. Solo lo slug è fisso.

**Ho scollegato una tassonomia da tutte le entità e i termini sono spariti dal sito.**
È normale: una tassonomia *Not attached* non viene usata. I termini non sono stati cancellati: ricollegando la tassonomia a un'entità (**Taxonomies → Edit → Attach to**) riappaiono.

**La pagina di un termine dà "pagina non trovata".**
Vai in *Impostazioni → Permalink* e clicca **Salva le modifiche**. Controlla anche che la tassonomia sia collegata ad almeno un'entità.

---

## Crediti

Il carattere per i titoli dello stile *Fumetto*, **Bangers**, è distribuito con licenza SIL Open Font License 1.1 (copia in `assets/fonts/bangers-OFL.txt`). È incluso nel tema: non viene scaricato da servizi esterni.
