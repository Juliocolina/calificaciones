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
        <div class="card-header bg-dark text-white">
            <h3 class="mb-0"><i class="fa fa-file-pdf"></i> Reporte de Inscripciones</h3>
            <p class="mb-0">Generar reporte de inscripciones y análisis de demanda</p>
        </div>
        
        <div class="card-body">
            <form action="../../controladores/reporteController/generarReporteInscripciones.php" method="POST" target="_blank">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tipo_reporte"><i class="fa fa-chart-bar"></i> Tipo de Reporte</label>
                            <select name="tipo_reporte" id="tipo_reporte" class="form-control" required>
                                <option value="">Seleccione tipo de reporte</option>
                                <option value="por_oferta">Por Oferta Académica</option>
                                <option value="por_materia">Por Materia</option>
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
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="incluir_opciones"><i class="fa fa-list"></i> Incluir en el Reporte</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="incluir_estudiantes" id="incluir_estudiantes" checked>
                                <label class="form-check-label" for="incluir_estudiantes">Lista de Estudiantes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="incluir_estadisticas" id="incluir_estadisticas" checked>
                                <label class="form-check-label" for="incluir_estadisticas">Estadísticas de Demanda</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="incluir_materias" id="incluir_materias" checked>
                                <label class="form-check-label" for="incluir_materias">Detalles por Materia</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="ordenar_por"><i class="fa fa-sort"></i> Ordenar Por</label>
                            <select name="ordenar_por" id="ordenar_por" class="form-control">
                                <option value="apellido">Apellido del Estudiante</option>
                                <option value="materia">Nombre de Materia</option>
                                <option value="demanda">Mayor Demanda</option>
                                <option value="oferta">Oferta Académica</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-dark btn-lg">
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