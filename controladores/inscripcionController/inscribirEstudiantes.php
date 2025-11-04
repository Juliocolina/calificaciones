<?php
require_once __DIR__ . '/../../config/conexion.php';
// Asegúrate de incluir tu helper de redirección si no está en conexion.php
// require_once __DIR__ . '/../../controladores/hellpers/auth.php'; 

$oferta_id = 0;
$oferta_info = null;
$materias_oferta = [];
$estudiantes_inscritos = [];
$error_message = '';
$conn = null;

// --- CONFIGURACIÓN DE REGLAS DE NEGOCIO ---
$LIMITE_ARRRASTRE = 2; // Máximo de materias reprobadas que un estudiante puede arrastrar.

// =======================================================
// --- FUNCIONES HELPER (Lógica para Arrastre) ---
// Estas funciones usan la nueva tabla 'calificaciones'.
// =======================================================

/**
 * Obtiene un listado detallado de las materias que el estudiante ha reprobado.
 * Muestra el 'riesgo de arrastre' al administrador.
 * @param PDO $conn Conexión a la base de datos.
 * @param int $estudianteId ID del estudiante.
 * @return array Lista de materias reprobadas con sus notas.
 */
function obtenerMateriasReprobadasDetalle(PDO $conn, $estudianteId) {
    // Consulta SQL que lista las materias donde la mejor nota (MAX) es menor a 12.
    $sql = "
        SELECT
            m.nombre AS materia_nombre,
            om.materia_id,
            MAX(c.nota_numerica) AS nota_final_max
        FROM
            inscripciones i
        JOIN
            calificaciones c ON i.id = c.inscripcion_id
        JOIN
            oferta_materias om ON i.oferta_materia_id = om.id
        JOIN
            materias m ON om.materia_id = m.id
        WHERE
            i.estudiante_id = :estudianteId
        GROUP BY
            om.materia_id, m.nombre
        HAVING
            nota_final_max < 12.00
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute(['estudianteId' => $estudianteId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// =======================================================


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

    // 1. Obtener info de la oferta y sus materias
    $stmt_oferta = $conn->prepare("
        SELECT p.nombre AS pnf, t.nombre AS trayecto, tr.nombre AS trimestre, oa.tipo_oferta, oa.aldea_id
        FROM oferta_academica oa
        JOIN pnfs p ON oa.pnf_id = p.id
        JOIN trayectos t ON oa.trayecto_id = t.id
        JOIN trimestres tr ON oa.trimestre_id = tr.id
        WHERE oa.id = ? AND oa.estatus = 'Abierto'
    ");
    $stmt_oferta->execute([$oferta_id]);
    $oferta_info = $stmt_oferta->fetch(PDO::FETCH_ASSOC);

    if (!$oferta_info) {
        throw new Exception("Oferta no encontrada o no está abierta para inscripciones.");
    }
    $oferta_info['trimestre_tipo'] = $oferta_info['trimestre'] . " (" . ucfirst($oferta_info['tipo_oferta']) . ")";


    $stmt_materias = $conn->prepare("
        SELECT om.id, m.nombre, m.codigo
        FROM oferta_materias om
        JOIN materias m ON om.materia_id = m.id
        WHERE om.oferta_academica_id = ?
    ");
    $stmt_materias->execute([$oferta_id]);
    $materias_oferta = $stmt_materias->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($materias_oferta)) {
        throw new Exception("Esta oferta no tiene materias asignadas. No se pueden realizar inscripciones.");
    }

    // 2. Obtener estudiantes ya inscritos
    $stmt_inscritos = $conn->prepare("
        SELECT DISTINCT e.id AS estudiante_id, u.cedula, u.nombre, u.apellido
        FROM estudiantes e
        JOIN usuarios u ON e.usuario_id = u.id
        JOIN inscripciones i ON e.id = i.estudiante_id
        JOIN oferta_materias om ON i.oferta_materia_id = om.id
        WHERE om.oferta_academica_id = ?
        ORDER BY u.apellido, u.nombre
    ");
    $stmt_inscritos->execute([$oferta_id]);
    $estudiantes_inscritos = $stmt_inscritos->fetchAll(PDO::FETCH_ASSOC);


    // 3. LÓGICA CONDICIONAL: Historial de Arrastre para la búsqueda en el formulario
    // Usamos 'cedula' aquí, ya que es el nombre del campo del formulario de búsqueda
    $cedula_buscada = isset($_POST['cedula']) ? trim($_POST['cedula']) : ''; 
    $estudiante_a_inscribir = null;
    $materias_arrastre_detalle = [];

    if (!empty($cedula_buscada)) {
        // Buscar estudiante por cédula
        $stmt_user = $conn->prepare("SELECT id, nombre, apellido FROM usuarios WHERE cedula = ?");
        $stmt_user->execute([$cedula_buscada]);
        $usuario = $stmt_user->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            // Obtener datos completos del estudiante incluyendo datos académicos
            $stmt_est = $conn->prepare("
                SELECT e.id, e.aldea_id, e.pnf_id, e.trayecto_id, e.trimestre_id, e.codigo_estudiante, e.estado_academico,
                       p.nombre AS pnf_nombre, t.slug AS trayecto_slug, tr.nombre AS trimestre_nombre, a.nombre AS aldea_nombre
                FROM estudiantes e
                LEFT JOIN pnfs p ON e.pnf_id = p.id
                LEFT JOIN trayectos t ON e.trayecto_id = t.id  
                LEFT JOIN trimestres tr ON e.trimestre_id = tr.id
                LEFT JOIN aldeas a ON e.aldea_id = a.id
                WHERE e.usuario_id = ?
            ");
            $stmt_est->execute([$usuario['id']]);
            $estudiante_db = $stmt_est->fetch(PDO::FETCH_ASSOC);

            if ($estudiante_db) {
                // VALIDACIÓN 1: Verificar que el perfil académico esté completo
                if (empty($estudiante_db['pnf_id']) || empty($estudiante_db['trayecto_id']) || 
                    empty($estudiante_db['trimestre_id']) || empty($estudiante_db['codigo_estudiante']) || 
                    empty($estudiante_db['estado_academico'])) {
                    $error_message = "El estudiante {$usuario['nombre']} {$usuario['apellido']} no puede inscribirse. El administrador debe completar primero sus datos académicos (PNF, trayecto, trimestre, código y estado académico).";
                } else {
                    // VALIDACIÓN 2: Verificar coincidencia entre estudiante y oferta
                    $stmt_oferta_datos = $conn->prepare("
                        SELECT pnf_id, trayecto_id, trimestre_id, aldea_id 
                        FROM oferta_academica 
                        WHERE id = ?
                    ");
                    $stmt_oferta_datos->execute([$oferta_id]);
                    $oferta_datos = $stmt_oferta_datos->fetch(PDO::FETCH_ASSOC);
                    
                    if ($estudiante_db['pnf_id'] != $oferta_datos['pnf_id'] || 
                        $estudiante_db['trayecto_id'] != $oferta_datos['trayecto_id'] || 
                        $estudiante_db['trimestre_id'] != $oferta_datos['trimestre_id']) {
                        $error_message = "El estudiante {$usuario['nombre']} {$usuario['apellido']} no puede inscribirse en esta oferta. Sus datos académicos no coinciden:<br>" .
                                       "Estudiante: {$estudiante_db['pnf_nombre']} - {$estudiante_db['trayecto_slug']} - {$estudiante_db['trimestre_nombre']}<br>" .
                                       "Oferta: {$oferta_info['pnf']} - {$oferta_info['trayecto']} - {$oferta_info['trimestre']}";
                    } elseif ($oferta_info['aldea_id'] && $estudiante_db['aldea_id'] != $oferta_info['aldea_id']) {
                        $error_message = "El estudiante {$usuario['nombre']} {$usuario['apellido']} no puede inscribirse en esta oferta. Pertenece a una aldea diferente.<br>" .
                                       "DEBUG: Estudiante aldea_id={$estudiante_db['aldea_id']}, Oferta aldea_id={$oferta_info['aldea_id']}";
                    } else {
                        // Todo está correcto, proceder con la inscripción
                        $estudiante_a_inscribir = [
                            'id' => $estudiante_db['id'],
                            'cedula' => $cedula_buscada,
                            'nombre_completo' => $usuario['nombre'] . ' ' . $usuario['apellido'],
                            'aldea_nombre' => $estudiante_db['aldea_nombre'],
                            'pnf_nombre' => $estudiante_db['pnf_nombre'],
                            'trayecto_slug' => $estudiante_db['trayecto_slug'],
                            'trimestre_nombre' => $estudiante_db['trimestre_nombre']
                        ];
                        
                        // Obtener el historial de materias reprobadas para ADVERTENCIA
                        $materias_arrastre_detalle = obtenerMateriasReprobadasDetalle($conn, $estudiante_db['id']);
                    }
                }
            } else {
                $error_message = "Usuario con cédula {$cedula_buscada} no es un estudiante registrado.";
            }
        } else {
            $error_message = "No se encontró un usuario con la cédula {$cedula_buscada}.";
        }
    }

} catch (Exception $e) {
    $error_message = $e->getMessage();
}

// *** La parte HTML (vista) de este archivo debe usar las variables definidas arriba. ***
?>