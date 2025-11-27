<?php
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
require_once __DIR__ . '/../../config/conexion.php';

verificarSesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../vistas/secciones/crearSeccion.php?error=' . urlencode('Método no permitido.'));
    exit;
}

$conn = conectar();

try {
    // Validar campos obligatorios
    $oferta_academica_id = intval($_POST['oferta_academica_id'] ?? 0);
    $materia_id = intval($_POST['materia_id'] ?? 0);
    $profesor_id = intval($_POST['profesor_id'] ?? 0);
    $cupo_maximo = intval($_POST['cupo_maximo'] ?? 30);
    
    if ($oferta_academica_id <= 0 || $materia_id <= 0 || $profesor_id <= 0) {
        header('Location: ../../vistas/secciones/crearSeccion.php?error=' . urlencode('Todos los campos son obligatorios.'));
        exit;
    }
    
    // Validar que la oferta académica existe y está abierta
    $stmt = $conn->prepare("SELECT id, estatus FROM oferta_academica WHERE id = ?");
    $stmt->execute([$oferta_academica_id]);
    $oferta = $stmt->fetch();
    
    if (!$oferta) {
        header('Location: ../../vistas/secciones/crearSeccion.php?error=' . urlencode('La oferta académica no existe.'));
        exit;
    }
    
    if ($oferta['estatus'] !== 'Abierto') {
        header('Location: ../../vistas/secciones/crearSeccion.php?error=' . urlencode('La oferta académica debe estar abierta para crear secciones.'));
        exit;
    }
    
    // Validar que la materia pertenece al PNF de la oferta
    $stmt = $conn->prepare("
        SELECT m.id 
        FROM materias m 
        JOIN oferta_academica oa ON m.pnf_id = oa.pnf_id 
        WHERE m.id = ? AND oa.id = ?
    ");
    $stmt->execute([$materia_id, $oferta_academica_id]);
    
    if (!$stmt->fetch()) {
        header('Location: ../../vistas/secciones/crearSeccion.php?error=' . urlencode('La materia no pertenece al PNF de esta oferta académica.'));
        exit;
    }
    
    // Validar que el profesor puede dar esta materia (opcional - comentado para permitir flexibilidad)
    /*
    $stmt = $conn->prepare("
        SELECT mp.id 
        FROM materia_profesor mp 
        WHERE mp.materia_id = ? AND mp.profesor_id = ?
    ");
    $stmt->execute([$materia_id, $profesor_id]);
    
    if (!$stmt->fetch()) {
        redirigir('error', 'El profesor no está asignado para enseñar esta materia.', 'secciones/crearSeccion.php');
        exit;
    }
    */
    
    // Verificar si ya existe esta combinación
    $stmt = $conn->prepare("SELECT COUNT(*) FROM secciones WHERE oferta_academica_id = ? AND materia_id = ? AND profesor_id = ?");
    $stmt->execute([$oferta_academica_id, $materia_id, $profesor_id]);
    $existe_combinacion = $stmt->fetchColumn() > 0;
    
    if ($existe_combinacion) {
        header('Location: ../../vistas/secciones/crearSeccion.php?error=' . urlencode('Ya existe una sección para esta combinación'));
        exit;
    }
    
    // Generar código de sección único
    $codigo_base = "SEC-{$oferta_academica_id}-{$materia_id}";
    $contador = 1;
    
    do {
        $codigo_seccion = "{$codigo_base}-{$contador}";
        $stmt = $conn->prepare("SELECT COUNT(*) FROM secciones WHERE codigo_seccion = ?");
        $stmt->execute([$codigo_seccion]);
        $existe = $stmt->fetchColumn() > 0;
        $contador++;
    } while ($existe);
    
    $conn->beginTransaction();
    
    // Insertar nueva sección
    $stmt = $conn->prepare("
        INSERT INTO secciones (oferta_academica_id, materia_id, profesor_id, cupo_maximo, codigo_seccion) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([$oferta_academica_id, $materia_id, $profesor_id, $cupo_maximo, $codigo_seccion]);
    
    $conn->commit();
    
    redirigir('exito', 'Sección creada exitosamente.', 'secciones/verSecciones.php');
    exit;
    
} catch (PDOException $e) {
    $conn->rollback();
    error_log("Error al crear sección: " . $e->getMessage());
    
    // Mostrar error específico para depuración
    $error_msg = 'Error al crear la sección: ' . $e->getMessage();
    header('Location: ../../vistas/secciones/crearSeccion.php?error=' . urlencode($error_msg));
    exit;
} catch (Exception $e) {
    $conn->rollback();
    error_log("Error general al crear sección: " . $e->getMessage());
    
    // Mostrar error específico para depuración
    $error_msg = 'Error inesperado: ' . $e->getMessage();
    header('Location: ../../vistas/secciones/crearSeccion.php?error=' . urlencode($error_msg));
    exit;
}
?>