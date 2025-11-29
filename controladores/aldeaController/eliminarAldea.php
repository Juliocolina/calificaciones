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
    // Validar que se recibió el ID
    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        redirigir('error', 'ID inválido.', 'aldeas/verAldeas.php');
        exit;
    }

    $id = intval($_POST['id']);

    try {
        // Eliminar usando el modelo
        if ($aldeaModel->eliminarAldea($id)) {
            redirigir('exito', 'Aldea eliminada exitosamente.', 'aldeas/verAldeas.php');
        } else {
            redirigir('error', 'No se encontró la aldea o ya fue eliminada.', 'aldeas/verAldeas.php');
        }

    } catch (PDOException $e) {
        redirigir('error', 'Error al eliminar la aldea: ' . $e->getMessage(), 'aldeas/verAldeas.php');
    }
}

exit;

