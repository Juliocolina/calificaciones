<?php
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
verificarRol(['admin', 'coordinador']);
require_once __DIR__ . '/../../models/header.php';
require_once __DIR__ . '/../../config/conexion.php';

// --- VALIDACIÓN DEL ID DE LA OFERTA ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('<div class="alert alert-danger text-center">Error: ID de oferta no válido.</div>');
}
$oferta_id = intval($_GET['id']);

$conn = conectar();
$oferta_info = null;
$materias_disponibles = [];
$error_message = '';

try {
    // 1. Obtener información de la oferta para mostrar en el título
    $stmt_oferta = $conn->prepare("
        SELECT oa.pnf_id, p.nombre AS nombre_pnf, t.nombre AS nombre_trayecto
        FROM oferta_academica oa
        INNER JOIN pnfs p ON oa.pnf_id = p.id
        INNER JOIN trayectos t ON oa.trayecto_id = t.id
        WHERE oa.id = ?
    ");
    $stmt_oferta->execute([$oferta_id]);
    $oferta_info = $stmt_oferta->fetch(PDO::FETCH_ASSOC);

    if (!$oferta_info) {
        throw new Exception("La oferta académica no fue encontrada.");
    }

    // 2. Obtener materias DISPONIBLES del PNF específico (excluyendo las ya asignadas)
    $stmt_asignadas = $conn->prepare("SELECT materia_id FROM oferta_materias WHERE oferta_academica_id = ?");
    $stmt_asignadas->execute([$oferta_id]);
    $ids_asignados = $stmt_asignadas->fetchAll(PDO::FETCH_COLUMN, 0);

    // Filtrar materias por PNF y excluir las ya asignadas
    $sql_disponibles = "SELECT id, codigo, nombre, duracion FROM materias WHERE pnf_id = ?";
    $params = [$oferta_info['pnf_id']];
    
    if (!empty($ids_asignados)) {
        $placeholders = implode(',', array_fill(0, count($ids_asignados), '?'));
        $sql_disponibles .= " AND id NOT IN ($placeholders)";
        $params = array_merge($params, $ids_asignados);
    }
    $sql_disponibles .= " ORDER BY nombre ASC";
    
    $stmt_disponibles = $conn->prepare($sql_disponibles);
    $stmt_disponibles->execute($params);
    $materias_disponibles = $stmt_disponibles->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error_message = "Error: " . htmlspecialchars($e->getMessage());
}
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
        <div class="alert alert-danger"><?= $error_message ?></div>
    <?php else: ?>
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h3 class="mb-0">Añadir Materia</h3>
                        <p class="mb-0">A la oferta: <strong><?= htmlspecialchars($oferta_info['nombre_pnf'] . ' - ' . $oferta_info['nombre_trayecto']) ?></strong></p>
                    </div>
                    <div class="card-body p-4">
                        <form action="../../controladores/ofertaController/crearOfertaMateria.php" method="POST" data-validar-form>
                            <input type="hidden" name="oferta_id" value="<?= $oferta_id ?>">
                            
                            <div class="form-group">
                                <label for="materia_id"><strong>Materias Disponibles</strong></label>
                                <?php if (empty($materias_disponibles)): ?>
                                    <div class="alert alert-warning">No hay más materias disponibles para añadir a esta oferta.</div>
                                <?php else: ?>
                                    <select name="materia_id" id="materia_id" class="form-control" 
                                            data-validar='{"tipo":"","opciones":{"requerido":true}}'
                                            data-nombre="Materia" required>
                                        <option value="">-- Seleccione una materia --</option>
                                        <?php foreach ($materias_disponibles as $materia): ?>
                                            <option value="<?= $materia['id'] ?>"><?= htmlspecialchars($materia['codigo'] . ' - ' . $materia['nombre'] . ' (' . ucfirst($materia['duracion']) . ')') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($materias_disponibles)): ?>
                            <div class="form-group">
                                <label for="duracion_oferta"><i class="fa fa-clock"></i> Duración para esta Oferta *</label>
                                <select name="duracion_oferta" id="duracion_oferta" class="form-control" required>
                                    <option value="">Seleccione la duración</option>
                                    <option value="anual">Anual</option>
                                    <option value="semestral">Semestral</option>
                                    <option value="trimestral">Trimestral</option>
                                    <option value="intensivo">Intensivo</option>
                                </select>
                            </div>
                            <?php endif; ?>

                            <div class="text-center mt-4">
                                <a href="verOfertasMaterias.php?id=<?= $oferta_id ?>" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-success btn-lg" <?= empty($materias_disponibles) ? 'disabled' : '' ?>>
                                    <i class="fa fa-plus-circle"></i> Añadir Materia
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