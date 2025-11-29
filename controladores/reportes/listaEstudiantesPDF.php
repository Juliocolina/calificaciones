<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
require_once __DIR__ . '/../../modelos/ReporteModel.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

verificarRol(['admin', 'coordinador']);

$aldea_id = intval($_GET['aldea_id'] ?? 0);
$pnf_id = intval($_GET['pnf_id'] ?? 0);
$trayecto_id = intval($_GET['trayecto_id'] ?? 0);
$trimestre_id = intval($_GET['trimestre_id'] ?? 0);

if (!$aldea_id || !$pnf_id) {
    die('Parámetros requeridos: aldea_id y pnf_id');
}

try {
    $conn = conectar();
    if (!$conn) {
        die('Error de conexión a la base de datos');
    }

    $reporteModel = new ReporteModel($conn);
    
    // Verificar permisos de coordinador
    if ($_SESSION['rol'] === 'coordinador') {
        $coord_aldea = $reporteModel->obtenerAldeaCoordinador($_SESSION['usuario_id']);
        if ($coord_aldea != $aldea_id) {
            die('No tiene permisos para generar reportes de esta aldea');
        }
    }
    
    // Obtener información del reporte
    $info = $reporteModel->obtenerInfoReporte($aldea_id, $pnf_id, $trayecto_id ?: null, $trimestre_id ?: null);
    if (!$info) {
        die('No se encontró información para los parámetros especificados');
    }
    
    // Obtener estudiantes
    $estudiantes = $reporteModel->obtenerEstudiantes($aldea_id, $pnf_id, $trayecto_id ?: null, $trimestre_id ?: null);
    
    // Generar HTML
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; font-size: 12px; }
            .header { text-align: center; margin-bottom: 20px; }
            .info { margin-bottom: 15px; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #000; padding: 5px; text-align: left; }
            th { background-color: #f0f0f0; font-weight: bold; }
            .footer { margin-top: 20px; font-size: 10px; }
        </style>
    </head>
    <body>
        <div class="header">
            <h2>LISTA DE ESTUDIANTES</h2>
            <h3>' . htmlspecialchars($info['aldea_nombre']) . '</h3>
            <h4>' . htmlspecialchars($info['pnf_nombre']) . '</h4>
            ' . ($info['trayecto_nombre'] ? '<h5>' . htmlspecialchars($info['trayecto_nombre']) . '</h5>' : '') . '
            ' . ($info['trimestre_nombre'] ? '<h5>' . htmlspecialchars($info['trimestre_nombre']) . '</h5>' : '') . '
        </div>
        
        <div class="info">
            <strong>Fecha:</strong> ' . date('d/m/Y') . '<br>
            <strong>Total de estudiantes:</strong> ' . count($estudiantes) . '
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Cédula</th>
                    <th>Apellidos y Nombres</th>
                    <th>Código</th>
                    <th>Trayecto</th>
                    <th>Estado</th>
                    <th>Fecha Ingreso</th>
                </tr>
            </thead>
            <tbody>';
    
    $contador = 1;
    foreach ($estudiantes as $estudiante) {
        $html .= '
                <tr>
                    <td>' . $contador . '</td>
                    <td>' . htmlspecialchars($estudiante['cedula']) . '</td>
                    <td>' . htmlspecialchars($estudiante['apellido'] . ', ' . $estudiante['nombre']) . '</td>
                    <td>' . htmlspecialchars($estudiante['codigo_estudiante'] ?? 'N/A') . '</td>
                    <td>' . htmlspecialchars($estudiante['trayecto_nombre'] ?? 'N/A') . '</td>
                    <td>' . ucfirst($estudiante['estado_academico']) . '</td>
                    <td>' . ($estudiante['fecha_ingreso'] ? date('d/m/Y', strtotime($estudiante['fecha_ingreso'])) : 'N/A') . '</td>
                </tr>';
        $contador++;
    }
    
    $html .= '
            </tbody>
        </table>
        
        <div class="footer">
            <p>Generado el ' . date('d/m/Y H:i:s') . ' por el Sistema de Calificaciones - Misión Sucre</p>
        </div>
    </body>
    </html>';
    
    // Generar PDF
    $options = new Options();
    $options->set('defaultFont', 'Arial');
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    $filename = 'Lista_Estudiantes_' . date('Y-m-d') . '.pdf';
    $dompdf->stream($filename, array('Attachment' => false));
    
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
?>