<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';

verificarSesion();

// Validar que se recibieron los datos necesarios
if (!isset($_POST['id']) || !is_numeric($_POST['id']) || !isset($_POST['tipo_graduacion'])) {
    redirigir('error', 'Datos de graduación inválidos.', 'estudiantes/verEstudiantes.php');
    exit;
}

$id_estudiante = intval($_POST['id']);
$tipo_graduacion = trim($_POST['tipo_graduacion']);

// Validar tipo de graduación
if (!in_array($tipo_graduacion, ['TSU', 'Licenciado'])) {
    redirigir('error', 'Tipo de graduación no válido.', 'estudiantes/verEstudiantes.php');
    exit;
}

$conn = conectar();

try {
    $conn->beginTransaction();
    
    // Verificar que el estudiante existe y está activo
    $stmt_verificar = $conn->prepare("
        SELECT e.id, e.estado_academico, e.pnf_id, u.nombre, u.apellido 
        FROM estudiantes e 
        INNER JOIN usuarios u ON e.usuario_id = u.id 
        WHERE e.id = ?
    ");
    $stmt_verificar->execute([$id_estudiante]);
    $estudiante = $stmt_verificar->fetch(PDO::FETCH_ASSOC);
    
    if (!$estudiante) {
        throw new Exception('Estudiante no encontrado.');
    }
    
    if ($estudiante['estado_academico'] !== 'activo') {
        throw new Exception('Solo se pueden graduar estudiantes con estado "activo".');
    }
    
    if (!$estudiante['pnf_id']) {
        throw new Exception('El estudiante debe tener un PNF asignado para graduarse.');
    }
    
    // Verificar si ya tiene esta graduación
    $stmt_check = $conn->prepare("
        SELECT id FROM graduaciones 
        WHERE estudiante_id = ? AND tipo_graduacion = ?
    ");
    $stmt_check->execute([$id_estudiante, $tipo_graduacion]);
    if ($stmt_check->fetch()) {
        throw new Exception('El estudiante ya tiene una graduación de tipo ' . $tipo_graduacion . '.');
    }
    
    // Insertar registro en tabla graduaciones
    $stmt_graduacion = $conn->prepare("
        INSERT INTO graduaciones (estudiante_id, tipo_graduacion, fecha_graduacion, pnf_id) 
        VALUES (?, ?, CURDATE(), ?)
    ");
    $stmt_graduacion->execute([$id_estudiante, $tipo_graduacion, $estudiante['pnf_id']]);
    
    // Actualizar estado del estudiante solo si es Licenciado o primera graduación
    if ($tipo_graduacion === 'Licenciado') {
        $stmt_graduar = $conn->prepare("
            UPDATE estudiantes 
            SET estado_academico = 'graduado', fecha_graduacion = CURDATE() 
            WHERE id = ?
        ");
        $stmt_graduar->execute([$id_estudiante]);
    }
    
    $conn->commit();
    
    $mensaje = "El estudiante {$estudiante['nombre']} {$estudiante['apellido']} ha sido graduado exitosamente como {$tipo_graduacion}.";
    redirigir('exito', $mensaje, 'estudiantes/verEstudiantes.php');
    
} catch (Exception $e) {
    $conn->rollBack();
    error_log('Error graduando estudiante: ' . $e->getMessage());
    redirigir('error', 'Error al graduar estudiante: ' . $e->getMessage(), 'estudiantes/verEstudiantes.php');
}

exit;
?>