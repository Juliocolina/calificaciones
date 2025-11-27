<?php
require_once __DIR__ . '/../config/conexion.php';

header('Content-Type: application/json');

if (!isset($_GET['oferta_id']) || empty($_GET['oferta_id'])) {
    echo json_encode([]);
    exit;
}

$oferta_id = (int)$_GET['oferta_id'];

try {
    $conn = conectar();
    
    // Obtener el PNF de la oferta y luego las materias de ese PNF
    $stmt = $conn->prepare("
        SELECT m.id, m.nombre, m.creditos, m.duracion
        FROM materias m
        JOIN oferta_academica oa ON m.pnf_id = oa.pnf_id
        WHERE oa.id = ?
        ORDER BY m.nombre ASC
    ");
    $stmt->execute([$oferta_id]);
    $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($materias);
} catch (PDOException $e) {
    echo json_encode([]);
}
?>