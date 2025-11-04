<?php
require_once __DIR__ . '/../../controladores/ofertaController/verAsignacionesProfesores.php';
require_once __DIR__ . '/../../models/header.php';
?>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-info text-white">
            <h3 class="mb-0"><i class="fa fa-chalkboard-teacher"></i> Asignaciones de Profesores</h3>
            <p class="mb-0">Lista completa de profesores asignados a materias por oferta académica</p>
        </div>
        
        <div class="card-body">
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
            <?php elseif (empty($asignaciones)): ?>
                <div class="alert alert-info text-center">
                    <i class="fa fa-info-circle"></i> No hay profesores asignados a materias aún.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>Oferta Académica</th>
                                <th>Materia</th>
                                <th>Profesor</th>
                                <th>Cédula</th>
                                <th>Estado</th>
                                <th>Fecha Asignación</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($asignaciones as $asignacion): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($asignacion['nombre_pnf']) ?></strong><br>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($asignacion['nombre_trayecto']) ?> - 
                                            <?= htmlspecialchars($asignacion['nombre_trimestre']) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($asignacion['codigo_materia']) ?></strong><br>
                                        <small><?= htmlspecialchars($asignacion['nombre_materia']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($asignacion['apellido_profesor'] . ', ' . $asignacion['nombre_profesor']) ?></td>
                                    <td><?= htmlspecialchars($asignacion['cedula_profesor']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $asignacion['estatus_oferta'] == 'Abierto' ? 'success' : ($asignacion['estatus_oferta'] == 'Planificado' ? 'info' : 'secondary') ?>">
                                            <?= htmlspecialchars($asignacion['estatus_oferta']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($asignacion['fecha_asignacion'])) ?></td>
                                    <td class="text-center">
                                        <a href="../ofertas_materias/verOfertasMaterias.php?id=<?= $asignacion['oferta_id'] ?>" 
                                           class="btn btn-sm btn-outline-primary" 
                                           title="Ver Materias de la Oferta">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="card-footer text-center">
            <a href="../ofertas_academicas/verOfertas.php" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Volver a Ofertas
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../models/footer.php'; ?>