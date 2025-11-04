<?php
require_once __DIR__ . '/../config/conexion.php';

try {
    $conn = conectar();
    
    echo "<h2>Debug: Jesús Arteaga - Validación de Aldea</h2>";
    
    // 1. Datos del estudiante
    echo "<h3>1. Datos del Estudiante (C.I: 32476804)</h3>";
    $stmt = $conn->prepare("
        SELECT e.id, e.aldea_id, e.pnf_id, e.trayecto_id, e.trimestre_id,
               u.cedula, u.nombre, u.apellido,
               a.nombre AS aldea_nombre
        FROM estudiantes e
        JOIN usuarios u ON e.usuario_id = u.id
        LEFT JOIN aldeas a ON e.aldea_id = a.id
        WHERE u.cedula = '32476804'
    ");
    $stmt->execute();
    $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($estudiante) {
        echo "<pre>";
        print_r($estudiante);
        echo "</pre>";
        
        echo "<p><strong>Aldea del estudiante:</strong> {$estudiante['aldea_id']} ({$estudiante['aldea_nombre']})</p>";
    } else {
        echo "No se encontró el estudiante<br>";
    }
    
    // 2. Ofertas disponibles para este estudiante
    if ($estudiante) {
        echo "<h3>2. Ofertas que coinciden con los datos académicos del estudiante</h3>";
        $stmt = $conn->prepare("
            SELECT oa.id, oa.aldea_id, oa.pnf_id, oa.trayecto_id, oa.trimestre_id, oa.estatus,
                   p.nombre AS pnf_nombre, t.nombre AS trayecto_nombre, tr.nombre AS trimestre_nombre,
                   a.nombre AS aldea_nombre
            FROM oferta_academica oa
            JOIN pnfs p ON oa.pnf_id = p.id
            JOIN trayectos t ON oa.trayecto_id = t.id
            JOIN trimestres tr ON oa.trimestre_id = tr.id
            LEFT JOIN aldeas a ON oa.aldea_id = a.id
            WHERE oa.pnf_id = ? AND oa.trayecto_id = ? AND oa.trimestre_id = ?
            ORDER BY oa.id DESC
        ");
        $stmt->execute([$estudiante['pnf_id'], $estudiante['trayecto_id'], $estudiante['trimestre_id']]);
        $ofertas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($ofertas) {
            foreach ($ofertas as $oferta) {
                echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>";
                echo "<h4>Oferta ID: {$oferta['id']} - {$oferta['estatus']}</h4>";
                echo "<p><strong>Aldea de la oferta:</strong> {$oferta['aldea_id']} ({$oferta['aldea_nombre']})</p>";
                echo "<p><strong>Coincide aldea:</strong> " . ($estudiante['aldea_id'] == $oferta['aldea_id'] ? 'SÍ' : 'NO') . "</p>";
                echo "<p>{$oferta['pnf_nombre']} - {$oferta['trayecto_nombre']} - {$oferta['trimestre_nombre']}</p>";
                echo "</div>";
            }
        } else {
            echo "No hay ofertas que coincidan con los datos académicos del estudiante<br>";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>