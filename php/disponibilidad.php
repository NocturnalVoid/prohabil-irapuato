<?php
// php/disponibilidad.php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

if (empty($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'trabajador') {
    json_response(false, 'No autorizado.');
}

$input = json_decode(file_get_contents('php://input'), true);
$disponible = isset($input['disponible']) ? (int)$input['disponible'] : 0;

db()->prepare('UPDATE trabajadores SET disponible = ? WHERE usuario_id = ?')
   ->execute([$disponible, $_SESSION['usuario_id']]);

json_response(true, 'Disponibilidad actualizada.');
