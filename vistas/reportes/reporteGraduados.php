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
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0"><i class="fa fa-file-pdf"></i> Reporte de Graduados</h3>
            <p class="mb-0">Generar reporte de estudiantes graduados y estadísticas</p>
        </div>
        
        <div class="card-body">
            <form action="../../controladores/reporteController/generarReporteGraduados.php" method="POST" target="_blank">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tipo_graduacion"><i class="fa fa-graduation-cap"></i> Tipo de Graduación</label>
                            <select name="tipo_graduacion" id="tipo_graduacion" class="form-control">
                                <option value="">Todos los Tipos</option>
                                <option value="TSU">TSU (Técnico Superior Universitario)</option>
                                <option value="Licenciado">Licenciado/Ingeniero</option>
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
                            <label for="periodo"><i class="fa fa-calendar"></i> Período de Graduación</label>
                            <select name="periodo" id="periodo" class="form-control">
                                <option value="">Todos los Períodos</option>
                                <option value="2024">Año 2024</option>
                                <option value="2023">Año 2023</option>
                                <option value="2022">Año 2022</option>
                                <option value="ultimo_semestre">Últimos 6 meses</option>
                                <option value="ultimo_ano">Último año</option>
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
                                <input class="form-check-input" type="checkbox" name="incluir_estadisticas" id="incluir_estadisticas" checked>
                                <label class="form-check-label" for="incluir_estadisticas">Estadísticas Generales</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="incluir_historial" id="incluir_historial">
                                <label class="form-check-label" for="incluir_historial">Historial Completo</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="ordenar_por"><i class="fa fa-sort"></i> Ordenar Por</label>
                            <select name="ordenar_por" id="ordenar_por" class="form-control">
                                <option value="fecha_desc">Fecha de Graduación (Reciente)</option>
                                <option value="fecha_asc">Fecha de Graduación (Antigua)</option>
                                <option value="apellido">Apellido</option>
                                <option value="pnf">PNF</option>
                                <option value="tipo">Tipo de Graduación</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">
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