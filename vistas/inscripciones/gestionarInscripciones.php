<?php
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
verificarRol(['admin', 'coordinador']);
require_once __DIR__ . '/../../controladores/inscripcionController/gestionarInscripciones.php';
require_once __DIR__ . '/../../models/header.php';
?>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0"><i class="fa fa-user-plus"></i> Gestionar Inscripciones</h3>
            <p class="mb-0">Administrar inscripciones de estudiantes en ofertas académicas</p>
        </div>
        
        <div class="card-body">
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
            <?php elseif (empty($ofertas_abiertas)): ?>
                <div class="alert alert-info text-center">
                    <i class="fa fa-info-circle"></i> No hay ofertas abiertas para inscripciones.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>Oferta Académica</th>
                                <th class="text-center">Materias</th>
                                <th class="text-center">Inscritos</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ofertas_abiertas as $oferta): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($oferta['nombre_pnf']) ?></strong><br>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($oferta['nombre_trayecto']) ?> - 
                                            <?= htmlspecialchars($oferta['nombre_trimestre']) ?>
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
                                        <a href="inscribirEstudiantes.php?oferta_id=<?= $oferta['id'] ?>" 
                                           class="btn btn-sm btn-primary" 
                                           title="Gestionar Inscripciones">
                                            <i class="fa fa-users"></i> Gestionar
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
                <i class="fa fa-arrow-left"></i> Ver Ofertas
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../models/footer.php'; ?>