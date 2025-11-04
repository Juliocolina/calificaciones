<?php
require_once __DIR__ . '/../../config/conexion.php';

// --- INICIALIZACIÓN DE VARIABLES ---
$oferta_id = 0;
$oferta_info = null;
$materias_asignadas = [];
$error_message = '';

// --- LÓGICA PRINCIPAL ---
try {
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception("ID de oferta no válido o no proporcionado.");
    }
    $oferta_id = intval($_GET['id']);

    $conn = conectar();
    if (!$conn) {
        throw new Exception("Error de conexión a la base de datos.");
    }
    
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

    // 2. Obtener la información de la oferta para mostrarla en el título.
    $stmt_oferta = $conn->prepare("
        SELECT p.nombre AS nombre_pnf, t.nombre AS nombre_trayecto, tr.nombre AS nombre_trimestre, oa.estatus, oa.aldea_id
        FROM oferta_academica oa
        INNER JOIN pnfs p ON oa.pnf_id = p.id
        INNER JOIN trayectos t ON oa.trayecto_id = t.id
        INNER JOIN trimestres tr ON oa.trimestre_id = tr.id
        WHERE oa.id = ?
    ");
    $stmt_oferta->execute([$oferta_id]);
    $oferta_info = $stmt_oferta->fetch(PDO::FETCH_ASSOC);

    if (!$oferta_info) {
        throw new Exception("La oferta académica con el ID {$oferta_id} no fue encontrada.");
    }
    
    // Determinar si se pueden gestionar materias (solo en estado Planificado)
    $puede_gestionar = ($oferta_info['estatus'] === 'Planificado');

    // 3. Obtener las materias asignadas y el profesor vinculado.
    $stmt_asignadas = $conn->prepare("SELECT 
            om.id AS asignacion_id, 
            m.codigo, 
            m.nombre AS nombre_materia,
            p.id AS profesor_id,
            p.usuario_id AS profesor_usuario_id,
            u.nombre AS nombre_profesor,
            u.apellido AS apellido_profesor
        FROM oferta_materias om
        INNER JOIN materias m ON om.materia_id = m.id
        LEFT JOIN oferta_materia_profesor omp ON om.id = omp.oferta_materia_id
        LEFT JOIN profesores p ON omp.profesor_id = p.id
        LEFT JOIN usuarios u ON p.usuario_id = u.id
        WHERE om.oferta_academica_id = ?
        ORDER BY m.nombre ASC");

    $stmt_asignadas->execute([$oferta_id]);
    $materias_asignadas = $stmt_asignadas->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    // Si algo falla, preparamos el mensaje de error para que la vista lo muestre.
    $error_message = htmlspecialchars($e->getMessage());
}

// Al final de este script, las variables están listas para ser usadas por el archivo de la vista.
?>