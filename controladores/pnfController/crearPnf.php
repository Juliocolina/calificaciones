<?php
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/funciones.php';

// Verificar sesión y rol admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

$conn = conectar();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('error', 'Método no permitido.', 'pnfs/crearPnf.php');
    exit;
}

$nombre      = trim($_POST['nombre_pnf'] ?? '');
$codigo      = trim($_POST['codigo_pnf'] ?? '');
$aldea_id    = intval($_POST['aldea_id'] ?? 0);
$descripcion = trim($_POST['descripcion'] ?? 'vacio');

if (empty($nombre) || empty($codigo) || $aldea_id <= 0) {
    redirigir('error', 'Por favor, completa todos los campos obligatorios (nombre, código y aldea).', 'pnfs/crearPnf.php');
    exit;
}


$verificar = $conn->prepare("SELECT id FROM pnfs WHERE nombre = ? OR codigo = ?");
$verificar->execute([$nombre, $codigo]);

if ($verificar->fetch()) {
    redirigir('error', 'PNF ya registrado con ese nombre o código.', 'pnfs/crearPnf.php');
    exit;
}

$insertar = $conn->prepare("INSERT INTO pnfs (nombre, codigo, aldea_id, descripcion) VALUES (?, ?, ?, ?)");
if ($insertar->execute([$nombre, $codigo, $aldea_id, $descripcion])) {
    redirigir('exito', 'PNF creado exitosamente.', 'pnfs/verPnfs.php');
    exit;
} else {
    redirigir('error', 'No se pudo registrar el PNF.', 'pnfs/crearPnf.php');
    exit;
}
