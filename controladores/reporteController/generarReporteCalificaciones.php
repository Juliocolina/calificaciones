<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';

use Dompdf\Dompdf;
use Dompdf\Options;

verificarSesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Acceso no permitido');
}

$conn = conectar();

// Obtener filtros
$tipo_reporte = $_POST['tipo_reporte'] ?? '';
$oferta_id = !empty($_POST['oferta_id']) ? intval($_POST['oferta_id']) : null;
$aldea_id = !empty($_POST['aldea_id']) ? intval($_POST['aldea_id']) : null;
$pnf_id = !empty($_POST['pnf_id']) ? intval($_POST['pnf_id']) : null;
$cedula_estudiante = !empty($_POST['cedula_estudiante']) ? trim($_POST['cedula_estudiante']) : null;
$rango_notas = $_POST['rango_notas'] ?? '';

$incluir_promedios = isset($_POST['incluir_promedios']);
$incluir_estadisticas = isset($_POST['incluir_estadisticas']);
$incluir_detalles = isset($_POST['incluir_detalles']);

// Verificar permisos de coordinador
$usuario_actual = $_SESSION['usuario'];
if ($usuario_actual['rol'] === 'coordinador') {
    $stmt_coord = $conn->prepare("SELECT aldea_id FROM coordinadores WHERE usuario_id = ?");
    $stmt_coord->execute([$usuario_actual['id']]);
    $coord_data = $stmt_coord->fetch(PDO::FETCH_ASSOC);
    $aldea_coordinador = $coord_data['aldea_id'] ?? null;
    
    // Forzar filtro por aldea del coordinador
    $aldea_id = $aldea_coordinador;
}

// Construir consulta base
$sql = "
    SELECT 
        c.nota_numerica,
        c.tipo_evaluacion,
        c.fecha_registro,
        u.cedula,
        u.nombre,
        u.apellido,
        m.nombre AS materia_nombre,
        m.codigo AS materia_codigo,
        m.duracion AS materia_duracion,
        p.nombre AS pnf_nombre,
        t.nombre AS trayecto_nombre,
        tr.nombre AS trimestre_nombre,
        a.nombre AS aldea_nombre,
        i.estatus AS estatus_inscripcion
    FROM calificaciones c
    JOIN inscripciones i ON c.inscripcion_id = i.id
    JOIN estudiantes e ON i.estudiante_id = e.id
    JOIN usuarios u ON e.usuario_id = u.id
    JOIN oferta_materias om ON i.oferta_materia_id = om.id
    JOIN materias m ON om.materia_id = m.id
    JOIN oferta_academica oa ON om.oferta_academica_id = oa.id
    JOIN pnfs p ON oa.pnf_id = p.id
    JOIN trayectos t ON oa.trayecto_id = t.id
    JOIN trimestres tr ON oa.trimestre_id = tr.id
    LEFT JOIN aldeas a ON e.aldea_id = a.id
    WHERE 1=1
";

$params = [];

// Aplicar filtros
if ($aldea_id) {
    $sql .= " AND e.aldea_id = ?";
    $params[] = $aldea_id;
}

if ($pnf_id) {
    $sql .= " AND oa.pnf_id = ?";
    $params[] = $pnf_id;
}

if ($oferta_id) {
    $sql .= " AND oa.id = ?";
    $params[] = $oferta_id;
}

if ($cedula_estudiante && $tipo_reporte === 'individual') {
    $sql .= " AND u.cedula = ?";
    $params[] = $cedula_estudiante;
}

// Filtro por rango de notas
if ($rango_notas) {
    switch ($rango_notas) {
        case 'excelente':
            $sql .= " AND c.nota_numerica BETWEEN 18 AND 20";
            break;
        case 'bueno':
            $sql .= " AND c.nota_numerica BETWEEN 15 AND 17";
            break;
        case 'regular':
            $sql .= " AND c.nota_numerica BETWEEN 12 AND 14";
            break;
        case 'deficiente':
            $sql .= " AND c.nota_numerica < 12";
            break;
    }
}

$sql .= " ORDER BY u.apellido, u.nombre, m.nombre, c.fecha_registro";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$calificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar datos según tipo de reporte
$datos_procesados = [];
$estadisticas = [];

if ($tipo_reporte === 'individual' && $cedula_estudiante) {
    // Agrupar por materia para el estudiante
    foreach ($calificaciones as $cal) {
        $materia_key = $cal['materia_codigo'];
        $datos_procesados[$materia_key]['materia'] = $cal['materia_nombre'];
        $datos_procesados[$materia_key]['codigo'] = $cal['materia_codigo'];
        $datos_procesados[$materia_key]['duracion'] = $cal['materia_duracion'];
        $datos_procesados[$materia_key]['notas'][] = $cal['nota_numerica'];
        $datos_procesados[$materia_key]['estudiante'] = $cal['nombre'] . ' ' . $cal['apellido'];
        $datos_procesados[$materia_key]['cedula'] = $cal['cedula'];
    }
    
    // Calcular promedios por materia
    foreach ($datos_procesados as &$materia) {
        $materia['promedio'] = array_sum($materia['notas']) / count($materia['notas']);
        $materia['total_notas'] = count($materia['notas']);
    }
    
} else {
    // Agrupar por estudiante para reportes grupales
    foreach ($calificaciones as $cal) {
        $estudiante_key = $cal['cedula'];
        $materia_key = $cal['materia_codigo'];
        
        $datos_procesados[$estudiante_key]['estudiante'] = $cal['nombre'] . ' ' . $cal['apellido'];
        $datos_procesados[$estudiante_key]['cedula'] = $cal['cedula'];
        $datos_procesados[$estudiante_key]['aldea'] = $cal['aldea_nombre'];
        $datos_procesados[$estudiante_key]['materias'][$materia_key]['nombre'] = $cal['materia_nombre'];
        $datos_procesados[$estudiante_key]['materias'][$materia_key]['notas'][] = $cal['nota_numerica'];
    }
    
    // Calcular promedios
    foreach ($datos_procesados as &$estudiante) {
        $promedios_materias = [];
        foreach ($estudiante['materias'] as &$materia) {
            $materia['promedio'] = array_sum($materia['notas']) / count($materia['notas']);
            $promedios_materias[] = $materia['promedio'];
        }
        $estudiante['promedio_general'] = !empty($promedios_materias) ? array_sum($promedios_materias) / count($promedios_materias) : 0;
    }
}

// Calcular estadísticas generales
if ($incluir_estadisticas) {
    $total_calificaciones = count($calificaciones);
    $notas_aprobadas = count(array_filter($calificaciones, function($c) { return $c['nota_numerica'] >= 12; }));
    $notas_reprobadas = $total_calificaciones - $notas_aprobadas;
    $promedio_general = $total_calificaciones > 0 ? array_sum(array_column($calificaciones, 'nota_numerica')) / $total_calificaciones : 0;
    
    $estadisticas = [
        'total_calificaciones' => $total_calificaciones,
        'notas_aprobadas' => $notas_aprobadas,
        'notas_reprobadas' => $notas_reprobadas,
        'promedio_general' => round($promedio_general, 2),
        'porcentaje_aprobacion' => $total_calificaciones > 0 ? round(($notas_aprobadas / $total_calificaciones) * 100, 2) : 0
    ];
}

// Obtener información de filtros para el título
$titulo_filtros = [];
if ($aldea_id) {
    $stmt_aldea = $conn->prepare("SELECT nombre FROM aldeas WHERE id = ?");
    $stmt_aldea->execute([$aldea_id]);
    $aldea_nombre = $stmt_aldea->fetchColumn();
    $titulo_filtros[] = "Aldea: $aldea_nombre";
}

if ($pnf_id) {
    $stmt_pnf = $conn->prepare("SELECT nombre FROM pnfs WHERE id = ?");
    $stmt_pnf->execute([$pnf_id]);
    $pnf_nombre = $stmt_pnf->fetchColumn();
    $titulo_filtros[] = "PNF: $pnf_nombre";
}

if ($oferta_id) {
    $stmt_oferta = $conn->prepare("
        SELECT CONCAT(p.nombre, ' - ', t.nombre, ' - ', tr.nombre) as oferta_nombre
        FROM oferta_academica oa
        JOIN pnfs p ON oa.pnf_id = p.id
        JOIN trayectos t ON oa.trayecto_id = t.id
        JOIN trimestres tr ON oa.trimestre_id = tr.id
        WHERE oa.id = ?
    ");
    $stmt_oferta->execute([$oferta_id]);
    $oferta_nombre = $stmt_oferta->fetchColumn();
    $titulo_filtros[] = "Oferta: $oferta_nombre";
}

$subtitulo = !empty($titulo_filtros) ? implode(' | ', $titulo_filtros) : 'Reporte General';

// Generar HTML para PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Calificaciones</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 15px; }
        .header { text-align: center; margin-bottom: 25px; }
        .header h1 { color: #28a745; margin-bottom: 5px; font-size: 18px; }
        .header p { color: #666; margin: 3px 0; }
        .info { background: #f8f9fa; padding: 8px; margin-bottom: 15px; border-radius: 3px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #28a745; color: white; font-weight: bold; font-size: 10px; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .footer { margin-top: 20px; text-align: center; font-size: 9px; color: #666; }
        .estadisticas { display: flex; justify-content: space-around; margin: 15px 0; }
        .stat-box { text-align: center; padding: 8px; background: #e9ecef; border-radius: 3px; }
        .promedio-alto { background-color: #d4edda; }
        .promedio-medio { background-color: #fff3cd; }
        .promedio-bajo { background-color: #f8d7da; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SISTEMA MISIÓN SUCRE</h1>
        <h2>Reporte de Calificaciones - ' . ucfirst($tipo_reporte) . '</h2>
        <p>' . $subtitulo . '</p>
        <p>Generado el: ' . date('d/m/Y H:i:s') . '</p>
    </div>';

// Mostrar estadísticas si están habilitadas
if ($incluir_estadisticas && !empty($estadisticas)) {
    $html .= '
    <div class="info">
        <strong>Estadísticas Generales:</strong><br>
        Total de Calificaciones: ' . $estadisticas['total_calificaciones'] . ' | 
        Aprobadas: ' . $estadisticas['notas_aprobadas'] . ' (' . $estadisticas['porcentaje_aprobacion'] . '%) | 
        Reprobadas: ' . $estadisticas['notas_reprobadas'] . ' | 
        Promedio General: ' . $estadisticas['promedio_general'] . '
    </div>';
}

// Generar contenido según tipo de reporte
if ($tipo_reporte === 'individual' && !empty($datos_procesados)) {
    $estudiante_info = reset($datos_procesados);
    $html .= '<h3>Estudiante: ' . htmlspecialchars($estudiante_info['estudiante']) . ' (C.I: ' . htmlspecialchars($estudiante_info['cedula']) . ')</h3>';
    
    $html .= '<table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Materia</th>
                <th>Duración</th>
                <th>Total Notas</th>';
    
    if ($incluir_detalles) {
        $html .= '<th>Notas Detalladas</th>';
    }
    
    if ($incluir_promedios) {
        $html .= '<th>Promedio</th>';
    }
    
    $html .= '</tr></thead><tbody>';
    
    $promedios_generales = [];
    foreach ($datos_procesados as $materia) {
        $clase_promedio = '';
        if ($incluir_promedios) {
            if ($materia['promedio'] >= 15) $clase_promedio = 'promedio-alto';
            elseif ($materia['promedio'] >= 12) $clase_promedio = 'promedio-medio';
            else $clase_promedio = 'promedio-bajo';
            $promedios_generales[] = $materia['promedio'];
        }
        
        $html .= '<tr class="' . $clase_promedio . '">
            <td>' . htmlspecialchars($materia['codigo']) . '</td>
            <td>' . htmlspecialchars($materia['materia']) . '</td>
            <td>' . ucfirst($materia['duracion']) . '</td>
            <td>' . $materia['total_notas'] . '</td>';
        
        if ($incluir_detalles) {
            $html .= '<td>' . implode(', ', $materia['notas']) . '</td>';
        }
        
        if ($incluir_promedios) {
            $html .= '<td><strong>' . round($materia['promedio'], 2) . '</strong></td>';
        }
        
        $html .= '</tr>';
    }
    
    if ($incluir_promedios && !empty($promedios_generales)) {
        $promedio_final = array_sum($promedios_generales) / count($promedios_generales);
        $html .= '<tr style="background-color: #28a745; color: white; font-weight: bold;">
            <td colspan="' . ($incluir_detalles ? '5' : '4') . '">PROMEDIO GENERAL (AVERAGE)</td>
            <td><strong>' . round($promedio_final, 2) . '</strong></td>
        </tr>';
    }
    
    $html .= '</tbody></table>';
    
} else {
    // Reporte grupal
    $html .= '<table>
        <thead>
            <tr>
                <th>Cédula</th>
                <th>Estudiante</th>
                <th>Aldea</th>
                <th>Materias Cursadas</th>';
    
    if ($incluir_promedios) {
        $html .= '<th>Promedio General</th>';
    }
    
    $html .= '</tr></thead><tbody>';
    
    foreach ($datos_procesados as $estudiante) {
        $clase_promedio = '';
        if ($incluir_promedios) {
            if ($estudiante['promedio_general'] >= 15) $clase_promedio = 'promedio-alto';
            elseif ($estudiante['promedio_general'] >= 12) $clase_promedio = 'promedio-medio';
            else $clase_promedio = 'promedio-bajo';
        }
        
        $html .= '<tr class="' . $clase_promedio . '">
            <td>' . htmlspecialchars($estudiante['cedula']) . '</td>
            <td>' . htmlspecialchars($estudiante['estudiante']) . '</td>
            <td>' . htmlspecialchars($estudiante['aldea']) . '</td>
            <td>' . count($estudiante['materias']) . '</td>';
        
        if ($incluir_promedios) {
            $html .= '<td><strong>' . round($estudiante['promedio_general'], 2) . '</strong></td>';
        }
        
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table>';
}

$html .= '
    <div class="footer">
        <p>Sistema Misión Sucre - Municipio Miranda, Falcón</p>
        <p>Reporte generado por: ' . htmlspecialchars($_SESSION['usuario']['nombre'] . ' ' . $_SESSION['usuario']['apellido']) . '</p>
    </div>
</body>
</html>';

// Configurar y generar PDF
$options = new Options();
$options->set('defaultFont', 'Arial');
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Nombre del archivo
$filename = 'reporte_calificaciones_' . $tipo_reporte . '_' . date('Y-m-d_H-i-s') . '.pdf';

// Enviar al navegador
$dompdf->stream($filename, ['Attachment' => false]);
?>