# Documentazione GymFinder

## Panoramica del Progetto

GymFinder è un'applicazione web completa che permette agli utenti di cercare palestre nella propria città, visualizzare dettagli, prenotare iscrizioni e lasciare recensioni. L'applicazione è sviluppata con un'architettura moderna che utilizza Vue.js per il frontend e PHP per il backend.

## Architettura

Il progetto è strutturato secondo un'architettura client-server:

- **Frontend**: Single Page Application (SPA) sviluppata con Vue.js 3, Pinia per la gestione dello stato, Vue Router per la navigazione e Bootstrap 5 per l'interfaccia utente responsive.
- **Backend**: API RESTful sviluppate in PHP con autenticazione JWT, che gestiscono utenti, palestre, prenotazioni, recensioni e immagini.
- **Database**: MySQL per la persistenza dei dati, con tabelle relazionali ottimizzate.

## Funzionalità Principali

### Per i Clienti
- Registrazione e login
- Ricerca palestre per città
- Visualizzazione dettagli palestra (informazioni, orari, prezzi, foto)
- Prenotazione iscrizioni
- Gestione prenotazioni (visualizzazione stato, annullamento)
- Pubblicazione e modifica recensioni

### Per i Gestori Palestra
- Registrazione e login come gestore
- Aggiunta e gestione delle proprie palestre
- Caricamento immagini
- Gestione prenotazioni (conferma, rifiuto)
- Visualizzazione iscritti

## Struttura del Progetto

```
GymFinder_Vue_PHP/
├── backend/                # Backend PHP
│   ├── api/                # Endpoint API
│   ├── includes/           # File di configurazione e utility
│   ├── models/             # Modelli dati
│   └── uploads/            # Directory per le immagini caricate
│       └── gyms/           # Immagini delle palestre
│
├── frontend/               # Frontend Vue.js
│   ├── src/
│   │   ├── assets/         # Asset statici (immagini, CSS)
│   │   ├── components/     # Componenti Vue riutilizzabili
│   │   ├── router/         # Configurazione routing
│   │   ├── store/          # Store Pinia per la gestione stato
│   │   ├── views/          # Componenti pagina
│   │   ├── App.vue         # Componente root
│   │   └── main.js         # Entry point
│   └── package.json        # Dipendenze npm
│
├── database_schema.md      # Schema del database
└── requisiti.md            # Requisiti del progetto
```

## Tecnologie Utilizzate

### Frontend
- Vue.js 3 (Composition API)
- Pinia per la gestione dello stato
- Vue Router per la navigazione
- Axios per le chiamate API
- Bootstrap 5 per l'interfaccia responsive

### Backend
- PHP 7.4+
- JWT per l'autenticazione
- PDO per la connessione al database
- MySQL 5.7+

## Installazione e Configurazione

### Requisiti
- Server web con PHP 7.4+
- MySQL 5.7+
- Node.js 14+ (per lo sviluppo frontend)

### Configurazione Backend
1. Importare lo schema del database in MySQL
2. Configurare i parametri di connessione in `backend/includes/config.php`
3. Assicurarsi che la directory `backend/uploads/gyms` abbia i permessi di scrittura

### Configurazione Frontend
1. Installare le dipendenze: `cd frontend && npm install`
2. Configurare l'URL dell'API in `frontend/src/main.js`
3. Compilare per produzione: `npm run build`

## API Endpoints

### Autenticazione
- `POST /api/auth/login` - Login utente
- `POST /api/auth/register` - Registrazione utente

### Utenti
- `GET /api/users?id={id}` - Ottieni dettagli utente
- `PUT /api/users?id={id}` - Aggiorna profilo utente
- `PUT /api/users?id={id}&action=change-password` - Cambia password

### Palestre
- `GET /api/gyms?action=search&citta={citta}` - Cerca palestre per città
- `GET /api/gyms?id={id}` - Ottieni dettagli palestra
- `GET /api/gyms?action=my` - Ottieni palestre dell'utente corrente
- `POST /api/gyms` - Crea nuova palestra
- `PUT /api/gyms?id={id}` - Aggiorna palestra
- `DELETE /api/gyms?id={id}` - Elimina palestra

### Prenotazioni
- `GET /api/bookings?action=my` - Ottieni prenotazioni dell'utente corrente
- `GET /api/bookings?gym_id={gym_id}` - Ottieni prenotazioni di una palestra
- `POST /api/bookings` - Crea nuova prenotazione
- `PUT /api/bookings?id={id}&action=status` - Aggiorna stato prenotazione
- `DELETE /api/bookings?id={id}` - Elimina prenotazione

### Recensioni
- `GET /api/reviews?gym_id={gym_id}` - Ottieni recensioni di una palestra
- `POST /api/reviews` - Crea nuova recensione
- `PUT /api/reviews?id={id}` - Aggiorna recensione
- `DELETE /api/reviews?id={id}` - Elimina recensione

### Immagini
- `POST /api/images` - Carica nuova immagine
- `DELETE /api/images?id={id}` - Elimina immagine

## Sicurezza

- Autenticazione basata su JWT
- Password criptate con algoritmo bcrypt
- Protezione contro SQL injection tramite prepared statements
- Validazione input lato server
- Controllo delle autorizzazioni per ogni operazione

## Flussi Utente Principali

### Ricerca e Prenotazione
1. L'utente cerca palestre nella propria città
2. Visualizza i risultati e seleziona una palestra
3. Visualizza i dettagli della palestra
4. Effettua login (se non già autenticato)
5. Prenota un'iscrizione
6. Attende la conferma del gestore

### Gestione Palestra
1. Il gestore effettua login
2. Accede alla dashboard di gestione
3. Visualizza e gestisce le proprie palestre
4. Conferma o rifiuta le prenotazioni
5. Visualizza gli iscritti

## Considerazioni per lo Sviluppo Futuro

- Implementazione di un sistema di pagamenti
- Aggiunta di un sistema di notifiche push
- Integrazione con mappe per la geolocalizzazione
- Implementazione di un'app mobile nativa
- Sistema di fidelizzazione clienti
