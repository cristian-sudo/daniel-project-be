<?php
/**
 * Modello Review per la gestione delle recensioni
 * 
 * Questa classe gestisce le operazioni CRUD per le recensioni
 */

class Review {
    // Connessione al database e nome tabella
    private $conn;
    private $table_name = "reviews";
    
    // Proprietà della recensione
    public $id;
    public $user_id;
    public $gym_id;
    public $valutazione;
    public $commento;
    public $data_recensione;
    
    /**
     * Costruttore
     * 
     * @param PDO $db Connessione al database
     */
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Crea una nuova recensione
     * 
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    public function create() {
        // Query per inserire una nuova recensione
        $query = "INSERT INTO " . $this->table_name . " 
                  (user_id, gym_id, valutazione, commento) 
                  VALUES (:user_id, :gym_id, :valutazione, :commento)";
        
        // Prepara la query
        $stmt = $this->conn->prepare($query);
        
        // Sanitizza i dati
        $this->commento = htmlspecialchars(strip_tags($this->commento));
        
        // Associa i valori
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":gym_id", $this->gym_id);
        $stmt->bindParam(":valutazione", $this->valutazione);
        $stmt->bindParam(":commento", $this->commento);
        
        // Esegui la query
        if ($stmt->execute()) {
            // Imposta l'ID della recensione appena creata
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    /**
     * Legge una singola recensione
     * 
     * @return array|bool Dati della recensione o false se non trovata
     */
    public function readOne() {
        // Query per leggere una singola recensione
        $query = "SELECT r.*, u.nome as user_nome, u.cognome as user_cognome 
                  FROM " . $this->table_name . " r
                  JOIN users u ON r.user_id = u.id
                  WHERE r.id = :id LIMIT 0,1";
        
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
     * Legge tutte le recensioni di una palestra
     * 
     * @return array|bool Dati delle recensioni o false se non trovate
     */
    public function readByGym() {
        // Query per leggere tutte le recensioni di una palestra
        $query = "SELECT r.*, u.nome as user_nome, u.cognome as user_cognome 
                  FROM " . $this->table_name . " r
                  JOIN users u ON r.user_id = u.id
                  WHERE r.gym_id = :gym_id 
                  ORDER BY r.data_recensione DESC";
        
        // Prepara la query
        $stmt = $this->conn->prepare($query);
        
        // Associa l'ID della palestra
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
     * Legge tutte le recensioni di un utente
     * 
     * @return array|bool Dati delle recensioni o false se non trovate
     */
    public function readByUser() {
        // Query per leggere tutte le recensioni di un utente
        $query = "SELECT r.*, g.nome as gym_nome 
                  FROM " . $this->table_name . " r
                  JOIN gyms g ON r.gym_id = g.id
                  WHERE r.user_id = :user_id 
                  ORDER BY r.data_recensione DESC";
        
        // Prepara la query
        $stmt = $this->conn->prepare($query);
        
        // Associa l'ID dell'utente
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
     * Aggiorna una recensione
     * 
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    public function update() {
        // Query per aggiornare una recensione
        $query = "UPDATE " . $this->table_name . " 
                  SET valutazione = :valutazione, commento = :commento 
                  WHERE id = :id";
        
        // Prepara la query
        $stmt = $this->conn->prepare($query);
        
        // Sanitizza i dati
        $this->commento = htmlspecialchars(strip_tags($this->commento));
        
        // Associa i valori
        $stmt->bindParam(":valutazione", $this->valutazione);
        $stmt->bindParam(":commento", $this->commento);
        $stmt->bindParam(":id", $this->id);
        
        // Esegui la query
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Elimina una recensione
     * 
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    public function delete() {
        // Query per eliminare una recensione
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
     * Verifica se un utente ha già recensito una palestra
     * 
     * @return bool True se l'utente ha già recensito la palestra, false altrimenti
     */
    public function userHasReviewed() {
        // Query per verificare se un utente ha già recensito una palestra
        $query = "SELECT id FROM " . $this->table_name . " 
                  WHERE user_id = :user_id AND gym_id = :gym_id 
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
    
    /**
     * Ottiene l'ID della recensione di un utente per una palestra
     * 
     * @return int|bool ID della recensione o false se non trovata
     */
    public function getUserReviewId() {
        // Query per ottenere l'ID della recensione
        $query = "SELECT id FROM " . $this->table_name . " 
                  WHERE user_id = :user_id AND gym_id = :gym_id 
                  LIMIT 0,1";
        
        // Prepara la query
        $stmt = $this->conn->prepare($query);
        
        // Associa i valori
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":gym_id", $this->gym_id);
        
        // Esegui la query
        $stmt->execute();
        
        // Ottieni il risultato
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            return $row['id'];
        }
        
        return false;
    }
}
