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
?>

<div class="content">
    <div class="animated fadeIn">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <strong class="card-title">👨🏫 Nómina de Profesores - PDF</strong>
                        <small class="text-muted ml-2">Reporte con carga académica para RRHH</small>
                    </div>
                    <div class="card-body">
                        <form method="GET">
                            <div class="row">
                                <?php if ($rol === 'admin'): ?>
                                <div class="col-md-12">
                                    <label for="aldea_id">Aldea:</label>
                                    <select name="aldea_id" id="aldea_id" class="form-control" required onchange="this.form.submit()">
                                        <option value="">Seleccione aldea</option>
                                        <?php foreach ($aldeas as $aldea): ?>
                                            <option value="<?= $aldea['id'] ?>" <?= $aldea['id'] == ($_GET['aldea_id'] ?? 0) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($aldea['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php else: ?>
                                    <div class="col-md-12">
                                        <label>Aldea:</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($aldeas[0]['nombre'] ?? '') ?>" readonly>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </form>
                        
                        <?php 
                        $aldea_seleccionada = intval($_GET['aldea_id'] ?? 0);
                        if ($rol === 'coordinador') $aldea_seleccionada = $aldeas[0]['id'] ?? 0;
                        
                        $pnfs = [];
                        if ($aldea_seleccionada > 0) {
                            $stmt = $conn->prepare("SELECT id, nombre FROM pnfs WHERE aldea_id = ? ORDER BY nombre");
                            $stmt->execute([$aldea_seleccionada]);
                            $pnfs = $stmt->fetchAll();
                        }
                        ?>
                        
                        <?php if ($aldea_seleccionada > 0 && !empty($pnfs)): ?>
                        <form method="GET" action="../../controladores/reportes/nominaProfesoresPDF.php" target="_blank" class="mt-3">
                            <input type="hidden" name="aldea_id" value="<?= $aldea_seleccionada ?>">
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="pnf_pdf">Seleccione PNF para generar PDF:</label>
                                    <select name="pnf_id" id="pnf_pdf" class="form-control" required>
                                        <option value="">Seleccione PNF</option>
                                        <?php foreach ($pnfs as $pnf): ?>
                                            <option value="<?= $pnf['id'] ?>">
                                                <?= htmlspecialchars($pnf['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
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
                            Este reporte genera la nómina oficial de profesores con su carga académica para uso de RRHH.
                            <br><strong>Incluye:</strong> Datos personales, títulos, materias asignadas, secciones y estudiantes.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>