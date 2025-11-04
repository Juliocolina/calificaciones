<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';

verificarSesion();

$calificaciones = [];
$ofertas = [];
$oferta_seleccionada = 0;
$error_message = '';

try {
    $conn = conectar();
    
    // Obtener ofertas disponibles
    $stmt_ofertas = $conn->prepare("
        SELECT 
            oa.id,
            CONCAT(p.nombre, ' - ', t.nombre, ' - ', tr.nombre) AS nombre_completo
        FROM oferta_academica oa
        JOIN pnfs p ON oa.pnf_id = p.id
        JOIN trayectos t ON oa.trayecto_id = t.id
        JOIN trimestres tr ON oa.trimestre_id = tr.id
        WHERE oa.estatus IN ('Abierto', 'Planificado')
        ORDER BY oa.created_at DESC
    ");
    $stmt_ofertas->execute();
    $ofertas = $stmt_ofertas->fetchAll(PDO::FETCH_ASSOC);
    
    // Si se seleccionó una oferta, obtener calificaciones
    if (isset($_GET['oferta_id']) && is_numeric($_GET['oferta_id'])) {
        $oferta_seleccionada = intval($_GET['oferta_id']);
        
        $stmt_calificaciones = $conn->prepare("
            SELECT 
                u.cedula,
                u.nombre,
                u.apellido,
                m.nombre AS materia_nombre,
                c.nota_numerica,
                c.tipo_evaluacion,
                c.fecha_registro,
                i.estatus
            FROM calificaciones c
            JOIN inscripciones i ON c.inscripcion_id = i.id
            JOIN estudiantes e ON i.estudiante_id = e.id
            JOIN usuarios u ON e.usuario_id = u.id
            JOIN oferta_materias om ON i.oferta_materia_id = om.id
            JOIN materias m ON om.materia_id = m.id
            WHERE om.oferta_academica_id = ?
            ORDER BY u.apellido, u.nombre, m.nombre, c.fecha_registro DESC
        ");
        $stmt_calificaciones->execute([$oferta_seleccionada]);
        $calificaciones = $stmt_calificaciones->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (Exception $e) {
    $error_message = 'Error al cargar calificaciones: ' . $e->getMessage();
}
?>