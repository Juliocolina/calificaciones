<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
require_once __DIR__ . '/../../modelos/UsuarioModelSimple.php';

verificarSesion();

$conn = conectar();
if (!$conn) {
    redirigir('error', 'No se pudo establecer conexión con la BD.', 'home.php');
    exit;
}

$usuarioModel = new UsuarioModelSimple($conn);

try {
    $usuarios = $usuarioModel->obtenerTodos();
} catch (PDOException $e) {
    redirigir('error', 'Error al obtener usuarios: ' . $e->getMessage(), 'home.php');
    exit;
}