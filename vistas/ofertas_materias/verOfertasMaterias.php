<?php
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
verificarRol(['admin', 'coordinador']);
require_once __DIR__ . '/../../controladores/ofertaController/verOfertasMaterias.php';
require_once __DIR__ . '/../../models/header.php';
?>
<div class="container mt-5">

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger text-center">
            <h4>Error</h4>
            <p><?= $error_message ?></p>
        </div>
        <div class="text-center">
            <a href="../ofertas_academicas/verOfertas.php" class="btn btn-secondary">Volver a la Lista de Ofertas</a>
        </div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-0">Materias Asignadas</h3>
                    <p class="mb-0">
                        Oferta: <strong><?= htmlspecialchars($oferta_info['nombre_pnf'] . ' - ' . $oferta_info['nombre_trayecto']) ?></strong>
                        (<?= htmlspecialchars($oferta_info['nombre_trimestre']) ?>)
                    </p>
                </div>
                <?php if ($puede_gestionar): ?>
                    <a href="crearOfertaMateria.php?id=<?= $oferta_id ?>" class="btn btn-light"><i class="fa fa-plus-circle"></i> Añadir Nueva Materia</a>
                <?php else: ?>
                    <span class="badge badge-<?= $oferta_info['estatus'] == 'Abierto' ? 'success' : 'secondary' ?>">Estado: <?= htmlspecialchars($oferta_info['estatus']) ?></span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($materias_asignadas)): ?>
                    <div class="alert alert-info text-center">
                        <i class="fa fa-info-circle"></i> Aún no se han asignado materias a esta oferta.
                    </div>
                <?php else: ?>
                    <ul class="list-group">
                        <?php foreach ($materias_asignadas as $materia): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fa fa-book-open text-primary mr-2"></i>
                                    <strong><?= htmlspecialchars($materia['codigo']) ?></strong> - <?= htmlspecialchars($materia['nombre_materia']) ?>
                                    <br>
                                    <small class="text-muted ml-4">
                                        <i class="fa fa-chalkboard-teacher mr-1"></i>
                                        Profesor: 
                                        <?php if ($materia['profesor_id']): ?>
                                            <strong><?= htmlspecialchars($materia['nombre_profesor'] . ' ' . $materia['apellido_profesor']) ?></strong>
                                        <?php else: ?>
                                            <span class="text-danger">Sin Asignar</span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <div class="d-flex align-items-center">
                                    <?php if ($puede_gestionar): ?>
                                        <a href="asignarProfesor.php?oferta_materia_id=<?= $materia['asignacion_id'] ?>" class="btn btn-sm btn-outline-primary mr-2" title="<?= $materia['profesor_id'] ? 'Cambiar Profesor' : 'Asignar Profesor' ?>">
                                            <i class="fa fa-user-plus"></i>
                                        </a>
                                        
                                        <?php if ($materia['profesor_id']): ?>
                                            <form action="../../controladores/ofertaController/desasignarProfesor.php" method="POST" onsubmit="return confirm('¿Estás seguro de que deseas desasignar este profesor?');" class="mr-2">
                                                <input type="hidden" name="oferta_materia_id" value="<?= $materia['asignacion_id'] ?>">
                                                <input type="hidden" name="oferta_id" value="<?= $oferta_id ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-warning" title="Desasignar Profesor">
                                                    <i class="fa fa-user-times"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form action="../../controladores/ofertaController/eliminarOfertaMateria.php" method="POST" onsubmit="return confirm('¿Estás seguro de que deseas quitar esta materia de la oferta?');">
                                            <input type="hidden" name="asignacion_id" value="<?= $materia['asignacion_id'] ?>">
                                            <input type="hidden" name="oferta_id" value="<?= $oferta_id ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Quitar Materia">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <small class="text-muted">Solo lectura</small>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
            </div>
             <div class="card-footer text-center bg-light">
                <a href="../ofertas_academicas/verOfertas.php" class="btn btn-secondary">Finalizar y Volver a la Lista</a>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../models/footer.php'; ?>