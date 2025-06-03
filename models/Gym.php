<?php
/**
 * Modello Gym per la gestione delle palestre
 * 
 * Questa classe gestisce le operazioni CRUD per le palestre
 */

class Gym {
    // Connessione al database e nome tabella
    private $conn;
    private $table_name = "gyms";
    
    // Proprietà della palestra
    public $id;
    public $user_id;
    public $nome;
    public $indirizzo;
    public $citta;
    public $cap;
    public $telefono;
    public $email;
    public $descrizione;
    public $prezzo_mensile;
    public $orario_apertura;
    public $orario_chiusura;
    public $giorni_apertura;
    public $data_creazione;
    public $ultima_modifica;
    public $iscritti_count;
    
    /**
     * Costruttore
     * 
     * @param PDO $db Connessione al database
     */
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Crea una nuova palestra
     * 
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    public function create() {
        try {
            // Query per inserire una nuova palestra
            $query = "INSERT INTO " . $this->table_name . " 
                      (user_id, nome, indirizzo, citta, cap, telefono, email, descrizione, 
                       prezzo_mensile, orario_apertura, orario_chiusura, giorni_apertura,
                       data_creazione, ultima_modifica) 
                      VALUES (:user_id, :nome, :indirizzo, :citta, :cap, :telefono, :email, :descrizione, 
                              :prezzo_mensile, :orario_apertura, :orario_chiusura, :giorni_apertura,
                              CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
            
            // Prepara la query
            $stmt = $this->conn->prepare($query);
            
            // Sanitizza i dati
            $this->nome = htmlspecialchars(strip_tags($this->nome));
            $this->indirizzo = htmlspecialchars(strip_tags($this->indirizzo));
            $this->citta = htmlspecialchars(strip_tags($this->citta));
            $this->cap = $this->cap ? htmlspecialchars(strip_tags($this->cap)) : null;
            $this->telefono = $this->telefono ? htmlspecialchars(strip_tags($this->telefono)) : null;
            $this->email = $this->email ? htmlspecialchars(strip_tags($this->email)) : null;
            $this->descrizione = $this->descrizione ? htmlspecialchars(strip_tags($this->descrizione)) : null;
            $this->giorni_apertura = htmlspecialchars(strip_tags($this->giorni_apertura));
            
            // Formatta gli orari nel formato corretto per MySQL
            $this->orario_apertura = date('H:i:s', strtotime($this->orario_apertura));
            $this->orario_chiusura = date('H:i:s', strtotime($this->orario_chiusura));
            
            // Converti il prezzo in decimale
            $this->prezzo_mensile = floatval($this->prezzo_mensile);
            
            // Associa i valori
            $stmt->bindParam(":user_id", $this->user_id, PDO::PARAM_INT);
            $stmt->bindParam(":nome", $this->nome, PDO::PARAM_STR);
            $stmt->bindParam(":indirizzo", $this->indirizzo, PDO::PARAM_STR);
            $stmt->bindParam(":citta", $this->citta, PDO::PARAM_STR);
            $stmt->bindParam(":cap", $this->cap, PDO::PARAM_STR);
            $stmt->bindParam(":telefono", $this->telefono, PDO::PARAM_STR);
            $stmt->bindParam(":email", $this->email, PDO::PARAM_STR);
            $stmt->bindParam(":descrizione", $this->descrizione, PDO::PARAM_STR);
            $stmt->bindParam(":prezzo_mensile", $this->prezzo_mensile, PDO::PARAM_STR);
            $stmt->bindParam(":orario_apertura", $this->orario_apertura, PDO::PARAM_STR);
            $stmt->bindParam(":orario_chiusura", $this->orario_chiusura, PDO::PARAM_STR);
            $stmt->bindParam(":giorni_apertura", $this->giorni_apertura, PDO::PARAM_STR);
            
            // Esegui la query
            if ($stmt->execute()) {
                // Imposta l'ID della palestra appena creata
                $this->id = $this->conn->lastInsertId();
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Errore durante la creazione della palestra: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Legge una singola palestra
     * 
     * @return array|bool Dati della palestra o false se non trovata
     */
    public function readOne() {
        // Query per leggere una singola palestra
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 0,1";
        
        // Prepara la query
        $stmt = $this->conn->prepare($query);
        
        // Associa l'ID
        $stmt->bindParam(":id", $this->id);
        
        // Esegui la query
        $stmt->execute();
        
        // Ottieni il risultato
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            return $row;
        }
        
        return false;
    }
    
    /**
     * Legge tutte le palestre
     * 
     * @return array|bool Dati delle palestre o false se non trovate
     */
    public function readAll() {
        // Query per leggere tutte le palestre con valutazione media e conteggio iscritti
        $query = "SELECT g.*, 
                  COALESCE(g.iscritti_count, 0) as iscritti_count,
                  COALESCE(AVG(r.valutazione), 0) as valutazione_media,
                  COUNT(DISTINCT r.id) as recensioni_count,
                  (SELECT COUNT(*) FROM bookings b WHERE b.gym_id = g.id AND b.stato = 'confermata') as iscritti_effettivi
                  FROM " . $this->table_name . " g
                  LEFT JOIN reviews r ON g.id = r.gym_id
                  GROUP BY g.id
                  ORDER BY iscritti_effettivi DESC, valutazione_media DESC, g.data_creazione DESC";
        
        // Prepara la query
        $stmt = $this->conn->prepare($query);
        
        // Esegui la query
        $stmt->execute();
        
        // Ottieni i risultati
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($results) {
            // Aggiorna il conteggio degli iscritti se necessario
            foreach ($results as &$gym) {
                if ($gym['iscritti_count'] != $gym['iscritti_effettivi']) {
                    // Aggiorna il conteggio nel database
                    $update_query = "UPDATE " . $this->table_name . " 
                                   SET iscritti_count = :count 
                                   WHERE id = :id";
                    $update_stmt = $this->conn->prepare($update_query);
                    $update_stmt->bindParam(":count", $gym['iscritti_effettivi'], PDO::PARAM_INT);
                    $update_stmt->bindParam(":id", $gym['id'], PDO::PARAM_INT);
                    $update_stmt->execute();
                    
                    // Aggiorna il valore nel risultato
                    $gym['iscritti_count'] = $gym['iscritti_effettivi'];
                }
            }
            return $results;
        }
        
        return false;
    }
    
    /**
     * Legge le palestre di un utente
     * 
     * @return array|bool Dati delle palestre o false se non trovate
     */
    public function readByUser() {
        // Query per leggere le palestre di un utente
        $query = "SELECT g.*, 
                  COALESCE(g.iscritti_count, 0) as iscritti_count,
                  COALESCE(AVG(r.valutazione), 0) as valutazione_media,
                  COUNT(DISTINCT b.id) as iscritti_count_check,
                  (SELECT COUNT(*) FROM bookings WHERE gym_id = g.id AND stato = 'confermata') as iscritti_effettivi
                  FROM " . $this->table_name . " g
                  LEFT JOIN reviews r ON g.id = r.gym_id
                  LEFT JOIN bookings b ON g.id = b.gym_id AND b.stato = 'confermata'
                  WHERE g.user_id = :user_id 
                  GROUP BY g.id
                  ORDER BY g.data_creazione DESC";
        
        // Prepara la query
        $stmt = $this->conn->prepare($query);
        
        // Associa l'ID utente
        $stmt->bindParam(":user_id", $this->user_id);
        
        // Esegui la query
        $stmt->execute();
        
        // Ottieni i risultati
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($results) {
            // Aggiorna il conteggio degli iscritti se necessario
            foreach ($results as &$gym) {
                if ($gym['iscritti_count'] != $gym['iscritti_effettivi']) {
                    // Aggiorna il conteggio nel database
                    $update_query = "UPDATE " . $this->table_name . " 
                                   SET iscritti_count = :count 
                                   WHERE id = :id";
                    $update_stmt = $this->conn->prepare($update_query);
                    $update_stmt->bindParam(":count", $gym['iscritti_effettivi'], PDO::PARAM_INT);
                    $update_stmt->bindParam(":id", $gym['id'], PDO::PARAM_INT);
                    $update_stmt->execute();
                    
                    // Aggiorna il valore nel risultato
                    $gym['iscritti_count'] = $gym['iscritti_effettivi'];
                }
            }
            return $results;
        }
        
        return false;
    }
    
    /**
     * Cerca palestre per città
     * 
     * @param string $citta Città da cercare
     * @return array|bool Dati delle palestre o false se non trovate
     */
    public function searchByCity($citta) {
        // Query per cercare palestre per città
        $query = "SELECT g.*, 
                  COALESCE(AVG(r.valutazione), 0) AS valutazione_media,
                  COUNT(r.id) AS recensioni_count,
                  (SELECT percorso_immagine FROM gym_images WHERE gym_id = g.id AND principale = TRUE LIMIT 1) AS immagine_principale
                  FROM " . $this->table_name . " g
                  LEFT JOIN reviews r ON g.id = r.gym_id
                  WHERE g.citta LIKE :citta
                  GROUP BY g.id
                  ORDER BY g.data_creazione DESC";
        
        // Prepara la query
        $stmt = $this->conn->prepare($query);
        
        // Sanitizza la città
        $citta = htmlspecialchars(strip_tags($citta));
        $citta = "%{$citta}%";
        
        // Associa la città
        $stmt->bindParam(":citta", $citta);
        
        // Esegui la query
        $stmt->execute();
        
        // Ottieni i risultati
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($results) {
            return $results;
        }
        
        return false;
    }
    
    /**
     * Aggiorna una palestra esistente
     * 
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    public function update() {
        try {
            // Query per aggiornare la palestra
            $query = "UPDATE " . $this->table_name . " 
                     SET nome = :nome, 
                         indirizzo = :indirizzo, 
                         citta = :citta, 
                         cap = :cap, 
                         telefono = :telefono, 
                         email = :email, 
                         descrizione = :descrizione, 
                         prezzo_mensile = :prezzo_mensile, 
                         orario_apertura = :orario_apertura, 
                         orario_chiusura = :orario_chiusura, 
                         giorni_apertura = :giorni_apertura,
                         ultima_modifica = CURRENT_TIMESTAMP 
                     WHERE id = :id";
            
            // Prepara la query
            $stmt = $this->conn->prepare($query);
            
            // Sanitizza i dati
            $this->nome = htmlspecialchars(strip_tags($this->nome));
            $this->indirizzo = htmlspecialchars(strip_tags($this->indirizzo));
            $this->citta = htmlspecialchars(strip_tags($this->citta));
            $this->cap = $this->cap ? htmlspecialchars(strip_tags($this->cap)) : null;
            $this->telefono = $this->telefono ? htmlspecialchars(strip_tags($this->telefono)) : null;
            $this->email = $this->email ? htmlspecialchars(strip_tags($this->email)) : null;
            $this->descrizione = $this->descrizione ? htmlspecialchars(strip_tags($this->descrizione)) : null;
            $this->giorni_apertura = htmlspecialchars(strip_tags($this->giorni_apertura));
            
            // Formatta gli orari nel formato corretto per MySQL
            $this->orario_apertura = date('H:i:s', strtotime($this->orario_apertura));
            $this->orario_chiusura = date('H:i:s', strtotime($this->orario_chiusura));
            
            // Converti il prezzo in decimale
            $this->prezzo_mensile = floatval($this->prezzo_mensile);
            
            // Associa i valori
            $stmt->bindParam(":id", $this->id, PDO::PARAM_INT);
            $stmt->bindParam(":nome", $this->nome, PDO::PARAM_STR);
            $stmt->bindParam(":indirizzo", $this->indirizzo, PDO::PARAM_STR);
            $stmt->bindParam(":citta", $this->citta, PDO::PARAM_STR);
            $stmt->bindParam(":cap", $this->cap, PDO::PARAM_STR);
            $stmt->bindParam(":telefono", $this->telefono, PDO::PARAM_STR);
            $stmt->bindParam(":email", $this->email, PDO::PARAM_STR);
            $stmt->bindParam(":descrizione", $this->descrizione, PDO::PARAM_STR);
            $stmt->bindParam(":prezzo_mensile", $this->prezzo_mensile, PDO::PARAM_STR);
            $stmt->bindParam(":orario_apertura", $this->orario_apertura, PDO::PARAM_STR);
            $stmt->bindParam(":orario_chiusura", $this->orario_chiusura, PDO::PARAM_STR);
            $stmt->bindParam(":giorni_apertura", $this->giorni_apertura, PDO::PARAM_STR);
            
            // Esegui la query
            return $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Errore durante l'aggiornamento della palestra: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Elimina una palestra
     * 
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    public function delete() {
        // Query per eliminare una palestra
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        
        // Prepara la query
        $stmt = $this->conn->prepare($query);
        
        // Associa l'ID
        $stmt->bindParam(":id", $this->id);
        
        // Esegui la query
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Ottiene la valutazione media e il numero di recensioni di una palestra
     * 
     * @return array Valutazione media e numero di recensioni
     */
    public function getRatings() {
        // Query per ottenere la valutazione media e il numero di recensioni
        $query = "SELECT COALESCE(AVG(valutazione), 0) AS valutazione_media, COUNT(id) AS recensioni_count 
                  FROM reviews WHERE gym_id = :id";
        
        // Prepara la query
        $stmt = $this->conn->prepare($query);
        
        // Associa l'ID
        $stmt->bindParam(":id", $this->id);
        
        // Esegui la query
        $stmt->execute();
        
        // Ottieni il risultato
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'valutazione_media' => number_format($row['valutazione_media'], 1),
            'recensioni_count' => $row['recensioni_count']
        ];
    }

    /**
     * Aggiorna il conteggio degli iscritti
     * 
     * @param bool $increment True per incrementare, False per decrementare
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    public function updateSubscriberCount($increment = true) {
        try {
            $query = "UPDATE " . $this->table_name . " 
                     SET iscritti_count = iscritti_count " . ($increment ? "+ 1" : "- 1") . "
                     WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $this->id);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Errore durante l'aggiornamento del conteggio iscritti: " . $e->getMessage());
            return false;
        }
    }
}
