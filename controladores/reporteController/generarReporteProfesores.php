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
$ordenar_por = $_POST['ordenar_por'] ?? 'apellido';

$incluir_contacto = isset($_POST['incluir_contacto']);
$incluir_materias = isset($_POST['incluir_materias']);
$incluir_carga = isset($_POST['incluir_carga']);

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
        u.cedula, u.nombre, u.apellido, u.correo, u.telefono,
        p.id as profesor_id,
        a.nombre AS aldea_nombre,
        pnf.nombre AS pnf_nombre
    FROM profesores p
    JOIN usuarios u ON p.usuario_id = u.id
    LEFT JOIN aldeas a ON p.aldea_id = a.id
    LEFT JOIN pnfs pnf ON p.pnf_id = pnf.id
    WHERE 1=1
";

$params = [];

if ($aldea_id) {
    $sql .= " AND p.aldea_id = ?";
    $params[] = $aldea_id;
}

if ($pnf_id) {
    $sql .= " AND p.pnf_id = ?";
    $params[] = $pnf_id;
}

// Ordenamiento
switch ($ordenar_por) {
    case 'aldea':
        $sql .= " ORDER BY a.nombre, u.apellido, u.nombre";
        break;
    case 'pnf':
        $sql .= " ORDER BY pnf.nombre, u.apellido, u.nombre";
        break;
    case 'carga':
        $sql .= " ORDER BY u.apellido, u.nombre"; // Se ordenará por carga después
        break;
    default:
        $sql .= " ORDER BY u.apellido, u.nombre";
}

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$profesores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener materias asignadas y carga académica para cada profesor
$profesores_con_materias = [];
foreach ($profesores as $profesor) {
    $profesor_data = $profesor;
    $profesor_data['materias'] = [];
    $profesor_data['total_materias'] = 0;
    $profesor_data['total_estudiantes'] = 0;
    
    if ($incluir_materias || $incluir_carga) {
        // Obtener materias asignadas al profesor
        $stmt_materias = $conn->prepare("
            SELECT DISTINCT
                m.nombre AS materia_nombre,
                m.codigo AS materia_codigo,
                m.duracion AS materia_duracion,
                COUNT(DISTINCT i.estudiante_id) AS total_estudiantes_materia,
                oa.id AS oferta_id,
                p_pnf.nombre AS oferta_pnf,
                t.nombre AS oferta_trayecto,
                tr.nombre AS oferta_trimestre
            FROM materia_profesor mp
            JOIN materias m ON mp.materia_id = m.id
            JOIN oferta_materias om ON m.id = om.materia_id
            JOIN oferta_academica oa ON om.oferta_academica_id = oa.id
            JOIN pnfs p_pnf ON oa.pnf_id = p_pnf.id
            JOIN trayectos t ON oa.trayecto_id = t.id
            JOIN trimestres tr ON oa.trimestre_id = tr.id
            LEFT JOIN inscripciones i ON om.id = i.oferta_materia_id
            WHERE mp.profesor_id = ? AND oa.estatus = 'Abierto'
            GROUP BY m.id, oa.id
            ORDER BY m.nombre
        ");
        $stmt_materias->execute([$profesor['profesor_id']]);
        $materias = $stmt_materias->fetchAll(PDO::FETCH_ASSOC);
        
        $profesor_data['materias'] = $materias;
        $profesor_data['total_materias'] = count($materias);
        $profesor_data['total_estudiantes'] = array_sum(array_column($materias, 'total_estudiantes_materia'));
    }
    
    $profesores_con_materias[] = $profesor_data;
}

// Ordenar por carga académica si se seleccionó
if ($ordenar_por === 'carga') {
    usort($profesores_con_materias, function($a, $b) {
        return $b['total_materias'] - $a['total_materias'];
    });
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

$subtitulo = !empty($titulo_filtros) ? implode(' | ', $titulo_filtros) : 'Todos los profesores';

// Generar HTML para PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Profesores</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 15px; }
        .header { text-align: center; margin-bottom: 25px; }
        .header h1 { color: #ffc107; margin-bottom: 5px; font-size: 18px; }
        .header p { color: #666; margin: 3px 0; }
        .info { background: #fff3cd; padding: 8px; margin-bottom: 15px; border-radius: 3px; border: 1px solid #ffeaa7; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #ffc107; color: #212529; font-weight: bold; font-size: 10px; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .footer { margin-top: 20px; text-align: center; font-size: 9px; color: #666; }
        .materias-list { font-size: 9px; }
        .carga-alta { background-color: #d4edda; }
        .carga-media { background-color: #fff3cd; }
        .carga-baja { background-color: #f8d7da; }
        .profesor-section { margin-bottom: 15px; page-break-inside: avoid; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SISTEMA MISIÓN SUCRE</h1>
        <h2>Reporte de Profesores</h2>
        <p>' . $subtitulo . '</p>
        <p>Generado el: ' . date('d/m/Y H:i:s') . '</p>
    </div>
    
    <div class="info">
        <strong>Total de profesores:</strong> ' . count($profesores_con_materias) . '
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Cédula</th>
                <th>Apellido y Nombre</th>';

if ($incluir_contacto) {
    $html .= '<th>Correo</th><th>Teléfono</th>';
}

$html .= '<th>Aldea</th><th>PNF</th>';

if ($incluir_carga) {
    $html .= '<th>Total Materias</th><th>Total Estudiantes</th>';
}

if ($incluir_materias) {
    $html .= '<th>Materias Asignadas</th>';
}

$html .= '</tr>
        </thead>
        <tbody>';

foreach ($profesores_con_materias as $profesor) {
    // Determinar clase de carga
    $clase_carga = '';
    if ($incluir_carga) {
        if ($profesor['total_materias'] >= 4) $clase_carga = 'carga-alta';
        elseif ($profesor['total_materias'] >= 2) $clase_carga = 'carga-media';
        else $clase_carga = 'carga-baja';
    }
    
    $html .= '<tr class="' . $clase_carga . '">
                <td>' . htmlspecialchars($profesor['cedula']) . '</td>
                <td>' . htmlspecialchars($profesor['apellido'] . ', ' . $profesor['nombre']) . '</td>';
    
    if ($incluir_contacto) {
        $html .= '<td>' . htmlspecialchars($profesor['correo']) . '</td>
                  <td>' . htmlspecialchars($profesor['telefono']) . '</td>';
    }
    
    $html .= '<td>' . htmlspecialchars($profesor['aldea_nombre']) . '</td>
              <td>' . htmlspecialchars($profesor['pnf_nombre']) . '</td>';
    
    if ($incluir_carga) {
        $html .= '<td><strong>' . $profesor['total_materias'] . '</strong></td>
                  <td>' . $profesor['total_estudiantes'] . '</td>';
    }
    
    if ($incluir_materias) {
        $materias_texto = '';
        if (!empty($profesor['materias'])) {
            $materias_array = [];
            foreach ($profesor['materias'] as $materia) {
                $materias_array[] = $materia['materia_codigo'] . ' - ' . $materia['materia_nombre'] . 
                                   ' (' . $materia['total_estudiantes_materia'] . ' est.)';
            }
            $materias_texto = implode('<br>', $materias_array);
        } else {
            $materias_texto = '<em>Sin materias asignadas</em>';
        }
        $html .= '<td class="materias-list">' . $materias_texto . '</td>';
    }
    
    $html .= '</tr>';
}

$html .= '</tbody>
    </table>';

// Resumen estadístico
if ($incluir_carga) {
    $total_materias_sistema = array_sum(array_column($profesores_con_materias, 'total_materias'));
    $total_estudiantes_sistema = array_sum(array_column($profesores_con_materias, 'total_estudiantes'));
    $promedio_materias = count($profesores_con_materias) > 0 ? round($total_materias_sistema / count($profesores_con_materias), 2) : 0;
    
    $html .= '
    <div class="info" style="margin-top: 20px;">
        <strong>Resumen de Carga Académica:</strong><br>
        Total de materias en el sistema: ' . $total_materias_sistema . ' | 
        Total de estudiantes atendidos: ' . $total_estudiantes_sistema . ' | 
        Promedio de materias por profesor: ' . $promedio_materias . '
    </div>';
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
$filename = 'reporte_profesores_' . date('Y-m-d_H-i-s') . '.pdf';

// Enviar al navegador
$dompdf->stream($filename, ['Attachment' => false]);
?>