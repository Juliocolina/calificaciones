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
$aldea_id = !empty($_POST['aldea_id']) ? intval($_POST['aldea_id']) : null;
$pnf_id = !empty($_POST['pnf_id']) ? intval($_POST['pnf_id']) : null;
$estado_academico = !empty($_POST['estado_academico']) ? $_POST['estado_academico'] : null;
$incluir_contacto = isset($_POST['incluir_contacto']);
$incluir_academico = isset($_POST['incluir_academico']);

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

// Construir consulta
$sql = "
    SELECT 
        u.cedula, u.nombre, u.apellido, u.correo, u.telefono,
        e.codigo_estudiante, e.estado_academico, e.fecha_ingreso,
        p.nombre AS pnf_nombre,
        t.nombre AS trayecto_nombre,
        tr.nombre AS trimestre_nombre,
        a.nombre AS aldea_nombre
    FROM estudiantes e
    JOIN usuarios u ON e.usuario_id = u.id
    LEFT JOIN pnfs p ON e.pnf_id = p.id
    LEFT JOIN trayectos t ON e.trayecto_id = t.id
    LEFT JOIN trimestres tr ON e.trimestre_id = tr.id
    LEFT JOIN aldeas a ON e.aldea_id = a.id
    WHERE 1=1
";

$params = [];

if ($aldea_id) {
    $sql .= " AND e.aldea_id = ?";
    $params[] = $aldea_id;
}

if ($pnf_id) {
    $sql .= " AND e.pnf_id = ?";
    $params[] = $pnf_id;
}

if ($estado_academico) {
    $sql .= " AND e.estado_academico = ?";
    $params[] = $estado_academico;
}

$sql .= " ORDER BY u.apellido, u.nombre";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

if ($estado_academico) {
    $titulo_filtros[] = "Estado: " . ucfirst($estado_academico);
}

$subtitulo = !empty($titulo_filtros) ? implode(' | ', $titulo_filtros) : 'Todos los estudiantes';

// Generar HTML para PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Estudiantes</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #2a5298; margin-bottom: 5px; }
        .header p { color: #666; margin: 5px 0; }
        .info { background: #f8f9fa; padding: 10px; margin-bottom: 20px; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #2a5298; color: white; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #666; }
        .total { font-weight: bold; background-color: #e9ecef; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SISTEMA MISIÓN SUCRE</h1>
        <h2>Reporte de Estudiantes</h2>
        <p>' . $subtitulo . '</p>
        <p>Generado el: ' . date('d/m/Y H:i:s') . '</p>
    </div>
    
    <div class="info">
        <strong>Total de estudiantes:</strong> ' . count($estudiantes) . '
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Cédula</th>
                <th>Apellido y Nombre</th>';

if ($incluir_contacto) {
    $html .= '<th>Correo</th><th>Teléfono</th>';
}

if ($incluir_academico) {
    $html .= '<th>Código</th><th>PNF</th><th>Trayecto</th><th>Estado</th>';
}

$html .= '<th>Aldea</th>
            </tr>
        </thead>
        <tbody>';

foreach ($estudiantes as $estudiante) {
    $html .= '<tr>
                <td>' . htmlspecialchars($estudiante['cedula']) . '</td>
                <td>' . htmlspecialchars($estudiante['apellido'] . ', ' . $estudiante['nombre']) . '</td>';
    
    if ($incluir_contacto) {
        $html .= '<td>' . htmlspecialchars($estudiante['correo']) . '</td>
                  <td>' . htmlspecialchars($estudiante['telefono']) . '</td>';
    }
    
    if ($incluir_academico) {
        $html .= '<td>' . htmlspecialchars($estudiante['codigo_estudiante']) . '</td>
                  <td>' . htmlspecialchars($estudiante['pnf_nombre']) . '</td>
                  <td>' . htmlspecialchars($estudiante['trayecto_nombre']) . '</td>
                  <td>' . htmlspecialchars(ucfirst($estudiante['estado_academico'])) . '</td>';
    }
    
    $html .= '<td>' . htmlspecialchars($estudiante['aldea_nombre']) . '</td>
              </tr>';
}

$html .= '</tbody>
    </table>
    
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
$filename = 'reporte_estudiantes_' . date('Y-m-d_H-i-s') . '.pdf';

// Enviar al navegador
$dompdf->stream($filename, ['Attachment' => false]);
?>