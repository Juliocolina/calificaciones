<?php
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/grupos_helper.php';

verificarRol(['admin', 'coordinador']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../vistas/inscripciones/gestionarInscripciones.php");
    exit;
}

$estudiante_id = intval($_POST['estudiante_id'] ?? 0);
$secciones = $_POST['secciones'] ?? [];

if ($estudiante_id <= 0 || empty($secciones)) {
    header("Location: ../../vistas/inscripciones/inscribirEnSecciones.php?error=datos_incompletos");
    exit;
}

$conn = conectar();

try {
    $conn->beginTransaction();
    
    $inscripciones_exitosas = 0;
    $errores = [];
    
    foreach ($secciones as $seccion_id) {
        $seccion_id = intval($seccion_id);
        
        // Verificar que la sección existe y tiene cupo
        $stmt = $conn->prepare("
            SELECT s.cupo_maximo, COUNT(DISTINCT i.estudiante_id) as inscritos
            FROM secciones s
            LEFT JOIN inscripciones i ON s.id = i.seccion_id AND i.estatus IN ('Cursando', 'Aprobada', 'Reprobada')
            WHERE s.id = ?
            GROUP BY s.id, s.cupo_maximo
        ");
        $stmt->execute([$seccion_id]);
        $seccion = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$seccion) {
            $errores[] = "Sección ID {$seccion_id} no existe";
            continue;
        }
        
        if ($seccion['inscritos'] >= $seccion['cupo_maximo']) {
            $errores[] = "Sección ID {$seccion_id} no tiene cupo disponible";
            continue;
        }
        
        // Obtener tipo de oferta de la sección
        $stmt = $conn->prepare("
            SELECT oa.tipo_oferta, s.materia_id
            FROM secciones s
            JOIN oferta_academica oa ON s.oferta_academica_id = oa.id
            WHERE s.id = ?
        ");
        $stmt->execute([$seccion_id]);
        $oferta_info = $stmt->fetch();
        
        if (!$oferta_info) {
            $errores[] = "No se pudo obtener información de la oferta para sección ID {$seccion_id}";
            continue;
        }
        
        // Verificar historial de la materia
        $stmt = $conn->prepare("
            SELECT c.nota_numerica, i.estatus
            FROM inscripciones i
            JOIN secciones s ON i.seccion_id = s.id
            LEFT JOIN calificaciones c ON i.id = c.inscripcion_id
            WHERE i.estudiante_id = ? AND s.materia_id = ?
            ORDER BY i.id DESC
            LIMIT 1
        ");
        $stmt->execute([$estudiante_id, $oferta_info['materia_id']]);
        $historial = $stmt->fetch();
        $nota_maxima = $historial['nota_numerica'] ?? 0;
        $ultimo_estatus = $historial['estatus'] ?? null;
        
        // Obtener nombre de la materia para validación específica
        $stmt = $conn->prepare("SELECT nombre FROM materias WHERE id = ?");
        $stmt->execute([$oferta_info['materia_id']]);
        $materia_nombre = $stmt->fetchColumn();
        
        // Determinar límite de aprobación según tipo de materia
        $es_proyecto = (strpos(strtolower($materia_nombre), 'proyecto socio tecnológico') !== false);
        $limite_aprobacion = $es_proyecto ? 16 : 12;
        
        // Validaciones según tipo de oferta
        if ($oferta_info['tipo_oferta'] === 'reparacion') {
            // Para reparación: debe haber cursado antes y estar reprobada
            if (!$historial) {
                $errores[] = "No puede inscribirse en reparación sin haber cursado la materia antes";
                continue;
            }
            if ($nota_maxima >= $limite_aprobacion) {
                $mensaje_aprobacion = $es_proyecto ? "(Proyecto requiere ≥16)" : "";
                $errores[] = "No puede inscribirse en reparación, ya aprobó la materia con nota {$nota_maxima} {$mensaje_aprobacion}";
                continue;
            }
            if ($ultimo_estatus !== 'Reprobada') {
                $errores[] = "No puede inscribirse en reparación, la materia no está en estatus 'Reprobada'";
                continue;
            }
        } else {
            // Para ofertas regulares: no debe haber aprobado
            if ($nota_maxima >= $limite_aprobacion) {
                $mensaje_aprobacion = $es_proyecto ? "(Proyecto requiere ≥16)" : "";
                $errores[] = "Estudiante ya aprobó esta materia con nota {$nota_maxima} {$mensaje_aprobacion}";
                continue;
            }
        }
        
        // Verificar que no esté inscrito actualmente en esta sección específica
        $stmt = $conn->prepare("
            SELECT id FROM inscripciones 
            WHERE estudiante_id = ? AND seccion_id = ? AND estatus = 'Cursando'
        ");
        $stmt->execute([$estudiante_id, $seccion_id]);
        
        if ($stmt->fetch()) {
            $errores[] = "Estudiante ya está inscrito en esta sección específica";
            continue;
        }
        
        // Obtener información de la materia de la sección
        $stmt_materia = $conn->prepare("
            SELECT s.materia_id, m.duracion 
            FROM secciones s 
            JOIN materias m ON s.materia_id = m.id 
            WHERE s.id = ?
        ");
        $stmt_materia->execute([$seccion_id]);
        $materia_info = $stmt_materia->fetch(PDO::FETCH_ASSOC);
        
        if (!$materia_info) {
            $errores[] = "No se pudo obtener información de la materia para sección ID {$seccion_id}";
            continue;
        }
        
        // Obtener o crear grupo para la materia
        $grupo = obtenerOCrearGrupo($materia_info['materia_id']);
        
        // Inscribir al estudiante con grupo
        $stmt = $conn->prepare("
            INSERT INTO inscripciones (estudiante_id, seccion_id, estatus, grupo_id) 
            VALUES (?, ?, 'Cursando', ?)
        ");
        $stmt->execute([$estudiante_id, $seccion_id, $grupo['id']]);
        $inscripcion_id = $conn->lastInsertId();
        
        // Calcular número de intento para esta materia
        $stmt = $conn->prepare("
            SELECT COUNT(*) + 1 as siguiente_intento
            FROM historial_intentos 
            WHERE estudiante_id = ? AND materia_id = ?
        ");
        $stmt->execute([$estudiante_id, $materia_info['materia_id']]);
        $intento_numero = $stmt->fetch()['siguiente_intento'];
        
        // Obtener tipo de oferta
        $stmt = $conn->prepare("
            SELECT oa.tipo_oferta
            FROM secciones s
            JOIN oferta_academica oa ON s.oferta_academica_id = oa.id
            WHERE s.id = ?
        ");
        $stmt->execute([$seccion_id]);
        $tipo_oferta = $stmt->fetch()['tipo_oferta'];
        
        // Registrar en historial de intentos
        $stmt = $conn->prepare("
            INSERT INTO historial_intentos (estudiante_id, materia_id, inscripcion_id, intento_numero, tipo_oferta, fecha_intento)
            VALUES (?, ?, ?, ?, ?, CURDATE())
        ");
        $stmt->execute([$estudiante_id, $materia_info['materia_id'], $inscripcion_id, $intento_numero, $tipo_oferta]);
        
        $inscripciones_exitosas++;
    }
    
    $conn->commit();
    
    if ($inscripciones_exitosas > 0) {
        $mensaje = "Estudiante inscrito exitosamente en {$inscripciones_exitosas} sección(es)";
        if (!empty($errores)) {
            $mensaje .= ". Algunos errores: " . implode(", ", $errores);
        }
        header("Location: ../../vistas/inscripciones/gestionarInscripciones.php?success=" . urlencode($mensaje));
    } else {
        header("Location: ../../vistas/inscripciones/inscribirEnSecciones.php?error=" . urlencode("No se pudo inscribir en ninguna sección: " . implode(", ", $errores)));
    }
    
} catch (PDOException $e) {
    $conn->rollback();
    error_log("Error en inscripción: " . $e->getMessage());
    error_log("SQL State: " . $e->getCode());
    error_log("Estudiante ID: " . $estudiante_id);
    error_log("Secciones: " . print_r($secciones, true));
    
    // Mostrar error detallado en desarrollo
    $error_detalle = "Error de base de datos: " . $e->getMessage();
    header("Location: ../../vistas/inscripciones/inscribirEnSecciones.php?error=" . urlencode($error_detalle));
}
?>