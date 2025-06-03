<?php
/**
 * API per la gestione dell'autenticazione
 * 
 * Questo file gestisce il login degli utenti
 */

// Includi i file necessari
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/jwt_auth.php';
require_once __DIR__ . '/../includes/utils.php';
require_once __DIR__ . '/../models/User.php';

// Inizializza le classi necessarie
$database = new Database();
$db = $database->getConnection();
$jwt_auth = new JwtAuth();
$user = new User($db);

// Verifica che il metodo sia POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Metodo non supportato']);
    exit;
}

// Ottieni i dati JSON dalla richiesta
$data = json_decode(file_get_contents('php://input'), true);

// Verifica che ci siano i dati necessari
if (!isset($data['username']) || !isset($data['password'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Dati incompleti']);
    exit;
}

// Imposta i dati dell'utente
$user->username = sanitizeInput($data['username']);

// Verifica le credenziali
$user_data = $user->login();

if (!$user_data) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Credenziali non valide']);
    exit;
}

// Verifica la password
if (!password_verify($data['password'], $user_data['password'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Credenziali non valide']);
    exit;
}

// Rimuovi la password dai dati
unset($user_data['password']);

// Genera il token JWT
$token = $jwt_auth->generateToken([
    'id' => $user_data['id'],
    'username' => $user_data['username'],
    'tipo_utente' => $user_data['tipo_utente']
]);

// Aggiorna l'ultimo accesso
$user->id = $user_data['id'];
$user->updateLastLogin();

// Invia la risposta
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Login effettuato con successo',
    'data' => [
        'token' => $token,
        'user' => $user_data
    ]
]);
