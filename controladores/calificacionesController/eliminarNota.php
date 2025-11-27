<?php
session_start();
require_once '../../config/conexion.php';
require_once '../hellpers/auth.php';

verificarSesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../vistas/calificaciones/cargarNotasFinal.php');
    exit;
}

try {
    $pdo = conectar();
    $calificacion_id = $_POST['calificacion_id'] ?? null;
    
    if (!$calificacion_id) {
        throw new Exception('ID de calificación requerido');
    }
    
    // Eliminar calificación
    $stmt = $pdo->prepare("DELETE FROM calificaciones WHERE id = ?");
    $stmt->execute([$calificacion_id]);
    
    // Actualizar estatus de inscripción
    $stmt = $pdo->prepare("UPDATE inscripciones SET estatus = 'Cursando' WHERE id = (SELECT inscripcion_id FROM calificaciones WHERE id = ?)");
    $stmt->execute([$calificacion_id]);
    
    $_SESSION['mensaje'] = [
        'tipo' => 'success',
        'texto' => 'Calificación eliminada exitosamente'
    ];
    
} catch (Exception $e) {
    $_SESSION['mensaje'] = [
        'tipo' => 'error',
        'texto' => 'Error: ' . $e->getMessage()
    ];
}

$referer = $_SERVER['HTTP_REFERER'] ?? '../../vistas/calificaciones/cargarNotasFinal.php';
header('Location: ' . $referer);
exit;
?>