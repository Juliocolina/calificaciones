<?php
require_once __DIR__ . '/../config/conexion.php';

header('Content-Type: application/json');

if (!isset($_GET['pnf_id']) || empty($_GET['pnf_id'])) {
    echo json_encode([]);
    exit;
}

$pnf_id = (int)$_GET['pnf_id'];

try {
    $conn = conectar();
    
    $stmt = $conn->prepare("
        SELECT 
            s.id,
            CONCAT(m.nombre, ' - ', u.nombre, ' ', u.apellido) as descripcion
        FROM secciones s
        JOIN materias m ON s.materia_id = m.id
        JOIN profesores p ON s.profesor_id = p.id
        JOIN usuarios u ON p.usuario_id = u.id
        JOIN oferta_academica oa ON s.oferta_academica_id = oa.id
        WHERE oa.pnf_id = ?
        ORDER BY m.nombre, u.apellido
    ");
    
    $stmt->execute([$pnf_id]);
    $secciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($secciones);
} catch (PDOException $e) {
    echo json_encode([]);
}
?>