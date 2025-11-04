<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';

$conn = conectar();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('error', 'Método no permitido.', 'trimestres/crearTrimestre.php');
    exit;
}

// Recibir y limpiar entradas
$nombre = trim($_POST['nombre'] ?? '');
$fecha_inicio = $_POST['fecha_inicio'] ?? '';
$fecha_fin = $_POST['fecha_fin'] ?? '';
$descripcion = trim($_POST['descripcion'] ?? '');

// Validar campos obligatorios
if (empty($nombre) || empty($fecha_inicio) || empty($fecha_fin)) {
    redirigir('error', 'Por favor, completa todos los campos obligatorios.', 'trimestres/crearTrimestre.php');
    exit;
}

// Validar fechas
if ($fecha_inicio > $fecha_fin) {
    redirigir('error', 'La fecha de inicio no puede ser posterior a la fecha de finalización.', 'trimestres/crearTrimestre.php');
    exit;
}

// Insertar nuevo TRIMESTRE
$insertar = $conn->prepare("INSERT INTO trimestres (nombre, fecha_inicio, fecha_fin, descripcion) VALUES (?, ?, ?, ?)");
$insertar->execute([$nombre, $fecha_inicio, $fecha_fin, $descripcion]);

redirigir('exito', 'Registro Exitoso..!', 'trimestres/crearTrimestre.php');
exit;
