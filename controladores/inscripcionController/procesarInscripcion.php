<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/funciones.php';

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('error', 'Método no permitido.', 'secciones/verSecciones.php');
    exit;
}

$conn = conectar();

try {
    // Validar datos de entrada
    $seccion_id = intval($_POST['seccion_id'] ?? 0);
    $estudiante_id = intval($_POST['estudiante_id'] ?? 0);
    
    if ($seccion_id <= 0 || $estudiante_id <= 0) {
        redirigir('error', 'Datos de inscripción inválidos.', 'secciones/verSecciones.php');
        exit;
    }
    
    $conn->beginTransaction();
    
    // Verificar que la sección existe y está disponible
    $stmt = $conn->prepare("
        SELECT s.cupo_maximo, oa.estatus, COUNT(i.id) as inscritos
        FROM secciones s
        JOIN oferta_academica oa ON s.oferta_academica_id = oa.id
        LEFT JOIN inscripciones i ON s.id = i.seccion_id
        WHERE s.id = ?
        GROUP BY s.id
    ");
    $stmt->execute([$seccion_id]);
    $seccion = $stmt->fetch();
    
    if (!$seccion) {
        throw new Exception('Sección no encontrada.');
    }
    
    if ($seccion['estatus'] !== 'Abierto') {
        throw new Exception('La oferta académica no está abierta para inscripciones.');
    }
    
    if ($seccion['inscritos'] >= $seccion['cupo_maximo']) {
        throw new Exception('No hay cupos disponibles en esta sección.');
    }
    
    // Verificar que el estudiante no esté ya inscrito en esta sección
    $stmt = $conn->prepare("SELECT id FROM inscripciones WHERE estudiante_id = ? AND seccion_id = ?");
    $stmt->execute([$estudiante_id, $seccion_id]);
    
    if ($stmt->fetch()) {
        throw new Exception('El estudiante ya está inscrito en esta sección.');
    }
    
    // Verificar que el estudiante no esté inscrito en otra sección de la misma materia en el mismo período
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM inscripciones i
        JOIN secciones s1 ON i.seccion_id = s1.id
        JOIN secciones s2 ON s2.id = ?
        WHERE i.estudiante_id = ? 
        AND s1.materia_id = s2.materia_id 
        AND s1.oferta_academica_id = s2.oferta_academica_id
        AND i.estatus IN ('Cursando', 'Aprobada')
    ");
    $stmt->execute([$seccion_id, $estudiante_id]);
    $conflicto = $stmt->fetch();
    
    if ($conflicto['count'] > 0) {
        throw new Exception('El estudiante ya está inscrito en otra sección de esta materia.');
    }
    
    // Insertar inscripción
    $stmt = $conn->prepare("
        INSERT INTO inscripciones (estudiante_id, seccion_id, estatus) 
        VALUES (?, ?, 'Cursando')
    ");
    $stmt->execute([$estudiante_id, $seccion_id]);
    
    $conn->commit();
    
    redirigir('success', 'Estudiante inscrito exitosamente en la sección.', "inscripciones/inscribirEstudiantes.php?seccion_id={$seccion_id}");
    
} catch (Exception $e) {
    $conn->rollback();
    error_log("Error al procesar inscripción: " . $e->getMessage());
    redirigir('error', $e->getMessage(), "inscripciones/inscribirEstudiantes.php?seccion_id={$seccion_id}");
}
?>