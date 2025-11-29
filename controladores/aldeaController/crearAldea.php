<?php
// Ejemplo: controladores/aldeas/crearAldea.php

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
require_once __DIR__ . '/../../modelos/AldeaModel.php'; // Incluir el Modelo

verificarRol(['admin']);

// --- Lógica de la Conexión (Controlador) ---
$conn = conectar();
if (!$conn) {
    redirigir('error', 'No se pudo establecer conexión con la BD.', 'aldeas/crearAldea.php');
    exit;
}

// 1. Inicializar el Modelo
$aldeaModel = new AldeaModel($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 📋 Recibir y limpiar datos (C)
    $nombre = trim($_POST['nombre_aldea'] ?? '');
    $codigo = trim($_POST['codigo_aldea'] ?? '');
    $direccion = trim($_POST['direccion_aldea'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    if (empty($nombre) || empty($codigo) || empty($direccion)) {
        redirigir('error', 'Faltan campos obligatorios.', 'aldeas/crearAldea.php');
        exit;
    }
    // Validaciones de longitud
    if (strlen($nombre) > 255) {
        redirigir('error', 'El nombre de la aldea supera los 255 caracteres.', 'aldeas/crearAldea.php');
        exit;
    }
    if (strlen($codigo) > 100) {
        redirigir('error', 'El código es demasiado largo.', 'aldeas/crearAldea.php');
        exit;
    }
    if (strlen($direccion) > 255) {
        redirigir('error', 'La dirección excede los 255 caracteres.', 'aldeas/crearAldea.php');
        exit;
    }

    try {
        // 2. Usar el Modelo para la lógica de negocio (C -> M)
        if ($aldeaModel->existeAldea($nombre, $codigo)) {
            redirigir('error', 'Aldea ya registrada.', 'aldeas/crearAldea.php');
            exit;
        }

        // 3. Usar el Modelo para guardar (C -> M)
        $aldeaModel->crearAldea($nombre, $codigo, $direccion, $descripcion);

        redirigir('exito', 'Registro Exitoso..!', 'aldeas/crearAldea.php'); 

    } catch (PDOException $e) {
        redirigir('error', 'Error al registrar: ' . $e->getMessage(), 'aldeas/crearAldea.php'); 
    }
}

exit;