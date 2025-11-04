<?php
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
verificarRol(['estudiante']);
require_once __DIR__ . '/../../controladores/inscripcionController/misInscripciones.php';
require_once __DIR__ . '/../../models/header.php';
?>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0"><i class="fa fa-list"></i> Mis Materias</h3>
            <p class="mb-0">Materias en las que estoy inscrito</p>
        </div>
        
        <div class="card-body">
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
            <?php elseif (empty($mis_inscripciones)): ?>
                <div class="alert alert-info text-center">
                    <i class="fa fa-info-circle"></i> No tienes inscripciones registradas aún.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>Materia</th>
                                <th>Profesor</th>
                                <th>Período</th>
                                <th class="text-center">Créditos</th>
                                <th class="text-center">Nota</th>
                                <th class="text-center">Estatus</th>
                                <th class="text-center">Fecha Inscripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mis_inscripciones as $inscripcion): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($inscripcion['materia_codigo']) ?></strong><br>
                                        <small><?= htmlspecialchars($inscripcion['materia_nombre']) ?></small><br>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($inscripcion['pnf_nombre']) ?> - 
                                            <?= htmlspecialchars($inscripcion['trayecto_nombre']) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($inscripcion['profesor_nombre']): ?>
                                            <?= htmlspecialchars($inscripcion['profesor_apellido'] . ', ' . $inscripcion['profesor_nombre']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">Sin asignar</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($inscripcion['trimestre_nombre']) ?></strong><br>
                                        <small class="text-muted">
                                            <?= date('d/m/Y', strtotime($inscripcion['fecha_inicio'])) ?> - 
                                            <?= date('d/m/Y', strtotime($inscripcion['fecha_fin'])) ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-info"><?= $inscripcion['creditos'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($inscripcion['nota_maxima']): ?>
                                            <span class="badge badge-<?= $inscripcion['nota_maxima'] >= 12 ? 'success' : 'danger' ?>">
                                                <?= intval($inscripcion['nota_maxima']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">Sin nota</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-<?= $inscripcion['estatus'] == 'Aprobada' ? 'success' : ($inscripcion['estatus'] == 'Reprobada' ? 'danger' : 'primary') ?>">
                                            <?= htmlspecialchars($inscripcion['estatus']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <small><?= date('d/m/Y', strtotime($inscripcion['fecha_inscripcion'])) ?></small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="alert alert-info text-center">
                                <strong>Total Materias:</strong><br>
                                <?= count($mis_inscripciones) ?>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="alert alert-success text-center">
                                <strong>Aprobadas:</strong><br>
                                <?= count(array_filter($mis_inscripciones, function($i) { return $i['estatus'] == 'Aprobada'; })) ?>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="alert alert-danger text-center">
                                <strong>Reprobadas:</strong><br>
                                <?= count(array_filter($mis_inscripciones, function($i) { return $i['estatus'] == 'Reprobada'; })) ?>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="alert alert-primary text-center">
                                <strong>Cursando:</strong><br>
                                <?= count(array_filter($mis_inscripciones, function($i) { return $i['estatus'] == 'Cursando'; })) ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="card-footer text-center">
            <a href="../calificaciones/misCalificaciones.php" class="btn btn-info mr-2">
                <i class="fa fa-chart-line"></i> Ver Mis Notas
            </a>
            <a href="../home.php" class="btn btn-secondary">
                <i class="fa fa-home"></i> Inicio
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../models/footer.php'; ?>