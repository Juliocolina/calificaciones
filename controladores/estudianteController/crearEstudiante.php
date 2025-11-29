<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
require_once __DIR__ . '/../../modelos/EstudianteModel.php';

verificarRol(['admin']);

$conn = conectar();
if (!$conn) {
    redirigir('error', 'No se pudo establecer conexión con la base de datos.', 'estudiantes/crearEstudiante.php');
    exit;
}

// Inicializar modelo
$estudianteModel = new EstudianteModel($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recibir y limpiar datos
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $cedula = trim($_POST['cedula'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $aldea_id = isset($_POST['aldea_id']) ? intval($_POST['aldea_id']) : 0;

    // Validaciones básicas
    if (empty($nombre) || empty($apellido) || empty($cedula) || empty($telefono) || $aldea_id <= 0) {
        redirigir('error', 'Todos los campos son obligatorios.', 'estudiantes/crearEstudiante.php');
        exit;
    }

    if (!is_numeric($cedula) || strlen($cedula) < 7) {
        redirigir('error', 'La cédula debe ser numérica y tener al menos 7 dígitos.', 'estudiantes/crearEstudiante.php');
        exit;
    }

    try {
        // Verificar duplicados usando el modelo
        if ($estudianteModel->existeEstudiante($cedula)) {
            redirigir('error', 'Ya existe un estudiante con esa cédula.', 'estudiantes/crearEstudiante.php');
            exit;
        }

        // Generar código de estudiante
        $codigo_estudiante = $estudianteModel->generarCodigoEstudiante();

        // Crear estudiante usando el modelo
        if ($estudianteModel->crearEstudiante($nombre, $apellido, $cedula, $telefono, $aldea_id, $codigo_estudiante)) {
            redirigir('exito', 'Estudiante registrado exitosamente. Código: ' . $codigo_estudiante, 'estudiantes/verEstudiantes.php');
        } else {
            redirigir('error', 'No se pudo registrar el estudiante.', 'estudiantes/crearEstudiante.php');
        }

    } catch (PDOException $e) {
        redirigir('error', 'Error al crear estudiante: ' . $e->getMessage(), 'estudiantes/crearEstudiante.php');
    }
}

exit;