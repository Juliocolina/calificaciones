<?php
session_start();
require_once '../../config/conexion.php';
require_once '../hellpers/auth.php';
require_once '../../vendor/autoload.php'; // Para DomPDF

use Dompdf\Dompdf;
use Dompdf\Options;

verificarRol(['admin', 'coordinador', 'profesor']);

$pdo = conectar();
$usuario_id = $_SESSION['usuario_id'];
$rol = $_SESSION['rol'];

$materia_id = intval($_GET['materia_id'] ?? 0);
$periodo_academico = $_GET['periodo_academico'] ?? '';

if ($materia_id <= 0 || empty($periodo_academico)) {
    die('Parámetros inválidos');
}

// Obtener información de la materia
$stmt = $pdo->prepare("
    SELECT 
        m.nombre as materia_nombre,
        m.duracion,
        m.creditos,
        MAX(pnf.nombre) as pnf_nombre,
        MAX(t.nombre) as trayecto_nombre,
        MAX(CONCAT(pr.nombre, ' ', pr.apellido)) as profesor_nombre
    FROM materias m
    JOIN secciones s ON m.id = s.materia_id
    JOIN oferta_academica oa ON s.oferta_academica_id = oa.id
    JOIN pnfs pnf ON oa.pnf_id = pnf.id
    JOIN trayectos t ON oa.trayecto_id = t.id
    JOIN profesores p ON s.profesor_id = p.id
    JOIN usuarios pr ON p.usuario_id = pr.id
    WHERE m.id = ?
    GROUP BY m.id
");
$stmt->execute([$materia_id]);
$acta_data = $stmt->fetch();

if (!$acta_data) {
    die('Materia no encontrada');
}

// Verificar que el profesor solo acceda a sus materias
if ($rol === 'profesor') {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM secciones s 
        JOIN profesores p ON s.profesor_id = p.id 
        WHERE s.materia_id = ? AND p.usuario_id = ?
    ");
    $stmt->execute([$materia_id, $usuario_id]);
    if ($stmt->fetchColumn() == 0) {
        die('No tiene permisos para generar acta de esta materia');
    }
}

// Obtener estudiantes
$where_profesor = '';
$params = [$materia_id, $periodo_academico];
if ($rol === 'profesor') {
    $where_profesor = 'AND p.usuario_id = ?';
    $params[] = $usuario_id;
}

$stmt = $pdo->prepare("
    SELECT 
        u.cedula,
        CONCAT(u.nombre, ' ', u.apellido) as nombre_completo,
        e.codigo_estudiante,
        c.nota_numerica,
        c.periodo_academico,
        i.estatus,
        tr.nombre as trimestre_nombre
    FROM inscripciones i
    JOIN estudiantes e ON i.estudiante_id = e.id
    JOIN usuarios u ON e.usuario_id = u.id
    JOIN secciones s ON i.seccion_id = s.id
    JOIN oferta_academica oa ON s.oferta_academica_id = oa.id
    JOIN trimestres tr ON oa.trimestre_id = tr.id
    JOIN profesores p ON s.profesor_id = p.id
    LEFT JOIN calificaciones c ON i.id = c.inscripcion_id
    WHERE s.materia_id = ? 
    AND (tr.nombre LIKE CONCAT('Trimestre ', ?, '%') OR c.periodo_academico IS NULL)
    $where_profesor
    ORDER BY u.apellido, u.nombre
");
$stmt->execute($params);
$estudiantes = $stmt->fetchAll();

// Calcular estadísticas
$contador = 1;
$aprobados = 0;
$reprobados = 0;

// Verificar si es proyecto
$es_proyecto = (strpos(strtolower($acta_data['materia_nombre']), 'proyecto socio tecnológico') !== false);
$limite_aprobacion = $es_proyecto ? 16 : 12;

foreach ($estudiantes as $estudiante) {
    if ($estudiante['nota_numerica'] >= $limite_aprobacion) $aprobados++;
    elseif ($estudiante['nota_numerica'] > 0) $reprobados++;
}

// Generar HTML para el PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .info { margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: center; }
        th { background-color: #f0f0f0; font-weight: bold; }
        .left { text-align: left; }
        .signatures { margin-top: 40px; }
        .signature-line { border-top: 1px solid #000; width: 200px; margin: 0 auto; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>MISIÓN SUCRE - MUNICIPIO MIRANDA</h2>
        <h3>ACTA DE CALIFICACIONES FINALES</h3>
        <p>Año Académico ' . htmlspecialchars($periodo_academico) . '</p>
    </div>
    
    <div class="info">
        <p><strong>PNF:</strong> ' . htmlspecialchars($acta_data['pnf_nombre']) . '</p>
        <p><strong>Trayecto:</strong> ' . htmlspecialchars($acta_data['trayecto_nombre']) . '</p>
        <p><strong>Materia:</strong> ' . htmlspecialchars($acta_data['materia_nombre']) . '</p>
        <p><strong>Duración:</strong> ' . ucfirst($acta_data['duracion']) . '</p>
        <p><strong>Créditos:</strong> ' . $acta_data['creditos'] . '</p>
        <p><strong>Profesor:</strong> ' . htmlspecialchars($acta_data['profesor_nombre']) . '</p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th width="8%">N°</th>
                <th width="15%">Cédula</th>
                <th width="50%">Apellidos y Nombres</th>
                <th width="15%">Código</th>
                <th width="12%">Nota Final</th>
            </tr>
        </thead>
        <tbody>';

$contador = 1;
foreach ($estudiantes as $estudiante) {
    $html .= '
            <tr>
                <td>' . $contador++ . '</td>
                <td>' . htmlspecialchars($estudiante['cedula']) . '</td>
                <td class="left">' . htmlspecialchars($estudiante['nombre_completo']) . '</td>
                <td>' . htmlspecialchars($estudiante['codigo_estudiante']) . '</td>
                <td><strong>' . ($estudiante['nota_numerica'] ?: 'S/C') . '</strong></td>
            </tr>';
}

$html .= '
        </tbody>
    </table>
    
    <div class="info">
        <p><strong>Total estudiantes:</strong> ' . count($estudiantes) . '</p>
        <p><strong>Aprobados:</strong> ' . $aprobados . '</p>
        <p><strong>Reprobados:</strong> ' . $reprobados . '</p>
    </div>
    
    <div class="signatures">
        <table style="border: none;">
            <tr>
                <td style="border: none; text-align: center; width: 50%;">
                    <div class="signature-line"></div>
                    <p><strong>Profesor(a)</strong><br>' . htmlspecialchars($acta_data['profesor_nombre']) . '</p>
                </td>
                <td style="border: none; text-align: center; width: 50%;">
                    <div class="signature-line"></div>
                    <p><strong>Coordinador(a) Académico</strong></p>
                </td>
            </tr>
        </table>
    </div>
    
    <div style="text-align: center; margin-top: 20px; font-size: 10px;">
        <p>Fecha de generación: ' . date('d/m/Y H:i') . '</p>
    </div>
</body>
</html>';

// Configurar DomPDF
$options = new Options();
$options->set('defaultFont', 'Arial');
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Nombre del archivo
$filename = 'acta-calificaciones-' . str_replace(' ', '-', $acta_data['materia_nombre']) . '-' . $periodo_academico . '.pdf';

// Mostrar en el navegador
$dompdf->stream($filename, array('Attachment' => false));
exit;
?>