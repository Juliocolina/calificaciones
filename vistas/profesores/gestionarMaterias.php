<?php
session_start();
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
verificarRol(['admin', 'coordinador']);
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/conexion.php';

$conn = conectar();
$profesor_id = intval($_GET['profesor_id'] ?? 0);

if ($profesor_id <= 0) {
    header("Location: verProfesores.php");
    exit;
}

// Obtener datos del profesor incluyendo su PNF
$stmt = $conn->prepare("
    SELECT p.id, u.nombre, u.apellido, u.cedula, p.pnf_id
    FROM profesores p 
    JOIN usuarios u ON p.usuario_id = u.id 
    WHERE p.id = ?
");
$stmt->execute([$profesor_id]);
$profesor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profesor) {
    header("Location: verProfesores.php");
    exit;
}

// Materias asignadas
$stmt = $conn->prepare("SELECT m.id, m.nombre FROM materias m JOIN materia_profesor mp ON m.id = mp.materia_id WHERE mp.profesor_id = ?");
$stmt->execute([$profesor_id]);
$materias_asignadas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Materias disponibles del mismo PNF
$stmt = $conn->prepare("SELECT id, nombre FROM materias WHERE pnf_id = ? AND id NOT IN (SELECT materia_id FROM materia_profesor WHERE profesor_id = ?)");
$stmt->execute([$profesor['pnf_id'], $profesor_id]);
$materias_disponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow">
                <div class="card-header bg-success text-white text-center">
                    <h3 class="mb-0">
                        <i class="fa fa-book"></i> Gestionar Materias
                    </h3>
                    <p class="mb-0">Profesor: <?= htmlspecialchars($profesor['nombre'] . ' ' . $profesor['apellido']) ?></p>
                </div>
                
                <div class="card-body">
                    <div class="row">
                        <!-- Materias Asignadas -->
                        <div class="col-md-6">
                            <h5 class="text-success"><i class="fa fa-check-circle"></i> Materias Asignadas</h5>
                            <?php if (count($materias_asignadas) > 0): ?>
                                <div class="list-group">
                                    <?php foreach ($materias_asignadas as $materia): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?= htmlspecialchars($materia['nombre']) ?></strong>
                                            </div>
                                            <form method="POST" action="../../controladores/profesorController/gestionarMateriaProfesor.php" style="display: inline;">
                                                <input type="hidden" name="profesor_id" value="<?= $profesor_id ?>">
                                                <input type="hidden" name="materia_id" value="<?= $materia['id'] ?>">
                                                <input type="hidden" name="accion" value="quitar">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Quitar materia">
                                                    <i class="fa fa-times"></i>
                                                </button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">No tiene materias asignadas</div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Materias Disponibles -->
                        <div class="col-md-6">
                            <h5 class="text-primary"><i class="fa fa-plus-circle"></i> Materias Disponibles</h5>
                            <?php if (count($materias_disponibles) > 0): ?>
                                <div class="list-group" style="max-height: 400px; overflow-y: auto;">
                                    <?php foreach ($materias_disponibles as $materia): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?= htmlspecialchars($materia['nombre']) ?></strong>
                                            </div>
                                            <form method="POST" action="../../controladores/profesorController/gestionarMateriaProfesor.php" style="display: inline;">
                                                <input type="hidden" name="profesor_id" value="<?= $profesor_id ?>">
                                                <input type="hidden" name="materia_id" value="<?= $materia['id'] ?>">
                                                <input type="hidden" name="accion" value="asignar">
                                                <button type="submit" class="btn btn-sm btn-outline-success" title="Asignar materia">
                                                    <i class="fa fa-plus"></i>
                                                </button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">No hay materias disponibles para asignar</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <a href="verProfesores.php" class="btn btn-secondary">
                            <i class="fa fa-arrow-left"></i> Volver a Profesores
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>