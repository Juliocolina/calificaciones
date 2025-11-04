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
$tipo_graduacion = $_POST['tipo_graduacion'] ?? '';
$pnf_id = !empty($_POST['pnf_id']) ? intval($_POST['pnf_id']) : null;
$aldea_id = !empty($_POST['aldea_id']) ? intval($_POST['aldea_id']) : null;
$periodo = $_POST['periodo'] ?? '';
$ordenar_por = $_POST['ordenar_por'] ?? 'fecha_desc';

$incluir_contacto = isset($_POST['incluir_contacto']);
$incluir_estadisticas = isset($_POST['incluir_estadisticas']);
$incluir_historial = isset($_POST['incluir_historial']);

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

// Construir consulta principal
$sql = "
    SELECT 
        g.id as graduacion_id,
        g.tipo_graduacion,
        g.fecha_graduacion,
        u.cedula,
        u.nombre,
        u.apellido,
        u.correo,
        u.telefono,
        p.nombre AS pnf_nombre,
        a.nombre AS aldea_nombre,
        e.codigo_estudiante
    FROM graduaciones g
    JOIN estudiantes e ON g.estudiante_id = e.id
    JOIN usuarios u ON e.usuario_id = u.id
    JOIN pnfs p ON g.pnf_id = p.id
    LEFT JOIN aldeas a ON e.aldea_id = a.id
    WHERE 1=1
";

$params = [];

// Aplicar filtros
if ($tipo_graduacion) {
    $sql .= " AND g.tipo_graduacion = ?";
    $params[] = $tipo_graduacion;
}

if ($pnf_id) {
    $sql .= " AND g.pnf_id = ?";
    $params[] = $pnf_id;
}

if ($aldea_id) {
    $sql .= " AND e.aldea_id = ?";
    $params[] = $aldea_id;
}

// Filtro por período
if ($periodo) {
    switch ($periodo) {
        case 'ultimo_semestre':
            $sql .= " AND g.fecha_graduacion >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
            break;
        case 'ultimo_ano':
            $sql .= " AND g.fecha_graduacion >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            break;
        case '2024':
        case '2023':
        case '2022':
            $sql .= " AND YEAR(g.fecha_graduacion) = ?";
            $params[] = $periodo;
            break;
    }
}

// Ordenamiento
switch ($ordenar_por) {
    case 'fecha_asc':
        $sql .= " ORDER BY g.fecha_graduacion ASC";
        break;
    case 'apellido':
        $sql .= " ORDER BY u.apellido, u.nombre";
        break;
    case 'pnf':
        $sql .= " ORDER BY p.nombre, u.apellido";
        break;
    case 'tipo':
        $sql .= " ORDER BY g.tipo_graduacion, g.fecha_graduacion DESC";
        break;
    default: // fecha_desc
        $sql .= " ORDER BY g.fecha_graduacion DESC";
}

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$graduados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular estadísticas
$estadisticas = [];
if ($incluir_estadisticas) {
    $total_graduados = count($graduados);
    $graduados_tsu = count(array_filter($graduados, function($g) { return $g['tipo_graduacion'] === 'TSU'; }));
    $graduados_licenciado = count(array_filter($graduados, function($g) { return $g['tipo_graduacion'] === 'Licenciado'; }));
    
    // Estadísticas por PNF
    $por_pnf = [];
    foreach ($graduados as $graduado) {
        $pnf = $graduado['pnf_nombre'];
        if (!isset($por_pnf[$pnf])) {
            $por_pnf[$pnf] = ['total' => 0, 'tsu' => 0, 'licenciado' => 0];
        }
        $por_pnf[$pnf]['total']++;
        if ($graduado['tipo_graduacion'] === 'TSU') {
            $por_pnf[$pnf]['tsu']++;
        } else {
            $por_pnf[$pnf]['licenciado']++;
        }
    }
    
    // Estadísticas por año
    $por_ano = [];
    foreach ($graduados as $graduado) {
        $ano = date('Y', strtotime($graduado['fecha_graduacion']));
        if (!isset($por_ano[$ano])) {
            $por_ano[$ano] = 0;
        }
        $por_ano[$ano]++;
    }
    
    $estadisticas = [
        'total_graduados' => $total_graduados,
        'graduados_tsu' => $graduados_tsu,
        'graduados_licenciado' => $graduados_licenciado,
        'por_pnf' => $por_pnf,
        'por_ano' => $por_ano
    ];
}

// Obtener historial completo si se solicita
$historial_completo = [];
if ($incluir_historial) {
    foreach ($graduados as $graduado) {
        $stmt_historial = $conn->prepare("
            SELECT g.tipo_graduacion, g.fecha_graduacion, p.nombre as pnf_nombre
            FROM graduaciones g
            JOIN pnfs p ON g.pnf_id = p.id
            WHERE g.estudiante_id = (
                SELECT e.id FROM estudiantes e 
                JOIN usuarios u ON e.usuario_id = u.id 
                WHERE u.cedula = ?
            )
            ORDER BY g.fecha_graduacion
        ");
        $stmt_historial->execute([$graduado['cedula']]);
        $historial_completo[$graduado['cedula']] = $stmt_historial->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Obtener información de filtros para el título
$titulo_filtros = [];
if ($tipo_graduacion) {
    $titulo_filtros[] = "Tipo: $tipo_graduacion";
}
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
if ($periodo) {
    $periodo_texto = '';
    switch ($periodo) {
        case 'ultimo_semestre': $periodo_texto = 'Últimos 6 meses'; break;
        case 'ultimo_ano': $periodo_texto = 'Último año'; break;
        default: $periodo_texto = "Año $periodo";
    }
    $titulo_filtros[] = "Período: $periodo_texto";
}

$subtitulo = !empty($titulo_filtros) ? implode(' | ', $titulo_filtros) : 'Todos los graduados';

// Generar HTML para PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Graduados</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 15px; }
        .header { text-align: center; margin-bottom: 25px; }
        .header h1 { color: #007bff; margin-bottom: 5px; font-size: 18px; }
        .header p { color: #666; margin: 3px 0; }
        .info { background: #d1ecf1; padding: 8px; margin-bottom: 15px; border-radius: 3px; border: 1px solid #bee5eb; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #007bff; color: white; font-weight: bold; font-size: 10px; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .footer { margin-top: 20px; text-align: center; font-size: 9px; color: #666; }
        .tsu { background-color: #d4edda; }
        .licenciado { background-color: #cce5ff; }
        .estadisticas { display: flex; justify-content: space-around; margin: 15px 0; }
        .stat-box { text-align: center; padding: 8px; background: #e9ecef; border-radius: 3px; margin: 0 5px; }
        .historial-item { font-size: 9px; margin: 2px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SISTEMA MISIÓN SUCRE</h1>
        <h2>Reporte de Graduados</h2>
        <p>' . $subtitulo . '</p>
        <p>Generado el: ' . date('d/m/Y H:i:s') . '</p>
    </div>';

// Mostrar estadísticas si están habilitadas
if ($incluir_estadisticas && !empty($estadisticas)) {
    $html .= '
    <div class="info">
        <strong>Estadísticas Generales:</strong><br>
        Total de Graduados: ' . $estadisticas['total_graduados'] . ' | 
        TSU: ' . $estadisticas['graduados_tsu'] . ' | 
        Licenciados: ' . $estadisticas['graduados_licenciado'] . '
    </div>';
    
    if (!empty($estadisticas['por_pnf'])) {
        $html .= '<div class="info"><strong>Por PNF:</strong><br>';
        foreach ($estadisticas['por_pnf'] as $pnf => $datos) {
            $html .= "$pnf: {$datos['total']} (TSU: {$datos['tsu']}, Lic: {$datos['licenciado']}) | ";
        }
        $html = rtrim($html, ' | ') . '</div>';
    }
}

$html .= '
    <table>
        <thead>
            <tr>
                <th>Cédula</th>
                <th>Apellido y Nombre</th>
                <th>PNF</th>
                <th>Tipo</th>
                <th>Fecha Graduación</th>';

if ($incluir_contacto) {
    $html .= '<th>Correo</th><th>Teléfono</th>';
}

$html .= '<th>Aldea</th>';

if ($incluir_historial) {
    $html .= '<th>Historial Completo</th>';
}

$html .= '</tr>
        </thead>
        <tbody>';

foreach ($graduados as $graduado) {
    $clase_tipo = strtolower($graduado['tipo_graduacion']) === 'tsu' ? 'tsu' : 'licenciado';
    
    $html .= '<tr class="' . $clase_tipo . '">
                <td>' . htmlspecialchars($graduado['cedula']) . '</td>
                <td>' . htmlspecialchars($graduado['apellido'] . ', ' . $graduado['nombre']) . '</td>
                <td>' . htmlspecialchars($graduado['pnf_nombre']) . '</td>
                <td><strong>' . htmlspecialchars($graduado['tipo_graduacion']) . '</strong></td>
                <td>' . date('d/m/Y', strtotime($graduado['fecha_graduacion'])) . '</td>';
    
    if ($incluir_contacto) {
        $html .= '<td>' . htmlspecialchars($graduado['correo']) . '</td>
                  <td>' . htmlspecialchars($graduado['telefono']) . '</td>';
    }
    
    $html .= '<td>' . htmlspecialchars($graduado['aldea_nombre']) . '</td>';
    
    if ($incluir_historial && isset($historial_completo[$graduado['cedula']])) {
        $html .= '<td>';
        foreach ($historial_completo[$graduado['cedula']] as $hist) {
            $html .= '<div class="historial-item">' . 
                     htmlspecialchars($hist['tipo_graduacion']) . ' - ' . 
                     htmlspecialchars($hist['pnf_nombre']) . ' (' . 
                     date('m/Y', strtotime($hist['fecha_graduacion'])) . ')</div>';
        }
        $html .= '</td>';
    }
    
    $html .= '</tr>';
}

$html .= '</tbody>
    </table>';

// Resumen final
if ($incluir_estadisticas && !empty($estadisticas['por_ano'])) {
    $html .= '<div class="info" style="margin-top: 20px;">
        <strong>Graduaciones por Año:</strong><br>';
    foreach ($estadisticas['por_ano'] as $ano => $cantidad) {
        $html .= "$ano: $cantidad graduados | ";
    }
    $html = rtrim($html, ' | ') . '</div>';
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
$filename = 'reporte_graduados_' . date('Y-m-d_H-i-s') . '.pdf';

// Enviar al navegador
$dompdf->stream($filename, ['Attachment' => false]);
?>