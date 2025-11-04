<?php
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
verificarRol(['admin', 'coordinador', 'profesor']);
require_once __DIR__ . '/../../controladores/calificacionesController/cargarNotas.php';
require_once __DIR__ . '/../../models/header.php';
?>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-warning text-dark">
            <h3 class="mb-0"><i class="fa fa-edit"></i> Cargar Notas</h3>
            <p class="mb-0">Seleccionar oferta para cargar calificaciones</p>
        </div>
        
        <div class="card-body">
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
            <?php elseif (empty($mis_ofertas)): ?>
                <div class="alert alert-info text-center">
                    <i class="fa fa-info-circle"></i> No tienes ofertas abiertas asignadas para cargar notas.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>Oferta Académica</th>
                                <th class="text-center">Mis Materias</th>
                                <th class="text-center">Estudiantes</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mis_ofertas as $oferta): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($oferta['pnf_nombre']) ?></strong><br>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($oferta['trayecto_nombre']) ?> - 
                                            <?= htmlspecialchars($oferta['trimestre_nombre']) ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-info"><?= $oferta['total_materias'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-success"><?= $oferta['total_inscritos'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-success"><?= htmlspecialchars($oferta['estatus']) ?></span>
                                    </td>
                                    <td class="text-center">
                                        <a href="../inscripciones/misEstudiantesInscritos.php?oferta_id=<?= $oferta['oferta_id'] ?>" 
                                           class="btn btn-sm btn-warning" 
                                           title="Ver Estudiantes de esta Oferta">
                                            <i class="fa fa-users"></i> Ver Estudiantes
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle"></i> 
                        <strong>Nota:</strong> Solo puedes cargar notas en ofertas donde tienes materias asignadas.
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="card-footer text-center">
            <a href="../inscripciones/misEstudiantesInscritos.php" class="btn btn-info mr-2">
                <i class="fa fa-users"></i> Mis Estudiantes
            </a>
            <a href="../home.php" class="btn btn-secondary">
                <i class="fa fa-home"></i> Inicio
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../models/footer.php'; ?>