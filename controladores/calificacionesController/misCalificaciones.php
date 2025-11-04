<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';

verificarSesion();

$mis_calificaciones = [];
$resumen = [];
$error_message = '';

try {
    $conn = conectar();
    $usuario_id = $_SESSION['usuario_id'];
    
    // Obtener todas las calificaciones del estudiante
    $stmt = $conn->prepare("
        SELECT 
            c.id,
            c.nota_numerica,
            c.tipo_evaluacion,
            c.fecha_registro,
            m.nombre AS materia_nombre,
            m.codigo AS materia_codigo,
            p.nombre AS pnf_nombre,
            t.nombre AS trayecto_nombre,
            tr.nombre AS trimestre_nombre,
            i.estatus AS estatus_final,
            prof_u.nombre AS profesor_nombre,
            prof_u.apellido AS profesor_apellido
        FROM calificaciones c
        JOIN inscripciones i ON c.inscripcion_id = i.id
        JOIN estudiantes e ON i.estudiante_id = e.id
        JOIN oferta_materias om ON i.oferta_materia_id = om.id
        JOIN materias m ON om.materia_id = m.id
        JOIN oferta_academica oa ON om.oferta_academica_id = oa.id
        JOIN pnfs p ON oa.pnf_id = p.id
        JOIN trayectos t ON oa.trayecto_id = t.id
        JOIN trimestres tr ON oa.trimestre_id = tr.id
        LEFT JOIN oferta_materia_profesor omp ON om.id = omp.oferta_materia_id
        LEFT JOIN profesores prof ON omp.profesor_id = prof.id
        LEFT JOIN usuarios prof_u ON prof.usuario_id = prof_u.id
        WHERE e.usuario_id = ?
        ORDER BY c.fecha_registro DESC
    ");
    
    $stmt->execute([$usuario_id]);
    $mis_calificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular resumen - AVERAGE real por materia
    if (!empty($mis_calificaciones)) {
        // Agrupar notas por materia
        $materias_notas = [];
        foreach ($mis_calificaciones as $calificacion) {
            $materia_id = $calificacion['materia_codigo'];
            $materias_notas[$materia_id][] = $calificacion['nota_numerica'];
        }
        
        // Calcular promedio por materia
        $promedios_materias = [];
        $materias_aprobadas = 0;
        
        foreach ($materias_notas as $materia_id => $notas) {
            $promedio_materia = array_sum($notas) / count($notas);
            $promedios_materias[] = $promedio_materia;
            
            if ($promedio_materia >= 12) {
                $materias_aprobadas++;
            }
        }
        
        // Promedio general (AVERAGE real)
        $promedio_general = array_sum($promedios_materias) / count($promedios_materias);
        
        $resumen = [
            'total' => count($materias_notas), // Total de materias
            'aprobadas' => $materias_aprobadas, // Materias aprobadas
            'reprobadas' => count($materias_notas) - $materias_aprobadas,
            'promedio' => round($promedio_general, 2) // AVERAGE real
        ];
    }
    
} catch (Exception $e) {
    $error_message = 'Error al cargar calificaciones: ' . $e->getMessage();
}
?>