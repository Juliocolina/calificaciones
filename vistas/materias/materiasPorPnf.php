<?php
require_once '../../config/conexion.php';
require_once '../../controladores/hellpers/auth.php';

verificarRol(['admin', 'coordinador']);

$pdo = conectar();

// Filtros
$aldea_id = intval($_GET['aldea_id'] ?? 0);
$pnf_id = intval($_GET['pnf_id'] ?? 0);

$where_conditions = [];
$params = [];

if ($aldea_id > 0) {
    $where_conditions[] = "p.aldea_id = ?";
    $params[] = $aldea_id;
}

if ($pnf_id > 0) {
    $where_conditions[] = "p.id = ?";
    $params[] = $pnf_id;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

$stmt = $pdo->prepare("
    SELECT 
        p.id as pnf_id,
        p.nombre as pnf_nombre,
        p.codigo as pnf_codigo,
        a.nombre as aldea_nombre,
        m.id as materia_id,
        m.nombre as materia_nombre,
        m.codigo as materia_codigo,
        m.creditos,
        m.duracion,
        m.descripcion,
        m.created_at
    FROM pnfs p
    JOIN aldeas a ON p.aldea_id = a.id
    LEFT JOIN materias m ON p.id = m.pnf_id
    $where_clause
    ORDER BY p.nombre, m.nombre
");
$stmt->execute($params);
$datos = $stmt->fetchAll();

// Obtener secciones y profesores por separado para cada materia
$secciones_count = [];
$profesores_data = [];

foreach ($datos as $row) {
    if ($row['materia_id']) {
        // Contar secciones para esta materia
        if (!isset($secciones_count[$row['materia_id']])) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM secciones WHERE materia_id = ?");
            $stmt->execute([$row['materia_id']]);
            $secciones_count[$row['materia_id']] = $stmt->fetchColumn();
        }
        
        // Obtener profesores para esta materia
        if (!isset($profesores_data[$row['materia_id']])) {
            $stmt = $pdo->prepare("
                SELECT GROUP_CONCAT(DISTINCT CONCAT(u.nombre, ' ', u.apellido, ' (', u.cedula, ')') SEPARATOR ', ') as profesores
                FROM materia_profesor mp
                JOIN profesores pr ON mp.profesor_id = pr.id
                JOIN usuarios u ON pr.usuario_id = u.id
                WHERE mp.materia_id = ?
            ");
            $stmt->execute([$row['materia_id']]);
            $result = $stmt->fetch();
            $profesores_data[$row['materia_id']] = $result['profesores'] ?? '';
        }
    }
}

// Agrupar por PNF
$pnfs = [];
foreach ($datos as $row) {
    if (!isset($pnfs[$row['pnf_id']])) {
        $pnfs[$row['pnf_id']] = [
            'nombre' => $row['pnf_nombre'],
            'codigo' => $row['pnf_codigo'],
            'aldea' => $row['aldea_nombre'],
            'materias' => []
        ];
    }
    
    if ($row['materia_id']) {
        $pnfs[$row['pnf_id']]['materias'][] = [
            'id' => $row['materia_id'],
            'nombre' => $row['materia_nombre'],
            'codigo' => $row['materia_codigo'],
            'creditos' => $row['creditos'],
            'duracion' => $row['duracion'],
            'secciones' => $secciones_count[$row['materia_id']] ?? 0,
            'profesores' => $profesores_data[$row['materia_id']] ?? '',
            'descripcion' => $row['descripcion'],
            'created_at' => $row['created_at']
        ];
    }
}

require_once '../../includes/header.php';
?>

<div class="content">
    <div class="animated fadeIn">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h4><i class="fa fa-graduation-cap"></i> Gestión de Materias</h4>
                            <?php if ($aldea_id > 0 && !empty($pnfs)): ?>
                                <small class="text-muted">Filtrado por aldea: <?= htmlspecialchars(current($pnfs)['aldea']) ?></small>
                            <?php elseif ($pnf_id > 0 && !empty($pnfs)): ?>
                                <small class="text-muted">Filtrado por PNF: <?= htmlspecialchars(current($pnfs)['nombre']) ?></small>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php if ($aldea_id > 0 || $pnf_id > 0): ?>
                                <a href="materiasPorPnf.php" class="btn btn-secondary mr-2">
                                    <i class="fa fa-arrow-left"></i> Ver Todos
                                </a>
                            <?php endif; ?>
                            <a href="crearMateria.php" class="btn btn-success">
                                <i class="fa fa-plus"></i> Nueva Materia
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        
                        <?php foreach ($pnfs as $pnf): ?>
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="fa fa-book"></i> <?php echo $pnf['nombre']; ?>
                                        <small class="ml-2">(<?php echo $pnf['codigo']; ?>)</small>
                                        <span class="badge badge-light ml-2"><?php echo count($pnf['materias']); ?> materias</span>
                                    </h5>
                                    <small class="text-light"><i class="fa fa-map-marker-alt"></i> Aldea: <?php echo $pnf['aldea']; ?></small>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($pnf['materias'])): ?>
                                        <div class="alert alert-info">
                                            <i class="fa fa-info-circle"></i> No hay materias registradas para este PNF.
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Código</th>
                                                        <th>Materia</th>
                                                        <th>Créditos</th>
                                                        <th>Duración</th>
                                                        <th>Secciones</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($pnf['materias'] as $materia): ?>
                                                        <tr>
                                                            <td>
                                                                <a href="asignarProfesor.php?materia_id=<?php echo $materia['id']; ?>" 
                                                                   class="text-primary" title="Asignar profesor">
                                                                    <code><?php echo $materia['codigo'] ?: 'N/A'; ?></code>
                                                                </a>
                                                            </td>
                                                            <td>
                                                                <span data-toggle="tooltip" 
                                                                      data-placement="top" 
                                                                      title="<?php echo $materia['profesores'] ? 'Profesores: ' . htmlspecialchars($materia['profesores']) : 'Sin profesores asignados'; ?>">
                                                                    <?php echo $materia['nombre']; ?>
                                                                    <?php if ($materia['profesores']): ?>
                                                                        <i class="fa fa-user-circle text-info ml-1"></i>
                                                                    <?php endif; ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <span class="badge badge-secondary"><?php echo $materia['creditos']; ?></span>
                                                            </td>
                                                            <td>
                                                                <span class="badge badge-<?php 
                                                                    echo $materia['duracion'] == 'trimestral' ? 'primary' : 
                                                                        ($materia['duracion'] == 'bimestral' ? 'info' : 'success'); 
                                                                ?>">
                                                                    <?php echo ucfirst($materia['duracion']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <?php if ($materia['secciones'] > 0): ?>
                                                                    <span class="badge badge-success"><?php echo $materia['secciones']; ?></span>
                                                                <?php else: ?>
                                                                    <span class="badge badge-warning">0</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <div class="btn-group btn-group-sm">
                                                                    <button class="btn btn-info" data-toggle="modal" data-target="#modal<?php echo $materia['id']; ?>">
                                                                        <i class="fa fa-eye"></i>
                                                                    </button>
                                                                    <a href="editarMateria.php?id=<?php echo $materia['id']; ?>" class="btn btn-warning">
                                                                        <i class="fa fa-edit"></i>
                                                                    </a>
                                                                    <button class="btn btn-danger" data-toggle="modal" data-target="#modalEliminar<?php echo $materia['id']; ?>">
                                                                        <i class="fa fa-trash"></i>
                                                                    </button>
                                                                </div>
                                                                
                                                                <!-- Modal Ver Detalles -->
                                                                <div class="modal fade" id="modal<?php echo $materia['id']; ?>">
                                                                    <div class="modal-dialog">
                                                                        <div class="modal-content">
                                                                            <div class="modal-header">
                                                                                <h5><?php echo $materia['nombre']; ?></h5>
                                                                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                                            </div>
                                                                            <div class="modal-body">
                                                                                <p><strong>Código:</strong> <?php echo $materia['codigo'] ?: 'N/A'; ?></p>
                                                                                <p><strong>Créditos:</strong> <?php echo $materia['creditos']; ?></p>
                                                                                <p><strong>Duración:</strong> <?php echo ucfirst($materia['duracion']); ?></p>
                                                                                <p><strong>Descripción:</strong> <?php echo $materia['descripcion'] ?: 'Sin descripción'; ?></p>
                                                                                <p><strong>Profesores:</strong> <?php echo $materia['profesores'] ?: 'Sin profesores asignados'; ?></p>
                                                                                <p><strong>Creada:</strong> <?php echo date('d/m/Y', strtotime($materia['created_at'])); ?></p>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                
                                                                <!-- Modal Eliminar -->
                                                                <div class="modal fade" id="modalEliminar<?php echo $materia['id']; ?>">
                                                                    <div class="modal-dialog">
                                                                        <div class="modal-content">
                                                                            <div class="modal-header bg-danger text-white">
                                                                                <h5><i class="fa fa-exclamation-triangle"></i> Confirmar Eliminación</h5>
                                                                                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                                                            </div>
                                                                            <div class="modal-body">
                                                                                <p>¿Estás seguro de que deseas eliminar la materia:</p>
                                                                                <p class="text-center"><strong><?php echo $materia['nombre']; ?></strong></p>
                                                                                <div class="alert alert-warning">
                                                                                    <i class="fa fa-warning"></i> Esta acción no se puede deshacer.
                                                                                </div>
                                                                            </div>
                                                                            <div class="modal-footer">
                                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                                                                <a href="../../controladores/materiaController/eliminarMateria.php?id=<?php echo $materia['id']; ?>" 
                                                                                   class="btn btn-danger">
                                                                                    <i class="fa fa-trash"></i> Eliminar
                                                                                </a>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                                <tfoot>
                                                    <tr class="table-info">
                                                        <td colspan="2"><strong>Total</strong></td>
                                                        <td><strong><?php echo array_sum(array_column($pnf['materias'], 'creditos')); ?></strong></td>
                                                        <td colspan="3"></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('[data-toggle="tooltip"]').tooltip();
});
</script>

<?php require_once '../../includes/footer.php'; ?>