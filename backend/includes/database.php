<?php
/**
 * Classe per la connessione al database
 * 
 * Questa classe gestisce la connessione al database MySQL utilizzando PDO
 */

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;
    
    /**
     * Costruttore della classe
     */
    public function __construct() {
        $this->host = 'localhost';
        $this->db_name = 'gymfinder';
        $this->username = 'root';
        $this->password = '';
    }
    
    /**
     * Connessione al database
     * 
     * @return PDO Oggetto PDO per la connessione al database
     */
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $e) {
            echo "Errore di connessione: " . $e->getMessage();
        }
        
        return $this->conn;
    }
}
