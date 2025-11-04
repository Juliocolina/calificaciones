<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';

verificarSesion();

$asignaciones = [];
$error_message = '';

try {
    $conn = conectar();
    
    // Obtener todas las asignaciones de profesores con información completa
    $stmt = $conn->prepare("
        SELECT 
            omp.id,
            oa.id AS oferta_id,
            p.nombre AS nombre_pnf,
            t.nombre AS nombre_trayecto,
            tr.nombre AS nombre_trimestre,
            m.nombre AS nombre_materia,
            m.codigo AS codigo_materia,
            u.nombre AS nombre_profesor,
            u.apellido AS apellido_profesor,
            u.cedula AS cedula_profesor,
            oa.estatus AS estatus_oferta,
            omp.created_at AS fecha_asignacion
        FROM oferta_materia_profesor omp
        JOIN oferta_materias om ON omp.oferta_materia_id = om.id
        JOIN oferta_academica oa ON om.oferta_academica_id = oa.id
        JOIN materias m ON om.materia_id = m.id
        JOIN pnfs p ON oa.pnf_id = p.id
        JOIN trayectos t ON oa.trayecto_id = t.id
        JOIN trimestres tr ON oa.trimestre_id = tr.id
        JOIN profesores prof ON omp.profesor_id = prof.id
        JOIN usuarios u ON prof.usuario_id = u.id
        ORDER BY oa.id DESC, m.nombre ASC
    ");
    
    $stmt->execute();
    $asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error_message = 'Error al cargar las asignaciones: ' . $e->getMessage();
}
?>