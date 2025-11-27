<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
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
    
    // Verificar que coordinador solo acceda a su aldea
    if ($_SESSION['rol'] === 'coordinador') {
        $stmt = $conn->prepare("SELECT aldea_id FROM coordinadores WHERE usuario_id = ?");
        $stmt->execute([$_SESSION['usuario_id']]);
        $coord_aldea = $stmt->fetchColumn();
        if ($coord_aldea != $aldea_id) {
            die('No tiene permisos para generar reportes de esta aldea');
        }
    }
    
    // Obtener información de aldea, PNF, trayecto y trimestre
    $stmt = $conn->prepare("
        SELECT 
            a.nombre as aldea_nombre, 
            p.nombre as pnf_nombre,
            t.nombre as trayecto_nombre,
            tr.nombre as trimestre_nombre
        FROM aldeas a, pnfs p
        LEFT JOIN trayectos t ON t.id = ?
        LEFT JOIN trimestres tr ON tr.id = ?
        WHERE a.id = ? AND p.id = ?
    ");
    $stmt->execute([$trayecto_id ?: null, $trimestre_id ?: null, $aldea_id, $pnf_id]);
    $info = $stmt->fetch();
    
    // Construir consulta de estudiantes con filtros opcionales
    $where_conditions = ["e.aldea_id = ?", "e.pnf_id = ?"];
    $params = [$aldea_id, $pnf_id];
    
    if ($trayecto_id > 0) {
        $where_conditions[] = "e.trayecto_id = ?";
        $params[] = $trayecto_id;
    }
    
    if ($trimestre_id > 0) {
        $where_conditions[] = "e.trimestre_id = ?";
        $params[] = $trimestre_id;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Obtener estudiantes
    $stmt = $conn->prepare("
        SELECT 
            u.cedula,
            u.nombre,
            u.apellido,
            e.codigo_estudiante,
            e.fecha_ingreso,
            e.estado_academico,
            t.nombre as trayecto_nombre,
            tr.nombre as trimestre_nombre
        FROM estudiantes e
        JOIN usuarios u ON e.usuario_id = u.id
        LEFT JOIN trayectos t ON e.trayecto_id = t.id
        LEFT JOIN trimestres tr ON e.trimestre_id = tr.id
        WHERE $where_clause
        ORDER BY u.apellido, u.nombre
    ");
    $stmt->execute($params);
    $estudiantes = $stmt->fetchAll();
    
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