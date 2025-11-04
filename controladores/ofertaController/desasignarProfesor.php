<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';

verificarSesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('error', 'Método no permitido.', 'ofertas_academicas/verOfertas.php');
    exit;
}

$oferta_materia_id = isset($_POST['oferta_materia_id']) ? intval($_POST['oferta_materia_id']) : 0;
$oferta_id = isset($_POST['oferta_id']) ? intval($_POST['oferta_id']) : 0;

$redirect_url = "ofertas_materias/verOfertasMaterias.php?id=" . $oferta_id;

if ($oferta_materia_id <= 0 || $oferta_id <= 0) {
    redirigir('error', 'Datos inválidos para desasignar profesor.', $redirect_url);
    exit;
}

try {
    $conn = conectar();
    
    // Verificar que existe la asignación
    $stmt_check = $conn->prepare("SELECT id FROM oferta_materia_profesor WHERE oferta_materia_id = ?");
    $stmt_check->execute([$oferta_materia_id]);
    
    if (!$stmt_check->fetch()) {
        redirigir('error', 'No hay profesor asignado a esta materia.', $redirect_url);
        exit;
    }
    
    // Eliminar la asignación
    $stmt_delete = $conn->prepare("DELETE FROM oferta_materia_profesor WHERE oferta_materia_id = ?");
    $stmt_delete->execute([$oferta_materia_id]);
    
    redirigir('exito', 'Profesor desasignado correctamente de la materia.', $redirect_url);
    
} catch (PDOException $e) {
    redirigir('error', 'Error al desasignar profesor: ' . $e->getMessage(), $redirect_url);
}

exit;