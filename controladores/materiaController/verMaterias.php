<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';

$conn = conectar();

// 1. Consultar todas las materias uniendo con la tabla pnf
$consulta = $conn->prepare("
    SELECT 
        m.id, 
        m.nombre, 
        m.codigo, 
        m.creditos,
        m.duracion,      
        m.descripcion,
        p.nombre AS pnf_nombre 
    FROM materias m
    INNER JOIN pnfs p ON m.pnf_id = p.id
    ORDER BY p.nombre, m.nombre /* Opcional: Ordenar por PNF y luego por nombre de materia */
");

try {
    $consulta->execute();
    $materias = $consulta->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $materias = [];
}


?>