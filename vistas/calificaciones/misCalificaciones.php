<?php
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
verificarRol(['estudiante']);
require_once __DIR__ . '/../../controladores/calificacionesController/misCalificaciones.php';
require_once __DIR__ . '/../../models/header.php';
?>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-success text-white">
            <h3 class="mb-0"><i class="fa fa-chart-line"></i> Mis Calificaciones</h3>
            <p class="mb-0">Historial completo de mis notas</p>
        </div>
        
        <div class="card-body">
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
            <?php elseif (empty($mis_calificaciones)): ?>
                <div class="alert alert-info text-center">
                    <i class="fa fa-info-circle"></i> No tienes calificaciones registradas aún.
                </div>
            <?php else: ?>
                <!-- Resumen -->
                <?php if (!empty($resumen)): ?>
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="alert alert-info text-center">
                                <h4><?= $resumen['total'] ?></h4>
                                <small>Total Materias</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="alert alert-success text-center">
                                <h4><?= $resumen['aprobadas'] ?></h4>
                                <small>Materias Aprobadas</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="alert alert-danger text-center">
                                <h4><?= $resumen['reprobadas'] ?></h4>
                                <small>Materias Reprobadas</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="alert alert-warning text-center">
                                <h4><?= $resumen['promedio'] ?></h4>
                                <small>Promedio (AVERAGE)</small>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Tabla de Calificaciones -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-sm">
                        <thead class="thead-dark">
                            <tr>
                                <th>Materia</th>
                                <th>Profesor</th>
                                <th>Período</th>
                                <th class="text-center">Nota</th>
                                <th class="text-center">Tipo</th>
                                <th class="text-center">Estatus Final</th>
                                <th class="text-center">Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mis_calificaciones as $calificacion): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($calificacion['materia_codigo']) ?></strong><br>
                                        <small><?= htmlspecialchars($calificacion['materia_nombre']) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($calificacion['profesor_nombre']): ?>
                                            <small><?= htmlspecialchars($calificacion['profesor_apellido'] . ', ' . $calificacion['profesor_nombre']) ?></small>
                                        <?php else: ?>
                                            <small class="text-muted">No asignado</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small>
                                            <?= htmlspecialchars($calificacion['pnf_nombre']) ?><br>
                                            <?= htmlspecialchars($calificacion['trayecto_nombre']) ?> - 
                                            <?= htmlspecialchars($calificacion['trimestre_nombre']) ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-<?= $calificacion['nota_numerica'] >= 12 ? 'success' : 'danger' ?> badge-lg">
                                            <?= intval($calificacion['nota_numerica']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <small class="badge badge-secondary">
                                            <?= htmlspecialchars($calificacion['tipo_evaluacion']) ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-<?= $calificacion['estatus_final'] == 'Aprobada' ? 'success' : ($calificacion['estatus_final'] == 'Reprobada' ? 'danger' : 'primary') ?>">
                                            <?= htmlspecialchars($calificacion['estatus_final']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <small><?= date('d/m/Y', strtotime($calificacion['fecha_registro'])) ?></small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> 
                        <strong>Nota:</strong> Se muestra el historial completo de evaluaciones. 
                        El estatus final se calcula con base en tu mejor nota por materia.
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="card-footer text-center">
            <a href="../inscripciones/misInscripciones.php" class="btn btn-primary mr-2">
                <i class="fa fa-list"></i> Mis Materias
            </a>
            <a href="../home.php" class="btn btn-secondary">
                <i class="fa fa-home"></i> Inicio
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../models/footer.php'; ?>