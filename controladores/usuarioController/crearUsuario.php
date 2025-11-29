<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
require_once __DIR__ . '/../../modelos/UsuarioModelSimple.php';

verificarRol(['admin']);

$conn = conectar();
if (!$conn) {
    redirigir('error', 'No se pudo conectar con la base de datos.', 'usuarios/crearUsuario.php');
    exit;
}

// Inicializar modelo
$usuarioModel = new UsuarioModelSimple($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recibir y limpiar datos
    $cedula = trim($_POST['cedula'] ?? '');
    $rol = trim($_POST['rol'] ?? '');
    $clave = trim($_POST['clave'] ?? '');

    // Validar campos obligatorios
    if (empty($cedula) || empty($rol) || empty($clave)) {
        redirigir('error', 'Todos los campos son obligatorios.', 'usuarios/crearUsuario.php');
        exit;
    }

    // Validar longitud de clave
    if (strlen($clave) < 6) {
        redirigir('error', 'La clave debe tener al menos 6 caracteres.', 'usuarios/crearUsuario.php');
        exit;
    }

    try {
        // Verificar duplicados usando el modelo
        if ($usuarioModel->existeUsuario($cedula)) {
            redirigir('error', 'La cédula ya está registrada.', 'usuarios/crearUsuario.php');
            exit;
        }

        // Hashear clave
        $clave_hasheada = password_hash($clave, PASSWORD_DEFAULT);

        // Crear usuario usando el modelo
        if ($usuarioModel->crearUsuario($cedula, $rol, $clave_hasheada)) {
            redirigir('exito', 'Usuario creado correctamente. La clave ha sido cifrada.', 'usuarios/verUsuario.php');
        } else {
            redirigir('error', 'No se pudo crear el usuario.', 'usuarios/crearUsuario.php');
        }

    } catch (PDOException $e) {
        redirigir('error', 'Error al crear usuario: ' . $e->getMessage(), 'usuarios/crearUsuario.php');
    }
}

exit;