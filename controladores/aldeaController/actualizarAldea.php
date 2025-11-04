<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';

$conn = conectar();

## Validar y limpiar el ID
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    redirigir('error', 'ID inválido.', 'aldeas/verAldeas.php');
    exit;
}
$id = intval($_POST['id']);

## Recibir y limpiar datos del formulario
$nombre = trim($_POST['nombre'] ?? '');
$codigo = trim($_POST['codigo'] ?? '');
$direccion = trim($_POST['direccion'] ?? '');

// Convertir cadena vacía a NULL para campos opcionales
$descripcion = !empty($_POST['descripcion']) ? trim($_POST['descripcion']) : null;

## Validación de campos obligatorios
if (empty($nombre) || empty($codigo) || empty($direccion)) {
    redirigir('error', 'Todos los campos son obligatorios.', 'aldeas/editarAldea.php?id=' . $id);
    exit;
}

## Verificar duplicados en otros registros
$verificar = $conn->prepare("SELECT id FROM aldeas WHERE (nombre = ? OR codigo = ?) AND id != ?");
$verificar->execute([$nombre, $codigo, $id]);

if ($verificar->fetch()) {
    redirigir('error', 'duplicado_existente', 'aldeas/editarAldea.php?id=' . $id);
    exit; // Añadir exit
}

## Actualizar la aldea
$actualizar = $conn->prepare("
    UPDATE aldeas SET 
        nombre = ?, 
        codigo = ?, 
        direccion = ?, 
        descripcion = ? 
    WHERE id = ?
");

$exito = $actualizar->execute([$nombre, $codigo, $direccion, $descripcion, $id]);

## Manejar el resultado de la operación
if ($exito) {
    redirigir('exito', 'Aldea actualizada con exito', 'aldeas/verAldeas.php');
    exit;
} else {
    redirigir('error', 'Error al actualizar la aldea.', 'aldeas/editarAldea.php?id=' . $id);
    exit;
}
?>