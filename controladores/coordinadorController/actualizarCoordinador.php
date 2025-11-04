<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php'; // Asumo que aquí tienes tu función redirigir()

$conn = conectar();

// 1. Validar que se recibieron ambos IDs (del coordinador y del usuario)
if (!isset($_POST['id'], $_POST['usuario_id']) || !is_numeric($_POST['id']) || !is_numeric($_POST['usuario_id'])) {
    // Si no se reciben los IDs, no podemos continuar.
    // La función redirigir() no está definida, así que usaré header() como estándar.
    header('Location: ../../vistas/coordinadores/verCoordinadores.php?error=invalido');
    exit;
}

$id_coordinador = $_POST['id'];
$id_usuario = $_POST['usuario_id'];

// 2. Recibir y limpiar datos del formulario (los nombres ahora son más simples)
// Datos para la tabla 'usuarios'
$nombre   = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$cedula   = trim($_POST['cedula'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');

// Datos para la tabla 'coordinadores'
$aldea_id             = intval($_POST['aldea_id'] ?? 0);
$fecha_inicio_gestion = trim($_POST['fecha_inicio_gestion'] ?? '');
$fecha_fin_gestion    = trim($_POST['fecha_fin_gestion'] ?? '');
$descripcion          = trim($_POST['descripcion'] ?? '');

// 3. Validar campos obligatorios
if (empty($nombre) || empty($apellido) || empty($cedula) || empty($aldea_id) || empty($fecha_inicio_gestion)) {
    header('Location: ../../vistas/coordinadores/verCoordinadores.php?error=campos_vacios'); // Redirigir a la lista general en caso de error grave
    exit;
}

// 4. Verificar si la cédula ya existe EN OTRO USUARIO
$stmt = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE cedula = ? AND id != ?");
$stmt->execute([$cedula, $id_usuario]);
if ($stmt->fetchColumn() > 0) {
    header('Location: ../../vistas/coordinadores/verCoordinadores.php?error=cedula_duplicada');
    exit;
}

// 5. Iniciar la transacción para asegurar la integridad de los datos
$conn->beginTransaction();

try {
    // ---- PASO A: Actualizar la tabla 'usuarios' con los datos personales ----
    $sql_usuario = "UPDATE usuarios SET nombre = ?, apellido = ?, cedula = ?, telefono = ? WHERE id = ?";
    $stmt_usuario = $conn->prepare($sql_usuario);
    $stmt_usuario->execute([
        $nombre,
        $apellido,
        $cedula,
        empty($telefono) ? null : $telefono, // Convertir vacío a NULL
        $id_usuario
    ]);

    // ---- PASO B: Actualizar la tabla 'coordinadores' con los datos de gestión ----
    $sql_coordinador = "UPDATE coordinadores SET aldea_id = ?, fecha_inicio_gestion = ?, fecha_fin_gestion = ?, descripcion = ? WHERE id = ?";
    $stmt_coordinador = $conn->prepare($sql_coordinador);
    $stmt_coordinador->execute([
        $aldea_id,
        $fecha_inicio_gestion,
        empty($fecha_fin_gestion) ? null : $fecha_fin_gestion, // Convertir vacío a NULL
        empty($descripcion) ? null : $descripcion, // Convertir vacío a NULL
        $id_coordinador
    ]);

    // 6. Si ambas actualizaciones fueron exitosas, confirmar la transacción
    $conn->commit();
    header('Location: ../../vistas/coordinadores/verCoordinadores.php?exito=actualizado');
    exit;

} catch (PDOException $e) {
    // 7. Si algo falló, revertir todos los cambios
    $conn->rollBack();
    // Podrías registrar el error $e->getMessage() para depuración
    header('Location: ../../vistas/coordinadores/verCoordinadores.php?error=db_fallo');
    exit;
}
?>