<?php
require_once __DIR__ . '/../../config/conexion.php';
$conn = conectar();

try {
    $aldea_id = intval($_GET['aldea_id'] ?? 0);
    
    if ($aldea_id > 0) {
        $sql = "SELECT p.*, a.nombre as aldea_nombre FROM pnfs p JOIN aldeas a ON p.aldea_id = a.id WHERE p.aldea_id = ? ORDER BY p.nombre ASC";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$aldea_id]);
    } else {
        $sql = "SELECT p.*, a.nombre as aldea_nombre FROM pnfs p JOIN aldeas a ON p.aldea_id = a.id ORDER BY a.nombre, p.nombre ASC";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
    }
    
    $pnfs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al obtener PNFs: " . $e->getMessage());
    $pnfs = [];
}
