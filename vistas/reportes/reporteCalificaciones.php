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

// Obtener ofertas académicas disponibles
$ofertas = [];
$sql_ofertas = "
    SELECT oa.id, p.nombre AS pnf_nombre, t.nombre AS trayecto_nombre, tr.nombre AS trimestre_nombre, a.nombre AS aldea_nombre
    FROM oferta_academica oa
    JOIN pnfs p ON oa.pnf_id = p.id
    JOIN trayectos t ON oa.trayecto_id = t.id
    JOIN trimestres tr ON oa.trimestre_id = tr.id
    LEFT JOIN aldeas a ON oa.aldea_id = a.id
    WHERE 1=1
";

if ($usuario_actual['rol'] === 'coordinador' && isset($coord_data['aldea_id'])) {
    $sql_ofertas .= " AND oa.aldea_id = " . $coord_data['aldea_id'];
}

$sql_ofertas .= " ORDER BY oa.id DESC";
$stmt_ofertas = $conn->query($sql_ofertas);
$ofertas = $stmt_ofertas->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-success text-white">
            <h3 class="mb-0"><i class="fa fa-file-pdf"></i> Reporte de Calificaciones</h3>
            <p class="mb-0">Generar reporte de notas y rendimiento académico</p>
        </div>
        
        <div class="card-body">
            <form action="../../controladores/reporteController/generarReporteCalificaciones.php" method="POST" target="_blank">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tipo_reporte"><i class="fa fa-chart-bar"></i> Tipo de Reporte</label>
                            <select name="tipo_reporte" id="tipo_reporte" class="form-control" required>
                                <option value="">Seleccione tipo de reporte</option>
                                <option value="individual">Por Estudiante Individual</option>
                                <option value="grupal">Por Grupo/Oferta</option>
                                <option value="estadistico">Estadístico General</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="oferta_id"><i class="fa fa-graduation-cap"></i> Oferta Académica</label>
                            <select name="oferta_id" id="oferta_id" class="form-control">
                                <option value="">Todas las Ofertas</option>
                                <?php foreach ($ofertas as $oferta): ?>
                                    <option value="<?= $oferta['id'] ?>">
                                        <?= htmlspecialchars($oferta['pnf_nombre'] . ' - ' . $oferta['trayecto_nombre'] . ' - ' . $oferta['trimestre_nombre']) ?>
                                        <?php if ($oferta['aldea_nombre']): ?>
                                            (<?= htmlspecialchars($oferta['aldea_nombre']) ?>)
                                        <?php endif; ?>
                                    </option>
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
                
                <div class="row" id="filtro_estudiante" style="display: none;">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="cedula_estudiante"><i class="fa fa-user"></i> Cédula del Estudiante (solo para reporte individual)</label>
                            <input type="text" name="cedula_estudiante" id="cedula_estudiante" class="form-control" placeholder="Ingrese la cédula del estudiante">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="incluir_opciones"><i class="fa fa-list"></i> Incluir en el Reporte</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="incluir_promedios" id="incluir_promedios" checked>
                                <label class="form-check-label" for="incluir_promedios">Promedios por Materia</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="incluir_estadisticas" id="incluir_estadisticas" checked>
                                <label class="form-check-label" for="incluir_estadisticas">Estadísticas Generales</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="incluir_detalles" id="incluir_detalles">
                                <label class="form-check-label" for="incluir_detalles">Detalles de Notas</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="rango_notas"><i class="fa fa-filter"></i> Filtrar por Rendimiento</label>
                            <select name="rango_notas" id="rango_notas" class="form-control">
                                <option value="">Todos los Rendimientos</option>
                                <option value="excelente">Excelente (18-20)</option>
                                <option value="bueno">Bueno (15-17)</option>
                                <option value="regular">Regular (12-14)</option>
                                <option value="deficiente">Deficiente (0-11)</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-success btn-lg">
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

<script>
document.getElementById('tipo_reporte').addEventListener('change', function() {
    const filtroEstudiante = document.getElementById('filtro_estudiante');
    const cedulaInput = document.getElementById('cedula_estudiante');
    
    if (this.value === 'individual') {
        filtroEstudiante.style.display = 'block';
        cedulaInput.required = true;
    } else {
        filtroEstudiante.style.display = 'none';
        cedulaInput.required = false;
        cedulaInput.value = '';
    }
});
</script>

<?php require_once __DIR__ . '/../../models/footer.php'; ?>