<?php
/**
 * Classe per la gestione dell'autenticazione JWT
 * 
 * Questa classe gestisce la creazione, validazione e decodifica dei token JWT
 */

require_once __DIR__ . '/config.php';

class JwtAuth {
    private $secret;
    private $expiration;
    
    /**
     * Costruttore della classe
     */
    public function __construct() {
        $this->secret = JWT_SECRET;
        $this->expiration = JWT_EXPIRATION;
    }
    
    /**
     * Genera un token JWT
     * 
     * @param array $payload Dati da includere nel token
     * @return string Token JWT generato
     */
    public function generateToken($payload) {
        // Header
        $header = json_encode([
            'typ' => 'JWT',
            'alg' => 'HS256'
        ]);
        $header = $this->base64UrlEncode($header);
        
        // Payload
        $payload['iat'] = time(); // Issued at
        $payload['exp'] = time() + $this->expiration; // Expiration time
        $payload = json_encode($payload);
        $payload = $this->base64UrlEncode($payload);
        
        // Signature
        $signature = hash_hmac('sha256', "$header.$payload", $this->secret, true);
        $signature = $this->base64UrlEncode($signature);
        
        // Token
        return "$header.$payload.$signature";
    }
    
    /**
     * Valida un token JWT
     * 
     * @param string $token Token JWT da validare
     * @return bool True se il token è valido, false altrimenti
     */
    public function validateToken($token) {
        // Verifica che il token sia nel formato corretto
        $parts = explode('.', $token);
        if (count($parts) != 3) {
            return false;
        }
        
        list($header, $payload, $signature) = $parts;
        
        // Verifica la firma
        $valid_signature = $this->base64UrlEncode(
            hash_hmac('sha256', "$header.$payload", $this->secret, true)
        );
        
        if ($signature !== $valid_signature) {
            return false;
        }
        
        // Decodifica il payload
        $payload = json_decode($this->base64UrlDecode($payload), true);
        
        // Verifica la scadenza
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Decodifica un token JWT
     * 
     * @param string $token Token JWT da decodificare
     * @return array|null Payload decodificato o null se il token non è valido
     */
    public function decodeToken($token) {
        if (!$this->validateToken($token)) {
            return null;
        }
        
        $parts = explode('.', $token);
        $payload = $this->base64UrlDecode($parts[1]);
        
        return json_decode($payload, true);
    }
    
    /**
     * Codifica una stringa in base64 URL-safe
     * 
     * @param string $data Stringa da codificare
     * @return string Stringa codificata
     */
    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Decodifica una stringa in base64 URL-safe
     * 
     * @param string $data Stringa da decodificare
     * @return string Stringa decodificata
     */
    private function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
