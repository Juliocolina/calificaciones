<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';

$conn = conectar();
if (!$conn) {
    redirigir('error', 'No se pudo establecer conexión con la base de datos.', 'estudiantes/crearEstudiante.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('error', 'Método no permitido.', 'estudiantes/crearEstudiante.php');
    exit;
}

// 📋 Recibir y limpiar datos del formulario (SOLO los campos de información personal)
$cedula = trim($_POST['cedula'] ?? '');
$nombre = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$fecha_nacimiento = trim($_POST['fecha_nacimiento'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$direccion = trim($_POST['direccion'] ?? '');
$nacionalidad = trim($_POST['nacionalidad'] ?? 'Venezolano');
$genero = trim($_POST['genero'] ?? 'Masculino');
$religion = trim($_POST['religion'] ?? '');
$etnia = trim($_POST['etnia'] ?? '');
$discapacidad = trim($_POST['discapacidad'] ?? '');
$nivel_estudio = trim($_POST['nivel_estudio'] ?? 'Bachillerato');
$institucion_procedencia = trim($_POST['institucion_procedencia'] ?? '');
$usuario_id   = isset($_POST['usuario_id']) ? intval($_POST['usuario_id']) : 0;
$aldea_id     = isset($_POST['aldea_id']) ? intval($_POST['aldea_id']) : 0; 





// === CAMPOS CONTROLADOS POR EL ADMINISTRADOR O SISTEMA ===

// 1. Campos que deben ser NULL para asignación posterior
$codigo_estudiante = null;
$pnf_id = null;
$trayecto_id = null;
$trimestre_id = null;
$observaciones = null; // No asignado por el estudiante
$fecha_graduacion = null; // No asignado por el estudiante

// 2. Estado Inicial (Mejor 'pendiente' o 'registrado' que NULL)
$estado_academico = null; 

// 3. Fecha de Ingreso (Se puede dejar en NULL si el Admin debe asignarla, o poner la fecha actual de registro)
// Opción A (NULL): El administrador debe validar y asignar la fecha de ingreso
$fecha_ingreso = null; 

// Opción B (Fecha Actual): Descomentar si la fecha de registro es la fecha de ingreso
// $fecha_ingreso = date('Y-m-d'); 

// --- INICIO DE VALIDACIONES PARA USUARIO ID ---

// 1. Validar que el usuario_id sea un valor positivo
if ($usuario_id <= 0) {
    redirigir('error', 'El ID de usuario es un campo obligatorio e inválido.', 'estudiantes/crearEstudiante.php');
    exit;
}

// 2. Verificar que el ID de usuario exista en la tabla 'usuarios'
try {
    $stmt_usuario = $conn->prepare("SELECT id FROM usuarios WHERE id = ?");
    $stmt_usuario->execute([$usuario_id]);
    
    if ($stmt_usuario->rowCount() === 0) {
        redirigir('error', 'El ID de usuario proporcionado no existe en el sistema de usuarios.', 'estudiantes/crearEstudiante.php');
        exit;
    }
} catch (PDOException $e) {
    redirigir('error', 'Error de verificación de existencia de usuario: ' . $e->getMessage(), 'estudiantes/crearEstudiante.php');
    exit;
}

// 3. Verificar que el usuario_id no esté YA asignado a otro estudiante (Relación 1:1)
try {
    $stmt_user_asignado = $conn->prepare("SELECT id FROM estudiantes WHERE usuario_id = ?");
    $stmt_user_asignado->execute([$usuario_id]);
    
    if ($stmt_user_asignado->rowCount() > 0) {
        redirigir('error', 'El ID de usuario ya está asociado a otro registro de estudiante.', 'estudiantes/crearEstudiante.php');
        exit;
    }
} catch (PDOException $e) {
    redirigir('error', 'Error de verificación de asignación del usuario: ' . $e->getMessage(), 'estudiantes/crearEstudiante.php');
    exit;
}

// --- FIN DE VALIDACIONES PARA USUARIO ID ---

if ($aldea_id <= 0) {
    redirigir('error', 'Debe seleccionar una aldea, es un campo obligatorio.', 'estudiantes/crearEstudiante.php');
    exit;
}

// *** CAMBIO 2B: VERIFICAR QUE LA ALDEA EXISTA ***
try {
    // Asume que la tabla de aldeas se llama 'aldeas'
    $stmt_aldea = $conn->prepare("SELECT id FROM aldeas WHERE id = ?");
    $stmt_aldea->execute([$aldea_id]);
    
    if ($stmt_aldea->rowCount() === 0) {
        redirigir('error', 'La aldea seleccionada no existe en la base de datos.', 'estudiantes/crearEstudiante.php');
        exit;
    }
} catch (PDOException $e) {
    redirigir('error', 'Error de verificación de aldea: ' . $e->getMessage(), 'estudiantes/crearEstudiante.php');
    exit;
}


// 🛑 Validar campos obligatorios que NO son administrados
if (
    !$cedula || !$nombre || !$apellido || !$fecha_nacimiento || !$correo ||
    !$telefono || !$direccion || !$nacionalidad ||
    !$genero || !$religion || !$etnia || !$discapacidad || !$nivel_estudio ||
    !$institucion_procedencia
) {
    redirigir('error', 'Faltan datos obligatorios. Por favor, complete todos los campos de información personal requeridos.', 'estudiantes/crearEstudiante.php');
    exit;
}

// 🔍 Verificar existencia y duplicidad (solo por cédula o correo)
try {
    $stmt_duplicado = $conn->prepare("SELECT id FROM estudiantes WHERE cedula = ? OR correo = ?");
    $stmt_duplicado->execute([$cedula, $correo]);
    if ($stmt_duplicado->rowCount() > 0) {
        redirigir('error', 'La cédula o correo ya están registrados.', 'estudiantes/crearEstudiante.php');
        exit;
    }
} catch (PDOException $e) {
    redirigir('error', 'Error en la verificación de datos: ' . $e->getMessage(), 'estudiantes/crearEstudiante.php');
    exit;
}

// 🗃️ Preparar y ejecutar la inserción en la base de datos
$sql = "INSERT INTO estudiantes (
            cedula, nombre, apellido, fecha_nacimiento, correo, telefono, usuario_id,
            aldea_id, pnf_id, trayecto_id, trimestre_id, codigo_estudiante,
            estado_academico, observaciones, fecha_ingreso, fecha_graduacion,
            direccion, nacionalidad, genero, religion, etnia, discapacidad,
            nivel_estudio, institucion_procedencia
        ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )";

$stmt = $conn->prepare($sql);

$exito = $stmt->execute([
    $cedula, $nombre, $apellido, $fecha_nacimiento, $correo, $telefono, $usuario_id,
    $aldea_id, $pnf_id, $trayecto_id, $trimestre_id, $codigo_estudiante,
    $estado_academico, $observaciones, $fecha_ingreso, $fecha_graduacion,
    $direccion, $nacionalidad, $genero,
    $religion, $etnia, $discapacidad,
    $nivel_estudio, $institucion_procedencia
]);

if ($exito) {
    redirigir('exito', 'Estudiante registrado exitosamente para revisión administrativa. Su estado inicial es: ' . $estado_academico, 'estudiantes/crearEstudiante.php');
} else {
    $errorInfo = $stmt->errorInfo();
    redirigir('error', 'Error al guardar el estudiante: ' . $errorInfo[2], 'estudiantes/crearEstudiante.php');
}
exit;