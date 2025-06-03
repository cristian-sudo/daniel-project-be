<?php
/**
 * Funzioni di utilità per l'applicazione
 * 
 * Questo file contiene funzioni di utilità generiche utilizzate in tutta l'applicazione
 */

/**
 * Genera una risposta JSON
 * 
 * @param bool $success Indica se l'operazione è stata completata con successo
 * @param string $message Messaggio da includere nella risposta
 * @param array $data Dati da includere nella risposta
 * @param int $status_code Codice di stato HTTP
 * @return void
 */
function jsonResponse($success, $message = '', $data = [], $status_code = 200) {
    $origin = getAllowedOrigin();
    
    if ($origin) {
        header("Access-Control-Allow-Origin: $origin");
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Allow-Credentials: true');
    }
    
    http_response_code($status_code);
    header('Content-Type: application/json');
    
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

/**
 * Ottiene l'origine consentita per CORS
 * 
 * @return string|null Origine consentita o null se non consentita
 */
function getAllowedOrigin() {
    $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
    
    if (empty($origin)) {
        return null;
    }
    
    if (defined('ALLOWED_ORIGINS') && is_array(ALLOWED_ORIGINS)) {
        if (in_array($origin, ALLOWED_ORIGINS)) {
            return $origin;
        }
    }
    
    return null;
}

/**
 * Ottiene il token JWT dall'header Authorization
 * 
 * @return string|null Token JWT o null se non presente
 */
function getBearerToken() {
    $headers = getallheaders();
    error_log("All headers: " . json_encode($headers));
    
    // Try different header cases since some servers might modify the case
    $authHeader = null;
    $headerNames = ['Authorization', 'authorization', 'AUTHORIZATION'];
    
    foreach ($headerNames as $headerName) {
        if (isset($headers[$headerName])) {
            $authHeader = $headers[$headerName];
            break;
        }
    }
    
    if ($authHeader) {
        error_log("Found Authorization header: " . $authHeader);
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            error_log("Extracted token: " . $matches[1]);
            return $matches[1];
        }
    }
    
    error_log("No valid bearer token found in headers");
    return null;
}

/**
 * Verifica che l'utente sia autenticato
 * 
 * @param JwtAuth $jwt_auth Istanza della classe JwtAuth
 * @return array|bool Dati dell'utente se autenticato, false altrimenti
 */
function isAuthenticated($jwt_auth) {
    $token = getBearerToken();
    
    if (!$token) {
        return false;
    }
    
    if (!$jwt_auth->validateToken($token)) {
        return false;
    }
    
    return $jwt_auth->decodeToken($token);
}

/**
 * Verifica che l'utente abbia il ruolo specificato
 * 
 * @param array $user_data Dati dell'utente
 * @param string $role Ruolo da verificare
 * @return bool True se l'utente ha il ruolo specificato, false altrimenti
 */
function hasRole($user_data, $role) {
    return isset($user_data['tipo_utente']) && $user_data['tipo_utente'] === $role;
}

/**
 * Genera un nome file univoco per l'upload
 * 
 * @param string $original_name Nome originale del file
 * @return string Nome file univoco
 */
function generateUniqueFilename($original_name) {
    $extension = pathinfo($original_name, PATHINFO_EXTENSION);
    return uniqid() . '_' . time() . '.' . $extension;
}

/**
 * Verifica che il file sia valido per l'upload
 * 
 * @param array $file File da verificare ($_FILES['file'])
 * @return array Risultato della verifica [success, message]
 */
function validateFile($file) {
    // Verifica che il file esista
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return [false, 'Errore durante l\'upload del file'];
    }
    
    // Verifica la dimensione
    if ($file['size'] > MAX_FILE_SIZE) {
        return [false, 'Il file è troppo grande (max ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB)'];
    }
    
    // Verifica l'estensione
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return [false, 'Estensione non consentita. Estensioni consentite: ' . implode(', ', ALLOWED_EXTENSIONS)];
    }
    
    return [true, ''];
}

/**
 * Sanitizza l'input
 * 
 * @param string $data Dati da sanitizzare
 * @return string Dati sanitizzati
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Ottiene i dati JSON dalla richiesta
 * 
 * @return array Dati JSON
 */
function getJsonData() {
    $json = file_get_contents('php://input');
    return json_decode($json, true) ?? [];
}
