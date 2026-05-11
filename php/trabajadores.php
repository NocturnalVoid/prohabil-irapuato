<?php
// ============================================
// trabajadores.php — Listar trabajadores disponibles
// GET /php/trabajadores.php?oficio=1
// ============================================
require_once __DIR__ . '/config.php';

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = db();

    $oficio_id = isset($_GET['oficio']) ? (int) $_GET['oficio'] : null;

    $sql = 'SELECT * FROM vista_trabajadores WHERE disponible = 1';
    $params = [];

    if ($oficio_id) {
        // Necesitamos filtrar por oficio_id — buscamos en la vista
        $sql .= ' AND trabajador_id IN (
            SELECT t.id FROM trabajadores t
            JOIN oficios o ON t.oficio_id = o.id
            WHERE o.id = ?
        )';
        $params[] = $oficio_id;
    }

    $sql .= ' ORDER BY calificacion_promedio DESC, total_trabajos DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $trabajadores = $stmt->fetchAll();

    json_response(true, 'OK', $trabajadores);

} catch (PDOException $e) {
    json_response(false, 'Error: ' . $e->getMessage());
}
