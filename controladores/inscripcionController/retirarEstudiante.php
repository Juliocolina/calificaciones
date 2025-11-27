<?php
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
require_once __DIR__ . '/../../config/conexion.php';

verificarRol(['admin', 'coordinador']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../vistas/inscripciones/gestionarInscripciones.php");
    exit;
}

$estudiante_id = intval($_POST['estudiante_id'] ?? 0);
$seccion_id = intval($_POST['seccion_id'] ?? 0);

if ($estudiante_id <= 0 || $seccion_id <= 0) {
    header("Location: ../../vistas/inscripciones/gestionarInscripciones.php?error=datos_invalidos");
    exit;
}

$conn = conectar();

try {
    // Cambiar estatus de la inscripción a 'Retirado'
    $stmt = $conn->prepare("
        UPDATE inscripciones 
        SET estatus = 'Retirado' 
        WHERE estudiante_id = ? AND seccion_id = ? AND estatus = 'Cursando'
    ");
    
    $resultado = $stmt->execute([$estudiante_id, $seccion_id]);
    
    if ($resultado && $stmt->rowCount() > 0) {
        header("Location: ../../vistas/inscripciones/gestionarInscripciones.php?success=Estudiante retirado exitosamente");
    } else {
        header("Location: ../../vistas/inscripciones/gestionarInscripciones.php?error=No se pudo retirar al estudiante");
    }
    
} catch (PDOException $e) {
    error_log("Error al retirar estudiante: " . $e->getMessage());
    header("Location: ../../vistas/inscripciones/gestionarInscripciones.php?error=Error en la base de datos");
}
?>