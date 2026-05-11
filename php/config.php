<?php
// ============================================
// config.php — Configuración central de ProHabil
// ============================================

// --- Base de datos ---
define('DB_HOST', 'bd');
define('DB_NAME', 'prohabil_db');
define('DB_USER', 'root');       // Cambia por tu usuario de phpMyAdmin
define('DB_PASS', 'goldenfan666');           // Cambia por tu contraseña
define('DB_CHARSET', 'utf8mb4');

// --- App ---
define('APP_NAME', 'ProHabil');
define('APP_URL',  'http://localhost/prohabil');  // Cambia si es otro dominio
define('SESSION_LIFETIME', 60 * 60 * 24 * 7);    // 7 días

// --- Seguridad ---
define('BCRYPT_COST', 12);

// --- Conexión PDO singleton ---
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

// --- Sesión segura ---
session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME,
    'path'     => '/',
    'secure'   => false, // true en producción con HTTPS
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

// --- Helper: respuesta JSON ---
function json_response(bool $ok, string $msg, array $data = []): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => $ok, 'mensaje' => $msg, 'data' => $data]);
    exit;
}

// --- Helper: sanitizar entrada ---
function clean(string $value): string {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}
