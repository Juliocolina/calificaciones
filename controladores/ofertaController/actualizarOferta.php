<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../index.php");
    exit;
}

$conn = conectar();
$redirect_view = 'ofertas_academicas/verOfertas.php';

if (!$conn) {
    redirigir('error', 'Error de conexión.', $redirect_view);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('error', 'Método no permitido.', $redirect_view);
    exit;
}

// 1. Recibir y validar todos los datos del formulario
$id           = isset($_POST['id']) ? intval($_POST['id']) : 0;
$pnf_id       = isset($_POST['pnf_id']) ? intval($_POST['pnf_id']) : 0;
$trayecto_id  = isset($_POST['trayecto_id']) ? intval($_POST['trayecto_id']) : 0;
$trimestre_id = isset($_POST['trimestre_id']) ? intval($_POST['trimestre_id']) : 0;
$tipo_oferta  = trim($_POST['tipo_oferta'] ?? ''); // <--- ¡CRÍTICO: CAMPO AÑADIDO!
$aldea_id     = isset($_POST['aldea_id']) ? intval($_POST['aldea_id']) : 0;

$fecha_inicio_excepcion = !empty($_POST['fecha_inicio_excepcion']) ? trim($_POST['fecha_inicio_excepcion']) : null;
$fecha_fin_excepcion    = !empty($_POST['fecha_fin_excepcion']) ? trim($_POST['fecha_fin_excepcion']) : null;

$redirect_edit = 'ofertas_academicas/editarOferta.php?id=' . $id;

// 2. Validaciones de campos obligatorios
if ($id <= 0 || $pnf_id <= 0 || $trayecto_id <= 0 || $trimestre_id <= 0 || empty($tipo_oferta) || $aldea_id <= 0) {
    redirigir('error', 'Faltan campos obligatorios (ID, Aldea, PNF, Trayecto, Trimestre o Tipo de Oferta).', $redirect_edit);
    exit;
}

// 2.1. Validar que el 'tipo_oferta' sea un valor ENUM válido
$tipos_validos = ['regular', 'intensivo', 'reparacion'];
if (!in_array($tipo_oferta, $tipos_validos)) {
    redirigir('error', 'El Tipo de Oferta seleccionado no es válido.', $redirect_edit);
    exit;
}

// 2.2. Validar consistencia de fechas de excepción
if (($fecha_inicio_excepcion && !$fecha_fin_excepcion) || (!$fecha_inicio_excepcion && $fecha_fin_excepcion)) {
    redirigir('error', 'Debe proporcionar ambas fechas de excepción o ninguna.', $redirect_edit);
    exit;
}
if ($fecha_inicio_excepcion && $fecha_fin_excepcion && (strtotime($fecha_inicio_excepcion) > strtotime($fecha_fin_excepcion))) {
    redirigir('error', 'La fecha de inicio de excepción no puede ser posterior a la fecha de fin.', $redirect_edit);
    exit;
}

try {
    // 3. Verificar el Estatus Actual y la existencia
    $stmt_check = $conn->prepare("SELECT estatus FROM oferta_academica WHERE id = ?");
    $stmt_check->execute([$id]);
    $oferta_actual = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$oferta_actual) {
        redirigir('error', 'La oferta que intentas editar no existe.', $redirect_view);
        exit;
    }

    // 4. Aplicando la Regla de Inmutabilidad
    if ($oferta_actual['estatus'] !== 'Planificado') {
        redirigir('error', 'Error: No se puede editar una oferta que ya no está en estado "Planificado".', $redirect_view);
        exit;
    }
    
    // 5. Verificar unicidad (que no choque con otra oferta existente)
    // CRÍTICO: Se añadió 'tipo_oferta' y 'aldea_id' a la verificación UNIQUE.
    $stmt_unique = $conn->prepare("
        SELECT id FROM oferta_academica 
        WHERE pnf_id = ? 
          AND trayecto_id = ? 
          AND trimestre_id = ? 
          AND tipo_oferta = ?
          AND aldea_id = ?
          AND id != ?
    ");
    $stmt_unique->execute([$pnf_id, $trayecto_id, $trimestre_id, $tipo_oferta, $aldea_id, $id]);
    
    if ($stmt_unique->fetch()) {
        redirigir('error', 'Ya existe otra oferta académica con esa misma combinación (PNF, Trayecto, Trimestre y Tipo).', $redirect_edit);
        exit;
    }

} catch (PDOException $e) {
    redirigir('error', 'Error de base de datos al verificar la oferta: ' . $e->getMessage(), $redirect_view);
    exit;
}

// 6. Preparar y ejecutar la actualización
$sql = "UPDATE oferta_academica SET 
            pnf_id = ?, 
            trayecto_id = ?, 
            trimestre_id = ?,
            tipo_oferta = ?, /* <--- ¡CRÍTICO: CAMPO AÑADIDO AL UPDATE! */
            aldea_id = ?,
            fecha_inicio_excepcion = ?,
            fecha_fin_excepcion = ?
        WHERE id = ?";

$stmt_update = $conn->prepare($sql);
$exito = $stmt_update->execute([
    $pnf_id,
    $trayecto_id,
    $trimestre_id,
    $tipo_oferta,
    $aldea_id,
    $fecha_inicio_excepcion,
    $fecha_fin_excepcion,
    $id
]);

// 7. Manejar el resultado
if ($exito) {
    redirigir('exito', 'Oferta académica actualizada correctamente.', $redirect_view);
} else {
    $errorInfo = $stmt_update->errorInfo();
    // En caso de fallo de clave foránea, se debería mostrar un mensaje más amigable
    if ($errorInfo[0] === '23000') {
         redirigir('error', 'Error de clave foránea: El PNF, Trayecto o Trimestre seleccionado no existe.', $redirect_edit);
    } else {
         redirigir('error', 'Error al actualizar la oferta: ' . $errorInfo[2], $redirect_edit);
    }
}
exit;