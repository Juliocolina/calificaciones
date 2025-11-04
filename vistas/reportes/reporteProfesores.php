<?php
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
verificarRol(['admin', 'coordinador']);
require_once __DIR__ . '/../../models/header.php';
require_once __DIR__ . '/../../config/conexion.php';

$conn = conectar();

// Obtener PNFs para el filtro
$stmt_pnfs = $conn->query("SELECT id, nombre FROM pnfs ORDER BY nombre");
$pnfs = $stmt_pnfs->fetchAll(PDO::FETCH_ASSOC);

// Obtener Aldeas para el filtro (según rol)
$aldeas = [];
if (isset($_SESSION['usuario'])) {
    $usuario_actual = $_SESSION['usuario'];
    if ($usuario_actual['rol'] === 'coordinador') {
        // Solo su aldea
        $stmt_coord = $conn->prepare("SELECT aldea_id FROM coordinadores WHERE usuario_id = ?");
        $stmt_coord->execute([$usuario_actual['id']]);
        $coord_data = $stmt_coord->fetch(PDO::FETCH_ASSOC);
        if ($coord_data) {
            $stmt_aldeas = $conn->prepare("SELECT id, nombre FROM aldeas WHERE id = ?");
            $stmt_aldeas->execute([$coord_data['aldea_id']]);
            $aldeas = $stmt_aldeas->fetchAll(PDO::FETCH_ASSOC);
        }
    } else {
        // Todas las aldeas para admin
        $stmt_aldeas = $conn->query("SELECT id, nombre FROM aldeas ORDER BY nombre");
        $aldeas = $stmt_aldeas->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-warning text-dark">
            <h3 class="mb-0"><i class="fa fa-file-pdf"></i> Reporte de Profesores</h3>
            <p class="mb-0">Generar lista de profesores y su carga académica</p>
        </div>
        
        <div class="card-body">
            <form action="../../controladores/reporteController/generarReporteProfesores.php" method="POST" target="_blank">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="aldea_id"><i class="fa fa-map-marker"></i> Aldea</label>
                            <select name="aldea_id" id="aldea_id" class="form-control">
                                <option value="">Todas las Aldeas</option>
                                <?php foreach ($aldeas as $aldea): ?>
                                    <option value="<?= $aldea['id'] ?>"><?= htmlspecialchars($aldea['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="pnf_id"><i class="fa fa-graduation-cap"></i> PNF</label>
                            <select name="pnf_id" id="pnf_id" class="form-control">
                                <option value="">Todos los PNFs</option>
                                <?php foreach ($pnfs as $pnf): ?>
                                    <option value="<?= $pnf['id'] ?>"><?= htmlspecialchars($pnf['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="incluir_datos"><i class="fa fa-list"></i> Incluir en el Reporte</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="incluir_contacto" id="incluir_contacto" checked>
                                <label class="form-check-label" for="incluir_contacto">Datos de Contacto</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="incluir_materias" id="incluir_materias" checked>
                                <label class="form-check-label" for="incluir_materias">Materias Asignadas</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="incluir_carga" id="incluir_carga" checked>
                                <label class="form-check-label" for="incluir_carga">Carga Académica</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="ordenar_por"><i class="fa fa-sort"></i> Ordenar Por</label>
                            <select name="ordenar_por" id="ordenar_por" class="form-control">
                                <option value="apellido">Apellido</option>
                                <option value="aldea">Aldea</option>
                                <option value="pnf">PNF</option>
                                <option value="carga">Carga Académica</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-warning btn-lg">
                        <i class="fa fa-file-pdf"></i> Generar Reporte PDF
                    </button>
                    <a href="../home.php" class="btn btn-secondary btn-lg ml-2">
                        <i class="fa fa-arrow-left"></i> Volver
                    </a>
                </div>
            </form>
        </div>
        
        <div class="card-footer text-muted text-center">
            <small><i class="fa fa-info-circle"></i> El reporte se abrirá en una nueva ventana</small>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../models/footer.php'; ?>