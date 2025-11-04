<?php
require_once __DIR__ . '/../config/conexion.php';

$conn = conectar();

try {
    $conn->beginTransaction();
    
    // Migrar estudiantes graduados existentes
    $stmt = $conn->prepare("
        SELECT e.id, e.pnf_id, e.fecha_graduacion 
        FROM estudiantes e 
        WHERE e.estado_academico = 'graduado' 
        AND e.fecha_graduacion IS NOT NULL 
        AND e.pnf_id IS NOT NULL
    ");
    $stmt->execute();
    $graduados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $migrados = 0;
    foreach ($graduados as $graduado) {
        // Verificar si ya existe en graduaciones
        $stmt_check = $conn->prepare("
            SELECT id FROM graduaciones WHERE estudiante_id = ?
        ");
        $stmt_check->execute([$graduado['id']]);
        
        if (!$stmt_check->fetch()) {
            // Insertar como Licenciado (asumiendo que los graduados existentes son Licenciados)
            $stmt_insert = $conn->prepare("
                INSERT INTO graduaciones (estudiante_id, tipo_graduacion, fecha_graduacion, pnf_id) 
                VALUES (?, 'Licenciado', ?, ?)
            ");
            $stmt_insert->execute([
                $graduado['id'], 
                $graduado['fecha_graduacion'], 
                $graduado['pnf_id']
            ]);
            $migrados++;
        }
    }
    
    $conn->commit();
    echo "Migración completada. {$migrados} graduaciones migradas.\n";
    
} catch (Exception $e) {
    $conn->rollBack();
    echo "Error en migración: " . $e->getMessage() . "\n";
}
?>