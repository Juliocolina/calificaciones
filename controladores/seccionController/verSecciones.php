<?php
require_once __DIR__ . '/../../config/conexion.php';

$conn = conectar();

// Filtros
$filtro_aldea = $_GET['aldea_id'] ?? '';
$filtro_oferta = $_GET['oferta_id'] ?? '';

// Construir consulta con filtros
$where_conditions = [];
$params = [];

if (!empty($filtro_aldea)) {
    $where_conditions[] = "oa.aldea_id = ?";
    $params[] = $filtro_aldea;
}

if (!empty($filtro_oferta)) {
    $where_conditions[] = "oa.id = ?";
    $params[] = $filtro_oferta;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Consulta principal de secciones
$query = "
    SELECT 
        s.id,
        s.codigo_seccion,
        s.cupo_maximo,
        s.created_at,
        oa.id as oferta_id,
        oa.estatus as oferta_estatus,
        a.nombre as aldea,
        p.nombre as pnf,
        t.nombre as trayecto,
        tr.nombre as trimestre,
        m.nombre as materia,
        m.creditos,
        m.duracion,
        CONCAT(u.nombre, ' ', u.apellido) as profesor,
        u.cedula as cedula_profesor,
        COUNT(DISTINCT i.estudiante_id) as estudiantes_inscritos,
        (s.cupo_maximo - COUNT(DISTINCT i.estudiante_id)) as cupos_disponibles
    FROM secciones s
    JOIN oferta_academica oa ON s.oferta_academica_id = oa.id
    JOIN aldeas a ON oa.aldea_id = a.id
    JOIN pnfs p ON oa.pnf_id = p.id
    JOIN trayectos t ON oa.trayecto_id = t.id
    JOIN trimestres tr ON oa.trimestre_id = tr.id
    JOIN materias m ON s.materia_id = m.id
    JOIN profesores pr ON s.profesor_id = pr.id
    JOIN usuarios u ON pr.usuario_id = u.id
    LEFT JOIN inscripciones i ON s.id = i.seccion_id AND i.estatus IN ('Cursando', 'Aprobada', 'Reprobada')
    $where_clause
    GROUP BY s.id
    ORDER BY a.nombre, p.nombre, m.nombre
";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$secciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener datos para filtros según rol
if ($_SESSION['rol'] === 'coordinador') {
    // Coordinador solo ve su aldea
    $stmt_aldea = $conn->prepare("
        SELECT a.id, a.nombre 
        FROM aldeas a
        JOIN coordinadores c ON a.id = c.aldea_id
        JOIN usuarios u ON c.usuario_id = u.id
        WHERE u.id = ?
    ");
    $stmt_aldea->execute([$_SESSION['usuario_id']]);
    $aldeas = $stmt_aldea->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Admin ve todas las aldeas
    $aldeas = $conn->query("SELECT id, nombre FROM aldeas ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
}

$ofertas = $conn->query("
    SELECT oa.id, CONCAT(p.nombre, ' - ', t.nombre, ' - ', tr.nombre, ' (', UPPER(oa.tipo_oferta), ')') as nombre
    FROM oferta_academica oa
    JOIN pnfs p ON oa.pnf_id = p.id
    JOIN trayectos t ON oa.trayecto_id = t.id
    JOIN trimestres tr ON oa.trimestre_id = tr.id
    WHERE oa.estatus IN ('Abierto', 'Planificado')
    ORDER BY p.nombre, t.nombre, oa.tipo_oferta
")->fetchAll(PDO::FETCH_ASSOC);
?>