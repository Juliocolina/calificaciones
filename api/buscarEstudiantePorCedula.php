<?php
require_once __DIR__ . '/../config/conexion.php';

header('Content-Type: application/json');

if (!isset($_GET['cedula']) || empty($_GET['cedula'])) {
    echo json_encode(['success' => false, 'message' => 'Cédula requerida']);
    exit;
}

$cedula = trim($_GET['cedula']);

try {
    $conn = conectar();
    
    // Buscar estudiante con todos sus datos
    $stmt = $conn->prepare("
        SELECT 
            e.id,
            u.nombre,
            u.apellido,
            u.cedula,
            e.estado_academico,
            p.nombre as pnf_nombre,
            e.pnf_id,
            t.slug as trayecto_nombre,
            e.trayecto_id,
            tr.nombre as trimestre_nombre,
            e.trimestre_id,
            a.nombre as aldea_nombre
        FROM estudiantes e
        JOIN usuarios u ON e.usuario_id = u.id
        LEFT JOIN pnfs p ON e.pnf_id = p.id
        LEFT JOIN trayectos t ON e.trayecto_id = t.id
        LEFT JOIN trimestres tr ON e.trimestre_id = tr.id
        LEFT JOIN aldeas a ON e.aldea_id = a.id
        WHERE u.cedula = ? AND e.estado_academico = 'activo'
    ");
    
    $stmt->execute([$cedula]);
    $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$estudiante) {
        echo json_encode(['success' => false, 'message' => 'Estudiante no encontrado']);
        exit;
    }
    
    if (!$estudiante['pnf_id']) {
        echo json_encode(['success' => false, 'message' => 'El estudiante no tiene PNF asignado']);
        exit;
    }
    
    // Obtener materias realmente pendientes (reprobadas que NO han sido aprobadas después)
    $stmt_pendientes = $conn->prepare("
        SELECT DISTINCT 
            m.nombre as materia_nombre, 
            MIN(c.nota_numerica) as nota_numerica
        FROM inscripciones i
        JOIN secciones s ON i.seccion_id = s.id
        JOIN materias m ON s.materia_id = m.id
        JOIN calificaciones c ON i.id = c.inscripcion_id
        WHERE i.estudiante_id = ?
          AND m.id NOT IN (
              -- Excluir materias que ya fueron aprobadas
              SELECT DISTINCT m2.id
              FROM inscripciones i2
              JOIN secciones s2 ON i2.seccion_id = s2.id
              JOIN materias m2 ON s2.materia_id = m2.id
              JOIN calificaciones c2 ON i2.id = c2.inscripcion_id
              WHERE i2.estudiante_id = ?
                AND (
                    (m2.nombre LIKE '%proyecto socio tecnológico%' AND c2.nota_numerica >= 16) OR
                    (m2.nombre NOT LIKE '%proyecto socio tecnológico%' AND c2.nota_numerica >= 12)
                )
          )
          AND (
              (m.nombre LIKE '%proyecto socio tecnológico%' AND c.nota_numerica < 16) OR
              (m.nombre NOT LIKE '%proyecto socio tecnológico%' AND c.nota_numerica < 12)
          )
        GROUP BY m.id, m.nombre
    ");
    $stmt_pendientes->execute([$estudiante['id'], $estudiante['id']]);
    $materias_reprobadas = $stmt_pendientes->fetchAll(PDO::FETCH_ASSOC);
    
    $materias_pendientes = count($materias_reprobadas);
    $puede_inscribirse = $materias_pendientes <= 3; // Límite de 3 materias pendientes
    
    $estudiante['materias_pendientes'] = $materias_pendientes;
    $estudiante['materias_reprobadas'] = $materias_reprobadas;
    $estudiante['puede_inscribirse'] = $puede_inscribirse;
    
    echo json_encode([
        'success' => true,
        'estudiante' => $estudiante
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos']);
}
?>