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

// 1. Validar Fechas Básicas
if ($fecha_inicio > $fecha_fin) {
    redirigir('error', 'La fecha de inicio no puede ser posterior a la fecha de finalización.', 'trimestres/crearTrimestre.php');
    exit;
}

// 2. NUEVA VALIDACIÓN CLAVE: Coherencia de Año (Nombre vs. Fecha de Inicio)
// Esto asegura que si el nombre es '2027-3', la fecha de inicio sea del 2027.
try {
    // a. Extraer el año del nombre (asumiendo formato YYYY-X)
    $partes_nombre = explode('-', $nombre);
    $anio_nombre = $partes_nombre[0] ?? '';

    // b. Extraer el año de la fecha de inicio
    $anio_fecha = date('Y', strtotime($fecha_inicio));
    
    // c. Comparar los años
    if (!is_numeric($anio_nombre) || $anio_nombre !== $anio_fecha) {
        $mensaje_error = "Error de Coherencia: El año en el nombre del trimestre ({$anio_nombre}) no coincide con el año de la fecha de inicio ({$anio_fecha}). Asegúrate de que el formato sea correcto (Ej: 2027-1).";
        redirigir('error', $mensaje_error, 'trimestres/crearTrimestre.php');
        exit;
    }

} catch (\Exception $e) {
    // Esto captura errores si strtotime o date fallan por formatos de fecha raros.
    redirigir('error', 'Error al procesar la fecha o el nombre del trimestre.', 'trimestres/crearTrimestre.php');
    exit;
}


// Insertar nuevo TRIMESTRE
$insertar = $conn->prepare("INSERT INTO trimestres (nombre, fecha_inicio, fecha_fin, descripcion) VALUES (?, ?, ?, ?)");
$insertar->execute([$nombre, $fecha_inicio, $fecha_fin, $descripcion]);

redirigir('exito', 'Registro Exitoso..!', 'trimestres/crearTrimestre.php');
exit;