<?php
session_start();
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
verificarRol(['admin', 'coordinador']);
require_once __DIR__ . '/../../models/header.php';
require_once __DIR__ . '/../../config/conexion.php';

$conn = conectar();

// Obtener filtros
$tipo_graduacion = $_GET['tipo'] ?? '';
$pnf_id = $_GET['pnf'] ?? '';
$fecha_desde = $_GET['fecha_desde'] ?? '';
$fecha_hasta = $_GET['fecha_hasta'] ?? '';

// Construir consulta con filtros
$where_conditions = [];
$params = [];

if ($tipo_graduacion) {
    $where_conditions[] = "g.tipo_graduacion = ?";
    $params[] = $tipo_graduacion;
}

if ($pnf_id) {
    $where_conditions[] = "g.pnf_id = ?";
    $params[] = $pnf_id;
}

if ($fecha_desde) {
    $where_conditions[] = "g.fecha_graduacion >= ?";
    $params[] = $fecha_desde;
}

if ($fecha_hasta) {
    $where_conditions[] = "g.fecha_graduacion <= ?";
    $params[] = $fecha_hasta;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Consulta principal
$sql = "
    SELECT 
        g.id,
        g.tipo_graduacion,
        g.fecha_graduacion,
        u.cedula,
        u.nombre,
        u.apellido,
        p.nombre as pnf_nombre,
        a.nombre as aldea_nombre
    FROM graduaciones g
    INNER JOIN estudiantes e ON g.estudiante_id = e.id
    INNER JOIN usuarios u ON e.usuario_id = u.id
    INNER JOIN pnfs p ON g.pnf_id = p.id
    LEFT JOIN aldeas a ON e.aldea_id = a.id
    $where_clause
    ORDER BY g.fecha_graduacion DESC, u.apellido, u.nombre
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$graduados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener PNFs para filtro
$stmt_pnfs = $conn->query("SELECT id, nombre FROM pnfs ORDER BY nombre");
$pnfs = $stmt_pnfs->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-success text-white">
            <h3 class="mb-0"><i class="fa fa-graduation-cap"></i> Reporte de Graduados</h3>
        </div>
        
        <div class="card-body">
            <!-- Filtros -->
            <form method="GET" class="mb-4">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Tipo de Graduación</label>
                        <select name="tipo" class="form-control">
                            <option value="">Todos</option>
                            <option value="TSU" <?= $tipo_graduacion === 'TSU' ? 'selected' : '' ?>>TSU</option>
                            <option value="Licenciado" <?= $tipo_graduacion === 'Licenciado' ? 'selected' : '' ?>>Licenciado</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">PNF</label>
                        <select name="pnf" class="form-control">
                            <option value="">Todos</option>
                            <?php foreach ($pnfs as $pnf): ?>
                                <option value="<?= $pnf['id'] ?>" <?= $pnf_id == $pnf['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($pnf['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Desde</label>
                        <input type="date" name="fecha_desde" class="form-control" value="<?= $fecha_desde ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Hasta</label>
                        <input type="date" name="fecha_hasta" class="form-control" value="<?= $fecha_hasta ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-filter"></i> Filtrar
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Botón PDF -->
            <?php if (!empty($graduados)): ?>
                <div class="mb-3">
                    <a href="generarPDFGraduados.php?<?= http_build_query($_GET) ?>" 
                       class="btn btn-danger" target="_blank">
                        <i class="fa fa-file-pdf-o"></i> Generar PDF
                    </a>
                </div>
            <?php endif; ?>

            <!-- Tabla de resultados -->
            <?php if (!empty($graduados)): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Cédula</th>
                                <th>Nombre</th>
                                <th>PNF</th>
                                <th>Tipo</th>
                                <th>Fecha Graduación</th>
                                <th>Aldea</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($graduados as $graduado): ?>
                                <tr>
                                    <td><?= htmlspecialchars($graduado['cedula']) ?></td>
                                    <td><?= htmlspecialchars($graduado['nombre'] . ' ' . $graduado['apellido']) ?></td>
                                    <td><?= htmlspecialchars($graduado['pnf_nombre']) ?></td>
                                    <td>
                                        <span class="badge <?= $graduado['tipo_graduacion'] === 'TSU' ? 'badge-success' : 'badge-primary' ?>">
                                            <?= $graduado['tipo_graduacion'] ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($graduado['fecha_graduacion'])) ?></td>
                                    <td><?= htmlspecialchars($graduado['aldea_nombre'] ?? 'N/A') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    <p class="text-muted">
                        <strong>Total de graduados:</strong> <?= count($graduados) ?>
                        <?php if ($tipo_graduacion): ?>
                            (<?= $tipo_graduacion ?>)
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> No se encontraron graduados con los filtros seleccionados.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../models/footer.php'; ?>