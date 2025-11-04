<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php'; // Asumo que redirigir está aquí

$conn = conectar();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('error', 'Acceso no permitido.', 'ofertas_academicas/verOfertas.php');
    exit;
}

// --- OBTENER DATOS Y PREPARAR REDIRECCIÓN ---
$oferta_id = isset($_POST['oferta_id']) ? intval($_POST['oferta_id']) : 0;
// NOTA: La estructura del POST de la vista corregida DEBE ser: $calificaciones[inscripcion_id][nota], $calificaciones[inscripcion_id][tipo]
$calificaciones_data = isset($_POST['calificaciones']) && is_array($_POST['calificaciones']) ? $_POST['calificaciones'] : [];
$redirect_url = "calificaciones/registrarCalificacion.php?oferta_id=" . $oferta_id;

if ($oferta_id <= 0) {
    redirigir('error', 'ID de oferta no válido.', 'ofertas_academicas/verOfertas.php');
    exit;
}

if (empty($calificaciones_data)) {
    redirigir('error', 'No se recibieron calificaciones para procesar.', $redirect_url);
    exit;
}

// --- PROCESAR CALIFICACIONES (LÓGICA DE RETOMA) ---
try {
    $conn->beginTransaction();
    $estatus_actualizados_count = 0;
    
    // Iteramos sobre CADA REGISTRO que vino del POST
    foreach ($calificaciones_data as $inscripcion_id => $datos) {
        $inscripcion_id = intval($inscripcion_id);
        $nota_numerica = (float)($datos['nota'] ?? 0);
        // NOTA: La vista que corregimos ya no envía estatus, sino que DEBE enviar el tipo_evaluacion
        // Si usamos la vista corregida, el tipo debería ser enviado. Usaremos 'Ordinaria' como fallback.
        $tipo_evaluacion = $datos['tipo_evaluacion'] ?? 'Ordinaria'; 

        // 1. OBTENER METADATOS: Estudiante ID y Materia ID
        $sql_get_ids = "
            SELECT 
                i.estudiante_id, 
                om.materia_id
            FROM inscripciones i
            JOIN oferta_materias om ON i.oferta_materia_id = om.id
            WHERE i.id = ?
        ";
        $stmt_get_ids = $conn->prepare($sql_get_ids);
        $stmt_get_ids->execute([$inscripcion_id]);
        $ids = $stmt_get_ids->fetch(PDO::FETCH_ASSOC);

        if (!$ids) {
            // Si una inscripción no se encuentra, saltamos a la siguiente sin revertir
            continue; 
        }
        $estudiante_id = $ids['estudiante_id'];
        $materia_id = $ids['materia_id'];


        // 2. REGISTRAR LA NUEVA NOTA en la tabla 'calificaciones' (HISTORIAL)
        $sql_insert_calif = "
            INSERT INTO calificaciones 
                (inscripcion_id, nota_numerica, tipo_evaluacion, fecha_registro) 
            VALUES 
                (?, ?, ?, NOW())
        ";
        $stmt_insert = $conn->prepare($sql_insert_calif);
        $stmt_insert->execute([$inscripcion_id, $nota_numerica, $tipo_evaluacion]);


        // 3. LÓGICA DE RETOMA: DETERMINAR LA NOTA MÁXIMA HISTÓRICA
        // Busca la mejor nota en CUALQUIERA de las inscripciones (retomas) para esa materia y estudiante.
        $sql_max_nota = "
            SELECT
                MAX(c.nota_numerica) AS nota_final_max
            FROM
                calificaciones c
            JOIN
                inscripciones i ON c.inscripcion_id = i.id
            JOIN
                oferta_materias om ON i.oferta_materia_id = om.id
            WHERE
                i.estudiante_id = :estudiante_id AND om.materia_id = :materia_id
        ";
        $stmt_max_nota = $conn->prepare($sql_max_nota);
        $stmt_max_nota->execute(['estudiante_id' => $estudiante_id, 'materia_id' => $materia_id]);
        $resultado_max = $stmt_max_nota->fetch(PDO::FETCH_ASSOC);

        $nota_final_max = (float)$resultado_max['nota_final_max'];
        $nuevo_estatus = ($nota_final_max >= 12.00) ? 'Aprobada' : 'Reprobada';


        // 4. ACTUALIZAR ESTATUS DE TODAS LAS INSCRIPCIONES RELACIONADAS (CRÍTICO)
        // Esto asegura que si el estudiante aprueba en la retoma, todas sus inscripciones pasadas cambien a 'Aprobada'.
        $sql_update_estatus = "
            UPDATE inscripciones i
            JOIN oferta_materias om ON i.oferta_materia_id = om.id
            SET i.estatus = ?
            WHERE i.estudiante_id = ? AND om.materia_id = ?
        ";
        $stmt_update = $conn->prepare($sql_update_estatus);
        $stmt_update->execute([$nuevo_estatus, $estudiante_id, $materia_id]);
        
        $estatus_actualizados_count++;
    }

    // 5. Confirmar transacción
    $conn->commit();
    // Redirección según rol
    if ($_SESSION['rol'] === 'profesor') {
        redirigir('exito', "{$estatus_actualizados_count} calificaciones registradas. Estatus finales actualizados.", 'inscripciones/misEstudiantesInscritos.php');
    } else {
        redirigir('exito', "{$estatus_actualizados_count} calificaciones registradas. Estatus finales actualizados.", $redirect_url);
    }

} catch (Exception $e) {
    // 6. Si algo falló, revertir TODOS los cambios
    if ($conn->inTransaction()) { 
        $conn->rollBack(); 
    }
    redirigir('error', 'Error al guardar las calificaciones: ' . $e->getMessage(), $redirect_url);
}

exit;