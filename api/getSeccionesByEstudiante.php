<?php
require_once __DIR__ . '/../config/conexion.php';

header('Content-Type: application/json');

if (!isset($_GET['estudiante_id']) || !isset($_GET['pnf_id']) || !isset($_GET['trayecto_id']) || !isset($_GET['trimestre_id'])) {
    echo json_encode([]);
    exit;
}

$estudiante_id = (int)$_GET['estudiante_id'];
$pnf_id = (int)$_GET['pnf_id'];
$trayecto_id = (int)$_GET['trayecto_id'];
$trimestre_id = (int)$_GET['trimestre_id'];

try {
    $conn = conectar();
    
    // Verificar si aprobó el proyecto del trayecto ANTERIOR
    $puede_ver_proyecto_actual = true;
    if ($trayecto_id > 6) { // Trayecto 6 = TRAY-1, 7 = TRAY-2, etc.
        // Mapear trayecto real a número romano
        $trayectos_romanos = [6 => 'I', 7 => 'II', 8 => 'III', 9 => 'IV'];
        $trayecto_anterior = $trayecto_id - 1;
        $proyecto_anterior = 'Proyecto socio tecnológico ' . $trayectos_romanos[$trayecto_anterior];
        
        $stmt_proyecto = $conn->prepare("
            SELECT MAX(c.nota_numerica) as nota_proyecto
            FROM inscripciones i
            JOIN secciones s ON i.seccion_id = s.id
            JOIN materias m ON s.materia_id = m.id
            JOIN calificaciones c ON i.id = c.inscripcion_id
            WHERE i.estudiante_id = ? 
              AND m.nombre = ?
        ");
        $stmt_proyecto->execute([$estudiante_id, $proyecto_anterior]);
        $resultado_proyecto = $stmt_proyecto->fetch();
        $nota_proyecto_anterior = $resultado_proyecto['nota_proyecto'] ?? null;
        
        // Si no aprobó proyecto anterior (≤15) no puede ver proyecto actual
        $puede_ver_proyecto_actual = ($nota_proyecto_anterior !== null && $nota_proyecto_anterior >= 16);
    }
    
    $stmt = $conn->prepare("
        SELECT 
            s.id,
            s.cupo_maximo,
            m.id as materia_id,
            m.nombre as materia_nombre,
            CONCAT(u.nombre, ' ', u.apellido) as profesor_nombre,
            COUNT(i.id) as inscritos,
            CASE WHEN ie.id IS NOT NULL THEN 1 ELSE 0 END as ya_inscrito,
            ie.estatus as estatus_inscripcion,
            CASE WHEN materias_aprobadas.materia_id IS NOT NULL THEN 1 ELSE 0 END as ya_aprobada,
            CASE WHEN m.nombre LIKE 'Proyecto socio tecnológico%' AND ? = 0 THEN 1 ELSE 0 END as bloqueado_por_proyecto
        FROM secciones s
        JOIN materias m ON s.materia_id = m.id
        JOIN profesores pr ON s.profesor_id = pr.id
        JOIN usuarios u ON pr.usuario_id = u.id
        JOIN oferta_academica oa ON s.oferta_academica_id = oa.id
        LEFT JOIN inscripciones i ON s.id = i.seccion_id
        LEFT JOIN inscripciones ie ON s.id = ie.seccion_id AND ie.estudiante_id = ?
        LEFT JOIN (
            SELECT DISTINCT sa.materia_id
            FROM inscripciones ia
            JOIN secciones sa ON ia.seccion_id = sa.id
            JOIN materias ma ON sa.materia_id = ma.id
            JOIN calificaciones ca ON ia.id = ca.inscripcion_id
            WHERE ia.estudiante_id = ? 
              AND (
                  (ma.nombre LIKE '%proyecto socio tecnológico%' AND ca.nota_numerica >= 16) OR
                  (ma.nombre NOT LIKE '%proyecto socio tecnológico%' AND ca.nota_numerica >= 12)
              )
        ) materias_aprobadas ON materias_aprobadas.materia_id = m.id
        WHERE oa.pnf_id = ? 
          AND oa.trayecto_id = ? 
          AND oa.estatus = 'Abierto'
          AND oa.trimestre_id = ?
        GROUP BY s.id, s.cupo_maximo, m.id, m.nombre, u.nombre, u.apellido, ie.id, materias_aprobadas.materia_id
        ORDER BY m.nombre
    ");
    
    $stmt->execute([$puede_ver_proyecto_actual ? 1 : 0, $estudiante_id, $estudiante_id, $pnf_id, $trayecto_id, $trimestre_id]);
    $secciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($secciones);
} catch (PDOException $e) {
    echo json_encode([]);
}
?>