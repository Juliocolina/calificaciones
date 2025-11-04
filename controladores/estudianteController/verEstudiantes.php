<?php
// 📄 Incluir archivos necesarios
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';

verificarSesion();
$conn = conectar();
if (!$conn) {
    throw new Exception("Error de conexión a la base de datos.");
}

// Obtener aldea del coordinador si es coordinador
$aldea_coordinador = null;
if ($_SESSION['rol'] === 'coordinador') {
    $stmt_coord = $conn->prepare("SELECT aldea_id FROM coordinadores WHERE usuario_id = ?");
    $stmt_coord->execute([$_SESSION['usuario_id']]);
    $coord_data = $stmt_coord->fetch(PDO::FETCH_ASSOC);
    $aldea_coordinador = $coord_data['aldea_id'] ?? null;
}

try {
    // 🗄️ Obtener usuarios con rol estudiante (filtrados por aldea si es coordinador)
    $sql = "SELECT
            u.id AS usuario_id,
            u.cedula AS usuario_cedula,
            u.nombre AS usuario_nombre,
            u.apellido AS usuario_apellido,
            u.telefono AS usuario_telefono,
            u.correo AS usuario_correo,
            u.activo AS usuario_activo,
            e.id AS estudiante_id,
            e.fecha_nacimiento,
            e.codigo_estudiante,
            e.estado_academico,
            e.observaciones,
            e.fecha_ingreso,
            e.fecha_graduacion,
            e.parroquia,
            e.nacionalidad,
            e.genero,
            e.religion,
            e.etnia,
            e.discapacidad,
            e.nivel_estudio,
            e.institucion_procedencia,
            e.aldea_id,
            a.nombre AS nombre_aldea,
            e.pnf_id,
            p.nombre AS nombre_pnf,
            e.trayecto_id,
            t.nombre AS nombre_trayecto,
            e.trimestre_id,
            tr.nombre AS nombre_trimestre,
            -- Estado del perfil personal (completado por estudiante)
            CASE 
                WHEN e.id IS NULL THEN 'Sin Perfil'
                WHEN e.aldea_id IS NOT NULL AND e.parroquia IS NOT NULL AND e.institucion_procedencia IS NOT NULL 
                     AND e.nacionalidad IS NOT NULL AND e.genero IS NOT NULL AND e.religion IS NOT NULL 
                THEN 'Perfil Completo'
                ELSE 'Perfil Pendiente'
            END AS estado_perfil_personal,
            
            -- Estado de datos académicos (completado por admin) - fecha_graduacion y observaciones son opcionales
            CASE 
                WHEN e.id IS NULL THEN 'Sin Asignar'
                WHEN e.pnf_id IS NOT NULL AND e.trayecto_id IS NOT NULL AND e.trimestre_id IS NOT NULL 
                     AND e.codigo_estudiante IS NOT NULL AND e.estado_academico IS NOT NULL
                THEN 'Académico Completo'
                ELSE 'Académico Pendiente'
            END AS estado_academico,
            
            -- Estado general combinado - fecha_graduacion y observaciones son opcionales
            CASE 
                WHEN e.id IS NULL THEN 'Sin Perfil'
                WHEN (e.aldea_id IS NOT NULL AND e.parroquia IS NOT NULL AND e.institucion_procedencia IS NOT NULL 
                      AND e.nacionalidad IS NOT NULL AND e.genero IS NOT NULL AND e.religion IS NOT NULL)
                     AND (e.pnf_id IS NOT NULL AND e.trayecto_id IS NOT NULL AND e.trimestre_id IS NOT NULL 
                          AND e.codigo_estudiante IS NOT NULL AND e.estado_academico IS NOT NULL)
                THEN 'Totalmente Completo'
                ELSE 'Pendiente'
            END AS estado_general
        FROM usuarios u
        LEFT JOIN estudiantes e ON u.id = e.usuario_id
        LEFT JOIN aldeas a ON e.aldea_id = a.id
        LEFT JOIN pnfs p ON e.pnf_id = p.id
        LEFT JOIN trayectos t ON e.trayecto_id = t.id
        LEFT JOIN trimestres tr ON e.trimestre_id = tr.id
        WHERE u.rol = 'estudiante'";
    
    // Agregar filtro por aldea si es coordinador
    $params = [];
    if ($_SESSION['rol'] === 'coordinador' && $aldea_coordinador) {
        $sql .= " AND (e.aldea_id = ? OR e.aldea_id IS NULL)";
        $params[] = $aldea_coordinador;
    }
    
    $sql .= " ORDER BY u.apellido, u.nombre";
    
    $consulta = $conn->prepare($sql);
    $consulta->execute($params);
    $estudiantes = $consulta->fetchAll(PDO::FETCH_ASSOC);
    
    // Separar estudiantes según su estado general
    $estudiantes_completos = array_filter($estudiantes, function($est) {
        return $est['estado_general'] === 'Totalmente Completo';
    });
    
    $estudiantes_pendientes = array_filter($estudiantes, function($est) {
        return $est['estado_general'] !== 'Totalmente Completo';
    });

} catch (PDOException $e) {
    echo "Error en la base de datos: " . $e->getMessage();
    exit;
} catch (Exception $e) {
    // ❌ Capturar y manejar otros errores generales
    echo "Error: " . $e->getMessage();
    exit;
}
?>