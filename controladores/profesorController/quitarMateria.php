<?php
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
require_once __DIR__ . '/../../config/conexion.php';

verificarRol(['admin', 'coordinador']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../vistas/profesores/verProfesores.php");
    exit;
}

$profesor_id = intval($_POST['profesor_id'] ?? 0);
$materia_id = intval($_POST['materia_id'] ?? 0);

if ($profesor_id <= 0 || $materia_id <= 0) {
    header("Location: ../../vistas/profesores/verProfesores.php");
    exit;
}

$conn = conectar();

try {
    // Quitar asignación de materia
    $stmt = $conn->prepare("DELETE FROM materia_profesor WHERE profesor_id = ? AND materia_id = ?");
    $stmt->execute([$profesor_id, $materia_id]);
    
    $_SESSION['mensaje'] = ['tipo' => 'success', 'texto' => 'Profesor removido de la materia exitosamente'];
    header("Location: ../../vistas/materias/asignarProfesor.php?materia_id={$materia_id}");
    
} catch (PDOException $e) {
    $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'Error al remover profesor: ' . $e->getMessage()];
    header("Location: ../../vistas/materias/asignarProfesor.php?materia_id={$materia_id}");
}
?>