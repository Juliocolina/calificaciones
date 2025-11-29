<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
require_once __DIR__ . '/../../modelos/ProfesorModel.php';

verificarRol(['admin']);

$conn = conectar();
if (!$conn) {
    redirigir('error', 'No se pudo establecer conexión con la BD.', 'profesores/crearProfesor.php');
    exit;
}

$profesorModel = new ProfesorModel($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $cedula = trim($_POST['cedula'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $aldea_id = intval($_POST['aldea_id'] ?? 0);
    $especialidad = trim($_POST['especialidad'] ?? '');

    if (empty($nombre) || empty($apellido) || empty($cedula) || empty($telefono) || $aldea_id <= 0 || empty($especialidad)) {
        redirigir('error', 'Todos los campos son obligatorios.', 'profesores/crearProfesor.php');
        exit;
    }

    if (!is_numeric($cedula) || strlen($cedula) < 7) {
        redirigir('error', 'La cédula debe ser numérica y tener al menos 7 dígitos.', 'profesores/crearProfesor.php');
        exit;
    }

    try {
        if ($profesorModel->existeProfesor($cedula)) {
            redirigir('error', 'Ya existe un profesor con esa cédula.', 'profesores/crearProfesor.php');
            exit;
        }

        if ($profesorModel->crearProfesor($nombre, $apellido, $cedula, $telefono, $aldea_id, $especialidad)) {
            redirigir('exito', 'Profesor registrado exitosamente.', 'profesores/verProfesores.php');
        } else {
            redirigir('error', 'No se pudo registrar el profesor.', 'profesores/crearProfesor.php');
        }

    } catch (PDOException $e) {
        redirigir('error', 'Error al crear profesor: ' . $e->getMessage(), 'profesores/crearProfesor.php');
    }
}

exit;