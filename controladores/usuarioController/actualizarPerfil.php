<?php
session_start();
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';

// 1. Verificaciones de Seguridad
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.php");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../vistas/usuarios/miPerfil.php');
    exit;
}

$conn = conectar();
$usuario_id = $_SESSION['usuario_id'];
$rol_usuario = $_SESSION['rol'];

// 2. Recibir TODOS los datos del formulario (personales y de rol)
// --- Datos Personales (siempre se reciben) ---
$nombre   = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$correo   = trim($_POST['correo'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');

// --- Datos Específicos del Rol (se reciben según el rol del usuario) ---
if ($rol_usuario === 'coordinador') {
    $aldea_id             = intval($_POST['aldea_id'] ?? 0);
    $fecha_inicio_gestion = trim($_POST['fecha_inicio_gestion'] ?? '');
    $fecha_fin_gestion    = trim($_POST['fecha_fin_gestion'] ?? '');
    $descripcion          = trim($_POST['descripcion'] ?? '');
} elseif ($rol_usuario === 'profesor') {
    $especialidad = trim($_POST['especialidad'] ?? '');
    $titulo       = trim($_POST['titulo'] ?? '');
} elseif ($rol_usuario === 'estudiante') {
    $aldea_id                = intval($_POST['aldea_id'] ?? 0);
    
    // Procesar fecha de nacimiento desde selectores separados
    $dia_nacimiento = trim($_POST['dia_nacimiento'] ?? '');
    $mes_nacimiento = trim($_POST['mes_nacimiento'] ?? '');
    $ano_nacimiento = trim($_POST['ano_nacimiento'] ?? '');
    
    $fecha_nacimiento = null;
    if (!empty($dia_nacimiento) && !empty($mes_nacimiento) && !empty($ano_nacimiento)) {
        $fecha_nacimiento = $ano_nacimiento . '-' . $mes_nacimiento . '-' . $dia_nacimiento;
        // Validar que la fecha sea válida
        if (!checkdate($mes_nacimiento, $dia_nacimiento, $ano_nacimiento)) {
            redirigir('error', 'La fecha de nacimiento no es válida.', 'usuarios/miPerfil.php');
            exit;
        }
    }
    
    $parroquia               = trim($_POST['parroquia'] ?? '');
    $nivel_estudio           = trim($_POST['nivel_estudio'] ?? '');
    $institucion_procedencia = trim($_POST['institucion_procedencia'] ?? '');
    $nacionalidad            = trim($_POST['nacionalidad'] ?? '');
    $genero                  = trim($_POST['genero'] ?? '');
    $religion                = trim($_POST['religion'] ?? '');
    $etnia                   = trim($_POST['etnia'] ?? '');
    $discapacidad            = trim($_POST['discapacidad'] ?? '');

// Convertir campos opcionales vacíos a valores por defecto (algunos no pueden ser NULL en BD)
$discapacidad = (empty($discapacidad) || trim($discapacidad) === '') ? 'Ninguna' : trim($discapacidad);
$etnia = (empty($etnia) || trim($etnia) === '') ? null : trim($etnia);
$nivel_estudio = (empty($nivel_estudio) || trim($nivel_estudio) === '') ? 'Bachillerato' : trim($nivel_estudio);

// Debug: verificar valores antes de insertar
error_log('Valores para estudiante: discapacidad=' . $discapacidad . ', etnia=' . ($etnia ?? 'NULL') . ', nivel_estudio=' . $nivel_estudio);

}

// 3. Validaciones por rol
if ($rol_usuario === 'estudiante') {
    // Campos obligatorios para estudiantes
    if (empty($nombre) || empty($apellido) || empty($aldea_id) || empty($parroquia) || 
        empty($institucion_procedencia) || empty($nacionalidad) || empty($genero) || empty($religion)) {
        redirigir('error', 'Todos los campos son obligatorios para completar el perfil de estudiante. Solo discapacidad, etnia y nivel de estudio son opcionales.', 'usuarios/miPerfil.php');
        exit;
    }
} elseif ($rol_usuario === 'profesor') {
    // Campos obligatorios para profesores
    if (empty($nombre) || empty($apellido) || empty($especialidad) || empty($titulo)) {
        redirigir('error', 'Nombre, apellido, especialidad y título son obligatorios para profesores.', 'usuarios/miPerfil.php');
        exit;
    }
} elseif ($rol_usuario === 'coordinador') {
    // Campos obligatorios para coordinadores (aldea_id no es obligatorio porque no pueden cambiarla)
    if (empty($nombre) || empty($apellido) || empty($fecha_inicio_gestion)) {
        redirigir('error', 'Nombre, apellido y fecha de inicio de gestión son obligatorios para coordinadores.', 'usuarios/miPerfil.php');
        exit;
    }
}

// Validación de correo si no está vacío
if (!empty($correo) && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    redirigir('error', 'El formato del correo electrónico no es válido.', 'usuarios/miPerfil.php');
    exit;
}

// 4. Iniciar la transacción
$conn->beginTransaction();

try {
    // --- PASO A: Actualizar SIEMPRE la tabla 'usuarios' ---
    $stmt_usuario = $conn->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, correo = ?, telefono = ? WHERE id = ?");
    $stmt_usuario->execute([$nombre, $apellido, $correo, $telefono, $usuario_id]);

    // --- PASO B: Lógica inteligente para cada rol ---
    
    // LÓGICA PARA COORDINADOR
    if ($rol_usuario === 'coordinador') {
        $stmt_check = $conn->prepare("SELECT id FROM coordinadores WHERE usuario_id = ?");
        $stmt_check->execute([$usuario_id]);
        if ($stmt_check->fetch()) {
            $sql_rol = "UPDATE coordinadores SET aldea_id = ?, fecha_inicio_gestion = ?, fecha_fin_gestion = ?, descripcion = ? WHERE usuario_id = ?";
            $params_rol = [$aldea_id, $fecha_inicio_gestion, (empty($fecha_fin_gestion) ? null : $fecha_fin_gestion), $descripcion, $usuario_id];
        } else {
            $sql_rol = "INSERT INTO coordinadores (aldea_id, fecha_inicio_gestion, fecha_fin_gestion, descripcion, usuario_id) VALUES (?, ?, ?, ?, ?)";
            $params_rol = [$aldea_id, $fecha_inicio_gestion, (empty($fecha_fin_gestion) ? null : $fecha_fin_gestion), $descripcion, $usuario_id];
        }
        $stmt_rol = $conn->prepare($sql_rol);
        $stmt_rol->execute($params_rol);
    } 
    // LÓGICA PARA PROFESOR
    elseif ($rol_usuario === 'profesor') {
        $stmt_check = $conn->prepare("SELECT id FROM profesores WHERE usuario_id = ?");
        $stmt_check->execute([$usuario_id]);
        if ($stmt_check->fetch()) {
            $sql_rol = "UPDATE profesores SET especialidad = ?, titulo = ? WHERE usuario_id = ?";
            $params_rol = [$especialidad, $titulo, $usuario_id];
        } else {
            // Crear perfil de profesor sin aldea (admin la asignará después)
            $sql_rol = "INSERT INTO profesores (especialidad, titulo, usuario_id) VALUES (?, ?, ?)";
            $params_rol = [$especialidad, $titulo, $usuario_id];
        }
        $stmt_rol = $conn->prepare($sql_rol);
        $stmt_rol->execute($params_rol);
    }
    // LÓGICA PARA ESTUDIANTE
    elseif ($rol_usuario === 'estudiante') {
        $stmt_check = $conn->prepare("SELECT id FROM estudiantes WHERE usuario_id = ?");
        $stmt_check->execute([$usuario_id]);
        if ($stmt_check->fetch()) {
                $sql_rol = "UPDATE estudiantes SET aldea_id = ?, fecha_nacimiento = ?, parroquia = ?, nivel_estudio = ?, institucion_procedencia = ?, nacionalidad = ?, genero = ?, religion = ?, etnia = ?, discapacidad = ? WHERE usuario_id = ?";
                $params_rol = [$aldea_id, $fecha_nacimiento, $parroquia, $nivel_estudio, $institucion_procedencia, $nacionalidad, $genero, $religion, $etnia, $discapacidad, $usuario_id];
        } else {
                $sql_rol = "INSERT INTO estudiantes (aldea_id, fecha_nacimiento, parroquia, nivel_estudio, institucion_procedencia, nacionalidad, genero, religion, etnia, discapacidad, usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $params_rol = [$aldea_id, $fecha_nacimiento, $parroquia, $nivel_estudio, $institucion_procedencia, $nacionalidad, $genero, $religion, $etnia, $discapacidad, $usuario_id];
        }
        $stmt_rol = $conn->prepare($sql_rol);
        $stmt_rol->execute($params_rol);
    }

    // Si todo fue exitoso, confirmar los cambios
    $conn->commit();
    redirigir('exito', 'Perfil actualizado correctamente.', 'usuarios/miPerfil.php');
    exit;

} catch (PDOException $e) {
    // Si algo falló, deshacer todos los cambios
    $conn->rollBack();
    // Log del error para depuración con más detalle
    error_log('Error actualizando perfil usuario_id=' . $usuario_id . ', rol=' . $rol_usuario . ': ' . $e->getMessage());
    
    // Mostrar error más específico para depuración
    $error_detalle = urlencode($e->getMessage());
    redirigir('error', 'Error al actualizar el perfil. Detalle: ' . $e->getMessage(), 'usuarios/miPerfil.php');
    exit;
}
?>
