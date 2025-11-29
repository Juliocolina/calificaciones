<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
require_once __DIR__ . '/../../modelos/MateriaModel.php';

verificarRol(['admin']);

$conn = conectar();
if (!$conn) {
    redirigir('error', 'No se pudo establecer conexión con la BD.', 'materias/crearMateria.php');
    exit;
}

$materiaModel = new MateriaModel($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $codigo = trim($_POST['codigo'] ?? '');
    $pnf_id = intval($_POST['pnf_id'] ?? 0);
    $duracion = trim($_POST['duracion'] ?? '');
    $creditos = intval($_POST['creditos'] ?? 0);
    $descripcion = trim($_POST['descripcion'] ?? '');

    if (empty($nombre) || empty($codigo) || $pnf_id <= 0 || empty($duracion) || $creditos <= 0) {
        redirigir('error', 'Todos los campos son obligatorios.', 'materias/crearMateria.php');
        exit;
    }

    try {
        if ($materiaModel->existeMateria($nombre, $codigo)) {
            redirigir('error', 'Ya existe una materia con ese nombre o código.', 'materias/crearMateria.php');
            exit;
        }

        if ($materiaModel->crearMateria($nombre, $codigo, $pnf_id, $duracion, $creditos, $descripcion)) {
            redirigir('exito', 'Materia creada exitosamente.', 'materias/materiasPorPnf.php');
        } else {
            redirigir('error', 'No se pudo crear la materia.', 'materias/crearMateria.php');
        }

    } catch (PDOException $e) {
        redirigir('error', 'Error al crear materia: ' . $e->getMessage(), 'materias/crearMateria.php');
    }
}

exit;