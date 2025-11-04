<?php
require_once __DIR__ . '/../../config/conexion.php';

$oferta_id = 0;
$oferta_info = null;
$materias_oferta = [];
$estudiantes_inscritos = [];
$calificaciones_historial = []; // Array renombrado para reflejar que guarda el historial de notas
$error_message = '';

try {
    if (!isset($_GET['oferta_id']) || !is_numeric($_GET['oferta_id'])) {
        throw new Exception("ID de oferta no válido.");
    }
    $oferta_id = intval($_GET['oferta_id']);
    $conn = conectar();
    
    // Verificar permisos de acceso para coordinadores
    if (isset($_SESSION['usuario'])) {
        $usuario_actual = $_SESSION['usuario'];
        if ($usuario_actual['rol'] === 'coordinador') {
            $stmt_coord = $conn->prepare("SELECT aldea_id FROM coordinadores WHERE usuario_id = ?");
            $stmt_coord->execute([$usuario_actual['id']]);
            $coord_data = $stmt_coord->fetch(PDO::FETCH_ASSOC);
            $aldea_coordinador = $coord_data['aldea_id'] ?? null;
            
            // Verificar que la oferta pertenece a la aldea del coordinador
            $stmt_check = $conn->prepare("SELECT aldea_id FROM oferta_academica WHERE id = ?");
            $stmt_check->execute([$oferta_id]);
            $oferta_aldea = $stmt_check->fetch(PDO::FETCH_ASSOC);
            
            if ($oferta_aldea && $oferta_aldea['aldea_id'] != $aldea_coordinador) {
                throw new Exception("No tiene permisos para acceder a esta oferta.");
            }
        }
    }

    // 1. Obtener información de la oferta
    $stmt_oferta = $conn->prepare("
        SELECT p.nombre AS pnf, t.nombre AS trayecto, tr.nombre AS trimestre, oa.aldea_id
        FROM oferta_academica oa
        JOIN pnfs p ON oa.pnf_id = p.id
        JOIN trayectos t ON oa.trayecto_id = t.id
        JOIN trimestres tr ON oa.trimestre_id = tr.id
        WHERE oa.id = ? AND oa.estatus = 'Abierto'
    ");
    $stmt_oferta->execute([$oferta_id]);
    $oferta_info = $stmt_oferta->fetch(PDO::FETCH_ASSOC);

    if (!$oferta_info) {
        throw new Exception("Oferta no encontrada o no está abierta.");
    }

    // 2. Obtener las materias de la oferta con su duración
    $stmt_materias = $conn->prepare("
        SELECT om.id AS oferta_materia_id, m.nombre AS nombre_materia, om.materia_id, m.duracion
        FROM oferta_materias om
        JOIN materias m ON om.materia_id = m.id
        WHERE om.oferta_academica_id = ?
        ORDER BY m.nombre
    ");
    $stmt_materias->execute([$oferta_id]);
    $materias_oferta = $stmt_materias->fetchAll(PDO::FETCH_ASSOC);

    // 3. Obtener TODOS los estudiantes que tienen al menos una inscripción en esta oferta
    $stmt_estudiantes = $conn->prepare(
        "SELECT DISTINCT e.id AS estudiante_id, u.cedula AS cedula, u.nombre AS nombre, u.apellido AS apellido
         FROM estudiantes e
         JOIN usuarios u ON e.usuario_id = u.id
         JOIN inscripciones i ON e.id = i.estudiante_id
         JOIN oferta_materias om ON i.oferta_materia_id = om.id
         WHERE om.oferta_academica_id = ?
         ORDER BY u.apellido, u.nombre"
    );
    $stmt_estudiantes->execute([$oferta_id]); 
    $estudiantes_inscritos = $stmt_estudiantes->fetchAll(PDO::FETCH_ASSOC);

    // 4. Obtener inscripciones y calificaciones considerando la duración de las materias
    $sql_calificaciones_historial = "
        SELECT 
            i.id AS inscripcion_id_actual,
            i.estudiante_id,
            i.oferta_materia_id,
            i.estatus AS estatus_inscripcion_actual,
            om.materia_id,
            m.duracion,
            -- Subconsulta para obtener todas las notas de esta materia para este estudiante
            (
                SELECT GROUP_CONCAT(c.nota_numerica ORDER BY c.fecha_registro SEPARATOR ',')
                FROM calificaciones c
                JOIN inscripciones i2 ON c.inscripcion_id = i2.id
                JOIN oferta_materias om2 ON i2.oferta_materia_id = om2.id
                WHERE i2.estudiante_id = i.estudiante_id AND om2.materia_id = om.materia_id
            ) AS notas_historicas,
            -- Subconsulta para contar cuántas notas tiene
            (
                SELECT COUNT(c.nota_numerica)
                FROM calificaciones c
                JOIN inscripciones i2 ON c.inscripcion_id = i2.id
                JOIN oferta_materias om2 ON i2.oferta_materia_id = om2.id
                WHERE i2.estudiante_id = i.estudiante_id AND om2.materia_id = om.materia_id
            ) AS total_notas
        FROM inscripciones i
        JOIN oferta_materias om ON i.oferta_materia_id = om.id
        JOIN materias m ON om.materia_id = m.id
        WHERE om.oferta_academica_id = ?
    ";
    
    $stmt_historial = $conn->prepare($sql_calificaciones_historial);
    $stmt_historial->execute([$oferta_id]);

    foreach ($stmt_historial->fetchAll(PDO::FETCH_ASSOC) as $registro) {
        $notas_array = !empty($registro['notas_historicas']) ? explode(',', $registro['notas_historicas']) : [];
        $duracion = $registro['duracion'];
        $total_notas = intval($registro['total_notas']);
        
        // Determinar cuántas notas necesita según la duración
        $notas_requeridas = 1; // Por defecto trimestral
        if ($duracion === 'semestral') $notas_requeridas = 2;
        elseif ($duracion === 'anual') $notas_requeridas = 3;
        
        // Calcular estatus según las notas y duración
        $estatus_calculado = $registro['estatus_inscripcion_actual'];
        if ($total_notas >= $notas_requeridas) {
            $promedio = array_sum($notas_array) / count($notas_array);
            $estatus_calculado = $promedio >= 12 ? 'Aprobada' : 'Reprobada';
        }
        
        $calificaciones_historial[$registro['estudiante_id']][$registro['oferta_materia_id']] = [
            'estatus' => $estatus_calculado,
            'inscripcion_id' => $registro['inscripcion_id_actual'],
            'notas_historicas' => $notas_array,
            'total_notas' => $total_notas,
            'notas_requeridas' => $notas_requeridas,
            'duracion' => $duracion,
            'materia_id' => $registro['materia_id']
        ];
    }

} catch (Exception $e) {
    $error_message = $e->getMessage();
}

// =======================================================
// ESTE SCRIPT ESTÁ LISTO PARA ALIMENTAR LA VISTA DE LA TABLA
// Ahora la variable $calificaciones_historial contiene la nota máxima
// y el estatus actual de cada materia/estudiante.
// =======================================================

// AHORA DEBEMOS CONTINUAR CON EL CONTROLADOR DE ACCIÓN (POST)
// DEBUG: Última actualización - <?= date('Y-m-d H:i:s') ?>