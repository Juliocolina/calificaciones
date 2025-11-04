<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';

$conn = conectar();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('error', 'Método de solicitud inválido.', 'usuarios/verUsuario.php');
    exit;
}

// 1. Validar ID de usuario (CRÍTICO)
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    redirigir('error', 'ID de usuario inválido.', 'usuarios/verUsuario.php');
    exit;
}

// 2. Recibir y limpiar datos
$id      = intval($_POST['id']);
$cedula  = trim($_POST['cedula'] ?? '');
$correo  = trim($_POST['correo'] ?? '');
$rol     = trim($_POST['rol'] ?? '');
$clave   = trim($_POST['clave'] ?? '');
$nombre  = trim($_POST['nombre'] ?? ''); 
$apellido = trim($_POST['apellido'] ?? ''); // Campo añadido

$redirect_url_error = 'usuarios/editarUsuario.php?id=' . $id;

// 3. Validaciones de Campos Obligatorios
if (empty($cedula) || empty($correo) || empty($rol) || empty($nombre) || empty($apellido)) {
    redirigir('error', 'Todos los campos (excepto la contraseña) son obligatorios.', $redirect_url_error);
    exit;
}

// 4. Validación de Formato de Correo
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    redirigir('error', 'Correo electrónico inválido.', $redirect_url_error);
    exit;
}

// 5. Verificar duplicados (cédula o correo)
// Debe verificar que la cédula/correo no estén siendo usados por *otro* usuario
$stmt = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE (cedula = ? OR correo = ?) AND id != ?");
$stmt->execute([$cedula, $correo, $id]);
if ($stmt->fetchColumn() > 0) {
    redirigir('error', 'Cédula o correo ya registrados en otro usuario.', $redirect_url_error);
    exit;
}

// 6. Lógica de Actualización y Clave
$sql_update = "";
$params = [];

if (!empty($clave)) {
    // Validar longitud de la clave si se intenta cambiar
    if (strlen($clave) < 6) { 
        redirigir('error', 'La nueva clave debe tener al menos 6 caracteres.', $redirect_url_error);
        exit;
    }

    $clave_hash = password_hash($clave, PASSWORD_DEFAULT);
    
    // Consulta con actualización de clave
    $sql_update = "UPDATE usuarios SET nombre = ?, apellido = ?, cedula = ?, correo = ?, rol = ?, clave = ? WHERE id = ?";
    $params = [$nombre, $apellido, $cedula, $correo, $rol, $clave_hash, $id];
} else {
    // Consulta sin actualizar clave
    $sql_update = "UPDATE usuarios SET nombre = ?, apellido = ?, cedula = ?, correo = ?, rol = ? WHERE id = ?";
    $params = [$nombre, $apellido, $cedula, $correo, $rol, $id];
}

// 7. Ejecutar la Transacción
try {
    $stmt = $conn->prepare($sql_update);
    $resultado = $stmt->execute($params);

    if ($resultado) {
        // Redirección exitosa (Manteniendo tu formato de URL de éxito)
        redirigir('exito', 'Usuario actualizado correctamente.', 'usuarios/verUsuario.php?id=' . $id);
    } else {
        // Error si la consulta no afectó ninguna fila
        redirigir('error', 'Error al actualizar el usuario. Ningún cambio realizado.', $redirect_url_error);
    }
} catch (PDOException $e) {
    // Error si la base de datos lanza una excepción
    redirigir('error', 'Error inesperado en la base de datos: ' . $e->getMessage(), $redirect_url_error);
}

exit;