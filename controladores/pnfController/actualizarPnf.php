<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';

$conn = conectar();

## 1. Validar y limpiar el ID del formulario
// El ID es el campo oculto del formulario de edición.
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    redirigir('error', 'ID de PNF inválido.', 'pnfs/verPnfs.php');
    exit;
}
$id = intval($_POST['id']);

## 2. Recibir y limpiar datos del formulario
$nombre      = trim($_POST['nombre'] ?? '');
$codigo      = trim($_POST['codigo'] ?? '');
// La descripción es opcional, convertimos cadena vacía a NULL
$descripcion = !empty($_POST['descripcion']) ? trim($_POST['descripcion']) : null;

## 3. Validar campos obligatorios
// En este caso, solo el nombre y el código son obligatorios.
if (empty($nombre) || empty($codigo)) {
    redirigir('error', 'El nombre y el código son campos obligatorios.', 'pnfs/editarPnf.php?id=' . $id);
    exit;
}

## 4. Verificar duplicados en otros registros
$verificar = $conn->prepare("SELECT id FROM pnfs WHERE (nombre = ? OR codigo = ?) AND id != ?");
$verificar->execute([$nombre, $codigo, $id]);

if ($verificar->fetch()) {
    redirigir('error', 'El nombre o el código ya están registrados para otro PNF.', 'pnfs/editarPnf.php?id=' . $id);
    exit;
}

## 5. Actualizar los datos en la tabla 'pnfs'
$actualizar = $conn->prepare("
    UPDATE pnfs SET 
        nombre = ?, 
        codigo = ?, 
        descripcion = ?
    WHERE id = ?
");

$exito = $actualizar->execute([$nombre, $codigo, $descripcion, $id]);

## 6. Manejar el resultado de la operación
if ($exito) {
    redirigir('exito', 'PNF actualizado correctamente.', 'pnfs/verPnfs.php');
    exit;
} else {
    redirigir('error', 'Error al actualizar el PNF.', 'pnfs/editarPnf.php?id=' . $id);
    exit;
}
?>