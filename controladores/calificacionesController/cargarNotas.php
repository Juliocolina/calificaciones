<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';

verificarSesion();

$mis_ofertas = [];
$error_message = '';

try {
    $conn = conectar();
    $usuario_id = $_SESSION['usuario_id'];
    
    // Obtener ofertas donde el profesor tiene materias asignadas
    $stmt = $conn->prepare("
        SELECT DISTINCT
            oa.id AS oferta_id,
            p.nombre AS pnf_nombre,
            t.nombre AS trayecto_nombre,
            tr.nombre AS trimestre_nombre,
            oa.estatus,
            COUNT(DISTINCT om.id) AS total_materias,
            COUNT(DISTINCT i.id) AS total_inscritos
        FROM oferta_academica oa
        JOIN pnfs p ON oa.pnf_id = p.id
        JOIN trayectos t ON oa.trayecto_id = t.id
        JOIN trimestres tr ON oa.trimestre_id = tr.id
        JOIN oferta_materias om ON oa.id = om.oferta_academica_id
        JOIN oferta_materia_profesor omp ON om.id = omp.oferta_materia_id
        JOIN profesores prof ON omp.profesor_id = prof.id
        LEFT JOIN inscripciones i ON om.id = i.oferta_materia_id
        WHERE prof.usuario_id = ? AND oa.estatus = 'Abierto'
        GROUP BY oa.id
        ORDER BY oa.created_at DESC
    ");
    
    $stmt->execute([$usuario_id]);
    $mis_ofertas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error_message = 'Error al cargar ofertas: ' . $e->getMessage();
}
?>