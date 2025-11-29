<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
require_once __DIR__ . '/../../modelos/CoordinadorModel.php';

verificarRol(['admin']);

$conn = conectar();
if (!$conn) {
    redirigir('error', 'No se pudo establecer conexión con la BD.', 'coordinadores/crearCoordinador.php');
    exit;
}

$coordinadorModel = new CoordinadorModel($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $cedula = trim($_POST['cedula'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $aldea_id = intval($_POST['aldea_id'] ?? 0);
    $fecha_inicio = trim($_POST['fecha_inicio'] ?? '');
    $fecha_fin = trim($_POST['fecha_fin'] ?? '') ?: null;
    $descripcion = trim($_POST['descripcion'] ?? '') ?: null;

    if (empty($nombre) || empty($apellido) || empty($cedula) || empty($telefono) || $aldea_id <= 0 || empty($fecha_inicio)) {
        redirigir('error', 'Los campos básicos son obligatorios.', 'coordinadores/crearCoordinador.php');
        exit;
    }

    if (!is_numeric($cedula) || strlen($cedula) < 7) {
        redirigir('error', 'La cédula debe ser numérica y tener al menos 7 dígitos.', 'coordinadores/crearCoordinador.php');
        exit;
    }

    try {
        if ($coordinadorModel->existeCoordinador($cedula)) {
            redirigir('error', 'Ya existe un coordinador con esa cédula.', 'coordinadores/crearCoordinador.php');
            exit;
        }

        if ($coordinadorModel->crearCoordinador($nombre, $apellido, $cedula, $telefono, $aldea_id, $fecha_inicio, $fecha_fin, $descripcion)) {
            redirigir('exito', 'Coordinador registrado exitosamente.', 'coordinadores/verCoordinadores.php');
        } else {
            redirigir('error', 'No se pudo registrar el coordinador.', 'coordinadores/crearCoordinador.php');
        }

    } catch (PDOException $e) {
        redirigir('error', 'Error al crear coordinador: ' . $e->getMessage(), 'coordinadores/crearCoordinador.php');
    }
}

exit;