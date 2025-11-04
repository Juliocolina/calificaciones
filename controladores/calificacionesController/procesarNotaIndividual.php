<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('error', 'Acceso no permitido.', 'calificaciones/registrarCalificaciones.php');
    exit;
}

// Obtener datos del formulario
$inscripcion_id = isset($_POST['inscripcion_id']) ? intval($_POST['inscripcion_id']) : 0;
$oferta_id = isset($_POST['oferta_id']) ? intval($_POST['oferta_id']) : 0;
$nota_numerica = isset($_POST['nota_numerica']) ? intval($_POST['nota_numerica']) : 0;
$tipo_evaluacion = trim($_POST['tipo_evaluacion'] ?? '');

// URLs de redirección
$form_url = "calificaciones/cargarNotaForm.php?inscripcion_id=" . $inscripcion_id;
$list_url = "calificaciones/registrarCalificaciones.php?oferta_id=" . $oferta_id;

// Validaciones básicas
if ($inscripcion_id <= 0) {
    redirigir('error', 'ID de inscripción no válido.', $list_url);
    exit;
}

if ($nota_numerica < 0 || $nota_numerica > 20) {
    redirigir('error', 'La nota debe estar entre 0 y 20.', $form_url);
    exit;
}

if (!in_array($tipo_evaluacion, ['Ordinaria', 'Reparacion', 'Intensivo', 'Especial'])) {
    redirigir('error', 'Tipo de evaluación no válido.', $form_url);
    exit;
}

// Validar permisos según estatus de oferta
try {
    $conn_temp = conectar();
    $stmt_oferta = $conn_temp->prepare("
        SELECT oa.estatus 
        FROM inscripciones i
        JOIN oferta_materias om ON i.oferta_materia_id = om.id
        JOIN oferta_academica oa ON om.oferta_academica_id = oa.id
        WHERE i.id = ?
    ");
    $stmt_oferta->execute([$inscripcion_id]);
    $oferta_data = $stmt_oferta->fetch(PDO::FETCH_ASSOC);
    
    if ($oferta_data && $oferta_data['estatus'] !== 'Abierto' && $_SESSION['rol'] !== 'admin') {
        redirigir('error', 'No puedes cargar notas en ofertas cerradas. Solo los administradores pueden hacerlo.', $form_url);
        exit;
    }
} catch (Exception $e) {
    redirigir('error', 'Error al validar permisos.', $form_url);
    exit;
}

try {
    $conn = conectar();
    $conn->beginTransaction();

    // 1. Obtener datos de la inscripción
    $stmt_inscripcion = $conn->prepare("
        SELECT 
            i.estudiante_id,
            om.materia_id,
            u.nombre,
            u.apellido,
            m.nombre as materia_nombre
        FROM inscripciones i
        JOIN estudiantes e ON i.estudiante_id = e.id
        JOIN usuarios u ON e.usuario_id = u.id
        JOIN oferta_materias om ON i.oferta_materia_id = om.id
        JOIN materias m ON om.materia_id = m.id
        WHERE i.id = ?
    ");
    $stmt_inscripcion->execute([$inscripcion_id]);
    $inscripcion_data = $stmt_inscripcion->fetch(PDO::FETCH_ASSOC);

    if (!$inscripcion_data) {
        throw new Exception('Inscripción no encontrada.');
    }

    $estudiante_id = $inscripcion_data['estudiante_id'];
    $materia_id = $inscripcion_data['materia_id'];
    $estudiante_nombre = $inscripcion_data['apellido'] . ', ' . $inscripcion_data['nombre'];
    $materia_nombre = $inscripcion_data['materia_nombre'];

    // 2. Registrar la nueva calificación
    $stmt_calificacion = $conn->prepare("
        INSERT INTO calificaciones (inscripcion_id, nota_numerica, tipo_evaluacion, fecha_registro, usuario_id)
        VALUES (?, ?, ?, NOW(), ?)
    ");
    $usuario_id = $_SESSION['usuario_id'] ?? null;
    $stmt_calificacion->execute([$inscripcion_id, $nota_numerica, $tipo_evaluacion, $usuario_id]);

    // 3. Calcular la nota máxima histórica para esta materia y estudiante
    $stmt_max_nota = $conn->prepare("
        SELECT MAX(c.nota_numerica) AS nota_max
        FROM calificaciones c
        JOIN inscripciones i ON c.inscripcion_id = i.id
        JOIN oferta_materias om ON i.oferta_materia_id = om.id
        WHERE i.estudiante_id = ? AND om.materia_id = ?
    ");
    $stmt_max_nota->execute([$estudiante_id, $materia_id]);
    $resultado = $stmt_max_nota->fetch(PDO::FETCH_ASSOC);
    $nota_max = floatval($resultado['nota_max']);

    // 4. Determinar nuevo estatus
    $nuevo_estatus = ($nota_max >= 12.00) ? 'Aprobada' : 'Reprobada';

    // 5. Actualizar estatus de todas las inscripciones de esta materia para este estudiante
    $stmt_update = $conn->prepare("
        UPDATE inscripciones i
        JOIN oferta_materias om ON i.oferta_materia_id = om.id
        SET i.estatus = ?
        WHERE i.estudiante_id = ? AND om.materia_id = ?
    ");
    $stmt_update->execute([$nuevo_estatus, $estudiante_id, $materia_id]);

    $conn->commit();

    // Mensaje de éxito detallado
    $mensaje = sprintf(
        'Nota %d registrada para %s en %s. Estatus: %s (Nota máxima histórica: %d)',
        $nota_numerica,
        $estudiante_nombre,
        $materia_nombre,
        $nuevo_estatus,
        $nota_max
    );

    // Redirección según rol
    if ($_SESSION['rol'] === 'profesor') {
        redirigir('exito', $mensaje, 'inscripciones/misEstudiantesInscritos.php');
    } else {
        redirigir('exito', $mensaje, $list_url);
    }

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    redirigir('error', 'Error al guardar la calificación: ' . $e->getMessage(), $form_url);
}

exit;