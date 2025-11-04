<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php'; 

$conn = conectar();
$redirect_view = 'usuarios/crearUsuario.php'; // URL de retorno por defecto

if (!$conn) {
    redirigir('error', 'No se pudo conectar con la base de datos.', $redirect_view);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('error', 'Método de solicitud inválido.', $redirect_view);
    exit;
}

// 📋 Recibir y limpiar los datos
$cedula = trim($_POST['cedula'] ?? '');
$rol    = trim($_POST['rol'] ?? '');
$clave  = trim($_POST['clave'] ?? '');

// ✅ Validar campos obligatorios
if (empty($cedula) || empty($rol) || empty($clave)) {
    redirigir('error', 'Todos los campos son obligatorios.', $redirect_view);
    exit;
}

// 🔒 CRÍTICO: Validar longitud de la clave para seguridad
if (strlen($clave) < 6) { 
    redirigir('error', 'La clave debe tener al menos 6 caracteres.', $redirect_view);
    exit;
}

// 🔍 Verificar duplicados por cédula
try {
    $verificar = $conn->prepare("SELECT id FROM usuarios WHERE cedula = ?");
    $verificar->execute([$cedula]);

    if ($verificar->fetch()) {
        redirigir('error', 'La cédula ya está registrada.', $redirect_view);
        exit;
    }
} catch (PDOException $e) {
    redirigir('error', 'Error al validar la cédula en la base de datos.', $redirect_view);
    exit;
}

// 🔐 Hashear la clave antes de insertarla
$clave_hasheada = password_hash($clave, PASSWORD_DEFAULT);


try {
    // 3. Insertar nuevo usuario (activo=1 por defecto)
    $insertar = $conn->prepare("INSERT INTO usuarios (cedula, rol, clave, activo) VALUES (?, ?, ?, 1)");
    $exito = $insertar->execute([$cedula, $rol, $clave_hasheada]);

    if ($exito) {
        redirigir('exito', 'Usuario creado correctamente. La clave ha sido cifrada.', 'usuarios/verUsuario.php'); 
        exit;
    } else {
        redirigir('error', 'No se pudo crear el usuario.', $redirect_view);
        exit;
    }
} catch (PDOException $e) {
    redirigir('error', 'Error inesperado en la base de datos: ' . $e->getMessage(), $redirect_view);
    exit;
}