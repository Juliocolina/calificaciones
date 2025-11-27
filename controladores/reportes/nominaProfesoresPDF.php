<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

verificarRol(['admin', 'coordinador']);

$aldea_id = intval($_GET['aldea_id'] ?? 0);
$pnf_id = intval($_GET['pnf_id'] ?? 0);

if (!$aldea_id) {
    die('Parámetros requeridos: aldea_id y pnf_id');
}

// Verificar que coordinador solo acceda a su aldea
if ($_SESSION['rol'] === 'coordinador') {
    $stmt = $conn->prepare("SELECT aldea_id FROM coordinadores WHERE usuario_id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $coord_aldea = $stmt->fetchColumn();
    if ($coord_aldea != $aldea_id) {
        die('No tiene permisos para generar reportes de esta aldea');
    }
}

try {
    $conn = conectar();
    
    // Obtener información de aldea y PNF
    $stmt = $conn->prepare("
        SELECT a.nombre as aldea_nombre, p.nombre as pnf_nombre 
        FROM aldeas a, pnfs p 
        WHERE a.id = ? AND p.id = ?
    ");
    $stmt->execute([$aldea_id, $pnf_id]);
    $info = $stmt->fetch();
    
    // Obtener profesores con carga académica
    $stmt = $conn->prepare("
        SELECT 
            u.cedula,
            u.nombre,
            u.apellido,
            p.titulo,
            GROUP_CONCAT(DISTINCT m.nombre ORDER BY m.nombre SEPARATOR ', ') as materias_nombres,
            GROUP_CONCAT(DISTINCT s.codigo_seccion ORDER BY s.codigo_seccion SEPARATOR ', ') as codigos_secciones,
            COALESCE((
                SELECT COUNT(DISTINCT i2.estudiante_id) 
                FROM secciones s2 
                JOIN inscripciones i2 ON s2.id = i2.seccion_id 
                WHERE s2.profesor_id = p.id AND i2.estatus IN ('Cursando', 'Aprobada', 'Reprobada')
            ), 0) as total_estudiantes
        FROM profesores p
        JOIN usuarios u ON p.usuario_id = u.id
        LEFT JOIN secciones s ON p.id = s.profesor_id
        LEFT JOIN materias m ON s.materia_id = m.id
        WHERE p.aldea_id = ? AND (? = 0 OR p.pnf_id = ?) AND u.activo = 1
        GROUP BY p.id, u.cedula, u.nombre, u.apellido
        ORDER BY u.apellido, u.nombre
    ");
    $stmt->execute([$aldea_id, $pnf_id, $pnf_id]);
    $profesores = $stmt->fetchAll();
    
    // Generar HTML
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; font-size: 11px; }
            .header { text-align: center; margin-bottom: 20px; }
            .info { margin-bottom: 15px; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #000; padding: 4px; text-align: left; }
            th { background-color: #f0f0f0; font-weight: bold; font-size: 10px; }
            .footer { margin-top: 20px; font-size: 9px; }
            .sin-asignacion { background-color: #fff2cc; }
        </style>
    </head>
    <body>
        <div class="header">
            <h2>NÓMINA DE PROFESORES</h2>
            <h3>' . htmlspecialchars($info['aldea_nombre']) . '</h3>
            <h4>' . htmlspecialchars($info['pnf_nombre']) . '</h4>
        </div>
        
        <div class="info">
            <strong>Fecha:</strong> ' . date('d/m/Y') . '<br>
            <strong>Total de profesores:</strong> ' . count($profesores) . '
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Cédula</th>
                    <th>Apellidos y Nombres</th>
                    <th>Título</th>
                    <th>Materias</th>
                    <th>Secciones</th>
                    <th>Estudiantes</th>
                </tr>
            </thead>
            <tbody>';
    
    $contador = 1;
    foreach ($profesores as $profesor) {
        $clase = empty($profesor['materias_nombres']) ? 'sin-asignacion' : '';
        $html .= '
                <tr class="' . $clase . '">
                    <td>' . $contador . '</td>
                    <td>' . htmlspecialchars($profesor['cedula']) . '</td>
                    <td>' . htmlspecialchars($profesor['apellido'] . ', ' . $profesor['nombre']) . '</td>
                    <td>' . htmlspecialchars($profesor['titulo'] ?? 'No especificado') . '</td>
                    <td>' . htmlspecialchars($profesor['materias_nombres'] ?? 'Sin asignación') . '</td>
                    <td>' . htmlspecialchars($profesor['codigos_secciones'] ?? 'N/A') . '</td>
                    <td>' . $profesor['total_estudiantes'] . '</td>
                </tr>';
        $contador++;
    }
    
    $html .= '
            </tbody>
        </table>
        
        <div class="footer">
            <p><strong>Nota:</strong> Profesores sin asignación aparecen resaltados en amarillo.</p>
            <p>Generado el ' . date('d/m/Y H:i:s') . ' por el Sistema de Calificaciones - Misión Sucre</p>
        </div>
    </body>
    </html>';
    
    // Generar PDF
    $options = new Options();
    $options->set('defaultFont', 'Arial');
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    
    $filename = 'Nomina_Profesores_' . date('Y-m-d') . '.pdf';
    $dompdf->stream($filename, array('Attachment' => false));
    
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
?>