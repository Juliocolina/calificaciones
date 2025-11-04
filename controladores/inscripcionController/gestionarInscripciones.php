<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';

verificarSesion();

$ofertas_abiertas = [];
$error_message = '';

try {
    $conn = conectar();
    

    
    // Obtener ofertas abiertas para inscripciones (sin filtro por aldea)
    $sql = "
        SELECT 
            oa.id,
            p.nombre AS nombre_pnf,
            t.nombre AS nombre_trayecto,
            tr.nombre AS nombre_trimestre,
            oa.estatus,
            COUNT(DISTINCT om.id) AS total_materias,
            COUNT(DISTINCT i.estudiante_id) AS total_inscritos
        FROM oferta_academica oa
        JOIN pnfs p ON oa.pnf_id = p.id
        JOIN trayectos t ON oa.trayecto_id = t.id
        JOIN trimestres tr ON oa.trimestre_id = tr.id
        LEFT JOIN oferta_materias om ON oa.id = om.oferta_academica_id
        LEFT JOIN inscripciones i ON om.id = i.oferta_materia_id
        WHERE oa.estatus = 'Abierto'
        GROUP BY oa.id ORDER BY oa.created_at DESC";
    
    $params = [];
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $ofertas_abiertas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error_message = 'Error al cargar ofertas: ' . $e->getMessage();
}
?>