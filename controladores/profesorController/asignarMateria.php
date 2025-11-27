<?php
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
require_once __DIR__ . '/../../config/conexion.php';

verificarRol(['admin', 'coordinador']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../vistas/profesores/verProfesores.php");
    exit;
}

$cedula_profesor = trim($_POST['cedula_profesor'] ?? '');
$materia_id = intval($_POST['materia_id'] ?? 0);

if (empty($cedula_profesor) || $materia_id <= 0) {
    $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'Datos incompletos'];
    header("Location: ../../vistas/materias/asignarProfesor.php?materia_id={$materia_id}");
    exit;
}

$conn = conectar();

// Buscar profesor por cédula
$stmt = $conn->prepare("
    SELECT p.id as profesor_id 
    FROM profesores p 
    JOIN usuarios u ON p.usuario_id = u.id 
    WHERE u.cedula = ?
");
$stmt->execute([$cedula_profesor]);
$profesor = $stmt->fetch();

if (!$profesor) {
    $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'Profesor no encontrado con esa cédula'];
    header("Location: ../../vistas/materias/asignarProfesor.php?materia_id={$materia_id}");
    exit;
}

$profesor_id = $profesor['profesor_id'];

try {
    // Verificar que no esté ya asignada
    $stmt = $conn->prepare("SELECT id FROM materia_profesor WHERE profesor_id = ? AND materia_id = ?");
    $stmt->execute([$profesor_id, $materia_id]);
    
    if ($stmt->fetch()) {
        $_SESSION['mensaje'] = ['tipo' => 'warning', 'texto' => 'Este profesor ya tiene asignada esta materia'];
        header("Location: ../../vistas/materias/asignarProfesor.php?materia_id={$materia_id}");
        exit;
    }
    
    // Asignar materia
    $stmt = $conn->prepare("INSERT INTO materia_profesor (profesor_id, materia_id) VALUES (?, ?)");
    $stmt->execute([$profesor_id, $materia_id]);
    
    $_SESSION['mensaje'] = ['tipo' => 'success', 'texto' => 'Profesor asignado exitosamente a la materia'];
    header("Location: ../../vistas/materias/asignarProfesor.php?materia_id={$materia_id}");
    
} catch (PDOException $e) {
    $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'Error al asignar profesor: ' . $e->getMessage()];
    header("Location: ../../vistas/materias/asignarProfesor.php?materia_id={$materia_id}");
}
?>