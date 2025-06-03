<?php
/**
 * Configurazione dell'applicazione GymFinder
 * 
 * Questo file contiene le configurazioni principali dell'applicazione
 */

// Configurazione del database
define('DB_HOST', 'localhost');
define('DB_NAME', 'gymfinder');
define('DB_USER', 'root');
define('DB_PASS', 'password');

// Configurazione dell'applicazione
define('APP_NAME', 'GymFinder');
define('APP_URL', 'http://localhost/GymFinder_Vue_PHP');
define('API_URL', 'http://localhost/GymFinder_Vue_PHP/backend/api');

// Configurazione JWT
define('JWT_SECRET', 'your_jwt_secret_key_here');
define('JWT_EXPIRATION', 3600); // 1 ora in secondi

// Configurazione upload
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Configurazione CORS
$allowed_origins = [
    'http://localhost:8080',
    'http://localhost:8081',
    'http://localhost:5173',
    'http://localhost:3000',
    'http://localhost',
    'http://localhost/GymFinder_Vue_PHP'
];

define('ALLOWED_ORIGINS', $allowed_origins);

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400'); // 24 hours
}

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Configurazione timezone
date_default_timezone_set('Europe/Rome');

// Configurazione errori
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Abilita il logging degli errori
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');
