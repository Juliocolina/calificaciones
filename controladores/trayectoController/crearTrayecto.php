<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';

$conn = conectar();

// Verificar método de solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('error', 'Método no permitido.', 'trayectos/crearTrayecto.php');
    exit;
}

// Recibir y limpiar datos
$nombre     = trim($_POST['nombre_trayecto'] ?? '');
$slug     = trim($_POST['slug_trayecto'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');

// ✅ Validar campos obligatorios
if (empty($nombre) || empty($slug)) {
    redirigir('error', 'Por favor, completa todos los campos obligatorios.', 'trayectos/crearTrayecto.php');
    exit;
}

// 🔍 Verificar duplicados por nombre o código
$verificar = $conn->prepare("SELECT id FROM trayectos WHERE nombre = ? OR slug = ?");
$verificar->execute([$nombre, $slug]);

if ($verificar->fetch()) {
    redirigir('error', 'Trayecto ya registrado con ese nombre o código.', 'trayectos/crearTrayecto.php');
    exit;
}

// 💾 Insertar nuevo trayecto
$insertar = $conn->prepare("INSERT INTO trayectos (nombre, slug, descripcion) VALUES (?, ?, ?)");
$insertar->execute([$nombre, $slug, $descripcion]);

redirigir('exito', 'Registro Exitoso..!', 'trayectos/crearTrayecto.php');
exit;
