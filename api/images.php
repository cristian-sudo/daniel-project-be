<?php
/**
 * API per la gestione delle immagini delle palestre
 * 
 * Questo file gestisce le operazioni CRUD per le immagini delle palestre
 */

// Includi i file necessari
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/jwt_auth.php';
require_once __DIR__ . '/../includes/utils.php';
require_once __DIR__ . '/../models/Gym.php';
require_once __DIR__ . '/../models/GymImage.php';

// Inizializza le classi necessarie
$database = new Database();
$db = $database->getConnection();
$jwt_auth = new JwtAuth();
$gym = new Gym($db);
$gym_image = new GymImage($db);

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
    global $gym_image;
    
    // Ottieni i parametri dalla query string
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $gym_id = isset($_GET['gym_id']) ? intval($_GET['gym_id']) : 0;
    
    // Gestisci le diverse azioni
    if ($id > 0) {
        // Ottieni una singola immagine
        $gym_image->id = $id;
        $result = $gym_image->readOne();
        
        if ($result) {
            jsonResponse(true, 'Immagine ottenuta con successo', ['image' => $result]);
        } else {
            jsonResponse(false, 'Immagine non trovata', [], 404);
        }
    } elseif ($gym_id > 0) {
        // Ottieni tutte le immagini di una palestra
        $gym_image->gym_id = $gym_id;
        $results = $gym_image->readByGym();
        
        if ($results) {
            jsonResponse(true, 'Immagini ottenute con successo', [
                'count' => count($results),
                'images' => $results
            ]);
        } else {
            jsonResponse(false, 'Nessuna immagine trovata', ['count' => 0, 'images' => []], 404);
        }
    } else {
        jsonResponse(false, 'ID o gym_id non fornito', [], 400);
    }
}

/**
 * Gestisce le richieste POST
 */
function handlePost() {
    global $gym, $gym_image, $jwt_auth;
    
    // Verifica l'autenticazione
    $user_data = isAuthenticated($jwt_auth);
    if (!$user_data) {
        jsonResponse(false, 'Non autorizzato', [], 401);
    }
    
    // Ottieni i parametri dalla query string
    $gym_id = isset($_GET['gym_id']) ? intval($_GET['gym_id']) : 0;
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    // Verifica che l'ID della palestra sia valido
    if ($gym_id <= 0) {
        jsonResponse(false, 'ID palestra non valido', [], 400);
    }
    
    // Imposta l'ID della palestra
    $gym->id = $gym_id;
    
    // Verifica che la palestra esista
    $gym_data = $gym->readOne();
    if (!$gym_data) {
        jsonResponse(false, 'Palestra non trovata', [], 404);
    }
    
    // Verifica che l'utente sia il proprietario della palestra o un amministratore
    if ($gym_data['user_id'] != $user_data['id'] && !hasRole($user_data, 'admin')) {
        jsonResponse(false, 'Non autorizzato a gestire le immagini di questa palestra', [], 403);
    }
    
    // Gestisci le diverse azioni
    if ($action === 'set-main') {
        // Imposta un'immagine come principale
        
        // Ottieni l'ID dell'immagine
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        // Verifica che l'ID dell'immagine sia valido
        if ($id <= 0) {
            jsonResponse(false, 'ID immagine non valido', [], 400);
        }
        
        // Imposta l'ID dell'immagine e della palestra
        $gym_image->id = $id;
        $gym_image->gym_id = $gym_id;
        
        // Verifica che l'immagine esista
        $image_data = $gym_image->readOne();
        if (!$image_data) {
            jsonResponse(false, 'Immagine non trovata', [], 404);
        }
        
        // Verifica che l'immagine appartenga alla palestra
        if ($image_data['gym_id'] != $gym_id) {
            jsonResponse(false, 'L\'immagine non appartiene a questa palestra', [], 400);
        }
        
        // Imposta l'immagine come principale
        if ($gym_image->setAsMain()) {
            jsonResponse(true, 'Immagine impostata come principale con successo');
        } else {
            jsonResponse(false, 'Errore durante l\'impostazione dell\'immagine come principale', [], 500);
        }
    } else {
        // Carica una nuova immagine
        
        // Verifica che ci sia un file
        if (!isset($_FILES['image'])) {
            jsonResponse(false, 'Nessun file caricato', [], 400);
        }
        
        // Verifica che il file sia valido
        $validation = validateFile($_FILES['image']);
        if (!$validation[0]) {
            jsonResponse(false, $validation[1], [], 400);
        }
        
        // Genera un nome file univoco
        $filename = generateUniqueFilename($_FILES['image']['name']);
        
        // Percorso di destinazione
        $upload_dir = UPLOAD_DIR . 'gyms/';
        $destination = $upload_dir . $filename;
        
        // Sposta il file
        if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
            // Imposta i dati dell'immagine
            $gym_image->gym_id = $gym_id;
            $gym_image->percorso_immagine = 'uploads/gyms/' . $filename;
            $gym_image->descrizione = isset($_POST['descrizione']) ? sanitizeInput($_POST['descrizione']) : null;
            $gym_image->principale = isset($_POST['principale']) && $_POST['principale'] === 'true' ? true : false;
            
            // Crea l'immagine
            if ($gym_image->create()) {
                jsonResponse(true, 'Immagine caricata con successo', ['image_id' => $gym_image->id]);
            } else {
                // Elimina il file se l'inserimento nel database fallisce
                unlink($destination);
                jsonResponse(false, 'Errore durante il caricamento dell\'immagine', [], 500);
            }
        } else {
            jsonResponse(false, 'Errore durante lo spostamento del file', [], 500);
        }
    }
}

/**
 * Gestisce le richieste PUT
 */
function handlePut() {
    global $gym, $gym_image, $jwt_auth;
    
    // Verifica l'autenticazione
    $user_data = isAuthenticated($jwt_auth);
    if (!$user_data) {
        jsonResponse(false, 'Non autorizzato', [], 401);
    }
    
    // Ottieni l'ID dell'immagine
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    // Verifica che l'ID dell'immagine sia valido
    if ($id <= 0) {
        jsonResponse(false, 'ID immagine non valido', [], 400);
    }
    
    // Imposta l'ID dell'immagine
    $gym_image->id = $id;
    
    // Verifica che l'immagine esista
    $image_data = $gym_image->readOne();
    if (!$image_data) {
        jsonResponse(false, 'Immagine non trovata', [], 404);
    }
    
    // Ottieni i dati della palestra
    $gym->id = $image_data['gym_id'];
    $gym_data = $gym->readOne();
    
    // Verifica che l'utente sia il proprietario della palestra o un amministratore
    if ($gym_data['user_id'] != $user_data['id'] && !hasRole($user_data, 'admin')) {
        jsonResponse(false, 'Non autorizzato a modificare questa immagine', [], 403);
    }
    
    // Ottieni i dati JSON dalla richiesta
    $data = getJsonData();
    
    // Verifica che ci siano i dati necessari
    if (empty($data)) {
        jsonResponse(false, 'Dati non forniti', [], 400);
    }
    
    // Imposta i dati dell'immagine
    if (isset($data['descrizione'])) {
        $gym_image->descrizione = sanitizeInput($data['descrizione']);
    }
    
    // Aggiorna l'immagine
    if ($gym_image->update()) {
        jsonResponse(true, 'Immagine aggiornata con successo');
    } else {
        jsonResponse(false, 'Errore durante l\'aggiornamento dell\'immagine', [], 500);
    }
}

/**
 * Gestisce le richieste DELETE
 */
function handleDelete() {
    global $gym, $gym_image, $jwt_auth;
    
    // Verifica l'autenticazione
    $user_data = isAuthenticated($jwt_auth);
    if (!$user_data) {
        jsonResponse(false, 'Non autorizzato', [], 401);
    }
    
    // Ottieni l'ID dell'immagine
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    // Verifica che l'ID dell'immagine sia valido
    if ($id <= 0) {
        jsonResponse(false, 'ID immagine non valido', [], 400);
    }
    
    // Imposta l'ID dell'immagine
    $gym_image->id = $id;
    
    // Verifica che l'immagine esista
    $image_data = $gym_image->readOne();
    if (!$image_data) {
        jsonResponse(false, 'Immagine non trovata', [], 404);
    }
    
    // Ottieni i dati della palestra
    $gym->id = $image_data['gym_id'];
    $gym_data = $gym->readOne();
    
    // Verifica che l'utente sia il proprietario della palestra o un amministratore
    if ($gym_data['user_id'] != $user_data['id'] && !hasRole($user_data, 'admin')) {
        jsonResponse(false, 'Non autorizzato a eliminare questa immagine', [], 403);
    }
    
    // Percorso del file
    $file_path = __DIR__ . '/backend/' . $image_data['percorso_immagine'];
    
    // Elimina l'immagine dal database
    if ($gym_image->delete()) {
        // Elimina il file
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        jsonResponse(true, 'Immagine eliminata con successo');
    } else {
        jsonResponse(false, 'Errore durante l\'eliminazione dell\'immagine', [], 500);
    }
}
