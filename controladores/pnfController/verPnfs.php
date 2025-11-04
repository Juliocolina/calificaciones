<?php

require_once '../../config/conexion.php';
$conn = conectar();

// 🔐 Validar sesión activa
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../index.php");
    exit;
}

try {
    $sql = "SELECT * FROM pnfs ORDER BY nombre ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $pnfs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al obtener PNFs: " . $e->getMessage());
    $pnfs = [];
}

include '../../vistas/pnfs/verPNFs.php';
