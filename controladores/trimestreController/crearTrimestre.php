<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
require_once __DIR__ . '/../../modelos/TrimestreModel.php';

verificarRol(['admin']);

$conn = conectar();
if (!$conn) {
    redirigir('error', 'No se pudo establecer conexión con la BD.', 'trimestres/crearTrimestre.php');
    exit;
}

$trimestreModel = new TrimestreModel($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $fecha_inicio = trim($_POST['fecha_inicio'] ?? '');
    $fecha_fin = trim($_POST['fecha_fin'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    if (empty($nombre) || empty($fecha_inicio) || empty($fecha_fin)) {
        redirigir('error', 'Todos los campos son obligatorios.', 'trimestres/crearTrimestre.php');
        exit;
    }

    try {
        if ($trimestreModel->existeTrimestre($nombre)) {
            redirigir('error', 'Ya existe un trimestre con ese nombre.', 'trimestres/crearTrimestre.php');
            exit;
        }

        if ($trimestreModel->crearTrimestre($nombre, $fecha_inicio, $fecha_fin, $descripcion)) {
            redirigir('exito', 'Trimestre creado exitosamente.', 'trimestres/verTrimestres.php');
        } else {
            redirigir('error', 'No se pudo crear el trimestre.', 'trimestres/crearTrimestre.php');
        }

    } catch (PDOException $e) {
        redirigir('error', 'Error al crear trimestre: ' . $e->getMessage(), 'trimestres/crearTrimestre.php');
    }
}

exit;