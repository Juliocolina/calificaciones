<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';

// Verificar sesión explícitamente antes de procesar POST/GET
verificarRol(['admin', 'coordinador']);

$conn = conectar();

// =================================================================================
// --- BLOQUE PARA PROCESAR EL FORMULARIO (MÉTODO POST) ---
// =================================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oferta_materia_id = isset($_POST['oferta_materia_id']) ? intval($_POST['oferta_materia_id']) : 0;
    $profesor_id = isset($_POST['profesor_id']) ? intval($_POST['profesor_id']) : 0;
    $oferta_id = isset($_POST['oferta_id']) ? intval($_POST['oferta_id']) : 0;

    $redirect_url = "ofertas_materias/verOfertasMaterias.php?id=" . $oferta_id;

    if ($oferta_materia_id <= 0 || $profesor_id <= 0 || $oferta_id <= 0) {
        redirigir('error', 'Datos inválidos para la asignación.', $redirect_url);
        exit;
    }

    try {
        // Usamos "INSERT ... ON DUPLICATE KEY UPDATE" para manejar tanto la asignación inicial como la actualización.
        // Esto es posible gracias a la UNIQUE KEY en la columna `oferta_materia_id`.
        $sql = "
            INSERT INTO oferta_materia_profesor (oferta_materia_id, profesor_id) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE profesor_id = VALUES(profesor_id)
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$oferta_materia_id, $profesor_id]);
        
        redirigir('exito', 'Profesor asignado a la materia correctamente.', $redirect_url);

    } catch (PDOException $e) {
        redirigir('error', 'Error al asignar el profesor: ' . $e->getMessage(), $redirect_url);
    }
    exit;
}

// =================================================================================
// --- BLOQUE PARA MOSTRAR EL FORMULARIO (MÉTODO GET) ---
// =================================================================================

$oferta_materia_id = 0;
$materia_info = null;
$profesores_disponibles = [];
$profesor_actual_id = null;
$error_message = '';

try {
    if (!isset($_GET['oferta_materia_id']) || !is_numeric($_GET['oferta_materia_id'])) {
        throw new Exception("ID de la materia no válido.");
    }
    $oferta_materia_id = intval($_GET['oferta_materia_id']);

    // 1. Obtener información de la materia y la oferta para el título
    $stmt_info = $conn->prepare("
        SELECT 
            m.nombre AS nombre_materia, 
            p.nombre AS nombre_pnf, 
            t.nombre AS nombre_trayecto,
            oa.id AS oferta_id
        FROM oferta_materias om
        JOIN materias m ON om.materia_id = m.id
        JOIN oferta_academica oa ON om.oferta_academica_id = oa.id
        JOIN pnfs p ON oa.pnf_id = p.id
        JOIN trayectos t ON oa.trayecto_id = t.id
        WHERE om.id = ?
    ");
    $stmt_info->execute([$oferta_materia_id]);
    $materia_info = $stmt_info->fetch(PDO::FETCH_ASSOC);

    if (!$materia_info) {
        throw new Exception("La materia especificada no fue encontrada.");
    }

    // 2. Obtener la lista de todos los profesores y sus datos personales desde la tabla `usuarios`
    // (antes el código asumía que 'cedula', 'nombre', 'apellido' estaban en la tabla 'profesores')
    $stmt_profes = $conn->prepare(
        "SELECT p.id AS id, u.cedula AS cedula, u.nombre AS nombre, u.apellido AS apellido,
                GROUP_CONCAT(m.codigo SEPARATOR ', ') AS materias_ensena
         FROM profesores p
         LEFT JOIN usuarios u ON p.usuario_id = u.id
         LEFT JOIN materia_profesor mp ON p.id = mp.profesor_id
         LEFT JOIN materias m ON mp.materia_id = m.id
         GROUP BY p.id, u.cedula, u.nombre, u.apellido
         ORDER BY u.apellido, u.nombre"
    );
    $stmt_profes->execute();
    $profesores_disponibles = $stmt_profes->fetchAll(PDO::FETCH_ASSOC);

    // 3. Averiguar qué profesor está asignado actualmente (si hay alguno)
    $stmt_actual = $conn->prepare("SELECT profesor_id FROM oferta_materia_profesor WHERE oferta_materia_id = ?");
    $stmt_actual->execute([$oferta_materia_id]);
    $profesor_actual_id = $stmt_actual->fetchColumn(); // fetchColumn() es ideal para obtener un solo valor

} catch (Exception $e) {
    $error_message = $e->getMessage();
}

require_once __DIR__ . '/../../models/header.php';
?>

<style>
    .card { 
        margin-top: 40px; 
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08); 
    }
</style>

<div class="container mt-4">
    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
    <?php else: ?>
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Asignar Profesor</h4>
                        <p class="mb-0">
                            <strong>Materia:</strong> <?= htmlspecialchars($materia_info['nombre_materia']) ?> <br>
                            <small><strong>Oferta:</strong> <?= htmlspecialchars($materia_info['nombre_pnf'] . ' - ' . $materia_info['nombre_trayecto']) ?></small>
                        </p>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST">
                            <input type="hidden" name="oferta_materia_id" value="<?= $oferta_materia_id ?>">
                            <input type="hidden" name="oferta_id" value="<?= $materia_info['oferta_id'] ?>">
                            
                            <div class="form-group">
                                <label for="profesor_id"><strong>Seleccione un profesor:</strong></label>
                                <select name="profesor_id" id="profesor_id" class="form-control" required>
                                    <option value="">-- Profesores Disponibles --</option>
                                    <?php foreach ($profesores_disponibles as $profesor): ?>
                                        <option value="<?= $profesor['id'] ?>" <?= ($profesor['id'] == $profesor_actual_id) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($profesor['apellido'] . ', ' . $profesor['nombre'] . ' (C.I: ' . $profesor['cedula'] . ')') ?>
                                            <?php if ($profesor['materias_ensena']): ?>
                                                - Materias: <?= htmlspecialchars($profesor['materias_ensena']) ?>
                                            <?php else: ?>
                                                - Sin materias
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="text-center mt-4">
                                <a href="verOfertasMaterias.php?id=<?= $materia_info['oferta_id'] ?>" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary btn-lg"><i class="fa fa-save"></i> Guardar Asignación</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../models/footer.php'; ?>