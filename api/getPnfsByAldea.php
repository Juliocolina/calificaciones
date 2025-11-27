<?php
require_once '../config/conexion.php';

$aldea_id = $_GET['aldea_id'] ?? '';

if ($aldea_id) {
    $pdo = conectar();
    $stmt = $pdo->prepare("SELECT id, nombre FROM pnfs WHERE aldea_id = ? ORDER BY nombre");
    $stmt->execute([$aldea_id]);
    $pnfs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($pnfs);
} else {
    echo json_encode([]);
}
?>