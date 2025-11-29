<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
require_once __DIR__ . '/../../modelos/TrayectoModel.php';

verificarRol(['admin']);

$conn = conectar();
if (!$conn) {
    redirigir('error', 'No se pudo establecer conexión con la BD.', 'trayectos/crearTrayecto.php');
    exit;
}

$trayectoModel = new TrayectoModel($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre_trayecto'] ?? '');
    $slug = trim($_POST['slug_trayecto'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    if (empty($nombre) || empty($slug)) {
        redirigir('error', 'Todos los campos son obligatorios.', 'trayectos/crearTrayecto.php');
        exit;
    }

    try {
        if ($trayectoModel->crearTrayecto($nombre, $slug, $descripcion)) {
            redirigir('exito', 'Trayecto creado exitosamente.', 'trayectos/verTrayectos.php');
        } else {
            redirigir('error', 'No se pudo crear el trayecto.', 'trayectos/crearTrayecto.php');
        }

    } catch (PDOException $e) {
        redirigir('error', 'Error al crear trayecto: ' . $e->getMessage(), 'trayectos/crearTrayecto.php');
    }
}

exit;