<?php
require_once __DIR__ . '/../config/conexion.php';

header('Content-Type: application/json');

if (!isset($_GET['materia_id']) || !is_numeric($_GET['materia_id'])) {
    echo json_encode([]);
    exit;
}

$conn = conectar();
$materia_id = intval($_GET['materia_id']);

try {
    $stmt = $conn->prepare("
        SELECT p.id, u.nombre, u.apellido
        FROM profesores p
        JOIN usuarios u ON p.usuario_id = u.id
        JOIN materia_profesor mp ON p.id = mp.profesor_id
        WHERE mp.materia_id = ?
        ORDER BY u.nombre, u.apellido
    ");
    
    $stmt->execute([$materia_id]);
    $profesores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($profesores);
    
} catch (Exception $e) {
    echo json_encode([]);
}
?>