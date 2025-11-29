<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
require_once __DIR__ . '/../../modelos/UsuarioModel.php';

verificarRol(['admin']);

$conn = conectar();
if (!$conn) {
    redirigir('error', 'No se pudo conectar con la base de datos.', 'usuarios/verUsuario.php');
    exit;
}

$usuarioModel = new UsuarioModelSimple($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar ID
    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        redirigir('error', 'ID de usuario inválido.', 'usuarios/verUsuario.php');
        exit;
    }

    $id = intval($_POST['id']);
    $cedula = trim($_POST['cedula'] ?? '');
    $rol = trim($_POST['rol'] ?? '');
    $clave = trim($_POST['clave'] ?? '');

    // Validar campos obligatorios (clave es opcional para actualizar)
    if (empty($cedula) || empty($rol)) {
        redirigir('error', 'Cédula y rol son obligatorios.', 'usuarios/editarUsuario.php?id=' . $id);
        exit;
    }

    try {
        // Verificar duplicados usando el modelo
        if ($usuarioModel->existeUsuario($cedula, $id)) {
            redirigir('error', 'La cédula ya está registrada en otro usuario.', 'usuarios/editarUsuario.php?id=' . $id);
            exit;
        }

        // Actualizar datos básicos
        if ($usuarioModel->actualizarUsuario($id, $cedula, $rol)) {
            // Si se proporciona nueva clave, actualizarla
            if (!empty($clave)) {
                if (strlen($clave) < 6) {
                    redirigir('error', 'La nueva clave debe tener al menos 6 caracteres.', 'usuarios/editarUsuario.php?id=' . $id);
                    exit;
                }
                $clave_hash = password_hash($clave, PASSWORD_DEFAULT);
                $usuarioModel->actualizarClave($id, $clave_hash);
            }
            
            redirigir('exito', 'Usuario actualizado correctamente.', 'usuarios/verUsuario.php');
        } else {
            redirigir('error', 'No se pudo actualizar el usuario.', 'usuarios/editarUsuario.php?id=' . $id);
        }

    } catch (PDOException $e) {
        redirigir('error', 'Error al actualizar usuario: ' . $e->getMessage(), 'usuarios/editarUsuario.php?id=' . $id);
    }
}

exit;