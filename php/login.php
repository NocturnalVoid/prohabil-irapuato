<?php
// ============================================
// login.php — Endpoint de inicio de sesión
// POST /php/login.php
// ============================================
require_once __DIR__ . '/config.php';

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Método no permitido.');
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

if (empty($input['correo']) || empty($input['contrasena'])) {
    json_response(false, 'Correo y contraseña son requeridos.');
}

$correo = filter_var(trim($input['correo']), FILTER_VALIDATE_EMAIL);
if (!$correo) json_response(false, 'Correo inválido.');

try {
    $pdo = db();

    $stmt = $pdo->prepare('
        SELECT id, nombre, apellido_paterno, tipo, contrasena, activo
        FROM usuarios
        WHERE correo = ?
        LIMIT 1
    ');
    $stmt->execute([$correo]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($input['contrasena'], $user['contrasena'])) {
        json_response(false, 'Correo o contraseña incorrectos.');
    }

    if (!$user['activo']) {
        json_response(false, 'Tu cuenta ha sido desactivada. Contáctanos.');
    }

    // Actualizar último login
    $pdo->prepare('UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?')
        ->execute([$user['id']]);

    // Sesión
    $_SESSION['usuario_id']     = $user['id'];
    $_SESSION['usuario_tipo']   = $user['tipo'];
    $_SESSION['usuario_nombre'] = $user['nombre'];

    json_response(true, 'Sesión iniciada correctamente.', [
        'usuario_id' => $user['id'],
        'tipo'       => $user['tipo'],
        'nombre'     => $user['nombre'] . ' ' . $user['apellido_paterno'],
    ]);

} catch (PDOException $e) {
    json_response(false, 'Error en la base de datos: ' . $e->getMessage());
}
