<?php
/**
 * Script para migrar ofertas académicas existentes y asignarles aldeas
 * Este script debe ejecutarse después de agregar la columna aldea_id
 */

require_once __DIR__ . '/../config/conexion.php';

try {
    $conn = conectar();
    
    echo "Iniciando migración de ofertas académicas...\n";
    
    // 1. Verificar si la columna aldea_id existe
    $stmt = $conn->query("SHOW COLUMNS FROM oferta_academica LIKE 'aldea_id'");
    if ($stmt->rowCount() == 0) {
        echo "ERROR: La columna aldea_id no existe en la tabla oferta_academica.\n";
        echo "Ejecute primero el script add_aldea_to_ofertas.sql\n";
        exit(1);
    }
    
    // 2. Obtener ofertas sin aldea asignada
    $stmt = $conn->query("SELECT COUNT(*) as count FROM oferta_academica WHERE aldea_id IS NULL");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $ofertas_sin_aldea = $result['count'];
    
    echo "Ofertas sin aldea asignada: {$ofertas_sin_aldea}\n";
    
    if ($ofertas_sin_aldea > 0) {
        // 3. Obtener la primera aldea disponible como default
        $stmt = $conn->query("SELECT id, nombre FROM aldeas ORDER BY id LIMIT 1");
        $aldea_default = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($aldea_default) {
            echo "Asignando aldea por defecto: {$aldea_default['nombre']} (ID: {$aldea_default['id']})\n";
            
            // 4. Actualizar ofertas sin aldea
            $stmt = $conn->prepare("UPDATE oferta_academica SET aldea_id = ? WHERE aldea_id IS NULL");
            $stmt->execute([$aldea_default['id']]);
            
            echo "Migración completada. {$ofertas_sin_aldea} ofertas actualizadas.\n";
        } else {
            echo "ERROR: No se encontraron aldeas en el sistema.\n";
            exit(1);
        }
    } else {
        echo "No hay ofertas que requieran migración.\n";
    }
    
    echo "Migración finalizada exitosamente.\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>