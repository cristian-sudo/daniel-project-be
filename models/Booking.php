<?php
/**
 * Modello Booking per la gestione delle prenotazioni
 * 
 * Questa classe gestisce le operazioni CRUD per le prenotazioni
 */

class Booking {
    // Connessione al database e nome tabella
    private $conn;
    private $table_name = "bookings";
    
    // Proprietà della prenotazione
    public $id;
    public $user_id;
    public $gym_id;
    public $data_inizio;
    public $data_fine;
    public $stato;
    public $note;
    public $data_richiesta;
    public $data_risposta;
    
    /**
     * Costruttore
     * 
     * @param PDO $db Connessione al database
     */
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Crea una nuova prenotazione
     * 
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    public function create() {
        // Query per inserire una nuova prenotazione
        $query = "INSERT INTO " . $this->table_name . " 
                  (user_id, gym_id, data_inizio, data_fine, stato, note) 
                  VALUES (:user_id, :gym_id, :data_inizio, :data_fine, :stato, :note)";
        
        // Prepara la query
        $stmt = $this->conn->prepare($query);
        
        // Sanitizza i dati
        $this->note = $this->note ? htmlspecialchars(strip_tags($this->note)) : null;
        
        // Associa i valori
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":gym_id", $this->gym_id);
        $stmt->bindParam(":data_inizio", $this->data_inizio);
        $stmt->bindParam(":data_fine", $this->data_fine);
        $stmt->bindParam(":stato", $this->stato);
        $stmt->bindParam(":note", $this->note);
        
        // Esegui la query
        if ($stmt->execute()) {
            // Imposta l'ID della prenotazione appena creata
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    /**
     * Legge una singola prenotazione
     * 
     * @return array|bool Dati della prenotazione o false se non trovata
     */
    public function readOne() {
        // Query per leggere una singola prenotazione
        $query = "SELECT b.*, g.nome as gym_nome, u.nome as user_nome, u.cognome as user_cognome 
                  FROM " . $this->table_name . " b
                  JOIN gyms g ON b.gym_id = g.id
                  JOIN users u ON b.user_id = u.id
                  WHERE b.id = :id LIMIT 0,1";
        
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
     * Legge tutte le prenotazioni di un utente
     * 
     * @return array|bool Dati delle prenotazioni o false se non trovate
     */
    public function readByUser() {
        // Query per leggere tutte le prenotazioni di un utente
        $query = "SELECT b.*, g.nome as gym_nome 
                  FROM " . $this->table_name . " b
                  JOIN gyms g ON b.gym_id = g.id
                  WHERE b.user_id = :user_id 
                  ORDER BY b.data_richiesta DESC";
        
        // Prepara la query
        $stmt = $this->conn->prepare($query);
        
        // Associa l'ID utente
        $stmt->bindParam(":user_id", $this->user_id);
        
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
     * Legge tutte le prenotazioni di una palestra
     * 
     * @return array|bool Dati delle prenotazioni o false se non trovate
     */
    public function readByGym() {
        // Query per leggere tutte le prenotazioni di una palestra
        $query = "SELECT b.*, u.nome as user_nome, u.cognome as user_cognome, u.email as user_email 
                  FROM " . $this->table_name . " b
                  JOIN users u ON b.user_id = u.id
                  WHERE b.gym_id = :gym_id 
                  ORDER BY b.data_richiesta DESC";
        
        // Prepara la query
        $stmt = $this->conn->prepare($query);
        
        // Associa l'ID palestra
        $stmt->bindParam(":gym_id", $this->gym_id);
        
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
     * Aggiorna lo stato di una prenotazione
     * 
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    public function updateStatus() {
        try {
            // Inizia la transazione
            $this->conn->beginTransaction();
            
            // Query per aggiornare lo stato di una prenotazione
            $query = "UPDATE " . $this->table_name . " 
                      SET stato = :stato, data_risposta = NOW() 
                      WHERE id = :id";
            
            // Prepara la query
            $stmt = $this->conn->prepare($query);
            
            // Associa i valori
            $stmt->bindParam(":stato", $this->stato);
            $stmt->bindParam(":id", $this->id);
            
            // Esegui la query
            if (!$stmt->execute()) {
                throw new Exception("Errore nell'aggiornamento dello stato della prenotazione");
            }
            
            // Se lo stato è 'confermata', incrementa il conteggio degli iscritti
            if ($this->stato === 'confermata') {
                // Ottieni i dati della prenotazione per avere il gym_id
                $query = "SELECT gym_id FROM " . $this->table_name . " WHERE id = :id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(":id", $this->id);
                $stmt->execute();
                $booking_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$booking_data) {
                    throw new Exception("Prenotazione non trovata");
                }
                
                // Aggiorna il conteggio degli iscritti nella palestra
                $gym = new Gym($this->conn);
                $gym->id = $booking_data['gym_id'];
                if (!$gym->updateSubscriberCount(true)) {
                    throw new Exception("Errore nell'aggiornamento del conteggio iscritti");
                }
            }
            
            // Commit della transazione
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            // Rollback in caso di errore
            $this->conn->rollBack();
            error_log("Errore durante l'aggiornamento dello stato: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Aggiorna una prenotazione
     * 
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    public function update() {
        // Query per aggiornare una prenotazione
        $query = "UPDATE " . $this->table_name . " 
                  SET data_inizio = :data_inizio, data_fine = :data_fine, note = :note 
                  WHERE id = :id";
        
        // Prepara la query
        $stmt = $this->conn->prepare($query);
        
        // Sanitizza i dati
        $this->note = $this->note ? htmlspecialchars(strip_tags($this->note)) : null;
        
        // Associa i valori
        $stmt->bindParam(":data_inizio", $this->data_inizio);
        $stmt->bindParam(":data_fine", $this->data_fine);
        $stmt->bindParam(":note", $this->note);
        $stmt->bindParam(":id", $this->id);
        
        // Esegui la query
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Elimina una prenotazione
     * 
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    public function delete() {
        // Query per eliminare una prenotazione
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
     * Verifica se un utente ha già una prenotazione attiva per una palestra
     * 
     * @return bool True se l'utente ha già una prenotazione attiva, false altrimenti
     */
    public function hasActiveBooking() {
        // Query per verificare se un utente ha già una prenotazione attiva
        $query = "SELECT id FROM " . $this->table_name . " 
                  WHERE user_id = :user_id AND gym_id = :gym_id 
                  AND (stato = 'in attesa' OR stato = 'confermata') 
                  LIMIT 0,1";
        
        // Prepara la query
        $stmt = $this->conn->prepare($query);
        
        // Associa i valori
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":gym_id", $this->gym_id);
        
        // Esegui la query
        $stmt->execute();
        
        // Ottieni il numero di righe
        $num = $stmt->rowCount();
        
        return $num > 0;
    }
}
