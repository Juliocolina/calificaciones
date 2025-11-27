<?php
require_once __DIR__ . '/../config/conexion.php';

header('Content-Type: application/json');

if (!isset($_GET['pnf_id']) || !isset($_GET['estudiante_id'])) {
    echo json_encode([]);
    exit;
}

$pnf_id = (int)$_GET['pnf_id'];
$estudiante_id = (int)$_GET['estudiante_id'];

try {
    $conn = conectar();
    
    $stmt = $conn->prepare("
        SELECT 
            s.id,
            s.cupo_maximo,
            m.nombre as materia_nombre,
            CONCAT(u.nombre, ' ', u.apellido) as profesor_nombre,
            CONCAT(a.nombre, ' - ', p.nombre, ' - ', t.slug, ' - ', tr.nombre) as oferta_descripcion,
            COUNT(i.id) as inscritos,
            CASE WHEN ie.id IS NOT NULL THEN 1 ELSE 0 END as ya_inscrito
        FROM secciones s
        JOIN materias m ON s.materia_id = m.id
        JOIN profesores pr ON s.profesor_id = pr.id
        JOIN usuarios u ON pr.usuario_id = u.id
        JOIN oferta_academica oa ON s.oferta_academica_id = oa.id
        JOIN aldeas a ON oa.aldea_id = a.id
        JOIN pnfs p ON oa.pnf_id = p.id
        JOIN trayectos t ON oa.trayecto_id = t.id
        JOIN trimestres tr ON oa.trimestre_id = tr.id
        LEFT JOIN inscripciones i ON s.id = i.seccion_id AND i.estatus = 'Cursando'
        LEFT JOIN inscripciones ie ON s.id = ie.seccion_id AND ie.estudiante_id = ? AND ie.estatus = 'Cursando'
        WHERE oa.pnf_id = ? AND oa.estatus = 'Abierto'
        GROUP BY s.id, s.cupo_maximo, m.nombre, u.nombre, u.apellido, a.nombre, p.nombre, t.slug, tr.nombre, ie.id
        ORDER BY m.nombre
    ");
    
    $stmt->execute([$estudiante_id, $pnf_id]);
    $secciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($secciones);
} catch (PDOException $e) {
    echo json_encode([]);
}
?>