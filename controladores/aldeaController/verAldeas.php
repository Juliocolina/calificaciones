<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
require_once __DIR__ . '/../../modelos/AldeaModel.php';

verificarRol(['admin']);

$conn = conectar();
if (!$conn) {
    redirigir('error', 'No se pudo establecer conexión con la BD.', 'home.php');
    exit;
}

// Usar el modelo para obtener las aldeas
$aldeaModel = new AldeaModel($conn);
$aldeas = $aldeaModel->obtenerTodas();

