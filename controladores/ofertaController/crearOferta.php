<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php'; 

verificarSesion();
$conn = conectar();
$redirect_view = 'ofertas_academicas/crearOferta.php';

if (!$conn) {
    redirigir('error', 'Error de conexión a la base de datos.', $redirect_view);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('error', 'Método no permitido.', $redirect_view);
    exit;
}

// Obtener aldea del coordinador si es coordinador
$usuario_actual = $_SESSION['usuario'];
$aldea_id = null;

if ($usuario_actual['rol'] === 'coordinador') {
    $stmt_coord = $conn->prepare("SELECT aldea_id FROM coordinadores WHERE usuario_id = ?");
    $stmt_coord->execute([$usuario_actual['id']]);
    $coord_data = $stmt_coord->fetch(PDO::FETCH_ASSOC);
    $aldea_id = $coord_data['aldea_id'] ?? null;
    
    if (!$aldea_id) {
        redirigir('error', 'No se pudo determinar la aldea del coordinador.', $redirect_view);
        exit;
    }
}

// 1. Recibir y limpiar datos del formulario
$pnf_id       = intval($_POST['pnf_id'] ?? 0);
$trayecto_id  = intval($_POST['trayecto_id'] ?? 0);
$trimestre_id = intval($_POST['trimestre_id'] ?? 0);
$tipo_oferta  = trim($_POST['tipo_oferta'] ?? ''); // <--- ¡CRÍTICO: Campo OBLIGATORIO Y UNIQUE!

// Recibir las fechas de excepción opcionales
$fecha_inicio_excepcion = !empty($_POST['fecha_inicio_excepcion']) ? trim($_POST['fecha_inicio_excepcion']) : null;
$fecha_fin_excepcion    = !empty($_POST['fecha_fin_excepcion']) ? trim($_POST['fecha_fin_excepcion']) : null;


// --- 2. INICIO DE VALIDACIONES ---

// 2.1. Validar campos obligatorios
if ($pnf_id <= 0 || $trayecto_id <= 0 || $trimestre_id <= 0 || empty($tipo_oferta)) {
    redirigir('error', 'Debe seleccionar un PNF, Trayecto, Trimestre y Tipo de Oferta.', $redirect_view);
    exit;
}

// 2.2. Validar que el 'tipo_oferta' sea un valor ENUM válido
$tipos_validos = ['regular', 'intensivo', 'reparacion'];
if (!in_array($tipo_oferta, $tipos_validos)) {
    redirigir('error', 'El Tipo de Oferta seleccionado no es válido.', $redirect_view);
    exit;
}

// 2.3. Validar consistencia de fechas de excepción
if (($fecha_inicio_excepcion && !$fecha_fin_excepcion) || (!$fecha_inicio_excepcion && $fecha_fin_excepcion)) {
    redirigir('error', 'Debe proporcionar ambas fechas de excepción o ninguna.', $redirect_view);
    exit;
}
if ($fecha_inicio_excepcion && $fecha_fin_excepcion && (strtotime($fecha_inicio_excepcion) > strtotime($fecha_fin_excepcion))) {
    redirigir('error', 'La fecha de inicio de excepción no puede ser posterior a la fecha de fin.', $redirect_view);
    exit;
}


// 2.4. Verificar unicidad de la oferta (CRÍTICO: Basado en los campos UNIQUE KEY + aldea)
try {
    $sql_check = "SELECT id FROM oferta_academica WHERE pnf_id = ? AND trayecto_id = ? AND trimestre_id = ? AND tipo_oferta = ?";
    $params_check = [$pnf_id, $trayecto_id, $trimestre_id, $tipo_oferta];
    
    // Si es coordinador, verificar también por aldea
    if ($aldea_id) {
        $sql_check .= " AND aldea_id = ?";
        $params_check[] = $aldea_id;
    }
    
    $stmt_oferta = $conn->prepare($sql_check);
    $stmt_oferta->execute($params_check);
    
    if ($stmt_oferta->rowCount() > 0) {
        redirigir('error', "Ya existe una oferta para este PNF, Trayecto, Trimestre y Tipo ({$tipo_oferta}) en esta aldea.", $redirect_view);
        exit;
    }
} catch (PDOException $e) {
    redirigir('error', 'Error en la verificación de datos: ' . $e->getMessage(), $redirect_view);
    exit;
}

// --- 3. INSERCIÓN ---

try {
    // Insertar oferta con aldea_id si es coordinador
    $sql = "INSERT INTO oferta_academica 
                (pnf_id, trayecto_id, trimestre_id, tipo_oferta, fecha_inicio_excepcion, fecha_fin_excepcion, aldea_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    $exito = $stmt->execute([
        $pnf_id,
        $trayecto_id,
        $trimestre_id,
        $tipo_oferta,
        $fecha_inicio_excepcion,
        $fecha_fin_excepcion,
        $aldea_id
    ]);

    if ($exito) {
        redirigir('exito', 'Oferta académica creada exitosamente en estado "Planificado".', 'ofertas_academicas/verOfertas.php');
    } else {
        // En caso de fallo de ejecución por un error no capturado.
        redirigir('error', 'Error al guardar la oferta: Fallo en la ejecución.', $redirect_view);
    }
} catch (PDOException $e) {
    // Esto captura fallos de claves foráneas (si el ID de PNF/Trayecto/Trimestre no existe)
    redirigir('error', 'Error inesperado al insertar la oferta. Verifique que los IDs de PNF, Trayecto y Trimestre existen.', $redirect_view);
}
exit;