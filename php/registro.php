<?php
// ============================================
// registro.php — Endpoint de registro de usuarios
// POST /php/registro.php
// ============================================
require_once __DIR__ . '/config.php';

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Método no permitido.');
}

// Leer cuerpo JSON o form-data
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

// --- Campos requeridos ---
$required = ['tipo','nombre','apellido_paterno','telefono','correo','contrasena'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        json_response(false, "El campo '$field' es requerido.");
    }
}

// --- Validaciones ---
$tipo   = in_array($input['tipo'], ['cliente','trabajador']) ? $input['tipo'] : null;
if (!$tipo) json_response(false, 'Tipo de usuario inválido.');

$correo = filter_var(trim($input['correo']), FILTER_VALIDATE_EMAIL);
if (!$correo) json_response(false, 'Correo electrónico inválido.');

$tel = preg_replace('/\D/', '', $input['telefono']);
if (strlen($tel) < 10 || strlen($tel) > 15) {
    json_response(false, 'Número de teléfono inválido (10 dígitos).');
}

$pass = $input['contrasena'];
if (strlen($pass) < 8) {
    json_response(false, 'La contraseña debe tener al menos 8 caracteres.');
}

try {
    $pdo = db();

    // Verificar correo duplicado
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE correo = ?');
    $stmt->execute([$correo]);
    if ($stmt->fetch()) {
        json_response(false, 'Este correo ya está registrado.');
    }

    // Hash de contraseña
    $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);

    // Insertar usuario
    $stmt = $pdo->prepare('
        INSERT INTO usuarios
          (tipo, nombre, apellido_paterno, apellido_materno,
           telefono, correo, contrasena, colonia, direccion,
           fecha_nacimiento, genero)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)
    ');
    $stmt->execute([
        $tipo,
        clean($input['nombre']),
        clean($input['apellido_paterno']),
        clean($input['apellido_materno'] ?? ''),
        $tel,
        $correo,
        $hash,
        clean($input['colonia'] ?? ''),
        clean($input['direccion'] ?? ''),
        !empty($input['fecha_nacimiento']) ? $input['fecha_nacimiento'] : null,
        $input['genero'] ?? 'prefiero_no_decir',
    ]);

    $usuario_id = (int) $pdo->lastInsertId();

    // Si es trabajador, insertar en tabla trabajadores
    if ($tipo === 'trabajador' && !empty($input['oficio_id'])) {
        $stmt2 = $pdo->prepare('
            INSERT INTO trabajadores
              (usuario_id, oficio_id, descripcion, años_experiencia, precio_hora, precio_dia)
            VALUES (?,?,?,?,?,?)
        ');
        $stmt2->execute([
            $usuario_id,
            (int) $input['oficio_id'],
            clean($input['descripcion'] ?? ''),
            (int) ($input['años_experiencia'] ?? 0),
            !empty($input['precio_hora']) ? (float) $input['precio_hora'] : null,
            !empty($input['precio_dia'])  ? (float) $input['precio_dia']  : null,
        ]);
    }

    // Iniciar sesión automáticamente
    $_SESSION['usuario_id']   = $usuario_id;
    $_SESSION['usuario_tipo'] = $tipo;
    $_SESSION['usuario_nombre'] = clean($input['nombre']);

    json_response(true, '¡Registro exitoso! Bienvenido a ProHabil.', [
        'usuario_id' => $usuario_id,
        'tipo'       => $tipo,
        'nombre'     => clean($input['nombre']),
    ]);

} catch (PDOException $e) {
    json_response(false, 'Error en la base de datos: ' . $e->getMessage());
}
