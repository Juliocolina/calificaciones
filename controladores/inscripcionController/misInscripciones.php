<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';

verificarSesion();

$mis_inscripciones = [];
$error_message = '';

try {
    $conn = conectar();
    $usuario_id = $_SESSION['usuario_id'];
    
    // Obtener inscripciones del estudiante
    $stmt = $conn->prepare("
        SELECT 
            i.id AS inscripcion_id,
            i.estatus,
            i.created_at AS fecha_inscripcion,
            m.nombre AS materia_nombre,
            m.codigo AS materia_codigo,
            m.creditos,
            p.nombre AS pnf_nombre,
            t.nombre AS trayecto_nombre,
            tr.nombre AS trimestre_nombre,
            tr.fecha_inicio,
            tr.fecha_fin,
            oa.estatus AS estatus_oferta,
            prof_u.nombre AS profesor_nombre,
            prof_u.apellido AS profesor_apellido,
            -- Obtener la nota máxima histórica
            (
                SELECT MAX(c.nota_numerica)
                FROM calificaciones c
                WHERE c.inscripcion_id = i.id
            ) AS nota_maxima
        FROM inscripciones i
        JOIN estudiantes e ON i.estudiante_id = e.id
        JOIN oferta_materias om ON i.oferta_materia_id = om.id
        JOIN materias m ON om.materia_id = m.id
        JOIN oferta_academica oa ON om.oferta_academica_id = oa.id
        JOIN pnfs p ON oa.pnf_id = p.id
        JOIN trayectos t ON oa.trayecto_id = t.id
        JOIN trimestres tr ON oa.trimestre_id = tr.id
        LEFT JOIN oferta_materia_profesor omp ON om.id = omp.oferta_materia_id
        LEFT JOIN profesores prof ON omp.profesor_id = prof.id
        LEFT JOIN usuarios prof_u ON prof.usuario_id = prof_u.id
        WHERE e.usuario_id = ?
        ORDER BY tr.fecha_inicio DESC, m.nombre ASC
    ");
    
    $stmt->execute([$usuario_id]);
    $mis_inscripciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error_message = 'Error al cargar inscripciones: ' . $e->getMessage();
}
?>