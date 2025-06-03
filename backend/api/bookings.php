<?php
/**
 * API per la gestione delle prenotazioni
 * 
 * Questo file gestisce le operazioni CRUD per le prenotazioni
 */

// Includi i file necessari
require_once __DIR__ . '/../includes/cors.php';  // Add CORS support
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/jwt_auth.php';
require_once __DIR__ . '/../includes/utils.php';
require_once __DIR__ . '/../models/Booking.php';
require_once __DIR__ . '/../models/Gym.php';

// Inizializza le classi necessarie
$database = new Database();
$db = $database->getConnection();
$jwt_auth = new JwtAuth();
$booking = new Booking($db);
$gym = new Gym($db);

// Ottieni il metodo HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Log the request details for debugging
error_log("Request Method: " . $method);
error_log("Request Headers: " . json_encode(getallheaders()));
error_log("GET params: " . json_encode($_GET));

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
    global $booking, $jwt_auth;
    
    // Verifica l'autenticazione
    $user_data = isAuthenticated($jwt_auth);
    if (!$user_data) {
        jsonResponse(false, 'Non autorizzato', [], 401);
    }
    
    // Ottieni i parametri dalla query string
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    $gym_id = isset($_GET['gym_id']) ? intval($_GET['gym_id']) : 0;
    
    // Gestisci le diverse azioni
    if ($id > 0) {
        // Ottieni una singola prenotazione
        $booking->id = $id;
        $result = $booking->readOne();
        
        if ($result) {
            // Verifica che l'utente sia autorizzato a vedere questa prenotazione
            if ($result['user_id'] != $user_data['id'] && $result['gym_id'] != $user_data['id'] && !hasRole($user_data, 'admin')) {
                jsonResponse(false, 'Non autorizzato a visualizzare questa prenotazione', [], 403);
            }
            
            jsonResponse(true, 'Prenotazione ottenuta con successo', ['booking' => $result]);
        } else {
            jsonResponse(false, 'Prenotazione non trovata', [], 404);
        }
    } elseif ($action === 'my') {
        // Ottieni le prenotazioni dell'utente corrente
        $booking->user_id = $user_data['id'];
        $results = $booking->readByUser();
        
        if ($results) {
            jsonResponse(true, 'Prenotazioni ottenute con successo', [
                'count' => count($results),
                'bookings' => $results
            ]);
        } else {
            jsonResponse(false, 'Nessuna prenotazione trovata', ['count' => 0, 'bookings' => []], 404);
        }
    } elseif ($gym_id > 0) {
        // Ottieni le prenotazioni di una palestra
        global $gym;
        
        // Verifica che la palestra esista
        $gym->id = $gym_id;
        $gym_data = $gym->readOne();
        
        if (!$gym_data) {
            jsonResponse(false, 'Palestra non trovata', [], 404);
        }
        
        // Verifica che l'utente sia il proprietario della palestra o un amministratore
        if ($gym_data['user_id'] != $user_data['id'] && !hasRole($user_data, 'admin')) {
            jsonResponse(false, 'Non autorizzato a visualizzare le prenotazioni di questa palestra', [], 403);
        }
        
        // Ottieni le prenotazioni della palestra
        $booking->gym_id = $gym_id;
        $results = $booking->readByGym();
        
        if ($results) {
            jsonResponse(true, 'Prenotazioni ottenute con successo', [
                'count' => count($results),
                'bookings' => $results
            ]);
        } else {
            jsonResponse(false, 'Nessuna prenotazione trovata', ['count' => 0, 'bookings' => []], 404);
        }
    } else {
        jsonResponse(false, 'Parametri non validi', [], 400);
    }
}

/**
 * Gestisce le richieste POST
 */
function handlePost() {
    global $booking, $gym, $jwt_auth;
    
    // Verifica l'autenticazione
    $user_data = isAuthenticated($jwt_auth);
    if (!$user_data) {
        jsonResponse(false, 'Non autorizzato', [], 401);
    }
    
    // Ottieni i dati JSON dalla richiesta
    $data = getJsonData();
    
    // Verifica che ci siano i dati necessari
    if (!isset($data['gym_id']) || !isset($data['data_inizio'])) {
        jsonResponse(false, 'Dati incompleti', [], 400);
    }
    
    // Verifica che la palestra esista
    $gym->id = intval($data['gym_id']);
    $gym_data = $gym->readOne();
    
    if (!$gym_data) {
        jsonResponse(false, 'Palestra non trovata', [], 404);
    }
    
    // Imposta i dati della prenotazione
    $booking->user_id = $user_data['id'];
    $booking->gym_id = intval($data['gym_id']);
    $booking->data_inizio = $data['data_inizio'];
    $booking->data_fine = isset($data['data_fine']) ? $data['data_fine'] : null;
    $booking->stato = 'in attesa';
    $booking->note = isset($data['note']) ? sanitizeInput($data['note']) : null;
    
    // Verifica che l'utente non abbia già una prenotazione attiva per questa palestra
    if ($booking->hasActiveBooking()) {
        jsonResponse(false, 'Hai già una prenotazione attiva per questa palestra', [], 400);
    }
    
    // Crea la prenotazione
    if ($booking->create()) {
        jsonResponse(true, 'Prenotazione creata con successo', ['booking_id' => $booking->id]);
    } else {
        jsonResponse(false, 'Errore durante la creazione della prenotazione', [], 500);
    }
}

/**
 * Gestisce le richieste PUT
 */
function handlePut() {
    global $booking, $jwt_auth;
    
    // Verifica l'autenticazione
    $user_data = isAuthenticated($jwt_auth);
    if (!$user_data) {
        jsonResponse(false, 'Non autorizzato', [], 401);
    }
    
    // Log user data for debugging
    error_log("User data: " . json_encode($user_data));
    
    // Ottieni l'ID dalla query string
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    // Verifica che l'ID sia valido
    if ($id <= 0) {
        jsonResponse(false, 'ID non valido', [], 400);
    }
    
    // Imposta l'ID della prenotazione
    $booking->id = $id;
    
    // Verifica che la prenotazione esista
    $booking_data = $booking->readOne();
    if (!$booking_data) {
        jsonResponse(false, 'Prenotazione non trovata', [], 404);
    }
    
    error_log("Booking data: " . json_encode($booking_data));
    
    // Gestisci le diverse azioni
    if ($action === 'status') {
        // Aggiorna lo stato della prenotazione
        
        // Verifica che l'utente sia il proprietario della palestra o un amministratore
        global $gym;
        $gym->id = $booking_data['gym_id'];
        $gym_data = $gym->readOne();
        
        error_log("Gym data: " . json_encode($gym_data));
        error_log("User ID: " . $user_data['id'] . ", Gym User ID: " . $gym_data['user_id']);
        
        if ($gym_data['user_id'] != $user_data['id'] && !hasRole($user_data, 'admin')) {
            jsonResponse(false, 'Non autorizzato a modificare lo stato di questa prenotazione', [], 403);
        }
        
        // Ottieni i dati JSON dalla richiesta
        $data = getJsonData();
        error_log("Request data: " . json_encode($data));
        
        // Verifica che ci siano i dati necessari
        if (!isset($data['stato'])) {
            jsonResponse(false, 'Stato non fornito', [], 400);
        }
        
        // Verifica che lo stato sia valido
        if (!in_array($data['stato'], ['in attesa', 'confermata', 'rifiutata'])) {
            jsonResponse(false, 'Stato non valido', [], 400);
        }
        
        // Imposta lo stato della prenotazione
        $booking->stato = $data['stato'];
        
        // Aggiorna lo stato
        if ($booking->updateStatus()) {
            jsonResponse(true, 'Stato della prenotazione aggiornato con successo');
        } else {
            jsonResponse(false, 'Errore durante l\'aggiornamento dello stato della prenotazione', [], 500);
        }
    } else {
        // Aggiorna la prenotazione
        
        // Verifica che l'utente sia il proprietario della prenotazione
        if ($booking_data['user_id'] != $user_data['id']) {
            jsonResponse(false, 'Non autorizzato a modificare questa prenotazione', [], 403);
        }
        
        // Verifica che la prenotazione sia ancora in attesa
        if ($booking_data['stato'] !== 'in attesa') {
            jsonResponse(false, 'Non è possibile modificare una prenotazione già confermata o rifiutata', [], 400);
        }
        
        // Ottieni i dati JSON dalla richiesta
        $data = getJsonData();
        
        // Verifica che ci siano i dati necessari
        if (empty($data)) {
            jsonResponse(false, 'Dati non forniti', [], 400);
        }
        
        // Imposta i dati della prenotazione
        if (isset($data['data_inizio'])) {
            $booking->data_inizio = $data['data_inizio'];
        }
        
        if (isset($data['data_fine'])) {
            $booking->data_fine = $data['data_fine'];
        }
        
        if (isset($data['note'])) {
            $booking->note = sanitizeInput($data['note']);
        }
        
        // Aggiorna la prenotazione
        if ($booking->update()) {
            jsonResponse(true, 'Prenotazione aggiornata con successo');
        } else {
            jsonResponse(false, 'Errore durante l\'aggiornamento della prenotazione', [], 500);
        }
    }
}

/**
 * Gestisce le richieste DELETE
 */
function handleDelete() {
    global $booking, $jwt_auth;
    
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
    
    // Imposta l'ID della prenotazione
    $booking->id = $id;
    
    // Verifica che la prenotazione esista
    $booking_data = $booking->readOne();
    if (!$booking_data) {
        jsonResponse(false, 'Prenotazione non trovata', [], 404);
    }
    
    // Verifica che l'utente sia il proprietario della prenotazione, il proprietario della palestra o un amministratore
    if ($booking_data['user_id'] != $user_data['id']) {
        global $gym;
        $gym->id = $booking_data['gym_id'];
        $gym_data = $gym->readOne();
        
        if ($gym_data['user_id'] != $user_data['id'] && !hasRole($user_data, 'admin')) {
            jsonResponse(false, 'Non autorizzato a eliminare questa prenotazione', [], 403);
        }
    }
    
    // Elimina la prenotazione
    if ($booking->delete()) {
        jsonResponse(true, 'Prenotazione eliminata con successo');
    } else {
        jsonResponse(false, 'Errore durante l\'eliminazione della prenotazione', [], 500);
    }
}
