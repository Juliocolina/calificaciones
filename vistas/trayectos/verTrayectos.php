<?php
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
verificarRol(['admin']);
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/trayectoController/verTrayectos.php';
?>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-11">
            <div class="card shadow">
                <div class="card-header text-center">
                    <h3 class="mb-0"><i class="fa fa-route"></i> Trayectos Registrados</h3>
                </div>

                <div class="card-body">
                <p class="text-center mb-3">
                    <i class="fa fa-info-circle text-info"></i>
                        Lista de trayectos registrados en el sistema.
                </p>

                <div class="mb-3 text-right">
                    <a href="crearTrayecto.php" class="btn btn-primary"><i class="fa fa-plus"></i> Nuevo Trayecto</a>
                </div>
                    <?php if (isset($trayectos) && count($trayectos) > 0): ?>
                <div class="table-responsive">
                <table id="datatables" class="table table-bordered table-hover table-striped align-middle">
                  
                            <thead class="thead-dark">
                                    <tr>
                                        <th>Código</th>
                                        <th>Nombre</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($trayectos as $trayecto): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($trayecto['slug']) ?></td>
                                        <td><?= htmlspecialchars($trayecto['nombre']) ?></td>
                                        <td class="acciones text-center">
                                           
                                       <button type="button"
                                        class="btn btn-sm btn-outline-info"
                                        title="Ver"
                                        data-toggle="modal"
                                        data-target="#modalTrayecto<?= $trayecto['id'] ?>">
                                       <i class="fa fa-eye"></i>
                                       </button>

                                        <form action="editarTrayecto.php" method="POST" style="display: inline-block; margin: 0; padding: 0;">
                                                <input type="hidden" name="id" value="<?= htmlspecialchars($trayecto['id']) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-primary mx-1" title="Editar trayecto">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                        </form>
                                       <button class="btn btn-sm btn-outline-danger mx-1" 
                                               data-toggle="modal" 
                                               data-target="#modalEliminar<?= $trayecto['id'] ?>" 
                                               title="Eliminar">
                                            <i class="fa fa-trash"></i>
                                        </button>

                                            <div class="modal fade" id="modalTrayecto<?= $trayecto['id'] ?>" tabindex="-1" role="dialog" aria-labelledby="modalTrayectoLabel<?= $trayecto['id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h6 class="modal-title" id="modalTrayectoLabel<?= $trayecto['id'] ?>">Detalles del Trayecto</h6>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <ul class="list-group">
                    <li class="list-group-item list-group-item-primary">Código: <?= htmlspecialchars($trayecto['slug']) ?></li>
                    <li class="list-group-item">Nombre: <?= htmlspecialchars($trayecto['nombre']) ?></li>
                    <li class="list-group-item">Descripción: <?= htmlspecialchars($trayecto['descripcion']) ?></li>
                
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
                                            <div class="modal fade" id="modalEliminar<?= $trayecto['id'] ?>" tabindex="-1" role="dialog" aria-labelledby="modalLabel<?= $trayecto['id'] ?>" aria-hidden="true">
                                              <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                  <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title" id="modalLabel<?= $trayecto['id'] ?>">Eliminar Trayecto</h5>
                                                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                                                      <span aria-hidden="true">&times;</span>
                                                    </button>
                                                  </div>
                                                  <div class="modal-body">
                                                    ¿Estás seguro de que deseas eliminar el trayecto <strong><?= htmlspecialchars($trayecto['nombre_trayecto']) ?></strong>?
                                                  </div>
                                                  <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>

                                                    <form action="../../controladores/trayectoController/eliminarTrayecto.php" method="POST" style="display:inline;">
                                                    <input type="hidden" name="id" value="<?= $trayecto['id'] ?>">
                                                    <button type="submit" class="btn btn-danger">
                                                         Eliminar
                                                     </button>
                                                     </form>
                                                    </div>

                                                </div>
                                              </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info text-center">No hay trayectos registrados.</div>
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