<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';

verificarSesion();

$mis_estudiantes = [];
$error_message = '';
$oferta_seleccionada = null;
$oferta_id = isset($_GET['oferta_id']) ? (int)$_GET['oferta_id'] : null;

try {
    $conn = conectar();
    $usuario_id = $_SESSION['usuario_id'];
    
    // Si hay oferta_id, obtener información de la oferta
    if ($oferta_id) {
        $stmt_oferta = $conn->prepare("
            SELECT DISTINCT
                oa.id AS oferta_id,
                p.nombre AS pnf_nombre,
                t.nombre AS trayecto_nombre,
                tr.nombre AS trimestre_nombre
            FROM oferta_academica oa
            JOIN pnfs p ON oa.pnf_id = p.id
            JOIN trayectos t ON oa.trayecto_id = t.id
            JOIN trimestres tr ON oa.trimestre_id = tr.id
            JOIN oferta_materias om ON oa.id = om.oferta_academica_id
            JOIN oferta_materia_profesor omp ON om.id = omp.oferta_materia_id
            JOIN profesores prof ON omp.profesor_id = prof.id
            WHERE oa.id = ? AND prof.usuario_id = ?
        ");
        $stmt_oferta->execute([$oferta_id, $usuario_id]);
        $oferta_seleccionada = $stmt_oferta->fetch(PDO::FETCH_ASSOC);
    }
    
    // Construir consulta base
    $sql = "
        SELECT DISTINCT
            e.id AS estudiante_id,
            u.cedula,
            u.nombre,
            u.apellido,
            u.correo,
            m.nombre AS materia_nombre,
            m.codigo AS materia_codigo,
            p.nombre AS pnf_nombre,
            t.nombre AS trayecto_nombre,
            tr.nombre AS trimestre_nombre,
            i.estatus AS estatus_inscripcion,
            oa.id AS oferta_id,
            om.id AS oferta_materia_id,
            i.id AS inscripcion_id
        FROM inscripciones i
        JOIN estudiantes e ON i.estudiante_id = e.id
        JOIN usuarios u ON e.usuario_id = u.id
        JOIN oferta_materias om ON i.oferta_materia_id = om.id
        JOIN oferta_materia_profesor omp ON om.id = omp.oferta_materia_id
        JOIN profesores prof ON omp.profesor_id = prof.id
        JOIN materias m ON om.materia_id = m.id
        JOIN oferta_academica oa ON om.oferta_academica_id = oa.id
        JOIN pnfs p ON oa.pnf_id = p.id
        JOIN trayectos t ON oa.trayecto_id = t.id
        JOIN trimestres tr ON oa.trimestre_id = tr.id
        WHERE prof.usuario_id = ?
    ";
    
    $params = [$usuario_id];
    
    // Filtrar por oferta si se especifica
    if ($oferta_id && $oferta_seleccionada) {
        $sql .= " AND oa.id = ?";
        $params[] = $oferta_id;
    }
    
    $sql .= " ORDER BY p.nombre, t.nombre, m.nombre, u.apellido, u.nombre";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $mis_estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error_message = 'Error al cargar estudiantes: ' . $e->getMessage();
}
?>