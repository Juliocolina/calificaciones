<?php
require_once __DIR__ . '/../config/conexion.php';

try {
    $conn = conectar();
    
    // Leer el archivo SQL
    $sql = file_get_contents(__DIR__ . '/add_aldea_to_ofertas.sql');
    
    // Ejecutar cada statement
    $statements = explode(';', $sql);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $conn->exec($statement);
            echo "✅ Ejecutado: " . substr($statement, 0, 50) . "...\n";
        }
    }
    
    echo "\n🎉 SQL ejecutado exitosamente!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>