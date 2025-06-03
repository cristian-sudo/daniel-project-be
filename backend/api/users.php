<?php
/**
 * API per la gestione degli utenti
 * 
 * Questo file gestisce le operazioni CRUD per gli utenti
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

// Ottieni il metodo HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Gestisci la richiesta in base al metodo HTTP
switch ($method) {
    case 'GET':
        handleGet();
        break;
    case 'POST':
        handlePost();
        break;
    case 'PUT':
        handlePut();
        break;
    case 'DELETE':
        handleDelete();
        break;
    default:
        jsonResponse(false, 'Metodo non supportato', [], 405);
}

/**
 * Gestisce le richieste GET
 */
function handleGet() {
    global $user, $jwt_auth;
    
    // Verifica l'autenticazione
    $user_data = isAuthenticated($jwt_auth);
    if (!$user_data) {
        jsonResponse(false, 'Non autorizzato', [], 401);
    }
    
    // Ottieni i parametri dalla query string
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    // Gestisci le diverse azioni
    if ($action === 'me') {
        // Ottieni i dati dell'utente corrente
        $user->id = $user_data['id'];
        $result = $user->readOne();
        
        if ($result) {
            // Rimuovi la password dai dati
            unset($result['password']);
            jsonResponse(true, 'Dati utente ottenuti con successo', ['user' => $result]);
        } else {
            jsonResponse(false, 'Utente non trovato', [], 404);
        }
    } elseif ($id > 0) {
        // Verifica che l'utente stia cercando di accedere ai propri dati o sia un amministratore
        if ($user_data['id'] != $id && !hasRole($user_data, 'admin')) {
            jsonResponse(false, 'Non autorizzato ad accedere a questi dati', [], 403);
        }
        
        // Ottieni i dati dell'utente specificato
        $user->id = $id;
        $result = $user->readOne();
        
        if ($result) {
            // Rimuovi la password dai dati
            unset($result['password']);
            jsonResponse(true, 'Dati utente ottenuti con successo', ['user' => $result]);
        } else {
            jsonResponse(false, 'Utente non trovato', [], 404);
        }
    } else {
        // Verifica che l'utente sia un amministratore per ottenere tutti gli utenti
        if (!hasRole($user_data, 'admin')) {
            jsonResponse(false, 'Non autorizzato ad accedere a questi dati', [], 403);
        }
        
        // Ottieni tutti gli utenti
        $result = $user->readAll();
        
        if ($result) {
            // Rimuovi le password dai dati
            foreach ($result as &$u) {
                unset($u['password']);
            }
            
            jsonResponse(true, 'Utenti ottenuti con successo', [
                'count' => count($result),
                'users' => $result
            ]);
        } else {
            jsonResponse(false, 'Nessun utente trovato', ['count' => 0, 'users' => []], 404);
        }
    }
}

/**
 * Gestisce le richieste POST
 */
function handlePost() {
    global $user, $jwt_auth;
    
    // Ottieni i dati JSON dalla richiesta
    $data = getJsonData();
    
    // Verifica che ci siano i dati necessari
    if (empty($data)) {
        jsonResponse(false, 'Dati non forniti', [], 400);
    }
    
    // Ottieni l'azione dalla query string
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    // Gestisci le diverse azioni
    if ($action === 'register') {
        // Registrazione nuovo utente
        
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
    } else {
        // Verifica l'autenticazione per altre azioni
        $user_data = isAuthenticated($jwt_auth);
        if (!$user_data) {
            jsonResponse(false, 'Non autorizzato', [], 401);
        }
        
        // Verifica che l'utente sia un amministratore per creare utenti
        if (!hasRole($user_data, 'admin')) {
            jsonResponse(false, 'Non autorizzato a creare utenti', [], 403);
        }
        
        // Verifica che ci siano tutti i dati necessari
        if (!isset($data['username']) || !isset($data['password']) || !isset($data['email']) || 
            !isset($data['nome']) || !isset($data['cognome']) || !isset($data['tipo_utente'])) {
            jsonResponse(false, 'Dati incompleti', [], 400);
        }
        
        // Verifica che il tipo utente sia valido
        if (!in_array($data['tipo_utente'], ['cliente', 'palestra', 'admin'])) {
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
            jsonResponse(true, 'Utente creato con successo');
        } else {
            jsonResponse(false, 'Errore durante la creazione dell\'utente', [], 500);
        }
    }
}

/**
 * Gestisce le richieste PUT
 */
function handlePut() {
    global $user, $jwt_auth;
    
    // Verifica l'autenticazione
    $user_data = isAuthenticated($jwt_auth);
    if (!$user_data) {
        jsonResponse(false, 'Non autorizzato', [], 401);
    }
    
    // Ottieni i dati JSON dalla richiesta
    $data = getJsonData();
    
    // Verifica che ci siano i dati necessari
    if (empty($data)) {
        jsonResponse(false, 'Dati non forniti', [], 400);
    }
    
    // Ottieni l'ID e l'azione dalla query string
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    // Verifica che l'ID sia valido
    if ($id <= 0) {
        jsonResponse(false, 'ID non valido', [], 400);
    }
    
    // Verifica che l'utente stia modificando i propri dati o sia un amministratore
    if ($user_data['id'] != $id && !hasRole($user_data, 'admin')) {
        jsonResponse(false, 'Non autorizzato a modificare questi dati', [], 403);
    }
    
    // Imposta l'ID dell'utente
    $user->id = $id;
    
    // Verifica che l'utente esista
    if (!$user->readOne()) {
        jsonResponse(false, 'Utente non trovato', [], 404);
    }
    
    // Gestisci le diverse azioni
    if ($action === 'change-password') {
        // Cambio password
        
        // Verifica che ci siano tutti i dati necessari
        if (!isset($data['current_password']) || !isset($data['new_password'])) {
            jsonResponse(false, 'Dati incompleti', [], 400);
        }
        
        // Verifica che la password attuale sia corretta
        if (!$user->verifyPassword($data['current_password'])) {
            jsonResponse(false, 'Password attuale non corretta', [], 400);
        }
        
        // Imposta la nuova password
        $user->password = password_hash($data['new_password'], PASSWORD_DEFAULT);
        
        // Aggiorna la password
        if ($user->updatePassword()) {
            jsonResponse(true, 'Password aggiornata con successo');
        } else {
            jsonResponse(false, 'Errore durante l\'aggiornamento della password', [], 500);
        }
    } else {
        // Aggiornamento dati utente
        
        // Imposta i dati dell'utente
        if (isset($data['email'])) {
            $user->email = sanitizeInput($data['email']);
        }
        
        if (isset($data['nome'])) {
            $user->nome = sanitizeInput($data['nome']);
        }
        
        if (isset($data['cognome'])) {
            $user->cognome = sanitizeInput($data['cognome']);
        }
        
        // Solo gli amministratori possono modificare il tipo utente
        if (isset($data['tipo_utente']) && hasRole($user_data, 'admin')) {
            // Verifica che il tipo utente sia valido
            if (!in_array($data['tipo_utente'], ['cliente', 'palestra', 'admin'])) {
                jsonResponse(false, 'Tipo utente non valido', [], 400);
            }
            
            $user->tipo_utente = $data['tipo_utente'];
        }
        
        // Aggiorna l'utente
        if ($user->update()) {
            jsonResponse(true, 'Utente aggiornato con successo');
        } else {
            jsonResponse(false, 'Errore durante l\'aggiornamento dell\'utente', [], 500);
        }
    }
}

/**
 * Gestisce le richieste DELETE
 */
function handleDelete() {
    global $user, $jwt_auth;
    
    // Verifica l'autenticazione
    $user_data = isAuthenticated($jwt_auth);
    if (!$user_data) {
        jsonResponse(false, 'Non autorizzato', [], 401);
    }
    
    // Ottieni l'ID dalla query string
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    // Verifica che l'ID sia valido
    if ($id <= 0) {
        jsonResponse(false, 'ID non valido', [], 400);
    }
    
    // Verifica che l'utente stia eliminando i propri dati o sia un amministratore
    if ($user_data['id'] != $id && !hasRole($user_data, 'admin')) {
        jsonResponse(false, 'Non autorizzato a eliminare questi dati', [], 403);
    }
    
    // Imposta l'ID dell'utente
    $user->id = $id;
    
    // Verifica che l'utente esista
    if (!$user->readOne()) {
        jsonResponse(false, 'Utente non trovato', [], 404);
    }
    
    // Elimina l'utente
    if ($user->delete()) {
        jsonResponse(true, 'Utente eliminato con successo');
    } else {
        jsonResponse(false, 'Errore durante l\'eliminazione dell\'utente', [], 500);
    }
}
