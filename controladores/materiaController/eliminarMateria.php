<?php

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
$conn = conectar();

// Validar que se recibió el ID (GET para JavaScript)
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirigir('error', 'ID inválido.', 'materias/materiasPorPnf.php');
}

$id = intval($_GET['id']);
try {
    // Preparar y ejecutar la consulta de eliminación
    $stmt = $conn->prepare("DELETE FROM materias WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    // Verificar si se eliminó algún registro
    if ($stmt->rowCount() > 0) {
        redirigir('success', 'Materia eliminada exitosamente.', 'materias/materiasPorPnf.php');
    } else {
        redirigir('error', 'No se encontró la materia o ya fue eliminada.', 'materias/materiasPorPnf.php');
    }
} catch (PDOException $e) {
    // Manejar errores de la base de datos
    redirigir('error', 'Error al eliminar la materia: ' . $e->getMessage(), 'materias/materiasPorPnf.php');
}

