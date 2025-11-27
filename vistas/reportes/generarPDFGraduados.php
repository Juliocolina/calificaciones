<?php
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
verificarRol(['admin', 'coordinador']);
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$conn = conectar();

// Obtener filtros
$tipo_graduacion = $_GET['tipo'] ?? '';
$pnf_id = $_GET['pnf'] ?? '';
$fecha_desde = $_GET['fecha_desde'] ?? '';
$fecha_hasta = $_GET['fecha_hasta'] ?? '';

// Construir consulta con filtros
$where_conditions = [];
$params = [];

if ($tipo_graduacion) {
    $where_conditions[] = "g.tipo_graduacion = ?";
    $params[] = $tipo_graduacion;
}

if ($pnf_id) {
    $where_conditions[] = "g.pnf_id = ?";
    $params[] = $pnf_id;
}

if ($fecha_desde) {
    $where_conditions[] = "g.fecha_graduacion >= ?";
    $params[] = $fecha_desde;
}

if ($fecha_hasta) {
    $where_conditions[] = "g.fecha_graduacion <= ?";
    $params[] = $fecha_hasta;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Consulta principal
$sql = "
    SELECT 
        g.tipo_graduacion,
        g.fecha_graduacion,
        u.cedula,
        u.nombre,
        u.apellido,
        p.nombre as pnf_nombre,
        a.nombre as aldea_nombre
    FROM graduaciones g
    INNER JOIN estudiantes e ON g.estudiante_id = e.id
    INNER JOIN usuarios u ON e.usuario_id = u.id
    INNER JOIN pnfs p ON g.pnf_id = p.id
    LEFT JOIN aldeas a ON e.aldea_id = a.id
    $where_clause
    ORDER BY g.fecha_graduacion DESC, u.apellido, u.nombre
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$graduados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generar título del reporte
$titulo = "Reporte de Graduados";
if ($tipo_graduacion) {
    $titulo .= " - " . $tipo_graduacion;
}

$filtros_texto = [];
if ($fecha_desde || $fecha_hasta) {
    $periodo = "";
    if ($fecha_desde) $periodo .= "Desde: " . date('d/m/Y', strtotime($fecha_desde));
    if ($fecha_hasta) $periodo .= ($fecha_desde ? " - " : "") . "Hasta: " . date('d/m/Y', strtotime($fecha_hasta));
    $filtros_texto[] = $periodo;
}

// HTML del PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .logo { width: 80px; height: auto; }
        .title { font-size: 18px; font-weight: bold; margin: 10px 0; }
        .subtitle { font-size: 14px; color: #666; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .badge-tsu { background-color: #28a745; color: white; padding: 2px 6px; border-radius: 3px; }
        .badge-lic { background-color: #007bff; color: white; padding: 2px 6px; border-radius: 3px; }
        .footer { margin-top: 20px; font-size: 10px; color: #666; }
        .total { margin-top: 15px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="title">Sistema de Carga de Notas</h1>
        <h2 class="title">' . $titulo . '</h2>
        <div class="subtitle">Misión Sucre - Municipio Miranda, Falcón</div>';

if (!empty($filtros_texto)) {
    $html .= '<div class="subtitle">' . implode(' | ', $filtros_texto) . '</div>';
}

$html .= '
        <div class="subtitle">Generado el: ' . date('d/m/Y H:i') . '</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Cédula</th>
                <th>Nombre Completo</th>
                <th>PNF</th>
                <th>Tipo</th>
                <th>Fecha Graduación</th>
                <th>Aldea</th>
            </tr>
        </thead>
        <tbody>';

foreach ($graduados as $graduado) {
    $badge_class = $graduado['tipo_graduacion'] === 'TSU' ? 'badge-tsu' : 'badge-lic';
    $html .= '
            <tr>
                <td>' . htmlspecialchars($graduado['cedula']) . '</td>
                <td>' . htmlspecialchars($graduado['nombre'] . ' ' . $graduado['apellido']) . '</td>
                <td>' . htmlspecialchars($graduado['pnf_nombre']) . '</td>
                <td><span class="' . $badge_class . '">' . $graduado['tipo_graduacion'] . '</span></td>
                <td>' . date('d/m/Y', strtotime($graduado['fecha_graduacion'])) . '</td>
                <td>' . htmlspecialchars($graduado['aldea_nombre'] ?? 'N/A') . '</td>
            </tr>';
}

$html .= '
        </tbody>
    </table>
    
    <div class="total">
        Total de graduados: ' . count($graduados) . '
    </div>
    
    <div class="footer">
        <p>Reporte generado por: ' . htmlspecialchars($_SESSION['nombre'] . ' ' . $_SESSION['apellido']) . ' (' . $_SESSION['rol'] . ')</p>
        <p>Sistema de Carga de Notas - Misión Sucre Miranda</p>
    </div>
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

// Nombre del archivo
$filename = 'reporte_graduados_' . date('Y-m-d_H-i-s') . '.pdf';

// Enviar PDF al navegador
$dompdf->stream($filename, array('Attachment' => false));
?>