<?php
require_once '../../config/conexion.php';

header('Content-Type: application/json');

try {
    $conn = conectar();
    
    // Obtener el último código numérico
    $stmt = $conn->prepare("SELECT codigo_estudiante FROM estudiantes WHERE codigo_estudiante REGEXP '^[0-9]+$' ORDER BY CAST(codigo_estudiante AS UNSIGNED) DESC LIMIT 1");
    $stmt->execute();
    $ultimo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($ultimo && !empty($ultimo['codigo_estudiante'])) {
        $siguiente_numero = intval($ultimo['codigo_estudiante']) + 1;
    } else {
        $siguiente_numero = 1;
    }
    
    $codigo = str_pad($siguiente_numero, 4, '0', STR_PAD_LEFT);
    
    echo json_encode([
        'success' => true,
        'codigo' => $codigo
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>