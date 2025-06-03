<?php
/**
 * API per la gestione dell'autenticazione
 * 
 * Questo file gestisce la registrazione degli utenti
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
$user = new User($db);

// Verifica che il metodo sia POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Metodo non supportato', [], 405);
}

// Ottieni i dati JSON dalla richiesta
$data = getJsonData();

// Verifica che ci siano tutti i dati necessari
if (!isset($data['username']) || !isset($data['password']) || !isset($data['email']) || 
    !isset($data['nome']) || !isset($data['cognome']) || !isset($data['tipo_utente'])) {
    jsonResponse(false, 'Dati incompleti', [], 400);
}

// Verifica che il tipo utente sia valido
if (!in_array($data['tipo_utente'], ['cliente', 'palestra'])) {
    jsonResponse(false, 'Tipo utente non valido', [], 400);
}

// Imposta i dati dell'utente
$user->username = sanitizeInput($data['username']);
$user->password = password_hash($data['password'], PASSWORD_DEFAULT);
$user->email = sanitizeInput($data['email']);
$user->nome = sanitizeInput($data['nome']);
$user->cognome = sanitizeInput($data['cognome']);
$user->tipo_utente = $data['tipo_utente'];

// Verifica che l'username e l'email non siano già in uso
if ($user->usernameExists()) {
    jsonResponse(false, 'Username già in uso', [], 400);
}

if ($user->emailExists()) {
    jsonResponse(false, 'Email già in uso', [], 400);
}

// Crea l'utente
if ($user->create()) {
    jsonResponse(true, 'Utente registrato con successo');
} else {
    jsonResponse(false, 'Errore durante la registrazione dell\'utente', [], 500);
}
