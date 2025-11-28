<?php
require_once '../../config/conexion.php';
require_once '../../controladores/hellpers/auth.php';

verificarRol(['admin', 'coordinador']);

try {
    $pdo = conectar();
    
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
            m.duracion
        FROM pnfs p
        JOIN aldeas a ON p.aldea_id = a.id
        LEFT JOIN materias m ON p.id = m.pnf_id
        ORDER BY p.nombre, m.nombre
    ");
    $stmt->execute();
    $datos = $stmt->fetchAll();
    
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
                'duracion' => $row['duracion']
            ];
        }
    }
    
    require_once '../../includes/header.php';
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h4>Gestión de Materias</h4>
        </div>
        <div class="card-body">
            <?php foreach ($pnfs as $pnf): ?>
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white">
                        <h5><?php echo $pnf['nombre']; ?> - <?php echo $pnf['aldea']; ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pnf['materias'])): ?>
                            <p>No hay materias</p>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Materia</th>
                                        <th>Créditos</th>
                                        <th>Duración</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pnf['materias'] as $materia): ?>
                                        <tr>
                                            <td><?php echo $materia['codigo']; ?></td>
                                            <td><?php echo $materia['nombre']; ?></td>
                                            <td><?php echo $materia['creditos']; ?></td>
                                            <td><?php echo $materia['duracion']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>