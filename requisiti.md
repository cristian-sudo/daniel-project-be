# Requisiti del Progetto GymFinder (Vue.js + PHP)

## Panoramica
GymFinder è un'applicazione web che permette agli utenti di cercare palestre nella propria città, visualizzare dettagli, lasciare recensioni e prenotarsi. I gestori delle palestre possono registrare e gestire le proprie strutture.

## Architettura
- **Frontend**: Vue.js 3 con Composition API
- **Backend**: PHP con API RESTful
- **Database**: MySQL

## Requisiti Funzionali

### Autenticazione e Gestione Utenti
- Registrazione con due tipi di utenti:
  - Cliente (può cercare palestre, prenotarsi e lasciare recensioni)
  - Gestore Palestra (può aggiungere e gestire palestre)
- Login sicuro con JWT (JSON Web Token)
- Gestione profilo utente

### Ricerca Palestre
- Ricerca per città
- Visualizzazione risultati con informazioni essenziali
- Filtri e ordinamento

### Dettaglio Palestra
- Informazioni complete (nome, indirizzo, orari, prezzo)
- Galleria immagini
- Recensioni e valutazioni
- Sistema di prenotazione

### Gestione Palestre (per gestori)
- Aggiunta/modifica palestre
- Caricamento immagini
- Gestione prenotazioni
- Dashboard con statistiche

### Recensioni e Prenotazioni
- Sistema di recensioni con valutazione a stelle
- Prenotazioni con stato (in attesa, confermata, rifiutata)
- Notifiche per nuove prenotazioni

## Requisiti Tecnici

### Frontend (Vue.js)
- **Componenti**:
  - Layout principale (header, footer, navigazione)
  - Pagina home
  - Ricerca palestre
  - Dettaglio palestra
  - Gestione prenotazioni
  - Gestione recensioni
  - Profilo utente
  - Dashboard gestore palestra
  - Modali per login/registrazione
  
- **Routing**:
  - Vue Router per la navigazione tra le pagine
  - Protezione delle rotte in base al tipo di utente
  
- **Gestione Stato**:
  - Vuex o Pinia per la gestione centralizzata dello stato
  - Moduli separati per autenticazione, palestre, prenotazioni, recensioni
  
- **UI/UX**:
  - Design responsive con framework CSS (Bootstrap o Tailwind)
  - Animazioni e transizioni fluide
  - Feedback visivo per le azioni dell'utente
  
- **Comunicazione API**:
  - Axios per le chiamate HTTP
  - Gestione token JWT per l'autenticazione
  - Intercettori per gestire errori e loading state

### Backend (PHP)
- **API RESTful**:
  - Endpoint per tutte le operazioni CRUD
  - Autenticazione con JWT
  - Validazione input
  - Gestione errori standardizzata
  
- **Modelli**:
  - User (con distinzione Cliente/Gestore)
  - Gym (palestra)
  - GymImage (immagini palestra)
  - Booking (prenotazione)
  - Review (recensione)
  
- **Sicurezza**:
  - Protezione contro SQL injection
  - Validazione e sanitizzazione input
  - CORS configurato correttamente
  - Rate limiting per prevenire abusi

### Database (MySQL)
- Tabelle normalizzate per utenti, palestre, immagini, prenotazioni, recensioni
- Relazioni e vincoli di integrità referenziale
- Indici per ottimizzare le query frequenti

### Gestione File
- Upload immagini con validazione tipo e dimensione
- Salvataggio in directory dedicata con riferimenti nel database
- Generazione thumbnail per ottimizzare il caricamento

## Requisiti Non Funzionali
- **Performance**: Tempo di risposta < 2 secondi per le operazioni principali
- **Sicurezza**: Protezione dati sensibili e autenticazione robusta
- **Usabilità**: Interfaccia intuitiva e responsive
- **Manutenibilità**: Codice modulare e ben documentato
- **Scalabilità**: Architettura che permetta future espansioni

## Deliverables
1. Codice sorgente completo (frontend Vue.js e backend PHP)
2. Schema del database
3. Documentazione API
4. Istruzioni per l'installazione e la configurazione
5. Guida utente base
