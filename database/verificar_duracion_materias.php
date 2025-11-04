<?php
require_once __DIR__ . '/../config/conexion.php';

try {
    $conn = conectar();
    
    echo "<h2>Verificar Duración de Materias - Oferta 19</h2>";
    
    // Ver las materias de la oferta 19 con su duración
    $stmt = $conn->prepare("
        SELECT om.id AS oferta_materia_id, m.nombre AS materia_nombre, m.duracion, m.codigo
        FROM oferta_materias om
        JOIN materias m ON om.materia_id = m.id
        WHERE om.oferta_academica_id = 19
        ORDER BY m.nombre
    ");
    $stmt->execute();
    $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Materias en Oferta 19:</h3>";
    foreach ($materias as $materia) {
        echo "<p>- <strong>{$materia['materia_nombre']}</strong> ({$materia['codigo']}) - Duración: <strong>{$materia['duracion']}</strong></p>";
    }
    
    // Verificar si la tabla materias tiene el campo duracion
    echo "<h3>Estructura de tabla materias:</h3>";
    $stmt = $conn->prepare("DESCRIBE materias");
    $stmt->execute();
    $campos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($campos as $campo) {
        echo "<p>- {$campo['Field']} ({$campo['Type']})</p>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>