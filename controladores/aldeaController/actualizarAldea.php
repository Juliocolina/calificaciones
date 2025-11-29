<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
require_once __DIR__ . '/../../modelos/AldeaModel.php';

verificarRol(['admin']);

$conn = conectar();
if (!$conn) {
    redirigir('error', 'No se pudo establecer conexión con la BD.', 'aldeas/verAldeas.php');
    exit;
}

// Inicializar modelo
$aldeaModel = new AldeaModel($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar y limpiar el ID
    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        redirigir('error', 'ID inválido.', 'aldeas/verAldeas.php');
        exit;
    }
    $id = intval($_POST['id']);

    // Recibir y limpiar datos del formulario
    $nombre = trim($_POST['nombre'] ?? '');
    $codigo = trim($_POST['codigo'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $descripcion = !empty($_POST['descripcion']) ? trim($_POST['descripcion']) : null;

    // Validación de campos obligatorios
    if (empty($nombre) || empty($codigo) || empty($direccion)) {
        redirigir('error', 'Todos los campos son obligatorios.', 'aldeas/editarAldea.php?id=' . $id);
        exit;
    }

    // Validaciones de longitud
    if (strlen($nombre) > 255) {
        redirigir('error', 'El nombre supera los 255 caracteres.', 'aldeas/editarAldea.php?id=' . $id);
        exit;
    }
    if (strlen($codigo) > 100) {
        redirigir('error', 'El código es demasiado largo.', 'aldeas/editarAldea.php?id=' . $id);
        exit;
    }
    if (strlen($direccion) > 255) {
        redirigir('error', 'La dirección excede los 255 caracteres.', 'aldeas/editarAldea.php?id=' . $id);
        exit;
    }

    try {
        // Verificar duplicados usando el modelo
        if ($aldeaModel->existeAldea($nombre, $codigo, $id)) {
            redirigir('error', 'Ya existe una aldea con ese nombre o código.', 'aldeas/editarAldea.php?id=' . $id);
            exit;
        }

        // Actualizar usando el modelo
        if ($aldeaModel->actualizarAldea($id, $nombre, $codigo, $direccion, $descripcion)) {
            redirigir('exito', 'Aldea actualizada con éxito.', 'aldeas/verAldeas.php');
        } else {
            redirigir('error', 'Error al actualizar la aldea.', 'aldeas/editarAldea.php?id=' . $id);
        }

    } catch (PDOException $e) {
        redirigir('error', 'Error al actualizar: ' . $e->getMessage(), 'aldeas/editarAldea.php?id=' . $id);
    }
}

exit;