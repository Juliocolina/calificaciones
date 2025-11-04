<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php'; 

// 1. Conexión y validación del método
$conn = conectar();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('error', 'Acceso no permitido.', 'ofertas_academicas/verOfertas.php');
    exit;
}

// 2. Obtener los IDs del formulario
$asignacion_id = isset($_POST['asignacion_id']) ? intval($_POST['asignacion_id']) : 0;
$oferta_id = isset($_POST['oferta_id']) ? intval($_POST['oferta_id']) : 0;

// URL para redirigir en cualquier caso
$redirect_url = "ofertas_materias/verOfertasMaterias.php?id=" . $oferta_id;

if ($asignacion_id <= 0 || $oferta_id <= 0) {
    redirigir('error', 'Datos inválidos para eliminar la materia.', $redirect_url);
    exit;
}

// 3. Preparar y ejecutar la eliminación
try {
    // La eliminación es directa sobre la tabla intermedia usando su ID
    $sql = "DELETE FROM oferta_materias WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $exito = $stmt->execute([$asignacion_id]);

    if ($exito) {
        redirigir('exito', 'Materia quitada de la oferta correctamente.', $redirect_url);
    } else {
        redirigir('error', 'No se pudo quitar la materia.', $redirect_url);
    }

} catch (PDOException $e) {
    redirigir('error', 'Error en la base de datos: ' . $e->getMessage(), $redirect_url);
}

exit;