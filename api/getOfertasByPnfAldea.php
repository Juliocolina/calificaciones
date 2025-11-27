<?php
header('Content-Type: application/json');
require_once '../config/conexion.php';

$pnf_id = intval($_GET['pnf_id'] ?? 0);
$aldea_id = intval($_GET['aldea_id'] ?? 0);

if (!$pnf_id || !$aldea_id) {
    echo json_encode([]);
    exit;
}

try {
    $pdo = conectar();
    
    $stmt = $pdo->prepare("
        SELECT 
            oa.id,
            CONCAT(a.nombre, ' - ', p.nombre, ' - ', t.slug, ' - ', tr.nombre) as descripcion
        FROM oferta_academica oa
        JOIN aldeas a ON oa.aldea_id = a.id
        JOIN pnfs p ON oa.pnf_id = p.id
        JOIN trayectos t ON oa.trayecto_id = t.id
        JOIN trimestres tr ON oa.trimestre_id = tr.id
        WHERE oa.pnf_id = ? AND oa.aldea_id = ? AND oa.estatus = 'Abierto'
        ORDER BY t.id, tr.nombre
    ");
    
    $stmt->execute([$pnf_id, $aldea_id]);
    $ofertas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($ofertas);
    
} catch (Exception $e) {
    echo json_encode([]);
}
?>