<?php
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
verificarRol(['admin', 'coordinador']);
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/aldeaController/verAldeas.php';
?>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-11">
            <div class="card shadow">
                <div class="card-header text-center">
                    <h3 class="mb-0"><i class="fa fa-chalkboard-teacher"></i> Aldea Registradas</h3>
                </div>

                <div class="card-body">
                <p class="text-center mb-3">
                    <i class="fa fa-info-circle text-info"></i>
                        Lista de aldeas registradas en el sistema.
                </p>

                <div class="mb-3 text-right">
                    <a href="crearAldea.php" class="btn btn-primary"><i class="fa fa-plus"></i> Nueva Aldea</a>
                </div>
                    <?php if (isset($aldeas) && count($aldeas) > 0): ?>
                <div class="table-responsive">
                <table id="datatables" class="table table-bordered table-hover table-striped align-middle">
                  
                            <thead class="thead-dark">
                                    <tr>
                                        <th>Codigo</th>
                                        <th>Nombre</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($aldeas as $aldea): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($aldea['codigo']) ?></td>
                                        <td><?= htmlspecialchars($aldea['nombre']) ?></td>
                                        <td class="acciones text-center">
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-toggle="dropdown">
                                                    Acciones
                                                </button>
                                                <div class="dropdown-menu">
                                                    <button type="button" class="dropdown-item" 
                                                            data-toggle="modal" 
                                                            data-target="#modalProfesor<?= $aldea['id'] ?>">
                                                        Ver Detalles
                                                    </button>
                                                    <a class="dropdown-item" href="../pnfs/verPnfs.php?aldea_id=<?= $aldea['id'] ?>">
                                                        Ver PNFs
                                                    </a>
                                                    <a class="dropdown-item" href="../materias/materiasPorPnf.php?aldea_id=<?= $aldea['id'] ?>">
                                                        Ver Materias
                                                    </a>
                                                    <?php if ($_SESSION['rol'] === 'admin'): ?>
                                                    <a class="dropdown-item" href="../ofertas_academicas/crearOferta.php?aldea_id=<?= $aldea['id'] ?>">
                                                        Crear Oferta
                                                    </a>
                                                    <div class="dropdown-divider"></div>
                                                    <form action="editarAldea.php" method="POST" style="display: inline-block; width: 100%;">
                                                        <input type="hidden" name="id" value="<?= htmlspecialchars($aldea['id']) ?>">
                                                        <button type="submit" class="dropdown-item">
                                                            Editar Aldea
                                                        </button>
                                                    </form>
                                                    <button class="dropdown-item text-danger" 
                                                            data-toggle="modal" 
                                                            data-target="#modalEliminar<?= $aldea['id'] ?>">
                                                        Eliminar
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <!-- Modal Ver Detalles -->
<div class="modal fade" id="modalProfesor<?= $aldea['id'] ?>" tabindex="-1" role="dialog" aria-labelledby="modalProfesorLabel<?= $profesor['id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h6 class="modal-title" id="modalProfesorLabel<?= $aldea['id'] ?>">Detalles de la Aldea</h6>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <ul class="list-group">
                    <li class="list-group-item list-group-item-primary"><?= htmlspecialchars($aldea['codigo']) ?></li>
                    <li class="list-group-item">Nombre: <?= htmlspecialchars($aldea['nombre']) ?></li>
                    <li class="list-group-item">Direccion: <?= htmlspecialchars($aldea['direccion']) ?></li>
                    <li class="list-group-item">Descripcion: <?= htmlspecialchars($aldea['descripcion']) ?></li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
                                            <!-- Modal Eliminar -->
                                            <div class="modal fade" id="modalEliminar<?= $aldea['id'] ?>" tabindex="-1" role="dialog" aria-labelledby="modalLabel<?= $aldea['id'] ?>" aria-hidden="true">
                                              <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                  <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title" id="modalLabel<?= $aldea['id'] ?>">Eliminar Aldea</h5>
                                                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                                                      <span aria-hidden="true">&times;</span>
                                                    </button>
                                                  </div>
                                                  <div class="modal-body">
                                                    ¿Estás seguro de que deseas eliminar al profesor <strong><?= htmlspecialchars($aldea['nombre']) ?></strong>?
                                                  </div>
                                                  <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>

                                                    <form action="../../controladores/aldeaController/eliminarAldea.php" method="POST" style="display:inline;">
                                                    <input type="hidden" name="id" value="<?= $aldea['id'] ?>">
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
                        <div class="alert alert-info text-center">No hay profesores registrados.</div>
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