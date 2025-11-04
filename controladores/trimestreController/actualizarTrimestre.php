<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';

$conn = conectar();

// Validar que se recibió el ID
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    redirigir('error', 'ID de trimestre inválido.', 'trimestres/verTrimestres.php');
    exit;
}

$id = $_POST['id'];

// Inicialización de variables usando el operador de fusión nula
$nombre = trim($_POST['nombre'] ?? '');
$fecha_inicio = trim($_POST['fecha_inicio'] ?? '');
$fecha_fin = trim($_POST['fecha_fin'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');

if (empty($nombre) || empty($fecha_inicio) || empty($fecha_fin)) {
    redirigir('error', 'Los campos son obligatorios.', 'trimestres/editarTrimestre.php?id=' . $id);
    exit;
}

// Validar formato de fechas
$date_inicio = DateTime::createFromFormat('Y-m-d', $fecha_inicio);
$date_fin = DateTime::createFromFormat('Y-m-d', $fecha_fin);
if (!$date_inicio || !$date_fin) {
    redirigir('error', 'Formato de fecha inválido. Use AAAA-MM-DD.', 'trimestres/editarTrimestre.php?id=' . $id);
    exit;
}
if ($date_inicio > $date_fin) {
    redirigir('error', 'La fecha de inicio no puede ser posterior a la fecha de fin.', 'trimestres/editarTrimestre.php?id=' . $id);
    exit;
}

// Reemplazar campos vacíos con null para la base de datos, si es necesario
$descripcion = empty($descripcion) ? null : $descripcion;

// Actualizar datos del trimestre
$stmt = $conn->prepare("UPDATE trimestres SET nombre = ?, fecha_inicio = ?, fecha_fin = ?, descripcion = ? WHERE id = ?");
$exito = $stmt->execute([
    $nombre,
    $fecha_inicio,
    $fecha_fin,
    $descripcion,
    $id
]);

if ($exito) {
    redirigir('exito', 'Trimestre actualizado correctamente.', 'trimestres/verTrimestres.php');
} else {
    // CORRECCIÓN DEL PATH DE REDIRECCIÓN
    redirigir('error', 'Error al actualizar el trimestre.', 'trimestres/editarTrimestre.php?id=' . $id);
}
exit;