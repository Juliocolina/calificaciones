<?php
require_once 'config/conexion.php';

try {
    $pdo = conectar();
    
    echo "<h2>Actualizando estudiantes específicos a T2</h2>";
    
    // Obtener el ID del trimestre T2 (2026-2)
    $stmt_t2 = $pdo->prepare("SELECT id FROM trimestres WHERE nombre LIKE '%2026-2%' OR nombre LIKE '%T2%' LIMIT 1");
    $stmt_t2->execute();
    $trimestre_t2 = $stmt_t2->fetch();
    
    if (!$trimestre_t2) {
        echo "<p style='color: red;'>❌ No se encontró el trimestre T2</p>";
        exit;
    }
    
    $trimestre_t2_id = $trimestre_t2['id'];
    echo "<p>✅ Trimestre T2 encontrado con ID: $trimestre_t2_id</p>";
    
    // Cédulas de los estudiantes a actualizar
    $cedulas = ['27895347', '30194496', '12345566'];
    
    foreach ($cedulas as $cedula) {
        // Buscar el estudiante por cédula
        $stmt_estudiante = $pdo->prepare("
            SELECT e.id, u.nombre, u.apellido, u.cedula 
            FROM estudiantes e 
            JOIN usuarios u ON e.usuario_id = u.id 
            WHERE u.cedula = ?
        ");
        $stmt_estudiante->execute([$cedula]);
        $estudiante = $stmt_estudiante->fetch();
        
        if ($estudiante) {
            // Actualizar a T2
            $stmt_update = $pdo->prepare("UPDATE estudiantes SET trimestre_id = ? WHERE id = ?");
            $stmt_update->execute([$trimestre_t2_id, $estudiante['id']]);
            
            echo "<p style='color: green;'>✅ {$estudiante['nombre']} {$estudiante['apellido']} (C.I: {$cedula}) actualizado a T2</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ No se encontró estudiante con cédula: $cedula</p>";
        }
    }
    
    // Verificar la actualización
    $stmt_verify = $pdo->prepare("
        SELECT 
            u.nombre, u.apellido, u.cedula,
            t.nombre as trimestre_nombre
        FROM estudiantes e
        JOIN usuarios u ON e.usuario_id = u.id
        JOIN trimestres t ON e.trimestre_id = t.id
        WHERE u.cedula IN ('" . implode("','", $cedulas) . "')
        ORDER BY u.apellido, u.nombre
    ");
    $stmt_verify->execute();
    $resultados = $stmt_verify->fetchAll();
    
    echo "<h3>Estado actual de los estudiantes:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Nombre</th><th>Cédula</th><th>Trimestre</th></tr>";
    foreach ($resultados as $resultado) {
        echo "<tr>";
        echo "<td>{$resultado['nombre']} {$resultado['apellido']}</td>";
        echo "<td>{$resultado['cedula']}</td>";
        echo "<td>{$resultado['trimestre_nombre']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>