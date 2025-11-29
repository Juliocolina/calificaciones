<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
require_once __DIR__ . '/../../modelos/UsuarioModelSimple.php';

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
        redirigir('error', 'ID inválido.', 'usuarios/verUsuario.php');
        exit;
    }

    $id = intval($_POST['id']);

    try {
        // Eliminar usando el modelo
        if ($usuarioModel->eliminarUsuario($id)) {
            redirigir('exito', 'Usuario eliminado exitosamente.', 'usuarios/verUsuario.php');
        } else {
            redirigir('error', 'No se encontró el usuario o ya fue eliminado.', 'usuarios/verUsuario.php');
        }

    } catch (PDOException $e) {
        redirigir('error', 'Error al eliminar usuario: ' . $e->getMessage(), 'usuarios/verUsuario.php');
    }
}

exit;

