<?php
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
verificarRol(['admin', 'coordinador']); // Solo admin y coordinador pueden ver ofertas
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../controladores/ofertaController/verOfertas.php'; 
?>
<div class="container mt-4"> <div class="row justify-content-center">
    <div class="col-12">
            <div class="card shadow">
                <div class="card-header text-center">
                    <h3 class="mb-0"><i class="fa fa-calendar-check"></i> Ofertas Académicas Registradas</h3>
                </div>

                <div class="card-body">
                    <p class="text-center mb-3">
                        <i class="fa fa-info-circle text-info"></i>
                        Lista de ofertas académicas disponibles en el sistema.
                    </p>

                    <div class="mb-3 text-right">
                        <?php if ($_SESSION['rol'] === 'coordinador'): ?>
                            <a href="crearOferta.php" class="btn btn-primary"><i class="fa fa-plus"></i> Nueva Oferta</a>
                        <?php else: ?>
                            <a href="../aldeas/verAldeas.php" class="btn btn-primary"><i class="fa fa-plus"></i> Nueva Oferta</a>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger"><?= $error_message ?></div>
                    <?php elseif (empty($ofertas)): ?>
                        <div class="alert alert-info text-center">No hay ofertas académicas registradas para su aldea.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-striped align-middle">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>PNF</th>
                                        <th>Trayecto</th>
                                        <th>Trimestre</th>
                                        <th>Tipo de Oferta</th>
                                        <th>Fecha de Inicio</th>
                                        <th class="text-center">Estatus</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
    <?php foreach ($ofertas as $oferta): ?>
    <tr>
        <td><?= htmlspecialchars($oferta['nombre_pnf']) ?></td>
        <td><?= htmlspecialchars($oferta['nombre_trayecto']) ?></td>
        <td><?= htmlspecialchars($oferta['nombre_trimestre']) ?></td>
        <td><?= htmlspecialchars($oferta['tipo_oferta'] ?? 'Regular') ?></td>
        <td>
            <?= htmlspecialchars(date("d/m/Y", strtotime($oferta['fecha_inicio_real']))) ?>
            <?php if ($oferta['fecha_inicio_excepcion']): ?>
                <i class="fa fa-exclamation-triangle text-warning" title="Esta oferta tiene una fecha de inicio especial."></i>
            <?php endif; ?>
        </td>
        <td class="text-center">
            <?php 
                $badge_class = 'badge-secondary';
                if ($oferta['estatus'] == 'Abierto') { $badge_class = 'badge-success'; } 
                elseif ($oferta['estatus'] == 'Planificado') { $badge_class = 'badge-info'; }
                elseif ($oferta['estatus'] == 'Cerrado') { $badge_class = 'badge-danger'; }
                echo "<span class='badge {$badge_class}'>" . htmlspecialchars($oferta['estatus']) . "</span>";
            ?>
        </td>
        <td class="acciones text-center">
            <?php if ($oferta['estatus'] == 'Planificado'): ?>
                <a href="editarOferta.php?id=<?= htmlspecialchars($oferta['id']) ?>" class="btn btn-sm btn-outline-primary">
                    <i class="fa fa-edit"></i> Editar
                </a>
                
                <form action="../../controladores/ofertaController/actualizarEstatusOferta.php" method="POST" style="display: inline;">
                    <input type="hidden" name="id" value="<?= $oferta['id'] ?>">
                    <input type="hidden" name="nuevo_estatus" value="Abierto">
                    <button type="submit" class="btn btn-sm btn-outline-success">
                        <i class="fa fa-play"></i> Abrir
                    </button>
                </form>
                
                <button type="button" class="btn btn-sm btn-outline-warning" data-toggle="modal" data-target="#modalDesactivar<?= $oferta['id'] ?>">
                    <i class="fa fa-ban"></i> Desactivar
                </button>

            <?php elseif ($oferta['estatus'] == 'Inactivo'): ?>
                <form action="../../controladores/ofertaController/actualizarEstatusOferta.php" method="POST" style="display: inline-block;">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($oferta['id']) ?>">
                    <input type="hidden" name="nuevo_estatus" value="Planificado">
                    <button type="submit" class="btn btn-sm btn-outline-success" title="Reactivar Oferta"><i class="fa fa-toggle-on"></i></button>
                </form>
            <?php elseif ($oferta['estatus'] == 'Abierto'): ?>
                <button type="button" class="btn btn-sm btn-outline-info" data-toggle="modal" data-target="#modalVerOferta<?= $oferta['id'] ?>">
                    <i class="fa fa-eye"></i> Ver
                </button>
    
            <?php elseif ($oferta['estatus'] == 'Cerrado'): ?>
                <button type="button" class="btn btn-sm btn-outline-info" data-toggle="modal" data-target="#modalVerOferta<?= $oferta['id'] ?>">
                    <i class="fa fa-eye"></i> Ver Detalles
                </button>
    <?php endif; ?>
        </td>
    </tr>

    <div class="modal fade" id="modalVerOferta<?= $oferta['id'] ?>" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h6 class="modal-title">Detalles de la Oferta Académica</h6>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <ul class="list-group">
                        <li class="list-group-item"><strong>PNF:</strong> <?= htmlspecialchars($oferta['nombre_pnf']) ?></li>
                        <li class="list-group-item"><strong>Trayecto:</strong> <?= htmlspecialchars($oferta['nombre_trayecto']) ?></li>
                        <li class="list-group-item"><strong>Trimestre:</strong> <?= htmlspecialchars($oferta['nombre_trimestre']) ?></li>
                        <li class="list-group-item"><strong>Aldea:</strong> <?= htmlspecialchars($oferta['nombre_aldea'] ?? 'Sin asignar') ?></li>
                        <li class="list-group-item"><strong>Fecha de Inicio:</strong> <?= htmlspecialchars(date("d/m/Y", strtotime($oferta['fecha_inicio_real']))) ?></li>
                        <li class="list-group-item"><strong>Estatus:</strong> <?= htmlspecialchars($oferta['estatus']) ?></li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDesactivar<?= $oferta['id'] ?>" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">Confirmar Desactivación</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas cambiar el estatus de esta oferta a <strong>"Inactivo"</strong>?</p>
                    <p class="font-weight-bold"><?= htmlspecialchars($oferta['nombre_pnf'] . ' - ' . $oferta['nombre_trayecto']) ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <form action="../../controladores/ofertaController/actualizarEstatusOferta.php" method="POST" style="display:inline;">
                        <input type="hidden" name="id" value="<?= $oferta['id'] ?>">
                        <input type="hidden" name="nuevo_estatus" value="Inactivo">
                        <button type="submit" class="btn btn-warning">Sí, Desactivar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    

    <?php endforeach; ?>
</tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-muted text-center small">
                    <i class="fa fa-lock"></i> Sistema exclusivo para uso de las aldeas de Misión Sucre - Municipio Miranda, Falcón.
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>