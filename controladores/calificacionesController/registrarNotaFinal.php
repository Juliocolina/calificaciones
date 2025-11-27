<?php
session_start();
require_once '../../config/conexion.php';
require_once '../hellpers/auth.php';

// Verificar autenticación
verificarSesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../vistas/calificaciones/cargarNotasFinal.php');
    exit;
}

try {
    $pdo = conectar();
    
    // =========================================================
    // 🚩 CAMBIO 1: CAPTURAR EL ID DE INSCRIPCIÓN ESPECÍFICO
    // =========================================================
    // La vista debe enviar el ID de la inscripción a calificar en 'inscripcion_id_final'.
    // Usamos esta variable como la fuente de verdad.
    $inscripcion_actual = $_POST['inscripcion_id_final'] ?? $_POST['inscripcion_id'] ?? null; 
    
    // Obtenemos los demás datos
    $inscripciones_ids = $_POST['inscripciones_ids'] ?? null;
    $estudiante_id = $_POST['estudiante_id'] ?? null;
    $nota_numerica = $_POST['nota_numerica'] ?? null;
    $periodo_academico = $_POST['periodo_academico'] ?? null;
    $tipo_evaluacion = $_POST['tipo_evaluacion'] ?? 'Ordinaria';
    $fecha_registro = date('Y-m-d');
    $usuario_id = $_SESSION['usuario_id'];
    
    // Validaciones básicas (se asegura que el ID de la inscripción exista)
    if (!$inscripcion_actual || !$nota_numerica || !$periodo_academico) {
        throw new Exception('Faltan datos clave para el registro de la nota.');
    }
    
    // Convertir IDs a array
    $inscripciones_array = explode(',', $inscripciones_ids);
    
    if ($nota_numerica < 0 || $nota_numerica > 20) {
        throw new Exception('La nota debe estar entre 0 y 20');
    }
    
    // =========================================================================
    // ✂️ CAMBIO 2: ELIMINACIÓN DE LA LÓGICA DE BÚSQUEDA ERRÓNEA (Líneas 40 a 79)
    // Se elimina el bloque que intentaba determinar el $inscripcion_actual por trimestre
    // o duración de la materia, ya que es la causa de la corrupción del dato.
    // Usamos directamente $inscripcion_actual, que es el ID de la inscripción de la sección actual.
    // =========================================================================
    
    // Solo eliminar calificación de la inscripción específica ($inscripcion_actual)
    // Esto asegura que si estamos guardando la nota de la inscripción 145 (Reparación), 
    // solo se borre la nota de la 145, y no la de la 129 (Regular).
    $stmt = $pdo->prepare("
        DELETE FROM calificaciones 
        WHERE inscripcion_id = ?
    ");
    $stmt->execute([$inscripcion_actual]); 
    
    // Obtener información de la materia (ahora basado en el ID específico $inscripcion_actual)
    $stmt = $pdo->prepare("
        SELECT m.duracion, m.nombre as materia_nombre
        FROM inscripciones i
        JOIN secciones s ON i.seccion_id = s.id
        JOIN materias m ON s.materia_id = m.id
        WHERE i.id = ?
    ");
    // Usamos $inscripcion_actual para buscar la materia
    $stmt->execute([$inscripcion_actual]); 
    $materia = $stmt->fetch();
    
    if (!$materia) {
        throw new Exception('No se encontró la inscripción para obtener la información de la materia.');
    }
    
    // El período académico es texto libre, no necesita validación
    
    // Insertar calificación (la variable $inscripcion_actual ya contiene el ID correcto)
    
    // Determinar estatus correcto (Proyecto necesita ≥16, regulares ≥12)
    $es_proyecto = (strpos(strtolower($materia['materia_nombre']), 'proyecto socio tecnológico') !== false);
    if ($es_proyecto) {
        $nuevo_estatus = ($nota_numerica >= 16) ? 'Aprobada' : 'Reprobada';
        $estatus_mensaje = $nuevo_estatus;
    } else {
        $nuevo_estatus = ($nota_numerica >= 12) ? 'Aprobada' : 'Reprobada';
        $estatus_mensaje = $nuevo_estatus;
    }
    
    // Usar INSERT ... ON DUPLICATE KEY UPDATE para manejar inserción/actualización
    // NOTA: Esta lógica funciona porque la clave única debe ser (inscripcion_id, tipo_evaluacion).
    // Ya eliminamos la calificación anterior de la misma inscripción, así que es un INSERT seguro.
    $stmt_upsert = $pdo->prepare("
        INSERT INTO calificaciones (
            inscripcion_id, 
            nota_numerica, 
            tipo_evaluacion, 
            fecha_registro, 
            usuario_id, 
            periodo_academico,
            estado_materia,
            fecha_finalizacion
        ) VALUES (?, ?, ?, ?, ?, ?, 'Finalizada', ?)
        ON DUPLICATE KEY UPDATE 
            nota_numerica = VALUES(nota_numerica),
            estado_materia = 'Finalizada',
            fecha_finalizacion = VALUES(fecha_finalizacion)
    ");
    
    $stmt_upsert->execute([
        $inscripcion_actual,
        $nota_numerica,
        $tipo_evaluacion,
        $fecha_registro,
        $usuario_id,
        $periodo_academico,
        $fecha_registro
    ]);
    
    // Actualizar estatus solo de la inscripción actual (Reparación)
    $stmt_inscripcion = $pdo->prepare("UPDATE inscripciones SET estatus = ? WHERE id = ?");
    $stmt_inscripcion->execute([$nuevo_estatus, $inscripcion_actual]);
    
    $_SESSION['mensaje'] = [
        'tipo' => 'success',
        'texto' => "Nota final registrada exitosamente para {$materia['materia_nombre']}. Calificación: {$nota_numerica} - {$estatus_mensaje}"
    ];
    
} catch (Exception $e) {
    $_SESSION['mensaje'] = [
        'tipo' => 'error',
        'texto' => 'Error: ' . $e->getMessage()
    ];
}

$referer = $_SERVER['HTTP_REFERER'] ?? '../../vistas/calificaciones/cargarNotasFinal.php';
header('Location: ' . $referer);
exit;
?>