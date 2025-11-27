<?php
session_start();
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
verificarRol(['estudiante', 'admin', 'coordinador']);
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$conn = conectar();

// Determinar ID del estudiante
if ($_SESSION['rol'] === 'estudiante') {
    $stmt_id = $conn->prepare("SELECT id FROM estudiantes WHERE usuario_id = ?");
    $stmt_id->execute([$_SESSION['usuario_id']]);
    $estudiante_data = $stmt_id->fetch(PDO::FETCH_ASSOC);
    
    if (!$estudiante_data) {
        echo "<div class='alert alert-danger'>No se encontró información del estudiante.</div>";
        exit;
    }
    
    $id_estudiante = $estudiante_data['id'];
} else {
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        echo "<div class='alert alert-danger'>ID de estudiante inválido.</div>";
        exit;
    }
    $id_estudiante = $_GET['id'];
}

// Obtener datos del estudiante
$stmt_estudiante = $conn->prepare("
    SELECT e.*, u.nombre, u.apellido, u.cedula, p.nombre as pnf_nombre, t.slug as trayecto_nombre, a.nombre as aldea_nombre
    FROM estudiantes e 
    INNER JOIN usuarios u ON e.usuario_id = u.id 
    LEFT JOIN pnfs p ON e.pnf_id = p.id
    LEFT JOIN trayectos t ON e.trayecto_id = t.id
    LEFT JOIN aldeas a ON e.aldea_id = a.id
    WHERE e.id = ?
");
$stmt_estudiante->execute([$id_estudiante]);
$estudiante = $stmt_estudiante->fetch(PDO::FETCH_ASSOC);

if (!$estudiante) {
    echo "<div class='alert alert-warning'>Estudiante no encontrado.</div>";
    exit;
}

// Obtener historial académico
$stmt_historial = $conn->prepare("
    SELECT 
        i.id as inscripcion_id,
        m.nombre as materia_nombre,
        c.nota_numerica,
        tr.nombre as trimestre_nombre,
        CASE 
            WHEN m.nombre LIKE '%proyecto socio tecnológico%' AND c.nota_numerica >= 16 THEN 'Aprobada'
            WHEN m.nombre NOT LIKE '%proyecto socio tecnológico%' AND c.nota_numerica >= 12 THEN 'Aprobada'
            WHEN c.nota_numerica IS NOT NULL THEN 'Reprobada'
            ELSE 'Cursando'
        END as estado_materia
    FROM inscripciones i
    INNER JOIN secciones s ON i.seccion_id = s.id
    INNER JOIN oferta_academica oa ON s.oferta_academica_id = oa.id
    INNER JOIN trimestres tr ON oa.trimestre_id = tr.id
    INNER JOIN materias m ON s.materia_id = m.id
    LEFT JOIN calificaciones c ON i.id = c.inscripcion_id
    WHERE i.estudiante_id = ?
    ORDER BY tr.nombre DESC, m.nombre ASC
");
$stmt_historial->execute([$id_estudiante]);
$historial = $stmt_historial->fetchAll(PDO::FETCH_ASSOC);

// Calcular estadísticas
$total_materias = count($historial);
$aprobadas = array_filter($historial, function($h) { return $h['estado_materia'] === 'Aprobada'; });
$reprobadas = array_filter($historial, function($h) { return $h['estado_materia'] === 'Reprobada'; });
$cursando = array_filter($historial, function($h) { return $h['estado_materia'] === 'Cursando'; });

$total_aprobadas = count($aprobadas);
$total_reprobadas = count($reprobadas);
$total_cursando = count($cursando);

// Calcular promedio
$notas_validas = array_filter($historial, function($h) { return $h['nota_numerica'] !== null; });
$promedio_general = 0;
if (!empty($notas_validas)) {
    $suma_notas = array_sum(array_column($notas_validas, 'nota_numerica'));
    $promedio_general = round($suma_notas / count($notas_validas), 2);
}

// HTML del PDF con diseño profesional adaptado
$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 2cm;
            size: A4;
        }
        body {
            font-family: "Times New Roman", serif;
            font-size: 11px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            color: #000;
        }
        .document-container {
            max-width: 100%;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #000;
        }
        .republic-title {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
            letter-spacing: 0.5px;
        }
        .ministry-title {
            font-size: 12px;
            font-weight: bold;
            margin: 3px 0;
            text-transform: uppercase;
        }
        .institution-title {
            font-size: 11px;
            font-weight: bold;
            margin: 2px 0;
            text-transform: uppercase;
        }
        .document-title {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 20px 0 15px 0;
            letter-spacing: 1px;
            text-decoration: underline;
        }
        .student-section {
            margin-bottom: 25px;
            padding: 15px;
            border: 1px solid #000;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 12px;
            text-align: center;
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 6px 8px;
            border: 1px solid #000;
            font-size: 10px;
            vertical-align: top;
        }
        .info-label {
            font-weight: bold;
            background-color: #f0f0f0;
            width: 25%;
        }
        .info-value {
            width: 25%;
        }
        .stats-container {
            display: flex;
            justify-content: space-around;
            margin: 25px 0;
            gap: 10px;
        }
        .stat-box {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 15px 10px;
            border-radius: 8px;
            text-align: center;
            min-width: 70px;
        }
        .stat-number {
            font-size: 18px;
            font-weight: 700;
            color: #0056b3;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 9px;
            color: #6b7280;
            font-weight: 600;
        }
        .iag-box {
            background-color: #0056b3;
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .iag-label {
            font-size: 11px;
            font-weight: 800;
            margin-bottom: 5px;
        }
        .iag-number {
            font-size: 20px;
            font-weight: 800;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        th {
            background-color: #0056b3;
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-weight: 700;
            font-size: 11px;
        }
        td {
            padding: 10px 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 11px;
        }
        .estado-simple {
            font-size: 10px;
            font-weight: 600;
            text-align: center;
        }
        .nota-aprobada { color: #16a34a; font-weight: 600; }
        .nota-reprobada { color: #dc2626; font-weight: 700; }
        .nota-cursando { color: #ca8a04; font-style: italic; }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #d1d5db;
            font-size: 10px;
            color: #6b7280;
        }
        .footer-note {
            margin-bottom: 15px;
        }
        .footer-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .signature-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 50px;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #374151;
            padding-top: 8px;
            color: #374151;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="document-container">
        <header class="header">
            <p class="republic-title">REPÚBLICA BOLIVARIANA DE VENEZUELA</p>
            <p class="ministry-title">MINISTERIO DEL PODER POPULAR PARA LA EDUCACIÓN UNIVERSITARIA</p>
            <p class="institution-title">MISIÓN SUCRE - MUNICIPIO MIRANDA, FALCÓN</p>
            <h1 class="document-title">REGISTRO DE NOTAS Y CRÉDITOS</h1>
        </header>

        <section class="student-section">
            <h2 class="section-title">DATOS DEL TRIUNFADOR(A)</h2>
            <table class="info-table">
                <tr>
                    <td class="info-label">NOMBRE COMPLETO:</td>
                    <td class="info-value">' . htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido']) . '</td>
                    <td class="info-label">CÉDULA DE IDENTIDAD:</td>
                    <td class="info-value">' . htmlspecialchars($estudiante['cedula']) . '</td>
                </tr>
                <tr>
                    <td class="info-label">PNF:</td>
                    <td class="info-value">' . htmlspecialchars($estudiante['pnf_nombre'] ?? 'No asignado') . '</td>
                    <td class="info-label">ALDEA UNIVERSITARIA:</td>
                    <td class="info-value">' . htmlspecialchars($estudiante['aldea_nombre'] ?? 'No asignada') . '</td>
                </tr>
                <tr>
                    <td class="info-label">TRAYECTO ACTUAL:</td>
                    <td class="info-value">' . htmlspecialchars($estudiante['trayecto_nombre'] ?? 'No asignado') . '</td>
                    <td class="info-label">ESTADO ACADÉMICO:</td>
                    <td class="info-value">' . strtoupper($estudiante['estado_academico']) . '</td>
                </tr>
            </table>
        </section>

        <section>
            <h2 class="section-title">Carga Académica y Calificaciones</h2>
            
            <table>
                <thead>
                    <tr>
                        <th>UNIDAD CURRICULAR</th>
                        <th>TRIMESTRE</th>
                        <th>NOTA</th>
                        <th>ESTADO</th>
                    </tr>
                </thead>
                <tbody>';

foreach ($historial as $materia) {
    $nota_class = '';
    $nota_display = $materia['nota_numerica'] !== null ? $materia['nota_numerica'] : '-';
    
    switch ($materia['estado_materia']) {
        case 'Aprobada':
            $nota_class = 'nota-aprobada';
            break;
        case 'Reprobada':
            $nota_class = 'nota-reprobada';
            break;
        case 'Cursando':
            $nota_class = 'nota-cursando';
            break;
    }
    
    $html .= '
                    <tr>
                        <td>' . htmlspecialchars($materia['materia_nombre']) . '</td>
                        <td>' . htmlspecialchars($materia['trimestre_nombre']) . '</td>
                        <td class="' . $nota_class . '">' . $nota_display . '</td>
                        <td class="estado-simple">' . strtoupper($materia['estado_materia']) . '</td>
                    </tr>';
}

$html .= '
                </tbody>
            </table>
        </section>

        <section class="stats-container">
            <div class="stat-box">
                <div class="stat-number">' . $total_materias . '</div>
                <div class="stat-label">UC REGISTRADAS</div>
            </div>
            <div class="stat-box">
                <div class="stat-number">' . $total_aprobadas . '</div>
                <div class="stat-label">UC APROBADAS</div>
            </div>
            <div class="stat-box">
                <div class="stat-number">' . $total_reprobadas . '</div>
                <div class="stat-label">UC PENDIENTES</div>
            </div>
            <div class="iag-box">
                <div class="iag-label">ÍNDICE ACADÉMICO GENERAL (IAG):</div>
                <div class="iag-number">' . $promedio_general . '</div>
            </div>
        </section>

        <footer class="footer">
            <p class="footer-note">
                <span class="info-label">Nota Importante:</span> La escala de calificación es de 0 a 20 puntos. La nota mínima de aprobación es 12.
            </p>
            <div class="footer-info">
                <span>Emitido por: Coordinación de la Aldea Universitaria</span>
                <span>Fecha de Emisión: ' . date('d/m/Y') . '</span>
            </div>
            
            <div class="signature-section">
                <div>
                    <div class="signature-line">Firma del Triunfador(a)</div>
                </div>
                <div>
                    <div class="signature-line">Firma del Coordinador(a) y Sello</div>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>';

// Configurar Dompdf
$options = new Options();
$options->set('defaultFont', 'Arial');
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Nombre del archivo
$filename = 'historial_academico_' . $estudiante['cedula'] . '_' . date('Y-m-d') . '.pdf';

// Enviar PDF al navegador
$dompdf->stream($filename, array('Attachment' => false));
?>