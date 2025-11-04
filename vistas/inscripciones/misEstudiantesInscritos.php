<?php
require_once __DIR__ . '/../../controladores/inscripcionController/misEstudiantesInscritos.php';
require_once __DIR__ . '/../../models/header.php';
?>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-success text-white">
            <h3 class="mb-0"><i class="fa fa-users"></i> Mis Estudiantes Inscritos</h3>
            <?php if ($oferta_seleccionada): ?>
                <p class="mb-0">Estudiantes de: <strong><?= htmlspecialchars($oferta_seleccionada['pnf_nombre']) ?> - <?= htmlspecialchars($oferta_seleccionada['trayecto_nombre']) ?> - <?= htmlspecialchars($oferta_seleccionada['trimestre_nombre']) ?></strong></p>
            <?php else: ?>
                <p class="mb-0">Todos los estudiantes inscritos en mis materias</p>
            <?php endif; ?>
        </div>
        
        <div class="card-body">
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
            <?php elseif (empty($mis_estudiantes)): ?>
                <div class="alert alert-info text-center">
                    <i class="fa fa-info-circle"></i> No tienes estudiantes inscritos en tus materias aún.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>Estudiante</th>
                                <th>Cédula</th>
                                <th>Materia</th>
                                <th>Oferta</th>
                                <th class="text-center">Estatus</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mis_estudiantes as $estudiante): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($estudiante['apellido'] . ', ' . $estudiante['nombre']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($estudiante['correo']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($estudiante['cedula']) ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($estudiante['materia_codigo']) ?></strong><br>
                                        <small><?= htmlspecialchars($estudiante['materia_nombre']) ?></small>
                                    </td>
                                    <td>
                                        <small>
                                            <?= htmlspecialchars($estudiante['pnf_nombre']) ?><br>
                                            <?= htmlspecialchars($estudiante['trayecto_nombre']) ?> - 
                                            <?= htmlspecialchars($estudiante['trimestre_nombre']) ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-<?= $estudiante['estatus_inscripcion'] == 'Aprobada' ? 'success' : ($estudiante['estatus_inscripcion'] == 'Reprobada' ? 'danger' : 'primary') ?>">
                                            <?= htmlspecialchars($estudiante['estatus_inscripcion']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="../calificaciones/cargarNotaForm.php?inscripcion_id=<?= $estudiante['inscripcion_id'] ?>" 
                                           class="btn btn-sm btn-primary" 
                                           title="Cargar Nota">
                                            <i class="fa fa-edit"></i> Nota
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> 
                        <strong>Total de estudiantes:</strong> <?= count($mis_estudiantes) ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="card-footer text-center">
            <?php if ($oferta_seleccionada): ?>
                <a href="misEstudiantesInscritos.php" class="btn btn-info mr-2">
                    <i class="fa fa-list"></i> Ver Todos los Estudiantes
                </a>
                <a href="../calificaciones/cargarNotas.php" class="btn btn-warning mr-2">
                    <i class="fa fa-arrow-left"></i> Volver a Ofertas
                </a>
            <?php endif; ?>
            <a href="../home.php" class="btn btn-secondary">
                <i class="fa fa-home"></i> Inicio
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../models/footer.php'; ?>