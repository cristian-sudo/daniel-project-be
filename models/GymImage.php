<?php
/**
 * Modello GymImage per la gestione delle immagini delle palestre
 * 
 * Questa classe gestisce le operazioni CRUD per le immagini delle palestre
 */

class GymImage {
    // Connessione al database e nome tabella
    private $conn;
    private $table_name = "gym_images";
    
    // Proprietà dell'immagine
    public $id;
    public $gym_id;
    public $percorso_immagine;
    public $descrizione;
    public $principale;
    public $data_caricamento;
    
    /**
     * Costruttore
     * 
     * @param PDO $db Connessione al database
     */
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Crea una nuova immagine
     * 
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    public function create() {
        // Query per inserire una nuova immagine
        $query = "INSERT INTO " . $this->table_name . " 
                  (gym_id, percorso_immagine, descrizione, principale) 
                  VALUES (:gym_id, :percorso_immagine, :descrizione, :principale)";
        
        // Prepara la query
        $stmt = $this->conn->prepare($query);
        
        // Sanitizza i dati
        $this->percorso_immagine = htmlspecialchars(strip_tags($this->percorso_immagine));
        $this->descrizione = $this->descrizione ? htmlspecialchars(strip_tags($this->descrizione)) : null;
        
        // Associa i valori
        $stmt->bindParam(":gym_id", $this->gym_id);
        $stmt->bindParam(":percorso_immagine", $this->percorso_immagine);
        $stmt->bindParam(":descrizione", $this->descrizione);
        $stmt->bindParam(":principale", $this->principale);
        
        // Esegui la query
        if ($stmt->execute()) {
            // Imposta l'ID dell'immagine appena creata
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    /**
     * Legge una singola immagine
     * 
     * @return array|bool Dati dell'immagine o false se non trovata
     */
    public function readOne() {
        // Query per leggere una singola immagine
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
     * Legge tutte le immagini di una palestra
     * 
     * @return array|bool Dati delle immagini o false se non trovate
     */
    public function readByGym() {
        // Query per leggere tutte le immagini di una palestra
        $query = "SELECT * FROM " . $this->table_name . " WHERE gym_id = :gym_id ORDER BY principale DESC, data_caricamento DESC";
        
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
     * Ottiene l'immagine principale di una palestra
     * 
     * @return array|bool Dati dell'immagine o false se non trovata
     */
    public function getMainImage() {
        // Query per ottenere l'immagine principale
        $query = "SELECT * FROM " . $this->table_name . " WHERE gym_id = :gym_id AND principale = TRUE LIMIT 0,1";
        
        // Prepara la query
        $stmt = $this->conn->prepare($query);
        
        // Associa l'ID della palestra
        $stmt->bindParam(":gym_id", $this->gym_id);
        
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
     * Imposta un'immagine come principale
     * 
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    public function setAsMain() {
        // Prima imposta tutte le immagini della palestra come non principali
        $query = "UPDATE " . $this->table_name . " SET principale = FALSE WHERE gym_id = :gym_id";
        
        // Prepara la query
        $stmt = $this->conn->prepare($query);
        
        // Associa l'ID della palestra
        $stmt->bindParam(":gym_id", $this->gym_id);
        
        // Esegui la query
        $stmt->execute();
        
        // Poi imposta l'immagine corrente come principale
        $query = "UPDATE " . $this->table_name . " SET principale = TRUE WHERE id = :id";
        
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
     * Aggiorna un'immagine
     * 
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    public function update() {
        // Query per aggiornare un'immagine
        $query = "UPDATE " . $this->table_name . " 
                  SET descrizione = :descrizione 
                  WHERE id = :id";
        
        // Prepara la query
        $stmt = $this->conn->prepare($query);
        
        // Sanitizza i dati
        $this->descrizione = $this->descrizione ? htmlspecialchars(strip_tags($this->descrizione)) : null;
        
        // Associa i valori
        $stmt->bindParam(":descrizione", $this->descrizione);
        $stmt->bindParam(":id", $this->id);
        
        // Esegui la query
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Elimina un'immagine
     * 
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    public function delete() {
        // Query per eliminare un'immagine
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
}
