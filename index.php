<?php
/**
 * Index principale per le API RESTful
 * 
 * Questo file gestisce tutte le richieste API e le indirizza ai controller appropriati
 */

// Includi i file necessari
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/jwt_auth.php';
require_once __DIR__ . '/includes/utils.php';

// Gestione delle richieste OPTIONS per CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: ' . getAllowedOrigin());
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Max-Age: 86400'); // 24 ore
    exit;
}

// Ottieni il percorso richiesto
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = '/api';

// Rimuovi il percorso base e i parametri di query
$path = parse_url($request_uri, PHP_URL_PATH);
$path = str_replace($base_path, '', $path);
$path = trim($path, '/');

// Ottieni il metodo HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Inizializza le classi necessarie
$database = new Database();
$db = $database->getConnection();
$jwt_auth = new JwtAuth();

// Routing delle richieste
$routes = [
    'users' => 'api/users.php',
    'gyms' => 'api/gyms.php',
    'bookings' => 'api/bookings.php',
    'reviews' => 'api/reviews.php',
    'images' => 'api/images.php',
    'auth/login' => 'api/auth/login.php',
    'auth/register' => 'api/auth/register.php'
];

// Estrai la prima parte del percorso per il routing
$route_parts = explode('/', $path);
$route = $route_parts[0];

// Se c'è un secondo segmento, potrebbe essere un'azione specifica
if (isset($route_parts[1])) {
    // Controlla se esiste una rotta specifica per questo percorso
    if (isset($routes[$route . '/' . $route_parts[1]])) {
        $route = $route . '/' . $route_parts[1];
    }
}

// Verifica se la rotta esiste
if (isset($routes[$route])) {
    // Includi il file della rotta
    require_once __DIR__ . '/' . $routes[$route];
} else {
    // Rotta non trovata
    jsonResponse(false, 'Endpoint non trovato', [], 404);
}
