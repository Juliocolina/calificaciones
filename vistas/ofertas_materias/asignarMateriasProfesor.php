<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';

verificarSesion();
$conn = conectar();

$profesor_id = isset($_GET['profesor_id']) ? intval($_GET['profesor_id']) : 0;
$profesor_info = null;
$materias_disponibles = [];
$materias_asignadas = [];
$error_message = '';

// Procesar formulario POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $materias_seleccionadas = $_POST['materias'] ?? [];
    
    try {
        // Eliminar asignaciones existentes
        $stmt_delete = $conn->prepare("DELETE FROM materia_profesor WHERE profesor_id = ?");
        $stmt_delete->execute([$profesor_id]);
        
        // Insertar nuevas asignaciones
        if (!empty($materias_seleccionadas)) {
            $stmt_insert = $conn->prepare("INSERT INTO materia_profesor (profesor_id, materia_id) VALUES (?, ?)");
            foreach ($materias_seleccionadas as $materia_id) {
                $stmt_insert->execute([$profesor_id, intval($materia_id)]);
            }
        }
        
        redirigir('exito', 'Materias asignadas correctamente al profesor.', 'profesores/verProfesores.php');
        exit;
        
    } catch (Exception $e) {
        $error_message = 'Error al asignar materias: ' . $e->getMessage();
    }
}

try {
    if ($profesor_id <= 0) {
        throw new Exception("ID de profesor no válido.");
    }
    
    // Obtener información del profesor
    $stmt_profesor = $conn->prepare("
        SELECT p.id, u.nombre, u.apellido, u.cedula, p.pnf_id, pnf.nombre AS pnf_nombre
        FROM profesores p
        JOIN usuarios u ON p.usuario_id = u.id
        LEFT JOIN pnfs pnf ON p.pnf_id = pnf.id
        WHERE p.id = ?
    ");
    $stmt_profesor->execute([$profesor_id]);
    $profesor_info = $stmt_profesor->fetch(PDO::FETCH_ASSOC);
    
    if (!$profesor_info) {
        throw new Exception("Profesor no encontrado.");
    }
    
    // Obtener materias disponibles (del PNF del profesor si tiene, sino todas)
    $sql_materias = "SELECT id, codigo, nombre FROM materias";
    $params_materias = [];
    
    if ($profesor_info['pnf_id']) {
        $sql_materias .= " WHERE pnf_id = ?";
        $params_materias[] = $profesor_info['pnf_id'];
    }
    
    $sql_materias .= " ORDER BY nombre";
    
    $stmt_materias = $conn->prepare($sql_materias);
    $stmt_materias->execute($params_materias);
    $materias_disponibles = $stmt_materias->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener materias ya asignadas al profesor
    $stmt_asignadas = $conn->prepare("SELECT materia_id FROM materia_profesor WHERE profesor_id = ?");
    $stmt_asignadas->execute([$profesor_id]);
    $materias_asignadas = $stmt_asignadas->fetchAll(PDO::FETCH_COLUMN);
    
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
    .card-header { 
        background: linear-gradient(90deg, #28a745, #218838); 
        color: white;
        border-radius: 12px 12px 0 0 !important;
    }
</style>

<div class="container mt-4">
    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <div class="text-center">
            <a href="../profesores/verProfesores.php" class="btn btn-secondary">Volver a Profesores</a>
        </div>
    <?php else: ?>
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h3 class="mb-0">Asignar Materias al Profesor</h3>
                        <p class="mb-0">
                            <strong>Profesor:</strong> <?= htmlspecialchars($profesor_info['nombre'] . ' ' . $profesor_info['apellido']) ?> 
                            (C.I: <?= htmlspecialchars($profesor_info['cedula']) ?>)
                        </p>
                        <?php if ($profesor_info['pnf_nombre']): ?>
                            <small>PNF: <?= htmlspecialchars($profesor_info['pnf_nombre']) ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-group">
                                <label><strong>Materias Disponibles:</strong></label>
                                <?php if (empty($materias_disponibles)): ?>
                                    <div class="alert alert-warning">No hay materias disponibles para asignar.</div>
                                <?php else: ?>
                                    <div class="row">
                                        <?php foreach ($materias_disponibles as $materia): ?>
                                            <div class="col-md-6 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                           name="materias[]" value="<?= $materia['id'] ?>"
                                                           id="materia_<?= $materia['id'] ?>"
                                                           <?= in_array($materia['id'], $materias_asignadas) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="materia_<?= $materia['id'] ?>">
                                                        <?= htmlspecialchars($materia['codigo'] . ' - ' . $materia['nombre']) ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="text-center mt-4">
                                <a href="../profesores/verProfesores.php" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-success btn-lg" <?= empty($materias_disponibles) ? 'disabled' : '' ?>>
                                    <i class="fa fa-save"></i> Guardar Asignaciones
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../models/footer.php'; ?>