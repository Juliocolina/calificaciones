<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';

$conn = conectar();

if (!$conn) {
    redirigir('error', 'No se pudo conectar con la base de datos.', 'pnfs/crearPnf.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('error', 'Método no permitido.', 'pnfs/crearPnf.php');
    exit;
}

$nombre      = trim($_POST['nombre_pnf'] ?? '');
$codigo      = trim($_POST['codigo_pnf'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? 'vacio');

if (empty($nombre) || empty($codigo)) {
    redirigir('error', 'Por favor, completa todos los campos obligatorios.', 'pnfs/crearPnf.php');
    exit;
}

if (strlen($nombre) > 255) {
    redirigir('error', 'El nombre excede los 255 caracteres.', 'profesores/crearProfesor.php');
    exit;
}
if (strlen($codigo) > 100) {
    redirigir('error', 'El codigo es demasiado largo.', 'profesores/crearProfesor.php');
    exit;
}


$verificar = $conn->prepare("SELECT id FROM pnfs WHERE nombre = ? OR codigo = ?");
$verificar->execute([$nombre, $codigo]);

if ($verificar->fetch()) {
    redirigir('error', 'PNF ya registrado con ese nombre o código.', 'pnfs/crearPnf.php');
    exit;
}

$insertar = $conn->prepare("INSERT INTO pnfs (nombre, codigo, descripcion) VALUES (?, ?, ?)");
if (!$insertar->execute([$nombre, $codigo, $descripcion])) {
    redirigir('error', 'No se pudo registrar el PNF.', 'pnfs/crearPnf.php');
    exit;
}

redirigir('exito', 'Registro Exitoso..!', 'pnfs/crearPnf.php');
exit;
