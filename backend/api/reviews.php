<?php
/**
 * API per la gestione delle recensioni
 * 
 * Questo file gestisce le operazioni CRUD per le recensioni
 */

// Includi i file necessari
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/jwt_auth.php';
require_once __DIR__ . '/../includes/utils.php';
require_once __DIR__ . '/../models/Review.php';
require_once __DIR__ . '/../models/Gym.php';
require_once __DIR__ . '/../models/Booking.php';

// Inizializza le classi necessarie
$database = new Database();
$db = $database->getConnection();
$jwt_auth = new JwtAuth();
$review = new Review($db);
$gym = new Gym($db);
$booking = new Booking($db);

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
    global $review;
    
    // Ottieni i parametri dalla query string
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $gym_id = isset($_GET['gym_id']) ? intval($_GET['gym_id']) : 0;
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    
    // Gestisci le diverse azioni
    if ($id > 0) {
        // Ottieni una singola recensione
        $review->id = $id;
        $result = $review->readOne();
        
        if ($result) {
            jsonResponse(true, 'Recensione ottenuta con successo', ['review' => $result]);
        } else {
            jsonResponse(false, 'Recensione non trovata', [], 404);
        }
    } elseif ($gym_id > 0) {
        // Ottieni tutte le recensioni di una palestra
        $review->gym_id = $gym_id;
        $results = $review->readByGym();
        
        if ($results) {
            jsonResponse(true, 'Recensioni ottenute con successo', [
                'count' => count($results),
                'reviews' => $results
            ]);
        } else {
            jsonResponse(true, 'Nessuna recensione trovata', ['count' => 0, 'reviews' => []], 200);
        }
    } elseif ($user_id > 0) {
        // Verifica l'autenticazione
        global $jwt_auth;
        $user_data = isAuthenticated($jwt_auth);
        if (!$user_data) {
            jsonResponse(false, 'Non autorizzato', [], 401);
        }
        
        // Verifica che l'utente stia cercando le proprie recensioni o sia un amministratore
        if ($user_data['id'] != $user_id && !hasRole($user_data, 'admin')) {
            jsonResponse(false, 'Non autorizzato a visualizzare queste recensioni', [], 403);
        }
        
        // Ottieni tutte le recensioni di un utente
        $review->user_id = $user_id;
        $results = $review->readByUser();
        
        if ($results) {
            jsonResponse(true, 'Recensioni ottenute con successo', [
                'count' => count($results),
                'reviews' => $results
            ]);
        } else {
            jsonResponse(false, 'Nessuna recensione trovata', ['count' => 0, 'reviews' => []], 404);
        }
    } else {
        jsonResponse(false, 'Parametri non validi', [], 400);
    }
}

/**
 * Gestisce le richieste POST
 */
function handlePost() {
    global $review, $gym, $booking, $jwt_auth;
    
    // Verifica l'autenticazione
    $user_data = isAuthenticated($jwt_auth);
    if (!$user_data) {
        jsonResponse(false, 'Non autorizzato', [], 401);
    }
    
    // Ottieni i dati JSON dalla richiesta
    $data = getJsonData();
    
    // Verifica che ci siano i dati necessari
    if (!isset($data['gym_id']) || !isset($data['valutazione']) || !isset($data['commento'])) {
        jsonResponse(false, 'Dati incompleti', [], 400);
    }
    
    // Verifica che la valutazione sia valida
    if ($data['valutazione'] < 1 || $data['valutazione'] > 5) {
        jsonResponse(false, 'Valutazione non valida (deve essere tra 1 e 5)', [], 400);
    }
    
    // Verifica che la palestra esista
    $gym->id = intval($data['gym_id']);
    $gym_data = $gym->readOne();
    
    if (!$gym_data) {
        jsonResponse(false, 'Palestra non trovata', [], 404);
    }
    
    // Verifica che l'utente abbia una prenotazione confermata per questa palestra
    $booking->user_id = $user_data['id'];
    $booking->gym_id = intval($data['gym_id']);
    
    // Imposta i dati della recensione
    $review->user_id = $user_data['id'];
    $review->gym_id = intval($data['gym_id']);
    
    // Verifica che l'utente non abbia già recensito questa palestra
    if ($review->userHasReviewed()) {
        // Se l'utente ha già recensito, aggiorna la recensione esistente
        $review->id = $review->getUserReviewId();
        $review->valutazione = intval($data['valutazione']);
        $review->commento = sanitizeInput($data['commento']);
        
        if ($review->update()) {
            jsonResponse(true, 'Recensione aggiornata con successo', ['review_id' => $review->id]);
        } else {
            jsonResponse(false, 'Errore durante l\'aggiornamento della recensione', [], 500);
        }
    } else {
        // Altrimenti, crea una nuova recensione
        $review->valutazione = intval($data['valutazione']);
        $review->commento = sanitizeInput($data['commento']);
        
        if ($review->create()) {
            jsonResponse(true, 'Recensione creata con successo', ['review_id' => $review->id]);
        } else {
            jsonResponse(false, 'Errore durante la creazione della recensione', [], 500);
        }
    }
}

/**
 * Gestisce le richieste PUT
 */
function handlePut() {
    global $review, $jwt_auth;
    
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
    
    // Imposta l'ID della recensione
    $review->id = $id;
    
    // Verifica che la recensione esista
    $review_data = $review->readOne();
    if (!$review_data) {
        jsonResponse(false, 'Recensione non trovata', [], 404);
    }
    
    // Verifica che l'utente sia il proprietario della recensione o un amministratore
    if ($review_data['user_id'] != $user_data['id'] && !hasRole($user_data, 'admin')) {
        jsonResponse(false, 'Non autorizzato a modificare questa recensione', [], 403);
    }
    
    // Ottieni i dati JSON dalla richiesta
    $data = getJsonData();
    
    // Verifica che ci siano i dati necessari
    if (!isset($data['valutazione']) || !isset($data['commento'])) {
        jsonResponse(false, 'Dati incompleti', [], 400);
    }
    
    // Verifica che la valutazione sia valida
    if ($data['valutazione'] < 1 || $data['valutazione'] > 5) {
        jsonResponse(false, 'Valutazione non valida (deve essere tra 1 e 5)', [], 400);
    }
    
    // Imposta i dati della recensione
    $review->valutazione = intval($data['valutazione']);
    $review->commento = sanitizeInput($data['commento']);
    
    // Aggiorna la recensione
    if ($review->update()) {
        jsonResponse(true, 'Recensione aggiornata con successo');
    } else {
        jsonResponse(false, 'Errore durante l\'aggiornamento della recensione', [], 500);
    }
}

/**
 * Gestisce le richieste DELETE
 */
function handleDelete() {
    global $review, $jwt_auth;
    
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
    
    // Imposta l'ID della recensione
    $review->id = $id;
    
    // Verifica che la recensione esista
    $review_data = $review->readOne();
    if (!$review_data) {
        jsonResponse(false, 'Recensione non trovata', [], 404);
    }
    
    // Verifica che l'utente sia il proprietario della recensione o un amministratore
    if ($review_data['user_id'] != $user_data['id'] && !hasRole($user_data, 'admin')) {
        jsonResponse(false, 'Non autorizzato a eliminare questa recensione', [], 403);
    }
    
    // Elimina la recensione
    if ($review->delete()) {
        jsonResponse(true, 'Recensione eliminata con successo');
    } else {
        jsonResponse(false, 'Errore durante l\'eliminazione della recensione', [], 500);
    }
}
