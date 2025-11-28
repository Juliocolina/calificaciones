<?php
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
verificarRol(['admin', 'coordinador']);
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../controladores/seccionController/verSecciones.php';
?>
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header text-center">
                    <h3 class="mb-0"><i class="fa fa-th-list"></i> Gestión de Secciones</h3>
                </div>

                <div class="card-body">
                    <!-- Filtros -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <form method="GET" action="">
                                <div class="row">
                                    <div class="col-lg-3 col-md-6 mb-2">
                                        <label for="aldea_id">Aldea:</label>
                                        <select name="aldea_id" id="aldea_id" class="form-control" <?= $_SESSION['rol'] === 'coordinador' ? 'disabled' : '' ?>>
                                            <?php if ($_SESSION['rol'] === 'admin'): ?>
                                                <option value="">Todas las aldeas</option>
                                            <?php endif; ?>
                                            <?php foreach ($aldeas as $aldea): ?>
                                                <option value="<?= $aldea['id'] ?>" <?= ($filtro_aldea == $aldea['id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($aldea['nombre']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-lg-2 col-md-4 mb-2">
                                        <label for="oferta_id">Oferta Académica:</label>
                                        <select name="oferta_id" id="oferta_id" class="form-control">
                                            <option value="">Todas las ofertas</option>
                                            <?php foreach ($ofertas as $oferta): ?>
                                                <option value="<?= $oferta['id'] ?>" <?= ($filtro_oferta == $oferta['id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($oferta['nombre']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-lg-2 col-md-4 mb-2">
                                        <label>&nbsp;</label>
                                        <div>
                                            <button type="submit" class="btn btn-primary btn-block">
                                                <i class="fa fa-filter"></i> Filtrar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Botón crear sección -->
                    <div class="mb-3 text-right">
                        <a href="crearSeccion.php" class="btn btn-primary">
                            <i class="fa fa-plus"></i> Nueva Sección
                        </a>
                    </div>

                    <!-- Tabla de secciones -->
                    <?php if (count($secciones) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-striped align-middle">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Código</th>
                                        <th>Materia</th>
                                        <th>Profesor</th>
                                        <th>Cupo</th>
                                        <th>Aldea</th>
                                        <th>PNF</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($secciones as $seccion): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($seccion['codigo_seccion']) ?></strong>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($seccion['materia']) ?>
                                                <small class="text-muted d-block">(<?= $seccion['creditos'] ?> créditos - <?= ucfirst($seccion['duracion']) ?>)</small>
                                            </td>
                                            <td><?= htmlspecialchars($seccion['profesor']) ?></td>
                                            <td>
                                                <span class="badge <?= $seccion['cupos_disponibles'] > 0 ? 'badge-success' : 'badge-danger' ?>">
                                                    <?= $seccion['estudiantes_inscritos'] ?>/<?= $seccion['cupo_maximo'] ?>
                                                </span>
                                                <small class="text-muted d-block">
                                                    <?= $seccion['cupos_disponibles'] ?> disponibles
                                                </small>
                                            </td>
                                            <td><?= htmlspecialchars($seccion['aldea']) ?></td>
                                            <td><?= htmlspecialchars($seccion['pnf']) ?></td>
                                            <td class="text-center">
                                                <?php if ($seccion['oferta_estatus'] === 'Abierto'): ?>
                                                    <span class="badge badge-success">Abierta</span>
                                                <?php elseif ($seccion['oferta_estatus'] === 'Planificado'): ?>
                                                    <span class="badge badge-info">Planificada</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Cerrada</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-info" 
                                                        data-toggle="modal" 
                                                        data-target="#modalSeccion<?= $seccion['id'] ?>" 
                                                        title="Ver Detalles">
                                                    <i class="fa fa-eye"></i>
                                                </button>

                                                <!-- Modal detalles -->
                                                <div class="modal fade" id="modalSeccion<?= $seccion['id'] ?>" tabindex="-1" role="dialog">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header bg-info text-white">
                                                                <h5 class="modal-title">
                                                                    Detalles de Sección: <?= htmlspecialchars($seccion['codigo_seccion']) ?>
                                                                </h5>
                                                                <button type="button" class="close text-white" data-dismiss="modal">
                                                                    <span>&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <h6><i class="fa fa-university"></i> Información Académica</h6>
                                                                        <ul class="list-unstyled">
                                                                            <li><strong>Aldea:</strong> <?= htmlspecialchars($seccion['aldea']) ?></li>
                                                                            <li><strong>PNF:</strong> <?= htmlspecialchars($seccion['pnf']) ?></li>
                                                                            <li><strong>Trayecto:</strong> <?= htmlspecialchars($seccion['trayecto']) ?></li>
                                                                            <li><strong>Trimestre:</strong> <?= htmlspecialchars($seccion['trimestre']) ?></li>
                                                                        </ul>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <h6><i class="fa fa-info-circle"></i> Información de Sección</h6>
                                                                        <ul class="list-unstyled">
                                                                            <li><strong>Materia:</strong> <?= htmlspecialchars($seccion['materia']) ?></li>
                                                                            <li><strong>Cédula Profesor:</strong> <?= htmlspecialchars($seccion['cedula_profesor']) ?></li>
                                                                            <li><strong>Profesor:</strong> <?= htmlspecialchars($seccion['profesor']) ?></li>
                                                                            <li><strong>Cupo máximo:</strong> <?= $seccion['cupo_maximo'] ?> estudiantes</li>
                                                                            <li><strong>Inscritos:</strong> <?= $seccion['estudiantes_inscritos'] ?> estudiantes</li>
                                                                        </ul>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
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
                        <div class="alert alert-info text-center">
                            <i class="fa fa-info-circle"></i> No hay secciones registradas con los filtros seleccionados.
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card-footer text-muted text-center small">
                    <i class="fa fa-th-list"></i> Sistema de Gestión de Secciones - Misión Sucre
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>