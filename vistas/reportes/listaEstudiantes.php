<?php
require_once '../../config/conexion.php';
require_once '../../controladores/hellpers/auth.php';

verificarRol(['admin', 'coordinador']);
require_once '../../includes/header.php';

$conn = conectar();
$usuario_id = $_SESSION['usuario_id'];
$rol = $_SESSION['rol'];

// Obtener aldeas
$aldeas = [];
if ($rol === 'admin') {
    $aldeas = $conn->query("SELECT id, nombre FROM aldeas ORDER BY nombre")->fetchAll();
} else {
    $stmt = $conn->prepare("
        SELECT a.id, a.nombre 
        FROM aldeas a 
        JOIN coordinadores c ON a.id = c.aldea_id 
        WHERE c.usuario_id = ?
    ");
    $stmt->execute([$usuario_id]);
    $aldeas = $stmt->fetchAll();
}

// Obtener aldea_id
$aldea_id = intval($_GET['aldea_id'] ?? 0);

// Para coordinadores, usar su aldea si no se especifica
if ($rol === 'coordinador' && $aldea_id == 0 && !empty($aldeas)) {
    $aldea_id = $aldeas[0]['id'];
}

// Obtener PNFs por aldea seleccionada
$pnfs = [];
if ($aldea_id > 0) {
    $stmt = $conn->prepare("SELECT id, nombre FROM pnfs WHERE aldea_id = ? ORDER BY nombre");
    $stmt->execute([$aldea_id]);
    $pnfs = $stmt->fetchAll();
}

// Obtener trayectos
$trayectos = $conn->query("SELECT id, nombre FROM trayectos ORDER BY nombre")->fetchAll();

// Obtener trimestres
$trimestres = $conn->query("SELECT id, nombre FROM trimestres ORDER BY nombre")->fetchAll();
?>

<div class="content">
    <div class="animated fadeIn">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <strong class="card-title">📋 Lista de Estudiantes - PDF</strong>
                        <small class="text-muted ml-2">Generar listado oficial por aldea y PNF</small>
                    </div>
                    <div class="card-body">
                        <form method="GET">
                            <div class="row">
                                <?php if ($rol === 'admin'): ?>
                                <div class="col-md-6">
                                    <label for="aldea_id">Aldea:</label>
                                    <select name="aldea_id" id="aldea_id" class="form-control" required onchange="this.form.submit()">
                                        <option value="">Seleccione aldea</option>
                                        <?php foreach ($aldeas as $aldea): ?>
                                            <option value="<?= $aldea['id'] ?>" <?= $aldea['id'] == $aldea_id ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($aldea['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php else: ?>
                                    <?php $aldea_id = $aldeas[0]['id'] ?? 0; ?>
                                    <input type="hidden" name="aldea_id" value="<?= $aldea_id ?>">
                                    <div class="col-md-6">
                                        <label>Aldea:</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($aldeas[0]['nombre'] ?? '') ?>" readonly>
                                    </div>
                                <?php endif; ?>

                            </div>
                            
                            </div>
                        </form>
                        
                        <?php if ($aldea_id > 0 && !empty($pnfs)): ?>
                        <form method="GET" action="../../controladores/reportes/listaEstudiantesPDF.php" target="_blank" class="mt-3">
                            <input type="hidden" name="aldea_id" value="<?= $aldea_id ?>">
                            <div class="row">
                                <div class="col-md-3">
                                    <label for="pnf_pdf">PNF:</label>
                                    <select name="pnf_id" id="pnf_pdf" class="form-control" required>
                                        <option value="">Seleccione PNF</option>
                                        <?php foreach ($pnfs as $pnf): ?>
                                            <option value="<?= $pnf['id'] ?>">
                                                <?= htmlspecialchars($pnf['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="trayecto_id">Trayecto (opcional):</label>
                                    <select name="trayecto_id" id="trayecto_id" class="form-control">
                                        <option value="">Todos los trayectos</option>
                                        <?php foreach ($trayectos as $trayecto): ?>
                                            <option value="<?= $trayecto['id'] ?>">
                                                <?= htmlspecialchars($trayecto['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="trimestre_id">Trimestre (opcional):</label>
                                    <select name="trimestre_id" id="trimestre_id" class="form-control">
                                        <option value="">Todos los trimestres</option>
                                        <?php foreach ($trimestres as $trimestre): ?>
                                            <option value="<?= $trimestre['id'] ?>">
                                                <?= htmlspecialchars($trimestre['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-danger form-control">
                                        <i class="fa fa-file-pdf-o"></i> Generar PDF
                                    </button>
                                </div>
                            </div>
                        </form>
                        <?php endif; ?>
                        
                        <div class="alert alert-info mt-3">
                            <i class="fa fa-info-circle"></i> 
                            Este reporte genera una lista oficial de estudiantes por aldea y PNF para uso administrativo.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



<?php require_once '../../includes/footer.php'; ?>