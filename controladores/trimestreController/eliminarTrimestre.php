<?php

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
$conn = conectar();

// Validar que se recibió el ID
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    redirigir('error', 'ID inválido.', 'trimestres/verTrimestres.php');
}

$id = intval($_POST['id']);
try {
    // Preparar y ejecutar la consulta de eliminación
    $stmt = $conn->prepare("DELETE FROM trimestres WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    // Verificar si se eliminó algún registro
    if ($stmt->rowCount() > 0) {
        redirigir('success', 'Trimestre eliminado exitosamente.', 'trimestres/verTrimestres.php');
    } else {
        redirigir('error', 'No se encontró el trimestre o ya fue eliminado.', 'trimestres/verTrimestres.php');
    }
} catch (PDOException $e) {
    // Manejar errores de la base de datos
    redirigir('error', 'Error al eliminar el trimestre: ' . $e->getMessage(), 'trimestres/verTrimestres.php');
}

