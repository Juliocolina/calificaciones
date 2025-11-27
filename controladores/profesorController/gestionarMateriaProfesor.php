<?php
session_start();
require_once __DIR__ . '/../hellpers/auth.php';
require_once __DIR__ . '/../../config/conexion.php';

verificarRol(['admin', 'coordinador']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../vistas/profesores/verProfesores.php");
    exit;
}

$profesor_id = intval($_POST['profesor_id'] ?? 0);
$materia_id = intval($_POST['materia_id'] ?? 0);
$accion = $_POST['accion'] ?? '';

if ($profesor_id <= 0 || $materia_id <= 0 || !in_array($accion, ['asignar', 'quitar'])) {
    $_SESSION['mensaje'] = 'Datos incompletos o acción inválida';
    header("Location: ../../vistas/profesores/gestionarMaterias.php?profesor_id={$profesor_id}&error=1");
    exit;
}

$conn = conectar();

try {
    if ($accion === 'asignar') {
        // Verificar que no esté ya asignada
        $stmt = $conn->prepare("SELECT id FROM materia_profesor WHERE profesor_id = ? AND materia_id = ?");
        $stmt->execute([$profesor_id, $materia_id]);
        
        if ($stmt->fetch()) {
            $_SESSION['mensaje'] = 'Este profesor ya tiene asignada esta materia';
            header("Location: ../../vistas/profesores/gestionarMaterias.php?profesor_id={$profesor_id}&error=1");
            exit;
        }
        
        // Asignar materia
        $stmt = $conn->prepare("INSERT INTO materia_profesor (profesor_id, materia_id) VALUES (?, ?)");
        $stmt->execute([$profesor_id, $materia_id]);
        
        $_SESSION['mensaje'] = 'Materia asignada exitosamente';
        
    } else { // quitar
        // Quitar asignación
        $stmt = $conn->prepare("DELETE FROM materia_profesor WHERE profesor_id = ? AND materia_id = ?");
        $stmt->execute([$profesor_id, $materia_id]);
        
        $_SESSION['mensaje'] = 'Materia removida exitosamente';
    }
    
    header("Location: ../../vistas/profesores/gestionarMaterias.php?profesor_id={$profesor_id}");
    
} catch (PDOException $e) {
    $_SESSION['mensaje'] = 'Error al procesar la solicitud: ' . $e->getMessage();
    header("Location: ../../vistas/profesores/gestionarMaterias.php?profesor_id={$profesor_id}&error=1");
}
?>