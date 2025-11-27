<?php
require_once 'conexion.php';

/**
 * Obtiene o crea un grupo para una materia en un año académico específico
 */
function obtenerOCrearGrupo($materia_id, $año_academico = null) {
    $conn = conectar();
    
    if (!$año_academico) {
        $año_academico = date('Y');
    }
    
    // Buscar grupo existente
    $consulta = $conn->prepare("
        SELECT id, grupo_codigo 
        FROM materia_grupos 
        WHERE materia_id = ? AND año_academico = ?
        ORDER BY id DESC LIMIT 1
    ");
    $consulta->execute([$materia_id, $año_academico]);
    $grupo = $consulta->fetch();
    
    if ($grupo) {
        return $grupo;
    }
    
    // Crear nuevo grupo
    $grupo_codigo = generarCodigoGrupo($materia_id, $año_academico);
    
    $insertar = $conn->prepare("
        INSERT INTO materia_grupos (materia_id, grupo_codigo, año_academico) 
        VALUES (?, ?, ?)
    ");
    $insertar->execute([$materia_id, $grupo_codigo, $año_academico]);
    
    return [
        'id' => $conn->lastInsertId(),
        'grupo_codigo' => $grupo_codigo
    ];
}

/**
 * Genera un código único para el grupo
 */
function generarCodigoGrupo($materia_id, $año_academico) {
    $conn = conectar();
    
    // Generar código simple: M{ID}-{AÑO}
    $año_corto = substr($año_academico, -2); // Últimos 2 dígitos del año
    $codigo_base = "M{$materia_id}-{$año_corto}";
    
    // Verificar si ya existe y agregar sufijo si es necesario
    $contador = 1;
    $codigo_final = $codigo_base;
    
    while (true) {
        $verificar = $conn->prepare("SELECT id FROM materia_grupos WHERE grupo_codigo = ?");
        $verificar->execute([$codigo_final]);
        
        if (!$verificar->fetch()) {
            break;
        }
        
        $contador++;
        $codigo_final = $codigo_base . "-{$contador}";
    }
    
    return $codigo_final;
}

/**
 * Obtiene los trimestres que debe cursar una materia según su tipo de duración
 */
function obtenerTrimestresPorTipo($duracion) {
    switch ($duracion) {
        case 'trimestral':
            return [1]; // Solo un trimestre
        case 'bimestral':
            return [1, 2]; // Dos trimestres
        case 'anual':
            return [1, 2, 3]; // Tres trimestres
        default:
            return [1];
    }
}

/**
 * Verifica si una materia está completa para un estudiante
 */
function materiaCompleta($estudiante_id, $materia_id, $grupo_id) {
    $conn = conectar();
    
    // Obtener tipo de duración de la materia
    $consulta = $conn->prepare("SELECT duracion FROM materias WHERE id = ?");
    $consulta->execute([$materia_id]);
    $materia = $consulta->fetch();
    
    if (!$materia) return false;
    
    $trimestres_requeridos = obtenerTrimestresPorTipo($materia['duracion']);
    
    // Verificar que tenga calificaciones en todos los trimestres requeridos
    $consulta_notas = $conn->prepare("
        SELECT DISTINCT trimestre_numero 
        FROM calificaciones c
        JOIN inscripciones i ON c.inscripcion_id = i.id
        WHERE i.estudiante_id = ? AND i.grupo_id = ?
    ");
    $consulta_notas->execute([$estudiante_id, $grupo_id]);
    $trimestres_cursados = $consulta_notas->fetchAll(PDO::FETCH_COLUMN);
    
    return count(array_intersect($trimestres_requeridos, $trimestres_cursados)) === count($trimestres_requeridos);
}
?>