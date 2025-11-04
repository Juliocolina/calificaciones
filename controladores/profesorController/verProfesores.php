<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';

verificarSesion();
$conn = conectar();

// Obtener aldea del coordinador si es coordinador
$aldea_coordinador = null;
if ($_SESSION['rol'] === 'coordinador') {
    $stmt_coord = $conn->prepare("SELECT aldea_id FROM coordinadores WHERE usuario_id = ?");
    $stmt_coord->execute([$_SESSION['usuario_id']]);
    $coord_data = $stmt_coord->fetch(PDO::FETCH_ASSOC);
    $aldea_coordinador = $coord_data['aldea_id'] ?? null;
}

// Obtener PNFs para el filtro
$stmt_pnfs = $conn->query("SELECT id, nombre FROM pnfs ORDER BY nombre");
$pnfs = $stmt_pnfs->fetchAll(PDO::FETCH_ASSOC);

// Obtener filtro PNF seleccionado
$filtro_pnf = isset($_GET['pnf_id']) ? intval($_GET['pnf_id']) : 0;

// Obtener lista de profesores (filtrados por aldea si es coordinador y por PNF si se selecciona)
$sql = "
    SELECT 
        p.id, 
        u.cedula, 
        u.nombre,
        u.apellido, 
        u.telefono,
        u.correo, 
        p.usuario_id,
        p.titulo,
        p.especialidad,
        p.aldea_id,
        p.pnf_id,
        pnf.nombre AS pnf_nombre
    FROM profesores p
    INNER JOIN usuarios u ON p.usuario_id = u.id
    LEFT JOIN pnfs pnf ON p.pnf_id = pnf.id";

$params = [];
$where_conditions = [];

if ($_SESSION['rol'] === 'coordinador' && $aldea_coordinador) {
    $where_conditions[] = "p.aldea_id = ?";
    $params[] = $aldea_coordinador;
}

if ($filtro_pnf > 0) {
    $where_conditions[] = "p.pnf_id = ?";
    $params[] = $filtro_pnf;
}

if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

$sql .= " ORDER BY u.apellido, u.nombre";

$consulta = $conn->prepare($sql);
$consulta->execute($params);

$profesores = $consulta->fetchAll(PDO::FETCH_ASSOC);
?>