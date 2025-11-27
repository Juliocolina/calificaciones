<?php
require_once '../config/conexion.php';

$pnf_id = $_GET['pnf_id'] ?? '';

if ($pnf_id) {
    $pdo = conectar();
    $stmt = $pdo->prepare("SELECT id, nombre FROM materias WHERE pnf_id = ? ORDER BY nombre");
    $stmt->execute([$pnf_id]);
    $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($materias);
} else {
    echo json_encode([]);
}
?>