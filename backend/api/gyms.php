<?php
/**
 * API per la gestione delle palestre
 * 
 * Questo file gestisce le operazioni CRUD per le palestre
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

// Configurazione CORS
$allowed_origins = array(
    'http://localhost:8081',
    'http://localhost'
);

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

if (in_array($origin, $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Credentials: true');
}

// Gestione delle richieste OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

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
    global $gym, $gym_image;
    
    // Ottieni i parametri dalla query string
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    $citta = isset($_GET['citta']) ? sanitizeInput($_GET['citta']) : '';
    
    // Gestisci le diverse azioni
    if ($action === 'search' && !empty($citta)) {
        // Cerca palestre per città
        $results = $gym->searchByCity($citta);
        
        if ($results) {
            jsonResponse(true, 'Palestre trovate con successo', [
                'count' => count($results),
                'gyms' => $results
            ]);
            return;
        }
        
        jsonResponse(false, 'Nessuna palestra trovata', ['count' => 0, 'gyms' => []], 404);
    } elseif ($action === 'my') {
        // Verifica l'autenticazione
        global $jwt_auth;
        $user_data = isAuthenticated($jwt_auth);
        if (!$user_data) {
            jsonResponse(false, 'Non autorizzato', [], 401);
        }
        
        // Verifica che l'utente sia un gestore palestra
        if (!hasRole($user_data, 'palestra')) {
            jsonResponse(false, 'Non autorizzato ad accedere a questi dati', [], 403);
        }
        
        // Ottieni le palestre dell'utente
        $gym->user_id = $user_data['id'];
        $results = $gym->readByUser();
        
        if ($results) {
            // Per ogni palestra, ottieni l'immagine principale
            foreach ($results as &$g) {
                $gym_image->gym_id = $g['id'];
                $main_image = $gym_image->getMainImage();
                $g['immagine_principale'] = $main_image ? $main_image['percorso_immagine'] : null;
            }
            
            jsonResponse(true, 'Palestre ottenute con successo', [
                'count' => count($results),
                'gyms' => $results
            ]);
        } else {
            jsonResponse(false, 'Nessuna palestra trovata', ['count' => 0, 'gyms' => []], 404);
        }
    } elseif ($id > 0) {
        // Ottieni una singola palestra
        $gym->id = $id;
        $result = $gym->readOne();
        
        if ($result) {
            // Ottieni le immagini della palestra
            $gym_image->gym_id = $id;
            $images = $gym_image->readByGym();
            $result['immagini'] = $images ?: [];
            
            // Ottieni la valutazione media e il numero di recensioni
            $ratings = $gym->getRatings();
            $result['valutazione_media'] = $ratings['valutazione_media'];
            $result['recensioni_count'] = $ratings['recensioni_count'];
            
            jsonResponse(true, 'Palestra ottenuta con successo', ['gym' => $result]);
        } else {
            jsonResponse(false, 'Palestra non trovata', [], 404);
        }
    } else {
        // Ottieni tutte le palestre
        $results = $gym->readAll();
        
        if ($results) {
            // Per ogni palestra, ottieni l'immagine principale
            foreach ($results as &$g) {
                $gym_image->gym_id = $g['id'];
                $main_image = $gym_image->getMainImage();
                $g['immagine_principale'] = $main_image ? $main_image['percorso_immagine'] : null;
                
                // Ottieni la valutazione media e il numero di recensioni
                $gym->id = $g['id'];
                $ratings = $gym->getRatings();
                $g['valutazione_media'] = $ratings['valutazione_media'];
                $g['recensioni_count'] = $ratings['recensioni_count'];
            }
            
            jsonResponse(true, 'Palestre ottenute con successo', [
                'count' => count($results),
                'gyms' => $results
            ]);
        } else {
            jsonResponse(false, 'Nessuna palestra trovata', ['count' => 0, 'gyms' => []], 404);
        }
    }
}

/**
 * Gestisce le richieste POST
 */
function handlePost() {
    global $gym, $jwt_auth;
    
    // Verifica l'autenticazione
    $user_data = isAuthenticated($jwt_auth);
    if (!$user_data) {
        jsonResponse(false, 'Non autorizzato', [], 401);
        return;
    }
    
    // Verifica che l'utente sia un gestore palestra
    if (!hasRole($user_data, 'palestra')) {
        jsonResponse(false, 'Non autorizzato a gestire le palestre', [], 403);
        return;
    }
    
    // Gestisci l'upload dell'immagine
    $upload_dir = __DIR__ . '/../uploads/gyms/';
    $image_path = null;
    
    if (isset($_FILES['immagine']) && $_FILES['immagine']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['immagine'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Verifica il tipo di file
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($file_extension, $allowed_types)) {
            jsonResponse(false, 'Tipo di file non supportato. Usa: ' . implode(', ', $allowed_types), [], 400);
            return;
        }
        
        // Genera un nome file unico
        $new_filename = uniqid('gym_') . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;
        
        // Sposta il file
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            $image_path = 'uploads/gyms/' . $new_filename;
        } else {
            jsonResponse(false, 'Errore durante il salvataggio dell\'immagine', [], 500);
            return;
        }
    }
    
    // Imposta i dati della palestra
    $gym->user_id = $user_data['id'];
    $gym->nome = $_POST['nome'] ?? null;
    $gym->indirizzo = $_POST['indirizzo'] ?? null;
    $gym->citta = $_POST['citta'] ?? null;
    $gym->cap = $_POST['cap'] ?? null;
    $gym->telefono = $_POST['telefono'] ?? null;
    $gym->email = $_POST['email'] ?? null;
    $gym->descrizione = $_POST['descrizione'] ?? null;
    $gym->prezzo_mensile = $_POST['prezzo_mensile'] ?? null;
    $gym->orario_apertura = $_POST['orario_apertura'] ?? null;
    $gym->orario_chiusura = $_POST['orario_chiusura'] ?? null;
    $gym->giorni_apertura = $_POST['giorni_apertura'] ?? null;
    
    // Se c'è un'immagine, imposta il percorso
    if ($image_path) {
        $gym->immagine_profilo = $image_path;
    }
    
    // Verifica campi obbligatori
    if (!$gym->nome || !$gym->indirizzo || !$gym->citta || !$gym->prezzo_mensile || 
        !$gym->orario_apertura || !$gym->orario_chiusura || !$gym->giorni_apertura) {
        jsonResponse(false, 'Campi obbligatori mancanti', [], 400);
        return;
    }
    
    // Crea la palestra
    if ($gym->create()) {
        // Se c'è un'immagine, salvala nella tabella gym_images
        if (isset($_FILES['immagine']) && $_FILES['immagine']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['immagine'];
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            // Verifica il tipo di file
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($file_extension, $allowed_types)) {
                jsonResponse(false, 'Tipo di file non supportato. Usa: ' . implode(', ', $allowed_types), [], 400);
                return;
            }
            
            // Genera un nome file unico
            $new_filename = uniqid('gym_') . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            // Sposta il file
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $image_path = 'uploads/gyms/' . $new_filename;
                
                // Inserisci l'immagine nel database
                $query = "INSERT INTO gym_images (gym_id, percorso_immagine, descrizione, principale) 
                         VALUES (:gym_id, :percorso_immagine, :descrizione, TRUE)";
                
                try {
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(":gym_id", $gym->id, PDO::PARAM_INT);
                    $stmt->bindParam(":percorso_immagine", $image_path, PDO::PARAM_STR);
                    $stmt->bindParam(":descrizione", $gym->nome, PDO::PARAM_STR);
                    
                    if (!$stmt->execute()) {
                        // Se fallisce l'inserimento dell'immagine, elimina il file
                        unlink($upload_path);
                        jsonResponse(false, 'Errore durante il salvataggio dell\'immagine nel database', [], 500);
                        return;
                    }
                } catch (Exception $e) {
                    // Se c'è un errore, elimina il file
                    unlink($upload_path);
                    error_log("Errore durante l'inserimento dell'immagine: " . $e->getMessage());
                    jsonResponse(false, 'Errore durante il salvataggio dell\'immagine', [], 500);
                    return;
                }
            } else {
                jsonResponse(false, 'Errore durante il salvataggio dell\'immagine', [], 500);
                return;
            }
        }
        
        jsonResponse(true, 'Palestra creata con successo', ['gym_id' => $gym->id]);
    } else {
        jsonResponse(false, 'Errore durante la creazione della palestra', [], 500);
    }
}

/**
 * Gestisce le richieste PUT
 */
function handlePut() {
    global $gym, $jwt_auth;
    
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
    
    // Imposta l'ID della palestra
    $gym->id = $id;
    
    // Verifica che la palestra esista
    $gym_data = $gym->readOne();
    if (!$gym_data) {
        jsonResponse(false, 'Palestra non trovata', [], 404);
    }
    
    // Verifica che l'utente sia il proprietario della palestra o un amministratore
    if ($gym_data['user_id'] != $user_data['id'] && !hasRole($user_data, 'admin')) {
        jsonResponse(false, 'Non autorizzato a modificare questa palestra', [], 403);
    }
    
    // Ottieni i dati JSON dalla richiesta
    $data = getJsonData();
    
    // Verifica che ci siano i dati necessari
    if (empty($data)) {
        jsonResponse(false, 'Dati non forniti', [], 400);
    }
    
    // Imposta i dati della palestra
    if (isset($data['nome'])) {
        $gym->nome = sanitizeInput($data['nome']);
    }
    
    if (isset($data['indirizzo'])) {
        $gym->indirizzo = sanitizeInput($data['indirizzo']);
    }
    
    if (isset($data['citta'])) {
        $gym->citta = sanitizeInput($data['citta']);
    }
    
    if (isset($data['cap'])) {
        $gym->cap = sanitizeInput($data['cap']);
    }
    
    if (isset($data['telefono'])) {
        $gym->telefono = sanitizeInput($data['telefono']);
    }
    
    if (isset($data['email'])) {
        $gym->email = sanitizeInput($data['email']);
    }
    
    if (isset($data['descrizione'])) {
        $gym->descrizione = sanitizeInput($data['descrizione']);
    }
    
    if (isset($data['prezzo_mensile'])) {
        $gym->prezzo_mensile = floatval($data['prezzo_mensile']);
    }
    
    if (isset($data['orario_apertura'])) {
        $gym->orario_apertura = $data['orario_apertura'];
    }
    
    if (isset($data['orario_chiusura'])) {
        $gym->orario_chiusura = $data['orario_chiusura'];
    }
    
    if (isset($data['giorni_apertura'])) {
        $gym->giorni_apertura = $data['giorni_apertura'];
    }
    
    // Aggiorna la palestra
    if ($gym->update()) {
        // Nel metodo handlePut, dopo la verifica dell'autorizzazione
        if (isset($_FILES['immagine']) && $_FILES['immagine']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['immagine'];
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            // Verifica il tipo di file
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($file_extension, $allowed_types)) {
                jsonResponse(false, 'Tipo di file non supportato. Usa: ' . implode(', ', $allowed_types), [], 400);
                return;
            }
            
            // Genera un nome file unico
            $new_filename = uniqid('gym_') . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            // Sposta il file
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $image_path = 'uploads/gyms/' . $new_filename;
                
                try {
                    // Verifica se esiste già un'immagine principale
                    $check_query = "SELECT id, percorso_immagine FROM gym_images WHERE gym_id = :gym_id AND principale = TRUE";
                    $check_stmt = $db->prepare($check_query);
                    $check_stmt->bindParam(":gym_id", $id, PDO::PARAM_INT);
                    $check_stmt->execute();
                    $existing_image = $check_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($existing_image) {
                        // Aggiorna l'immagine esistente
                        $update_query = "UPDATE gym_images 
                                       SET percorso_immagine = :percorso_immagine,
                                           descrizione = :descrizione
                                       WHERE id = :id";
                        
                        $update_stmt = $db->prepare($update_query);
                        $update_stmt->bindParam(":id", $existing_image['id'], PDO::PARAM_INT);
                        $update_stmt->bindParam(":percorso_immagine", $image_path, PDO::PARAM_STR);
                        $update_stmt->bindParam(":descrizione", $gym->nome, PDO::PARAM_STR);
                        
                        if ($update_stmt->execute()) {
                            // Elimina il vecchio file
                            if (file_exists(__DIR__ . '/../' . $existing_image['percorso_immagine'])) {
                                unlink(__DIR__ . '/../' . $existing_image['percorso_immagine']);
                            }
                        } else {
                            // Se fallisce l'aggiornamento, elimina il nuovo file
                            unlink($upload_path);
                            jsonResponse(false, "Errore durante l'aggiornamento dell'immagine nel database", [], 500);
                            return;
                        }
                    } else {
                        // Inserisci una nuova immagine
                        $insert_query = "INSERT INTO gym_images (gym_id, percorso_immagine, descrizione, principale) 
                                       VALUES (:gym_id, :percorso_immagine, :descrizione, TRUE)";
                        
                        $insert_stmt = $db->prepare($insert_query);
                        $insert_stmt->bindParam(":gym_id", $id, PDO::PARAM_INT);
                        $insert_stmt->bindParam(":percorso_immagine", $image_path, PDO::PARAM_STR);
                        $insert_stmt->bindParam(":descrizione", $gym->nome, PDO::PARAM_STR);
                        
                        if (!$insert_stmt->execute()) {
                            // Se fallisce l'inserimento, elimina il file
                            unlink($upload_path);
                            jsonResponse(false, 'Errore durante il salvataggio dell\'immagine nel database', [], 500);
                            return;
                        }
                    }
                } catch (Exception $e) {
                    // Se c'è un errore, elimina il file
                    unlink($upload_path);
                    error_log("Errore durante la gestione dell'immagine: " . $e->getMessage());
                    jsonResponse(false, 'Errore durante la gestione dell\'immagine', [], 500);
                    return;
                }
            } else {
                jsonResponse(false, 'Errore durante il salvataggio dell\'immagine', [], 500);
                return;
            }
        }
        
        jsonResponse(true, 'Palestra aggiornata con successo');
    } else {
        jsonResponse(false, 'Errore durante l\'aggiornamento della palestra', [], 500);
    }
}

/**
 * Gestisce le richieste DELETE
 */
function handleDelete() {
    global $gym, $jwt_auth;
    
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
    
    // Imposta l'ID della palestra
    $gym->id = $id;
    
    // Verifica che la palestra esista
    $gym_data = $gym->readOne();
    if (!$gym_data) {
        jsonResponse(false, 'Palestra non trovata', [], 404);
    }
    
    // Verifica che l'utente sia il proprietario della palestra o un amministratore
    if ($gym_data['user_id'] != $user_data['id'] && !hasRole($user_data, 'admin')) {
        jsonResponse(false, 'Non autorizzato a eliminare questa palestra', [], 403);
    }
    
    // Elimina la palestra
    if ($gym->delete()) {
        jsonResponse(true, 'Palestra eliminata con successo');
    } else {
        jsonResponse(false, 'Errore durante l\'eliminazione della palestra', [], 500);
    }
}
