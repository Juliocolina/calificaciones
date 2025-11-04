<?php
require_once __DIR__ . '/../../config/conexion.php';

$conn = conectar();
$pnfs = [];
$trayectos = [];
$trimestres = [];
$error_message = '';
$aldea_coordinador = null;

try {
    // Obtener aldea del coordinador si es coordinador
    if (isset($_SESSION['usuario']) && $_SESSION['usuario']['rol'] === 'coordinador') {
        $stmt_coord = $conn->prepare("SELECT a.nombre FROM coordinadores c JOIN aldeas a ON c.aldea_id = a.id WHERE c.usuario_id = ?");
        $stmt_coord->execute([$_SESSION['usuario']['id']]);
        $coord_data = $stmt_coord->fetch(PDO::FETCH_ASSOC);
        $aldea_coordinador = $coord_data['nombre'] ?? null;
    }
    
    // 1. Obtener todos los PNF (ordenados por nombre)
    $stmt_pnf = $conn->query("SELECT id, nombre FROM pnfs ORDER BY nombre ASC");
    $pnfs = $stmt_pnf->fetchAll(PDO::FETCH_ASSOC);

    // 2. Obtener todos los Trayectos
    // Se usa 'slug' para mostrar al usuario, asumiendo que contiene el nombre legible (Ej: 'TRAYECTO I')
    $stmt_trayectos = $conn->query("SELECT id, slug FROM trayectos ORDER BY id ASC");
    $trayectos = $stmt_trayectos->fetchAll(PDO::FETCH_ASSOC);

    // 3. Obtener los Trimestres ACTIVOS Y FUTUROS
    $stmt_trimestres = $conn->query(
        "SELECT id, nombre FROM trimestres 
         WHERE fecha_fin >= CURDATE() 
         ORDER BY fecha_inicio ASC"
    );
    $trimestres = $stmt_trimestres->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Manejo de errores de base de datos
    $error_message = "Error crítico al cargar los datos del formulario: " . htmlspecialchars($e->getMessage());
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
        background: linear-gradient(90deg, #1e3c72, #2a5298); 
        color: white;
        border-radius: 12px 12px 0 0 !important;
    }
    .btn-primary {
        background-color: #2a5298;
        border: none;
    }
    .btn-primary:hover {
        background-color: #1e3c72;
    }
</style>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header text-center">
                    <h3 class="mb-0"><i class="fa fa-plus-circle"></i> Crear Nueva Oferta Académica</h3>
                </div>
                <div class="card-body p-4">

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger"><?= $error_message ?></div>
                    <?php else: ?>
                        <?php if ($aldea_coordinador): ?>
                            <div class="alert alert-info">
                                <i class="fa fa-info-circle"></i> Esta oferta será creada para la aldea: <strong><?= htmlspecialchars($aldea_coordinador) ?></strong>
                            </div>
                        <?php endif; ?>
                        <form action="../../controladores/ofertaController/crearOferta.php" method="POST" data-validar-form>
                            
                            <div class="form-group">
                                <label for="pnf_id"><strong>Programa Nacional de Formación (PNF) (*)</strong></label>
                                <select name="pnf_id" id="pnf_id" class="form-control" 
                                        data-validar='{"tipo":"","opciones":{"requerido":true}}'
                                        data-nombre="PNF" required>
                                    <option value="">-- Seleccione un PNF --</option>
                                    <?php foreach ($pnfs as $pnf): ?>
                                        <option value="<?= htmlspecialchars($pnf['id']) ?>"><?= htmlspecialchars($pnf['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="trayecto_id"><strong>Trayecto (*)</strong></label>
                                <select name="trayecto_id" id="trayecto_id" class="form-control" 
                                        data-validar='{"tipo":"","opciones":{"requerido":true}}'
                                        data-nombre="Trayecto" required>
                                    <option value="">-- Seleccione un Trayecto --</option>
                                    <?php foreach ($trayectos as $trayecto): ?>
                                        <option value="<?= htmlspecialchars($trayecto['id']) ?>"><?= htmlspecialchars($trayecto['slug']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="trimestre_id"><strong>Período Académico (Trimestre) (*)</strong></label>
                                <?php if (empty($trimestres)): ?>
                                    <div class="alert alert-warning">
                                        No hay períodos académicos activos o futuros disponibles para crear ofertas. Por favor, registre un nuevo trimestre.
                                    </div>
                                <?php else: ?>
                                    <select name="trimestre_id" id="trimestre_id" class="form-control" 
                                            data-validar='{"tipo":"","opciones":{"requerido":true}}'
                                            data-nombre="Trimestre" required>
                                        <option value="">-- Seleccione un Trimestre --</option>
                                        <?php foreach ($trimestres as $trimestre): ?>
                                            <option value="<?= htmlspecialchars($trimestre['id']) ?>"><?= htmlspecialchars($trimestre['nombre']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="tipo_oferta"><strong>Tipo de Oferta (*)</strong></label>
                                <select name="tipo_oferta" id="tipo_oferta" class="form-control" 
                                        data-validar='{"tipo":"","opciones":{"requerido":true}}'
                                        data-nombre="Tipo de Oferta" required>
                                    <option value="">-- Seleccione el Tipo --</option>
                                    <option value="regular">Regular</option>
                                    <option value="intensivo">Intensivo</option>
                                    <option value="reparacion">Reparación</option>
                                </select>
                                <small class="form-text text-muted">La combinación de los 4 campos (PNF, Trayecto, Trimestre, Tipo) debe ser única.</small>
                            </div>
                            
                            <hr>

                            <div class="form-group">
                                <a class="btn btn-sm btn-outline-secondary" data-toggle="collapse" href="#fechasExcepcionCollapse" role="button" aria-expanded="false" aria-controls="fechasExcepcionCollapse">
                                    <i class="fa fa-calendar-alt"></i> Opciones de Fechas Especiales
                                </a>
                                <div class="collapse mt-3" id="fechasExcepcionCollapse">
                                    <div class="card card-body bg-light">
                                        <p class="small text-muted mb-2">
                                            Rellene **ambos** campos si esta oferta debe tener un rango de fechas diferente al trimestre oficial.
                                        </p>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label for="fecha_inicio_excepcion">Fecha de Inicio de Excepción</label>
                                                <input type="date" class="form-control" name="fecha_inicio_excepcion" id="fecha_inicio_excepcion"
                                                       data-validar='{"tipo":"fecha","opciones":{}}'
                                                       data-nombre="Fecha de Inicio">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="fecha_fin_excepcion">Fecha de Fin de Excepción</label>
                                                <input type="date" class="form-control" name="fecha_fin_excepcion" id="fecha_fin_excepcion"
                                                       data-validar='{"tipo":"fecha","opciones":{}}'
                                                       data-nombre="Fecha de Fin">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <p class="small text-danger">(*) Campos obligatorios.</p>
                                <button type="submit" class="btn btn-primary btn-lg" <?= empty($trimestres) ? 'disabled' : '' ?>>
                                    <i class="fa fa-save"></i> Crear Oferta
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