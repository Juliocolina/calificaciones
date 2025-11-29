<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
require_once __DIR__ . '/../../modelos/PnfModel.php';

verificarSesion();

$conn = conectar();
if (!$conn) {
    redirigir('error', 'No se pudo establecer conexión con la BD.', 'home.php');
    exit;
}

$pnfModel = new PnfModel($conn);

try {
    $pnfs = $pnfModel->obtenerTodos();
} catch (PDOException $e) {
    redirigir('error', 'Error al obtener PNFs: ' . $e->getMessage(), 'home.php');
    exit;
}