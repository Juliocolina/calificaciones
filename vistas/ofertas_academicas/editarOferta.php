<?php
require_once __DIR__ . '/../../models/header.php';
require_once __DIR__ . '/../../config/conexion.php';

// --- PASO 1: VALIDAR QUE RECIBIMOS UN ID POR POST O GET (HÍBRIDO) ---
// Esto permite que funcione tanto desde la tabla (POST) como desde un error de controlador (GET)
$id_oferta_a_editar = null;
if (isset($_POST['id']) && is_numeric($_POST['id'])) {
    $id_oferta_a_editar = intval($_POST['id']);
} elseif (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_oferta_a_editar = intval($_GET['id']);
}

if (!$id_oferta_a_editar) {
    die('<div class="alert alert-danger text-center">Error: ID de oferta no válido.</div>');
}


$conn = conectar();
$oferta_actual = null;
$pnfs = [];
$trayectos = [];
$trimestres = [];
$error_message = '';

try {
    // --- PASO 2 (CORREGIDO): BUSCAR TODOS LOS DATOS EDITABLES, INCLUYENDO 'tipo_oferta' ---
    $stmt_oferta = $conn->prepare(
        "SELECT pnf_id, trayecto_id, trimestre_id, tipo_oferta, estatus, fecha_inicio_excepcion, fecha_fin_excepcion 
         FROM oferta_academica WHERE id = ?"
    );
    $stmt_oferta->execute([$id_oferta_a_editar]);
    $oferta_actual = $stmt_oferta->fetch(PDO::FETCH_ASSOC);

    if (!$oferta_actual) {
        throw new Exception("La oferta académica con el ID {$id_oferta_a_editar} no fue encontrada.");
    }

    // Opcional: Impedir la edición desde la vista si el estatus no es 'Planificado'
    if ($oferta_actual['estatus'] !== 'Planificado') {
        throw new Exception("Advertencia: Esta oferta está en estado '{$oferta_actual['estatus']}' y no puede ser modificada. Solo las ofertas 'Planificado' son editables.");
    }

    // --- PASO 3: OBTENER TODAS LAS OPCIONES PARA LOS SELECTS ---
    $stmt_pnf = $conn->query("SELECT id, nombre FROM pnfs ORDER BY nombre ASC");
    $pnfs = $stmt_pnf->fetchAll(PDO::FETCH_ASSOC);

    $stmt_trayectos = $conn->query("SELECT id, slug FROM trayectos ORDER BY id ASC");
    $trayectos = $stmt_trayectos->fetchAll(PDO::FETCH_ASSOC);
    
    // Mostramos todos los trimestres disponibles para permitir flexibilidad al editar
    $stmt_trimestres = $conn->query("SELECT id, nombre FROM trimestres ORDER BY fecha_inicio DESC");
    $trimestres = $stmt_trimestres->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error_message = "Error crítico al cargar los datos: " . htmlspecialchars($e->getMessage());
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
        background: linear-gradient(90deg, #ffc107, #ff9800);
        color: #212529;
        border-radius: 12px 12px 0 0 !important;
    }
</style>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header text-center">
                    <h3 class="mb-0"><i class="fa fa-edit"></i> Editar Oferta Académica</h3>
                    <small>ID: <?= htmlspecialchars($id_oferta_a_editar) ?> | Estatus: <?= htmlspecialchars($oferta_actual['estatus'] ?? 'N/A') ?></small>
                </div>
                <div class="card-body p-4">

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger"><?= $error_message ?></div>
                    <?php else: ?>
                        <form action="../../controladores/ofertaController/actualizarOferta.php" method="POST">
                            
                            <input type="hidden" name="id" value="<?= htmlspecialchars($id_oferta_a_editar) ?>">

                            <div class="form-group">
                                <label for="pnf_id"><strong>Programa Nacional de Formación (PNF) (*)</strong></label>
                                <select name="pnf_id" id="pnf_id" class="form-control" required>
                                    <?php foreach ($pnfs as $pnf): ?>
                                        <option value="<?= htmlspecialchars($pnf['id']) ?>" 
                                            <?php if ($pnf['id'] == $oferta_actual['pnf_id']) echo 'selected'; ?>>
                                            <?= htmlspecialchars($pnf['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="trayecto_id"><strong>Trayecto (*)</strong></label>
                                <select name="trayecto_id" id="trayecto_id" class="form-control" required>
                                    <?php foreach ($trayectos as $trayecto): ?>
                                        <option value="<?= htmlspecialchars($trayecto['id']) ?>"
                                            <?php if ($trayecto['id'] == $oferta_actual['trayecto_id']) echo 'selected'; ?>>
                                            <?= htmlspecialchars($trayecto['slug']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="trimestre_id"><strong>Período Académico (Trimestre) (*)</strong></label>
                                <select name="trimestre_id" id="trimestre_id" class="form-control" required>
                                    <?php foreach ($trimestres as $trimestre): ?>
                                        <option value="<?= htmlspecialchars($trimestre['id']) ?>"
                                            <?php if ($trimestre['id'] == $oferta_actual['trimestre_id']) echo 'selected'; ?>>
                                            <?= htmlspecialchars($trimestre['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="tipo_oferta"><strong>Tipo de Oferta (*)</strong></label>
                                <select name="tipo_oferta" id="tipo_oferta" class="form-control" required>
                                    <option value="">-- Seleccione el Tipo --</option>
                                    <?php 
                                    $tipos = ['regular', 'intensivo', 'reparacion'];
                                    foreach ($tipos as $tipo): ?>
                                        <option value="<?= $tipo ?>" 
                                            <?= $tipo == $oferta_actual['tipo_oferta'] ? 'selected' : '' ?>>
                                            <?= ucfirst($tipo) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">La combinación de PNF, Trayecto, Trimestre y Tipo debe ser única.</small>
                            </div>

                            <hr>

                            <div class="form-group">
                                <a class="btn btn-sm btn-outline-secondary" data-toggle="collapse" href="#fechasExcepcionCollapse" role="button" aria-expanded="true" aria-controls="fechasExcepcionCollapse">
                                    <i class="fa fa-calendar-alt"></i> Opciones de Fechas Especiales
                                </a>
                                <div class="collapse show mt-3" id="fechasExcepcionCollapse"> 
                                    <div class="card card-body bg-light">
                                        <p class="small text-muted mb-2">
                                            Modifique **ambos** campos si esta oferta debe tener un rango de fechas diferente al trimestre oficial. Para quitar las fechas especiales, déjelos en blanco.
                                        </p>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label for="fecha_inicio_excepcion">Fecha de Inicio de Excepción</label>
                                                <input type="date" class="form-control" name="fecha_inicio_excepcion" id="fecha_inicio_excepcion"
                                                       value="<?= htmlspecialchars($oferta_actual['fecha_inicio_excepcion'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="fecha_fin_excepcion">Fecha de Fin de Excepción</label>
                                                <input type="date" class="form-control" name="fecha_fin_excepcion" id="fecha_fin_excepcion"
                                                       value="<?= htmlspecialchars($oferta_actual['fecha_fin_excepcion'] ?? '') ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-center mt-4">
                                <p class="small text-danger">(*) Campos obligatorios.</p>
                                <a href="verOfertas.php" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-warning btn-lg"> <i class="fa fa-save"></i> Guardar Cambios
                                </button>
                            </div>

                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../models/footer.php'; ?>