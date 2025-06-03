# Schema del Database per GymFinder (Vue.js + PHP)

## Panoramica
Questo documento descrive la struttura del database MySQL per l'applicazione GymFinder, che gestisce utenti, palestre, prenotazioni, recensioni e immagini.

## Tabelle

### 1. `users` - Gestione degli utenti
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    nome VARCHAR(50) NOT NULL,
    cognome VARCHAR(50) NOT NULL,
    tipo_utente ENUM('cliente', 'palestra') NOT NULL,
    data_registrazione DATETIME DEFAULT CURRENT_TIMESTAMP,
    ultimo_accesso DATETIME,
    token VARCHAR(255),
    token_scadenza DATETIME,
    attivo BOOLEAN DEFAULT TRUE
);
```

### 2. `gyms` - Gestione delle palestre
```sql
CREATE TABLE gyms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    indirizzo VARCHAR(255) NOT NULL,
    citta VARCHAR(100) NOT NULL,
    cap VARCHAR(10),
    telefono VARCHAR(20),
    email VARCHAR(100),
    descrizione TEXT,
    prezzo_mensile DECIMAL(10,2) NOT NULL,
    orario_apertura TIME NOT NULL,
    orario_chiusura TIME NOT NULL,
    giorni_apertura VARCHAR(20) NOT NULL, -- formato: "1,2,3,4,5" per Lun-Ven
    data_creazione DATETIME DEFAULT CURRENT_TIMESTAMP,
    ultima_modifica DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### 3. `gym_images` - Gestione delle immagini delle palestre
```sql
CREATE TABLE gym_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gym_id INT NOT NULL,
    percorso_immagine VARCHAR(255) NOT NULL,
    descrizione VARCHAR(255),
    principale BOOLEAN DEFAULT FALSE,
    data_caricamento DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (gym_id) REFERENCES gyms(id) ON DELETE CASCADE
);
```

### 4. `bookings` - Gestione delle prenotazioni
```sql
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    gym_id INT NOT NULL,
    data_inizio DATE NOT NULL,
    data_fine DATE,
    stato ENUM('in attesa', 'confermata', 'rifiutata') DEFAULT 'in attesa',
    note TEXT,
    data_richiesta DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_risposta DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (gym_id) REFERENCES gyms(id) ON DELETE CASCADE
);
```

### 5. `reviews` - Gestione delle recensioni
```sql
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    gym_id INT NOT NULL,
    valutazione TINYINT NOT NULL CHECK (valutazione BETWEEN 1 AND 5),
    commento TEXT NOT NULL,
    data_recensione DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (gym_id) REFERENCES gyms(id) ON DELETE CASCADE,
    UNIQUE KEY user_gym_unique (user_id, gym_id) -- Un utente può lasciare una sola recensione per palestra
);
```

## Indici aggiuntivi
```sql
-- Indice per la ricerca delle palestre per città
CREATE INDEX idx_gyms_citta ON gyms(citta);

-- Indice per le prenotazioni di un utente
CREATE INDEX idx_bookings_user ON bookings(user_id);

-- Indice per le prenotazioni di una palestra
CREATE INDEX idx_bookings_gym ON bookings(gym_id);

-- Indice per le recensioni di una palestra
CREATE INDEX idx_reviews_gym ON reviews(gym_id);
```

## Viste

### Vista per le palestre con valutazione media
```sql
CREATE VIEW gym_ratings AS
SELECT 
    g.id,
    g.nome,
    g.citta,
    g.prezzo_mensile,
    COALESCE(AVG(r.valutazione), 0) AS valutazione_media,
    COUNT(r.id) AS recensioni_count,
    (SELECT percorso_immagine FROM gym_images WHERE gym_id = g.id AND principale = TRUE LIMIT 1) AS immagine_principale
FROM 
    gyms g
LEFT JOIN 
    reviews r ON g.id = r.gym_id
GROUP BY 
    g.id;
```

## Trigger

### Trigger per impostare un'immagine come principale
```sql
DELIMITER //
CREATE TRIGGER set_main_image
AFTER INSERT ON gym_images
FOR EACH ROW
BEGIN
    DECLARE count_images INT;
    
    -- Conta quante immagini ha la palestra
    SELECT COUNT(*) INTO count_images FROM gym_images WHERE gym_id = NEW.gym_id;
    
    -- Se è la prima immagine, impostala come principale
    IF count_images = 1 THEN
        UPDATE gym_images SET principale = TRUE WHERE id = NEW.id;
    END IF;
END //
DELIMITER ;
```

### Trigger per garantire una sola immagine principale
```sql
DELIMITER //
CREATE TRIGGER ensure_one_main_image
BEFORE UPDATE ON gym_images
FOR EACH ROW
BEGIN
    IF NEW.principale = TRUE THEN
        -- Imposta tutte le altre immagini come non principali
        UPDATE gym_images SET principale = FALSE WHERE gym_id = NEW.gym_id AND id != NEW.id;
    END IF;
END //
DELIMITER ;
```

## Note di Implementazione
1. **Sicurezza**: Le password devono essere hashate prima di essere salvate nel database
2. **Integrità referenziale**: Tutte le relazioni sono protette da vincoli di chiave esterna
3. **Normalizzazione**: Lo schema è progettato per minimizzare la ridondanza dei dati
4. **Performance**: Indici strategici per ottimizzare le query più frequenti
5. **Scalabilità**: La struttura permette future estensioni (es. aggiunta di servizi offerti dalle palestre)
