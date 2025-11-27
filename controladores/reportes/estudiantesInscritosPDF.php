<?php
session_start();
require_once '../../config/conexion.php';
require_once '../../controladores/hellpers/auth.php';
require_once '../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

verificarRol(['admin', 'coordinador']);

$pdo = conectar();
$usuario_id = $_SESSION['usuario_id'];
$rol = $_SESSION['rol'];

// Obtener filtros
$aldea_id = intval($_POST['aldea_id'] ?? 0);
$pnf_id = intval($_POST['pnf_id'] ?? 0);
$trayecto_id = intval($_POST['trayecto_id'] ?? 0);
$materia_id = intval($_POST['materia_id'] ?? 0);
$periodo_academico = $_POST['periodo_academico'] ?? '';
$estado = $_POST['estado'] ?? '';

// Restricción para coordinadores
if ($rol === 'coordinador') {
    $stmt = $pdo->prepare("SELECT aldea_id FROM coordinadores WHERE usuario_id = ?");
    $stmt->execute([$usuario_id]);
    $coord_data = $stmt->fetch();
    $aldea_coordinador = $coord_data['aldea_id'] ?? null;
    
    if ($aldea_coordinador && $aldea_id !== $aldea_coordinador) {
        die('Acceso denegado: Solo puede generar reportes de su aldea asignada.');
    }
}

// Validaciones
if (!$aldea_id || !$pnf_id) {
    die('Error: Debe seleccionar aldea y PNF.');
}

// Construir consulta
$where_conditions = ["oa.aldea_id = ?", "oa.pnf_id = ?"];
$params = [$aldea_id, $pnf_id];

if ($trayecto_id > 0) {
    $where_conditions[] = "oa.trayecto_id = ?";
    $params[] = $trayecto_id;
}

if ($materia_id > 0) {
    $where_conditions[] = "m.id = ?";
    $params[] = $materia_id;
}

if (!empty($periodo_academico)) {
    $where_conditions[] = "(c.periodo_academico = ? OR c.periodo_academico IS NULL)";
    $params[] = $periodo_academico;
}

// Filtro por estado (solo basado en notas, ignora estatus inscripción)
$having_condition = '';
if ($estado === 'aprobados') {
    $having_condition = 'HAVING (MAX(m.nombre) LIKE "%Proyecto%" AND MAX(c.nota_numerica) >= 16) OR (MAX(m.nombre) NOT LIKE "%Proyecto%" AND MAX(c.nota_numerica) >= 12)';
} elseif ($estado === 'reprobados') {
    $having_condition = 'HAVING ((MAX(m.nombre) LIKE "%Proyecto%" AND MAX(c.nota_numerica) < 16) OR (MAX(m.nombre) NOT LIKE "%Proyecto%" AND MAX(c.nota_numerica) < 12)) AND MAX(c.nota_numerica) IS NOT NULL';
} elseif ($estado === 'pendientes') {
    $having_condition = 'HAVING MAX(c.nota_numerica) IS NULL';
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Consulta principal - Sin duplicados, prioriza inscripciones con nota
$stmt = $pdo->prepare("
    SELECT 
        u.cedula,
        CONCAT(u.nombre, ' ', u.apellido) as nombre_completo,
        MAX(e.codigo_estudiante) as codigo_estudiante,
        m.nombre as materia_nombre,
        MAX(m.creditos) as creditos,
        MAX(m.duracion) as duracion,
        MAX(s.codigo_seccion) as codigo_seccion,
        MAX(CONCAT(up.nombre, ' ', up.apellido)) as profesor_nombre,
        MAX(i.estatus) as estado_inscripcion,
        MAX(c.nota_numerica) as nota_numerica,
        MAX(c.periodo_academico) as periodo_calificacion,
        MAX(t.slug) as trayecto,
        CASE 
            WHEN MAX(c.nota_numerica) IS NOT NULL THEN 'Calificado'
            WHEN MAX(i.estatus) = 'Cursando' THEN 'Pendiente'
            ELSE 'Sin Calificar'
        END as estado_calificacion
    FROM inscripciones i
    JOIN estudiantes e ON i.estudiante_id = e.id
    JOIN usuarios u ON e.usuario_id = u.id
    JOIN secciones s ON i.seccion_id = s.id
    JOIN materias m ON s.materia_id = m.id
    JOIN oferta_academica oa ON s.oferta_academica_id = oa.id
    JOIN trayectos t ON oa.trayecto_id = t.id
    JOIN profesores pr ON s.profesor_id = pr.id
    JOIN usuarios up ON pr.usuario_id = up.id
    LEFT JOIN calificaciones c ON i.id = c.inscripcion_id
    $where_clause
    GROUP BY u.cedula, m.id, m.nombre
    $having_condition
    ORDER BY MAX(t.slug), m.nombre, u.apellido, u.nombre
");
$stmt->execute($params);
$estudiantes = $stmt->fetchAll();

// Obtener información adicional para el encabezado
$stmt = $pdo->prepare("
    SELECT a.nombre as aldea_nombre, p.nombre as pnf_nombre
    FROM aldeas a, pnfs p 
    WHERE a.id = ? AND p.id = ?
");
$stmt->execute([$aldea_id, $pnf_id]);
$info = $stmt->fetch();

$trayecto_nombre = '';
if ($trayecto_id > 0) {
    $stmt = $pdo->prepare("SELECT nombre FROM trayectos WHERE id = ?");
    $stmt->execute([$trayecto_id]);
    $trayecto_nombre = $stmt->fetchColumn();
}

$materia_nombre = '';
if ($materia_id > 0) {
    $stmt = $pdo->prepare("SELECT nombre FROM materias WHERE id = ?");
    $stmt->execute([$materia_id]);
    $materia_nombre = $stmt->fetchColumn();
}

// Estadísticas
$total_estudiantes = count($estudiantes);
$calificados = count(array_filter($estudiantes, fn($e) => $e['estado_calificacion'] === 'Calificado'));
$pendientes = $total_estudiantes - $calificados;

// Generar HTML para PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { font-size: 16px; margin: 5px 0; color: #2c3e50; }
        .header h2 { font-size: 14px; margin: 5px 0; color: #34495e; }
        .info-box { background: #f8f9fa; padding: 10px; margin: 15px 0; border-left: 4px solid #007bff; }
        .info-row { display: inline-block; width: 48%; margin: 2px 0; }
        .stats { background: #e9ecef; padding: 8px; margin: 10px 0; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #343a40; color: white; font-weight: bold; }
        .text-center { text-align: center; }
        .badge-success { background: #28a745; color: white; padding: 2px 6px; border-radius: 3px; }
        .badge-warning { background: #ffc107; color: #212529; padding: 2px 6px; border-radius: 3px; }
        .badge-info { background: #17a2b8; color: white; padding: 2px 6px; border-radius: 3px; }
        .small { font-size: 8px; color: #6c757d; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SICAN - Sistema Integral de Calificaciones Académicas</h1>
        <h2>Reporte de Estudiantes Inscritos</h2>
        <p><em>Misión Sucre | Municipio Miranda, Estado Falcón, Venezuela</em></p>
    </div>

    <div class="info-box">
        <div class="info-row"><strong>Aldea:</strong> ' . htmlspecialchars($info['aldea_nombre']) . '</div>
        <div class="info-row"><strong>PNF:</strong> ' . htmlspecialchars($info['pnf_nombre']) . '</div>
        ' . ($trayecto_nombre ? '<div class="info-row"><strong>Trayecto:</strong> ' . htmlspecialchars($trayecto_nombre) . '</div>' : '') . '
        ' . ($materia_nombre ? '<div class="info-row"><strong>Materia:</strong> ' . htmlspecialchars($materia_nombre) . '</div>' : '') . '
        ' . ($periodo_academico ? '<div class="info-row"><strong>Período:</strong> ' . htmlspecialchars($periodo_academico) . '</div>' : '') . '
        <div class="info-row"><strong>Fecha:</strong> ' . date('d/m/Y H:i') . '</div>
    </div>

    <div class="stats">
        <strong>Resumen:</strong> 
        Total: ' . $total_estudiantes . ' estudiantes | 
        Calificados: ' . $calificados . ' | 
        Pendientes: ' . $pendientes . '
    </div>

    <table>
        <thead>
            <tr>
                <th>Cédula</th>
                <th>Estudiante</th>
                <th>Código</th>
                <th>Materia</th>
                <th>Sección</th>
                <th>Profesor</th>
                <th>Estado</th>
                <th>Nota</th>
            </tr>
        </thead>
        <tbody>';

foreach ($estudiantes as $estudiante) {
    // Determinar badge según estado
    if ($estudiante['estado_calificacion'] === 'Calificado') {
        $estado_badge = 'badge-success';
    } elseif ($estudiante['estado_calificacion'] === 'Pendiente') {
        $estado_badge = 'badge-warning';
    } else {
        $estado_badge = 'badge-info';
    }
    
    $nota_display = $estudiante['nota_numerica'] ? $estudiante['nota_numerica'] : '-';
    
    // Mostrar estado de inscripción si está reprobada
    $estado_inscripcion = '';
    if ($estudiante['estado_inscripcion'] === 'Reprobada') {
        $estado_inscripcion = '<div class="small" style="color: #dc3545;">(Reprobada)</div>';
    } elseif ($estudiante['estado_inscripcion'] === 'Aprobada') {
        $estado_inscripcion = '<div class="small" style="color: #28a745;">(Aprobada)</div>';
    }
    
    $html .= '
            <tr>
                <td>' . htmlspecialchars($estudiante['cedula']) . '</td>
                <td>' . htmlspecialchars($estudiante['nombre_completo']) . '</td>
                <td>' . htmlspecialchars($estudiante['codigo_estudiante']) . '</td>
                <td>
                    ' . htmlspecialchars($estudiante['materia_nombre']) . '
                    <div class="small">(' . ($estudiante['creditos'] ?? 0) . ' créditos - ' . ucfirst($estudiante['duracion'] ?? '') . ')</div>
                    ' . $estado_inscripcion . '
                </td>
                <td>' . htmlspecialchars($estudiante['codigo_seccion']) . '</td>
                <td>' . htmlspecialchars($estudiante['profesor_nombre']) . '</td>
                <td class="text-center">
                    <span class="' . $estado_badge . '">' . $estudiante['estado_calificacion'] . '</span>
                </td>
                <td class="text-center">' . $nota_display . '</td>
            </tr>';
}

$html .= '
        </tbody>
    </table>
</body>
</html>';

// Configurar Dompdf
$options = new Options();
$options->set('defaultFont', 'Arial');
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// Generar nombre del archivo
$filename = 'estudiantes_inscritos_' . date('Y-m-d_H-i-s') . '.pdf';

// Enviar PDF al navegador
$dompdf->stream($filename, array('Attachment' => false));
?>