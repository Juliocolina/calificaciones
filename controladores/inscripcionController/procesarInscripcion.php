<?php
// Incluir archivos de configuración y utilidades
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php'; // Asumo que redirigir() está aquí

$conn = conectar();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('error', 'Acceso no permitido.', 'ofertas_academicas/verOfertas.php');
    exit;
}

// --- DATOS COMUNES Y REDIRECCIÓN ---
$oferta_id = isset($_POST['oferta_id']) ? intval($_POST['oferta_id']) : 0;
$accion = isset($_POST['accion']) ? $_POST['accion'] : '';
// Vista destino dentro de la carpeta `vistas/` para usar con redirigir()
$redirect_url = "inscripciones/inscribirEstudiantes.php?oferta_id=" . $oferta_id;

if ($oferta_id <= 0) {
    redirigir('error', 'ID de oferta no válido.', 'ofertas_academicas/verOfertas.php');
    exit;
}

// =======================================================
// --- FUNCIONES HELPER PARA EL HISTORIAL ACADÉMICO ---
// Estas funciones se basan en la nueva tabla 'calificaciones'.
// =======================================================

/**
 * Obtiene el estado final (Aprobada/Reprobada) de una materia. Asume nota mínima 12.
 * @param PDO $conn Conexión a la base de datos.
 * @param int $estudianteId ID del estudiante.
 * @param int $materiaId ID de la materia.
 * @return string|null 'Aprobada', 'Reprobada', o null si nunca se ha cursado/calificado.
 */
function obtenerEstatusFinalMateria(PDO $conn, $estudianteId, $materiaId) {
    $sql = "
        SELECT
            MAX(c.nota_numerica) AS nota_final_max
        FROM
            inscripciones i
        JOIN
            calificaciones c ON i.id = c.inscripcion_id
        JOIN
            oferta_materias om ON i.oferta_materia_id = om.id
        WHERE
            i.estudiante_id = :estudianteId AND om.materia_id = :materiaId
        GROUP BY
            om.materia_id
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute(['estudianteId' => $estudianteId, 'materiaId' => $materiaId]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$resultado) {
        return null; // Nunca ha cursado/calificado
    }

    $nota_final = (float)$resultado['nota_final_max'];
    return ($nota_final >= 12.00) ? 'Aprobada' : 'Reprobada';
}

/**
 * Cuenta las materias reprobadas que debe el estudiante.
 * @param PDO $conn Conexión a la base de datos.
 * @param int $estudianteId ID del estudiante.
 * @return int Número total de materias reprobadas pendientes.
 */
function contarMateriasReprobadas(PDO $conn, $estudianteId) {
    // Consulta la mejor nota de cada materia cursada y cuenta aquellas con nota < 12
    $sql = "
        SELECT
            COUNT(t1.materia_id) AS total_reprobadas
        FROM
        (
            SELECT
                om.materia_id,
                MAX(c.nota_numerica) AS nota_final_max
            FROM
                inscripciones i
            JOIN
                calificaciones c ON i.id = c.inscripcion_id
            JOIN
                oferta_materias om ON i.oferta_materia_id = om.id
            WHERE
                i.estudiante_id = :estudianteId
            GROUP BY
                om.materia_id
            HAVING
                nota_final_max < 12.00
        ) AS t1;
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute(['estudianteId' => $estudianteId]);
    return (int)$stmt->fetchColumn();
}


// =======================================================
// --- LÓGICA PARA INSCRIBIR A UN ESTUDIANTE (ACCIÓN 'inscribir') ---
// =======================================================
if ($accion === 'inscribir') {
    $cedula = isset($_POST['cedula']) ? trim($_POST['cedula']) : '';
    $LIMITE_ARRRASTRE = 2; // **REGLA DE NEGOCIO:** Límite máximo de arrastre

    if (empty($cedula)) {
        redirigir('error', 'Debe proporcionar una cédula.', $redirect_url);
        exit;
    }

    try {
        // 1. Búsqueda de IDs (Usuario y Estudiante)
        $stmt_user = $conn->prepare("SELECT id FROM usuarios WHERE cedula = ?");
        $stmt_user->execute([$cedula]);
        $usuario = $stmt_user->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            redirigir('error', "No se encontró un usuario con la cédula {$cedula}.", $redirect_url);
            exit;
        }
        $usuario_id = $usuario['id'];

        $stmt_est = $conn->prepare("SELECT id FROM estudiantes WHERE usuario_id = ?");
        $stmt_est->execute([$usuario_id]);
        $estudiante = $stmt_est->fetch(PDO::FETCH_ASSOC);

        if (!$estudiante) {
            redirigir('error', "El usuario con cédula {$cedula} no está registrado como estudiante.", $redirect_url);
            exit;
        }
        $estudiante_id = $estudiante['id'];
        
        // ------------------------------------------------------------------
        // A. VALIDACIÓN CRÍTICA: LÍMITE DE ARRASTRE
        // ------------------------------------------------------------------
        $total_reprobadas = contarMateriasReprobadas($conn, $estudiante_id);

        if ($total_reprobadas > $LIMITE_ARRRASTRE) {
            redirigir('error', "El estudiante tiene {$total_reprobadas} materias reprobadas y no puede inscribir esta oferta (Límite: {$LIMITE_ARRRASTRE}).", $redirect_url);
            exit;
        }
        
        // ------------------------------------------------------------------
        // B. OBTENER MATERIAS Y PROCESAR INSCRIPCIÓN
        // ------------------------------------------------------------------

        // Obtener la lista de oferta_materias_id y materia_id para la oferta
        $stmt_materias = $conn->prepare("
            SELECT om.id AS oferta_materia_id, om.materia_id 
            FROM oferta_materias om 
            WHERE om.oferta_academica_id = ?
        ");
        $stmt_materias->execute([$oferta_id]);
        $materias_oferta = $stmt_materias->fetchAll(PDO::FETCH_ASSOC);

        if (empty($materias_oferta)) {
            redirigir('error', 'Esta oferta no tiene materias para inscribir.', $redirect_url);
            exit;
        }

        // Iniciar una transacción
        $conn->beginTransaction();

        $sql_insert = "
            INSERT INTO inscripciones (estudiante_id, oferta_materia_id, estatus) 
            VALUES (?, ?, 'Cursando')";
        $stmt_insert = $conn->prepare($sql_insert);
        $inscripciones_exitosas = 0;

        foreach ($materias_oferta as $materia_data) {
            $oferta_materia_id = $materia_data['oferta_materia_id'];
            $materia_id = $materia_data['materia_id'];

            // **Validación considerando duración de materias:**
            // Obtener la duración de la materia
            $stmt_duracion = $conn->prepare("SELECT duracion FROM materias WHERE id = ?");
            $stmt_duracion->execute([$materia_id]);
            $materia_info = $stmt_duracion->fetch(PDO::FETCH_ASSOC);
            $duracion = $materia_info['duracion'] ?? 'trimestral';
            
            // Solo omitir materias trimestrales ya aprobadas
            // Las materias anuales/semestrales deben cursarse en múltiples trimestres
            if ($duracion === 'trimestral') {
                $estatus_actual = obtenerEstatusFinalMateria($conn, $estudiante_id, $materia_id);
                if ($estatus_actual === 'Aprobada') {
                    continue; // Omitir solo materias trimestrales ya aprobadas
                }
            }
            
            // Aquí se debería añadir la validación de PRERREQUISITOS, pero requiere una tabla específica.
            // Por ahora, solo se valida el límite de arrastre general.

            try {
                $stmt_insert->execute([$estudiante_id, $oferta_materia_id]);
                $inscripciones_exitosas++;
            } catch (PDOException $e) {
                $sqlState = $e->errorInfo[1] ?? null;
                if ($sqlState == 1062) {
                    // Duplicado: ya está inscrito. Es una retoma que ya se registró. Lo ignoramos.
                    continue;
                }
                throw $e; // Otro error: revertimos la transacción
            }
        }

        // Si no se inscribió en nada, puede ser un error o que ya aprobó todo.
        if ($inscripciones_exitosas === 0) {
            $conn->rollBack();
            redirigir('aviso', 'El estudiante ya tiene todas las materias de esta oferta aprobadas o ya está inscrito.', $redirect_url);
            exit;
        }

        // Si todo salió bien, confirmamos la transacción
        $conn->commit();
        redirigir('exito', "Estudiante inscrito en {$inscripciones_exitosas} materias de la oferta correctamente.", $redirect_url);

    } catch (Exception $e) {
        // Si la transacción aún está activa, revertirla
        if ($conn->inTransaction()) { $conn->rollBack(); }
        redirigir('error', 'Error al inscribir al estudiante: ' . $e->getMessage(), $redirect_url);
    }
}
// =======================================================
// --- LÓGICA PARA RETIRAR A UN ESTUDIANTE (ACCIÓN 'retirar') ---
// Esta parte no cambia.
// =======================================================
elseif ($accion === 'retirar') {
    $estudiante_id = isset($_POST['estudiante_id']) ? intval($_POST['estudiante_id']) : 0;
    if ($estudiante_id <= 0) {
        redirigir('error', 'ID de estudiante no válido.', $redirect_url);
        exit;
    }

    try {
        // Usamos un DELETE con JOIN para borrar todas las inscripciones
        // de un estudiante que pertenezcan a la oferta actual.
        // NOTA: Si estas inscripciones tienen notas en la tabla 'calificaciones', 
        // la FK en 'calificaciones' (ON DELETE RESTRICT) podría bloquear este DELETE. 
        // Si eso pasa, debes actualizar el estatus a 'Retirado' en lugar de DELETE.
        
        $sql = "
            DELETE i FROM inscripciones i
            JOIN oferta_materias om ON i.oferta_materia_id = om.id
            WHERE i.estudiante_id = ? AND om.oferta_academica_id = ?
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$estudiante_id, $oferta_id]);

        redirigir('exito', 'Estudiante retirado de la oferta correctamente.', $redirect_url);

    } catch (Exception $e) {
        // Si el error es por restricción FK, deberías cambiar el DELETE por un UPDATE de estatus.
        redirigir('error', 'Error al retirar al estudiante: ' . $e->getMessage(), $redirect_url);
    }
}
// =======================================================
else {
    redirigir('error', 'Acción no reconocida.', $redirect_url);
}

exit;