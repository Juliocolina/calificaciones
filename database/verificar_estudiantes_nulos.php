<?php
require_once __DIR__ . '/../config/conexion.php';

try {
    $conn = conectar();
    
    echo "<h3>🔍 ESTUDIANTES CON CAMPOS NULOS:</h3>";
    
    $stmt = $conn->query("
        SELECT 
            e.id,
            u.cedula,
            u.nombre,
            u.apellido,
            e.aldea_id,
            e.pnf_id,
            e.trayecto_id,
            e.trimestre_id,
            a.nombre AS aldea_nombre,
            p.nombre AS pnf_nombre
        FROM estudiantes e
        LEFT JOIN usuarios u ON e.usuario_id = u.id
        LEFT JOIN aldeas a ON e.aldea_id = a.id
        LEFT JOIN pnfs p ON e.pnf_id = p.id
        WHERE e.aldea_id IS NULL 
           OR e.pnf_id IS NULL 
           OR e.trayecto_id IS NULL 
           OR e.trimestre_id IS NULL
        ORDER BY u.nombre
    ");
    
    $estudiantes_nulos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($estudiantes_nulos)) {
        echo "<p>✅ No hay estudiantes con campos nulos.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Cédula</th><th>Aldea ID</th><th>PNF ID</th><th>Trayecto ID</th><th>Trimestre ID</th></tr>";
        
        foreach ($estudiantes_nulos as $est) {
            echo "<tr>";
            echo "<td>{$est['id']}</td>";
            echo "<td>{$est['nombre']} {$est['apellido']}</td>";
            echo "<td>{$est['cedula']}</td>";
            echo "<td>" . ($est['aldea_id'] ?: '<span style="color:red">NULL</span>') . "</td>";
            echo "<td>" . ($est['pnf_id'] ?: '<span style="color:red">NULL</span>') . "</td>";
            echo "<td>" . ($est['trayecto_id'] ?: '<span style="color:red">NULL</span>') . "</td>";
            echo "<td>" . ($est['trimestre_id'] ?: '<span style="color:red">NULL</span>') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<p><strong>Total estudiantes con campos nulos: " . count($estudiantes_nulos) . "</strong></p>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>