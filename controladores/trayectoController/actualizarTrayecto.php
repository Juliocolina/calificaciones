<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';

$conn = conectar();

// Validar que se recibió el ID
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    redirigir('error', 'ID de trayecto inválido.', 'trayectos/verTrayectos.php');
    exit;
}

$id = $_POST['id'];

// Inicialización de variables usando el operador de fusión nula
$nombre = trim($_POST['nombre_trayecto'] ?? '');
$slug = trim($_POST['slug'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');

if (empty($nombre) || empty($slug)) {
    redirigir('error', 'Los campos Nombre y Slug son obligatorios.', 'trayectos/editarTrayecto.php?id=' . $id);
    exit;
}

// Validar que el slug no exista en otro registro
$stmt = $conn->prepare("SELECT COUNT(*) FROM trayectos WHERE slug = ? AND id != ?");
$stmt->execute([$slug, $id]);
if ($stmt->fetchColumn() > 0) {
    redirigir('error', 'El slug ya existe en otro trayecto.', 'trayectos/editarTrayecto.php?id=' . $id);
    exit;
}

// Reemplazar campos vacíos con null para la base de datos, si es necesario
$descripcion = empty($descripcion) ? null : $descripcion;

// Actualizar datos del trayecto
$stmt = $conn->prepare("UPDATE trayectos SET nombre = ?, slug = ?, descripcion = ? WHERE id = ?");
$exito = $stmt->execute([
    $nombre,
    $slug,
    $descripcion,
    $id
]);

if ($exito) {
    redirigir('exito', 'Trayecto actualizado correctamente.', 'trayectos/verTrayectos.php');
} else {
    redirigir('error', 'Error al actualizar el trayecto.', 'trayectos/editarTrayecto.php?id=' . $id);
}
exit;