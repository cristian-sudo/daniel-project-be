<?php
/**
 * Modello User per la gestione degli utenti
 * 
 * Questa classe gestisce le operazioni CRUD per gli utenti
 */

class User {
    // Connessione al database e nome tabella
    private $conn;
    private $table_name = "users";
    
    // Proprietà dell'utente
    public $id;
    public $username;
    public $password;
    public $email;
    public $nome;
    public $cognome;
    public $tipo_utente;
    public $data_registrazione;
    public $ultimo_accesso;
    public $token;
    public $token_scadenza;
    public $attivo;
    
    /**
     * Costruttore
     * 
     * @param PDO $db Connessione al database
     */
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Crea un nuovo utente
     * 
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    public function create() {
        // Query per inserire un nuovo utente
        $query = "INSERT INTO " . $this->table_name . " 
                  (username, password, email, nome, cognome, tipo_utente) 
                  VALUES (:username, :password, :email, :nome, :cognome, :tipo_utente)";
        
        // Prepara la query
        $stmt = $this->conn->prepare($query);
        
        // Sanitizza i dati
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->cognome = htmlspecialchars(strip_tags($this->cognome));
        $this->tipo_utente = htmlspecialchars(strip_tags($this->tipo_utente));
        
        // Associa i valori
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":cognome", $this->cognome);
        $stmt->bindParam(":tipo_utente", $this->tipo_utente);
        
        // Esegui la query
        if ($stmt->execute()) {
            // Imposta l'ID dell'utente appena creato
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    /**
     * Legge un singolo utente
     * 
     * @return array|bool Dati dell'utente o false se non trovato
     */
    public function readOne() {
        // Query per leggere un singolo utente
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
     * Legge tutti gli utenti
     * 
     * @return array|bool Dati degli utenti o false se non trovati
     */
    public function readAll() {
        // Query per leggere tutti gli utenti
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY data_registrazione DESC";
        
        // Prepara la query
        $stmt = $this->conn->prepare($query);
        
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
     * Aggiorna un utente
     * 
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    public function update() {
        // Query per aggiornare un utente
        $query = "UPDATE " . $this->table_name . " 
                  SET email = :email, nome = :nome, cognome = :cognome";
        
        // Aggiungi il tipo utente se specificato
        if (isset($this->tipo_utente)) {
            $query .= ", tipo_utente = :tipo_utente";
        }
        
        $query .= " WHERE id = :id";
        
        // Prepara la query
        $stmt = $this->conn->prepare($query);
        
        // Sanitizza i dati
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->cognome = htmlspecialchars(strip_tags($this->cognome));
        
        // Associa i valori
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":cognome", $this->cognome);
        $stmt->bindParam(":id", $this->id);
        
        // Associa il tipo utente se specificato
        if (isset($this->tipo_utente)) {
            $this->tipo_utente = htmlspecialchars(strip_tags($this->tipo_utente));
            $stmt->bindParam(":tipo_utente", $this->tipo_utente);
        }
        
        // Esegui la query
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Aggiorna la password di un utente
     * 
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    public function updatePassword() {
        // Query per aggiornare la password
        $query = "UPDATE " . $this->table_name . " SET password = :password WHERE id = :id";
        
        // Prepara la query
        $stmt = $this->conn->prepare($query);
        
        // Associa i valori
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":id", $this->id);
        
        // Esegui la query
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Aggiorna l'ultimo accesso di un utente
     * 
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    public function updateLastLogin() {
        // Query per aggiornare l'ultimo accesso
        $query = "UPDATE " . $this->table_name . " SET ultimo_accesso = NOW() WHERE id = :id";
        
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
     * Elimina un utente
     * 
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    public function delete() {
        // Query per eliminare un utente
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
     * Verifica se un username esiste già
     * 
     * @return bool True se l'username esiste, false altrimenti
     */
    public function usernameExists() {
        // Query per verificare se un username esiste
        $query = "SELECT id FROM " . $this->table_name . " WHERE username = :username LIMIT 0,1";
        
        // Prepara la query
        $stmt = $this->conn->prepare($query);
        
        // Sanitizza l'username
        $this->username = htmlspecialchars(strip_tags($this->username));
        
        // Associa l'username
        $stmt->bindParam(":username", $this->username);
        
        // Esegui la query
        $stmt->execute();
        
        // Ottieni il numero di righe
        $num = $stmt->rowCount();
        
        return $num > 0;
    }
    
    /**
     * Verifica se un'email esiste già
     * 
     * @return bool True se l'email esiste, false altrimenti
     */
    public function emailExists() {
        // Query per verificare se un'email esiste
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email LIMIT 0,1";
        
        // Prepara la query
        $stmt = $this->conn->prepare($query);
        
        // Sanitizza l'email
        $this->email = htmlspecialchars(strip_tags($this->email));
        
        // Associa l'email
        $stmt->bindParam(":email", $this->email);
        
        // Esegui la query
        $stmt->execute();
        
        // Ottieni il numero di righe
        $num = $stmt->rowCount();
        
        return $num > 0;
    }
    
    /**
     * Login utente
     * 
     * @return array|bool Dati dell'utente o false se non trovato
     */
    public function login() {
        // Query per ottenere un utente tramite username
        $query = "SELECT * FROM " . $this->table_name . " WHERE username = :username LIMIT 0,1";
        
        // Prepara la query
        $stmt = $this->conn->prepare($query);
        
        // Sanitizza l'username
        $this->username = htmlspecialchars(strip_tags($this->username));
        
        // Associa l'username
        $stmt->bindParam(":username", $this->username);
        
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
     * Verifica la password di un utente
     * 
     * @param string $password Password da verificare
     * @return bool True se la password è corretta, false altrimenti
     */
    public function verifyPassword($password) {
        // Ottieni i dati dell'utente
        $user_data = $this->readOne();
        
        if (!$user_data) {
            return false;
        }
        
        // Verifica la password
        return password_verify($password, $user_data['password']);
    }
}
