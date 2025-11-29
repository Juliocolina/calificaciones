<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
require_once __DIR__ . '/../../modelos/PnfModel.php';

verificarRol(['admin']);

$conn = conectar();
if (!$conn) {
    redirigir('error', 'No se pudo establecer conexión con la BD.', 'pnfs/crearPnf.php');
    exit;
}

$pnfModel = new PnfModel($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre_pnf'] ?? '');
    $codigo = trim($_POST['codigo_pnf'] ?? '');
    $aldea_id = intval($_POST['aldea_id'] ?? 0);
    $descripcion = trim($_POST['descripcion'] ?? '') ?: null;

    if (empty($nombre) || empty($codigo) || $aldea_id <= 0) {
        redirigir('error', 'Todos los campos obligatorios deben completarse.', 'pnfs/crearPnf.php');
        exit;
    }

    try {
        if ($pnfModel->existePnf($nombre, $codigo)) {
            redirigir('error', 'Ya existe un PNF con ese nombre o código.', 'pnfs/crearPnf.php');
            exit;
        }

        if ($pnfModel->crearPnf($nombre, $codigo, $aldea_id, $descripcion)) {
            redirigir('exito', 'PNF creado exitosamente.', 'pnfs/verPnfs.php');
        } else {
            redirigir('error', 'No se pudo crear el PNF.', 'pnfs/crearPnf.php');
        }

    } catch (PDOException $e) {
        redirigir('error', 'Error al crear PNF: ' . $e->getMessage(), 'pnfs/crearPnf.php');
    }
}

exit;
