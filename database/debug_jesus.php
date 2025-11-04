<?php
require_once __DIR__ . '/../config/conexion.php';

try {
    $conn = conectar();
    
    echo "<h3>🔍 DEBUG: Jesús Arteaga</h3>";
    
    // Buscar por nombre
    $stmt = $conn->prepare("
        SELECT 
            u.id as usuario_id,
            u.cedula,
            u.nombre,
            u.apellido,
            e.id as estudiante_id,
            e.aldea_id,
            e.pnf_id,
            e.trayecto_id,
            e.trimestre_id,
            a.nombre AS aldea_nombre,
            p.nombre AS pnf_nombre
        FROM usuarios u
        LEFT JOIN estudiantes e ON u.id = e.usuario_id
        LEFT JOIN aldeas a ON e.aldea_id = a.id
        LEFT JOIN pnfs p ON e.pnf_id = p.id
        WHERE u.nombre LIKE '%Jesús%' OR u.nombre LIKE '%Jesus%'
    ");
    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($resultados)) {
        echo "<p>❌ No se encontró ningún usuario con nombre Jesús</p>";
    } else {
        foreach ($resultados as $user) {
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px;'>";
            echo "<h4>{$user['nombre']} {$user['apellido']} (C.I: {$user['cedula']})</h4>";
            echo "<p><strong>Usuario ID:</strong> {$user['usuario_id']}</p>";
            echo "<p><strong>Estudiante ID:</strong> " . ($user['estudiante_id'] ?: '<span style="color:red">NO ES ESTUDIANTE</span>') . "</p>";
            echo "<p><strong>Aldea ID:</strong> " . ($user['aldea_id'] ?: '<span style="color:red">NULL</span>') . "</p>";
            echo "<p><strong>Aldea Nombre:</strong> " . ($user['aldea_nombre'] ?: '<span style="color:red">Sin aldea</span>') . "</p>";
            echo "<p><strong>PNF ID:</strong> " . ($user['pnf_id'] ?: '<span style="color:red">NULL</span>') . "</p>";
            echo "<p><strong>PNF Nombre:</strong> " . ($user['pnf_nombre'] ?: '<span style="color:red">Sin PNF</span>') . "</p>";
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>