<?php
require_once 'config/conexion.php';

try {
    $pdo = conectar();
    
    echo "<h2>Actualizando estudiantes a Trimestre T1</h2>";
    
    // Obtener el ID del trimestre T1 (2026-1)
    $stmt_t1 = $pdo->prepare("SELECT id FROM trimestres WHERE nombre LIKE '%2026-1%' OR nombre LIKE '%T1%' LIMIT 1");
    $stmt_t1->execute();
    $trimestre_t1 = $stmt_t1->fetch();
    
    if (!$trimestre_t1) {
        echo "<p style='color: red;'>❌ No se encontró el trimestre T1</p>";
        exit;
    }
    
    $trimestre_id = $trimestre_t1['id'];
    echo "<p>✅ Trimestre T1 encontrado con ID: $trimestre_id</p>";
    
    // Mostrar trayectos disponibles
    $stmt_tray_list = $pdo->prepare("SELECT id, nombre, slug FROM trayectos ORDER BY id");
    $stmt_tray_list->execute();
    $trayectos = $stmt_tray_list->fetchAll();
    
    echo "<h3>Trayectos disponibles:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Slug</th></tr>";
    foreach ($trayectos as $tray) {
        echo "<tr><td>{$tray['id']}</td><td>{$tray['nombre']}</td><td>{$tray['slug']}</td></tr>";
    }
    echo "</table>";
    
    // Obtener el primer trayecto disponible
    $stmt_tray = $pdo->prepare("SELECT id FROM trayectos ORDER BY id LIMIT 1");
    $stmt_tray->execute();
    $trayecto_1 = $stmt_tray->fetch();
    
    if (!$trayecto_1) {
        echo "<p style='color: red;'>❌ No hay trayectos disponibles</p>";
        exit;
    }
    
    $trayecto_id = $trayecto_1['id'];
    echo "<p>✅ Trayecto 1 encontrado con ID: $trayecto_id</p>";
    
    // Actualizar todos los estudiantes al trimestre T1 y trayecto 1
    $stmt_update = $pdo->prepare("UPDATE estudiantes SET trimestre_id = ?, trayecto_id = ?");
    $stmt_update->execute([$trimestre_id, $trayecto_id]);
    $count = $stmt_update->rowCount();
    
    echo "<p style='color: green;'>✅ $count estudiantes actualizados al Trimestre T1 y Trayecto 1</p>";
    
    // Verificar la actualización
    $stmt_verify = $pdo->prepare("
        SELECT 
            COUNT(*) as total_estudiantes,
            t.nombre as trimestre_nombre,
            tr.slug as trayecto_nombre
        FROM estudiantes e
        JOIN trimestres t ON e.trimestre_id = t.id
        JOIN trayectos tr ON e.trayecto_id = tr.id
        GROUP BY e.trimestre_id, e.trayecto_id, t.nombre, tr.slug
    ");
    $stmt_verify->execute();
    $resultados = $stmt_verify->fetchAll();
    
    echo "<h3>Distribución actual de estudiantes:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Trayecto</th><th>Trimestre</th><th>Cantidad Estudiantes</th></tr>";
    foreach ($resultados as $resultado) {
        echo "<tr>";
        echo "<td>{$resultado['trayecto_nombre']}</td>";
        echo "<td>{$resultado['trimestre_nombre']}</td>";
        echo "<td>{$resultado['total_estudiantes']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>