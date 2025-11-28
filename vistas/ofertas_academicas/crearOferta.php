<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';

// Solo redirigir admin sin aldea_id, coordinador puede acceder directamente
if ($_SESSION['rol'] === 'admin' && !isset($_GET['aldea_id'])) {
    header('Location: ../aldeas/verAldeas.php');
    exit;
}

$conn = conectar();
$trayectos = [];
$trimestres = [];
$error_message = '';

try {
    // Obtener aldeas
    $aldeas = $conn->query("SELECT id, nombre FROM aldeas ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

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
    error_log("Error PDO en crearOferta.php: " . $e->getMessage());
} catch (Exception $e) {
    $error_message = "Error general: " . htmlspecialchars($e->getMessage());
    error_log("Error general en crearOferta.php: " . $e->getMessage());
}

require_once __DIR__ . '/../../includes/header.php';
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

                        <form action="../../controladores/ofertaController/crearOferta.php" method="POST" data-validar-form>
                            
                            <?php if ($_SESSION['rol'] === 'admin'): ?>
                            <div class="form-group">
                                <label for="aldea_id"><strong>Aldea (*)</strong></label>
                                <?php 
                                $aldea_preseleccionada = $_GET['aldea_id'] ?? null;
                                if ($aldea_preseleccionada): 
                                    $aldea_seleccionada = null;
                                    foreach ($aldeas as $aldea) {
                                        if ($aldea['id'] == $aldea_preseleccionada) {
                                            $aldea_seleccionada = $aldea;
                                            break;
                                        }
                                    }
                                ?>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($aldea_seleccionada['nombre'] ?? 'Aldea no encontrada') ?>" readonly>
                                    <input type="hidden" name="aldea_id" value="<?= htmlspecialchars($aldea_preseleccionada) ?>">
                                    <small class="form-text text-muted">Aldea preseleccionada desde la vista anterior</small>
                                <?php else: ?>
                                    <select name="aldea_id" id="aldea_id" class="form-control" required>
                                        <option value="">-- Seleccione una Aldea --</option>
                                        <?php foreach ($aldeas as $aldea): ?>
                                            <option value="<?= htmlspecialchars($aldea['id']) ?>"><?= htmlspecialchars($aldea['nombre']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                                <?php 
                                // Consulta directa para coordinador
                                $coord_query = $conn->prepare("SELECT c.aldea_id, a.nombre FROM coordinadores c JOIN aldeas a ON c.aldea_id = a.id WHERE c.usuario_id = ?");
                                $coord_query->execute([$_SESSION['usuario_id']]);
                                $coord_result = $coord_query->fetch(PDO::FETCH_ASSOC);
                                ?>
                                <div class="form-group">
                                    <label><strong>Aldea Asignada</strong></label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($coord_result['nombre'] ?? 'Sin aldea asignada') ?>" readonly>
                                    <input type="hidden" name="aldea_id" value="<?= $coord_result['aldea_id'] ?? '' ?>">
                                </div>
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label for="pnf_id"><strong>Programa Nacional de Formación (PNF) (*)</strong></label>
                                <select name="pnf_id" id="pnf_id" class="form-control" required>
                                    <?php if ($_SESSION['rol'] === 'coordinador'): ?>
                                        <?php 
                                        // Obtener PNFs de la aldea del coordinador
                                        $pnf_query = $conn->prepare("SELECT p.id, p.nombre FROM pnfs p JOIN coordinadores c ON p.aldea_id = c.aldea_id WHERE c.usuario_id = ?");
                                        $pnf_query->execute([$_SESSION['usuario_id']]);
                                        $coord_pnfs = $pnf_query->fetchAll(PDO::FETCH_ASSOC);
                                        ?>
                                        <option value="">-- Seleccione un PNF --</option>
                                        <?php foreach ($coord_pnfs as $pnf): ?>
                                            <option value="<?= htmlspecialchars($pnf['id']) ?>"><?= htmlspecialchars($pnf['nombre']) ?></option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <?php if ($aldea_preseleccionada): ?>
                                            <?php 
                                            $pnf_query = $conn->prepare("SELECT id, nombre FROM pnfs WHERE aldea_id = ?");
                                            $pnf_query->execute([$aldea_preseleccionada]);
                                            $pnfs_aldea = $pnf_query->fetchAll(PDO::FETCH_ASSOC);
                                            ?>
                                            <option value="">-- Seleccione un PNF --</option>
                                            <?php foreach ($pnfs_aldea as $pnf): ?>
                                                <option value="<?= htmlspecialchars($pnf['id']) ?>"><?= htmlspecialchars($pnf['nombre']) ?></option>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <option value="">Primero seleccione una aldea</option>
                                        <?php endif; ?>
                                    <?php endif; ?>
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    console.log('JavaScript cargado correctamente');
    
    <?php if ($_SESSION['rol'] === 'admin'): ?>
    // Para admin: cargar PNFs cuando se selecciona aldea
    $('#aldea_id').on('change', function() {
        const aldeaId = $(this).val();
        console.log('Aldea seleccionada:', aldeaId);
        
        if (aldeaId) {
            // Mostrar loading
            $('#pnf_id').html('<option value="">Cargando PNFs...</option>');
            
            $.ajax({
                url: '../../api/getPnfsByAldea.php',
                method: 'GET',
                data: { aldea_id: aldeaId },
                dataType: 'json',
                success: function(pnfs) {
                    console.log('PNFs recibidos:', pnfs);
                    let options = '<option value="">-- Seleccione un PNF --</option>';
                    
                    if (pnfs && pnfs.length > 0) {
                        pnfs.forEach(function(pnf) {
                            options += `<option value="${pnf.id}">${pnf.nombre}</option>`;
                        });
                    } else {
                        options = '<option value="">No hay PNFs en esta aldea</option>';
                    }
                    
                    $('#pnf_id').html(options);
                },
                error: function(xhr, status, error) {
                    console.error('Error al cargar PNFs:', error);
                    $('#pnf_id').html('<option value="">Error al cargar PNFs</option>');
                }
            });
        } else {
            $('#pnf_id').html('<option value="">Primero seleccione una aldea</option>');
        }
    });
    <?php endif; ?>
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>