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
$ordenar_por = $_POST['ordenar_por'] ?? 'apellido';

$incluir_estudiantes = isset($_POST['incluir_estudiantes']);
$incluir_estadisticas = isset($_POST['incluir_estadisticas']);
$incluir_materias = isset($_POST['incluir_materias']);

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
        i.id as inscripcion_id,
        i.estatus as estatus_inscripcion,
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
        oa.id AS oferta_id
    FROM inscripciones i
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

// Ordenamiento
switch ($ordenar_por) {
    case 'materia':
        $sql .= " ORDER BY m.nombre, u.apellido, u.nombre";
        break;
    case 'oferta':
        $sql .= " ORDER BY oa.id DESC, m.nombre, u.apellido";
        break;
    case 'demanda':
        $sql .= " ORDER BY m.nombre"; // Se ordenará por demanda después
        break;
    default:
        $sql .= " ORDER BY u.apellido, u.nombre, m.nombre";
}

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$inscripciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar datos según tipo de reporte
$datos_procesados = [];
$estadisticas = [];

if ($tipo_reporte === 'por_materia') {
    // Agrupar por materia
    foreach ($inscripciones as $insc) {
        $materia_key = $insc['materia_codigo'];
        $datos_procesados[$materia_key]['materia'] = $insc['materia_nombre'];
        $datos_procesados[$materia_key]['codigo'] = $insc['materia_codigo'];
        $datos_procesados[$materia_key]['duracion'] = $insc['materia_duracion'];
        $datos_procesados[$materia_key]['total_inscritos'] = ($datos_procesados[$materia_key]['total_inscritos'] ?? 0) + 1;
        $datos_procesados[$materia_key]['estudiantes'][] = [
            'cedula' => $insc['cedula'],
            'nombre' => $insc['nombre'] . ' ' . $insc['apellido'],
            'estatus' => $insc['estatus_inscripcion']
        ];
    }
    
    // Ordenar por demanda si se seleccionó
    if ($ordenar_por === 'demanda') {
        uasort($datos_procesados, function($a, $b) {
            return $b['total_inscritos'] - $a['total_inscritos'];
        });
    }
    
} elseif ($tipo_reporte === 'por_oferta') {
    // Agrupar por oferta
    foreach ($inscripciones as $insc) {
        $oferta_key = $insc['oferta_id'];
        $datos_procesados[$oferta_key]['oferta'] = $insc['pnf_nombre'] . ' - ' . $insc['trayecto_nombre'] . ' - ' . $insc['trimestre_nombre'];
        $datos_procesados[$oferta_key]['aldea'] = $insc['aldea_nombre'];
        $datos_procesados[$oferta_key]['total_inscritos'] = ($datos_procesados[$oferta_key]['total_inscritos'] ?? 0) + 1;
        
        $materia_key = $insc['materia_codigo'];
        $datos_procesados[$oferta_key]['materias'][$materia_key]['nombre'] = $insc['materia_nombre'];
        $datos_procesados[$oferta_key]['materias'][$materia_key]['inscritos'] = ($datos_procesados[$oferta_key]['materias'][$materia_key]['inscritos'] ?? 0) + 1;
    }
}

// Calcular estadísticas generales
if ($incluir_estadisticas) {
    $total_inscripciones = count($inscripciones);
    $estudiantes_unicos = count(array_unique(array_column($inscripciones, 'cedula')));
    $materias_ofertadas = count(array_unique(array_column($inscripciones, 'materia_codigo')));
    
    // Estadísticas por materia
    $por_materia = [];
    foreach ($inscripciones as $insc) {
        $materia = $insc['materia_nombre'];
        $por_materia[$materia] = ($por_materia[$materia] ?? 0) + 1;
    }
    arsort($por_materia);
    
    // Estadísticas por PNF
    $por_pnf = [];
    foreach ($inscripciones as $insc) {
        $pnf = $insc['pnf_nombre'];
        $por_pnf[$pnf] = ($por_pnf[$pnf] ?? 0) + 1;
    }
    
    // Estadísticas por estatus
    $por_estatus = [];
    foreach ($inscripciones as $insc) {
        $estatus = $insc['estatus_inscripcion'];
        $por_estatus[$estatus] = ($por_estatus[$estatus] ?? 0) + 1;
    }
    
    $estadisticas = [
        'total_inscripciones' => $total_inscripciones,
        'estudiantes_unicos' => $estudiantes_unicos,
        'materias_ofertadas' => $materias_ofertadas,
        'por_materia' => array_slice($por_materia, 0, 5), // Top 5
        'por_pnf' => $por_pnf,
        'por_estatus' => $por_estatus
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
    <title>Reporte de Inscripciones</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 15px; }
        .header { text-align: center; margin-bottom: 25px; }
        .header h1 { color: #343a40; margin-bottom: 5px; font-size: 18px; }
        .header p { color: #666; margin: 3px 0; }
        .info { background: #f8f9fa; padding: 8px; margin-bottom: 15px; border-radius: 3px; border: 1px solid #dee2e6; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #343a40; color: white; font-weight: bold; font-size: 10px; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .footer { margin-top: 20px; text-align: center; font-size: 9px; color: #666; }
        .alta-demanda { background-color: #d4edda; }
        .media-demanda { background-color: #fff3cd; }
        .baja-demanda { background-color: #f8d7da; }
        .estudiantes-list { font-size: 9px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SISTEMA MISIÓN SUCRE</h1>
        <h2>Reporte de Inscripciones - ' . ucfirst($tipo_reporte) . '</h2>
        <p>' . $subtitulo . '</p>
        <p>Generado el: ' . date('d/m/Y H:i:s') . '</p>
    </div>';

// Mostrar estadísticas si están habilitadas
if ($incluir_estadisticas && !empty($estadisticas)) {
    $html .= '
    <div class="info">
        <strong>Estadísticas Generales:</strong><br>
        Total de Inscripciones: ' . $estadisticas['total_inscripciones'] . ' | 
        Estudiantes Únicos: ' . $estadisticas['estudiantes_unicos'] . ' | 
        Materias Ofertadas: ' . $estadisticas['materias_ofertadas'] . '
    </div>';
    
    if (!empty($estadisticas['por_materia'])) {
        $html .= '<div class="info"><strong>Materias con Mayor Demanda:</strong><br>';
        foreach ($estadisticas['por_materia'] as $materia => $cantidad) {
            $html .= "$materia: $cantidad inscripciones | ";
        }
        $html = rtrim($html, ' | ') . '</div>';
    }
}

// Generar contenido según tipo de reporte
if ($tipo_reporte === 'por_materia' && !empty($datos_procesados)) {
    $html .= '<table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Materia</th>
                <th>Duración</th>
                <th>Total Inscritos</th>';
    
    if ($incluir_estudiantes) {
        $html .= '<th>Estudiantes Inscritos</th>';
    }
    
    $html .= '</tr></thead><tbody>';
    
    foreach ($datos_procesados as $materia) {
        $clase_demanda = '';
        if ($materia['total_inscritos'] >= 15) $clase_demanda = 'alta-demanda';
        elseif ($materia['total_inscritos'] >= 8) $clase_demanda = 'media-demanda';
        else $clase_demanda = 'baja-demanda';
        
        $html .= '<tr class="' . $clase_demanda . '">
            <td>' . htmlspecialchars($materia['codigo']) . '</td>
            <td>' . htmlspecialchars($materia['materia']) . '</td>
            <td>' . ucfirst($materia['duracion']) . '</td>
            <td><strong>' . $materia['total_inscritos'] . '</strong></td>';
        
        if ($incluir_estudiantes) {
            $estudiantes_texto = '';
            foreach ($materia['estudiantes'] as $est) {
                $estudiantes_texto .= $est['cedula'] . ' - ' . $est['nombre'] . ' (' . $est['estatus'] . ')<br>';
            }
            $html .= '<td class="estudiantes-list">' . $estudiantes_texto . '</td>';
        }
        
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table>';
    
} elseif ($tipo_reporte === 'por_oferta' && !empty($datos_procesados)) {
    $html .= '<table>
        <thead>
            <tr>
                <th>Oferta Académica</th>
                <th>Aldea</th>
                <th>Total Inscripciones</th>';
    
    if ($incluir_materias) {
        $html .= '<th>Materias y Demanda</th>';
    }
    
    $html .= '</tr></thead><tbody>';
    
    foreach ($datos_procesados as $oferta) {
        $html .= '<tr>
            <td>' . htmlspecialchars($oferta['oferta']) . '</td>
            <td>' . htmlspecialchars($oferta['aldea']) . '</td>
            <td><strong>' . $oferta['total_inscritos'] . '</strong></td>';
        
        if ($incluir_materias) {
            $materias_texto = '';
            foreach ($oferta['materias'] as $materia) {
                $materias_texto .= $materia['nombre'] . ': ' . $materia['inscritos'] . ' est.<br>';
            }
            $html .= '<td class="estudiantes-list">' . $materias_texto . '</td>';
        }
        
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table>';
    
} else {
    // Reporte estadístico general
    $html .= '<table>
        <thead>
            <tr>
                <th>Cédula</th>
                <th>Estudiante</th>
                <th>Materia</th>
                <th>Oferta</th>
                <th>Estatus</th>
            </tr>
        </thead>
        <tbody>';
    
    foreach ($inscripciones as $insc) {
        $html .= '<tr>
            <td>' . htmlspecialchars($insc['cedula']) . '</td>
            <td>' . htmlspecialchars($insc['apellido'] . ', ' . $insc['nombre']) . '</td>
            <td>' . htmlspecialchars($insc['materia_nombre']) . '</td>
            <td>' . htmlspecialchars($insc['pnf_nombre'] . ' - ' . $insc['trayecto_nombre']) . '</td>
            <td>' . htmlspecialchars($insc['estatus_inscripcion']) . '</td>
        </tr>';
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
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// Nombre del archivo
$filename = 'reporte_inscripciones_' . $tipo_reporte . '_' . date('Y-m-d_H-i-s') . '.pdf';

// Enviar al navegador
$dompdf->stream($filename, ['Attachment' => false]);
?>