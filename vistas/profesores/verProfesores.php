<?php
require_once __DIR__ . '/../../models/header.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/profesorController/verProfesores.php';
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Listado de Profesores</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            background: #f4f6f9;
        }
        .table th, .table td {
            vertical-align: middle;
        }
        .acciones a, .acciones button {
            margin-right: 6px;
        }
        .card {
            margin-top: 40px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(30,60,114,0.12);
        }
        .card-header {
            background: linear-gradient(90deg,#1e3c72,#2a5298);
            color: #fff;
            border-radius: 16px 16px 0 0;
        }
        .btn-primary {
            background: #2a5298;
            border: none;
        }
        .btn-primary:hover {
            background: #1e3c72;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-11">
            <div class="card shadow">
                <div class="card-header text-center">
                    <h3 class="mb-0"><i class="fa fa-chalkboard-teacher"></i> Profesores Registrados</h3>
                </div>

                <div class="card-body">
                <p class="text-center mb-3">
                    <i class="fa fa-info-circle text-info"></i>
                        Lista de profesores registrados en el sistema.
                </p>

                <!-- Filtro por PNF -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <form method="GET" action="">
                            <label for="pnf_id">Filtrar por PNF:</label>
                            <div class="input-group">
                                <select name="pnf_id" id="pnf_id" class="form-control">
                                    <option value="">Todos los PNF</option>
                                    <?php foreach ($pnfs as $pnf): ?>
                                        <option value="<?= $pnf['id'] ?>" <?= ($filtro_pnf == $pnf['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($pnf['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-primary">Filtrar</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                    <?php if (isset($profesores) && count($profesores) > 0): ?>
                <div class="table-responsive">
                <table id="datatables" class="table table-bordered table-hover table-striped align-middle">
                  
                            <thead class="thead-dark">
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Apellido</th>
                                        <th>Cedula</th>
                                        <th>Aldea</th>
                                        <th>PNF</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($profesores as $profesor): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($profesor['nombre']) ?></td>
                                        <td><?= htmlspecialchars($profesor['apellido']) ?></td>
                                        <td><?= htmlspecialchars($profesor['cedula']) ?></td>
                                        <td><?= htmlspecialchars($profesor['aldea_nombre'] ?? 'Sin asignar') ?></td>
                                        <td><?= htmlspecialchars($profesor['pnf_nombre'] ?? 'Sin asignar') ?></td>
                                        <td class="acciones text-center">
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-toggle="dropdown">
                                                    Acciones
                                                </button>
                                                <div class="dropdown-menu">
                                                    <button class="dropdown-item" data-toggle="modal" data-target="#modalProfesor<?= $profesor['id'] ?>">
                                                        Detalles
                                                    </button>
                                                    <form action="editarProfesor.php" method="POST" style="display: inline; width: 100%;">
                                                        <input type="hidden" name="id" value="<?= htmlspecialchars($profesor['id']) ?>">
                                                        <button type="submit" class="dropdown-item">
                                                            Editar
                                                        </button>
                                                    </form>
                                                    <a class="dropdown-item" href="gestionarMaterias.php?profesor_id=<?= $profesor['id'] ?>">
                                                        Gestionar Materias
                                                    </a>
                                                    <div class="dropdown-divider"></div>
                                                    <button class="dropdown-item text-danger" data-toggle="modal" data-target="#modalEliminar<?= $profesor['id'] ?>">
                                                        <i class="fa fa-trash"></i> Eliminar
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Modal Ver Detalles -->
<div class="modal fade" id="modalProfesor<?= $profesor['id'] ?>" tabindex="-1" role="dialog" aria-labelledby="modalProfesorLabel<?= $profesor['id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h6 class="modal-title" id="modalProfesorLabel<?= $profesor['id'] ?>">Detalles del Profesor</h6>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <ul class="list-group">
                    <li class="list-group-item list-group-item-primary"><?= htmlspecialchars($profesor['nombre']) ?> <?= htmlspecialchars($profesor['apellido']) ?></li>
                    <li class="list-group-item">Cédula: <?= htmlspecialchars($profesor['cedula']) ?></li>
                    <li class="list-group-item">Correo: <?= htmlspecialchars($profesor['correo']) ?></li>
                    <li class="list-group-item">Teléfono: <?= htmlspecialchars($profesor['telefono']) ?></li>
                    <li class="list-group-item">Aldea: <?= htmlspecialchars($profesor['aldea_nombre'] ?? 'Sin asignar') ?></li>
                    <li class="list-group-item">PNF: <?= htmlspecialchars($profesor['pnf_nombre'] ?? 'Sin asignar') ?></li>
                    <li class="list-group-item">Título: <?= htmlspecialchars($profesor['titulo']) ?></li>
                    <li class="list-group-item">Especialidad: <?= htmlspecialchars($profesor['especialidad']) ?></li>
                    <li class="list-group-item">
                        <strong>Materias que imparte:</strong>
                        <?php if (!empty($profesor['materias'])): ?>
                            <ul class="mt-2 mb-0">
                                <?php foreach ($profesor['materias'] as $materia): ?>
                                    <li><?= htmlspecialchars($materia['nombre']) ?> <small class="text-muted">(<?= htmlspecialchars($materia['pnf_nombre']) ?>)</small></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <span class="text-muted">No tiene materias asignadas</span>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
                                            <!-- Modal Eliminar -->
                                            <div class="modal fade" id="modalEliminar<?= $profesor['id'] ?>" tabindex="-1" role="dialog" aria-labelledby="modalLabel<?= $profesor['id'] ?>" aria-hidden="true">
                                              <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                  <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title" id="modalLabel<?= $profesor['id'] ?>">Eliminar Profesor</h5>
                                                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                                                      <span aria-hidden="true">&times;</span>
                                                    </button>
                                                  </div>
                                                  <div class="modal-body">
                                                    ¿Estás seguro de que deseas eliminar al profesor <strong><?= htmlspecialchars($profesor['nombre']) ?> <?= htmlspecialchars($profesor['apellido']) ?></strong>?
                                                  </div>
                                                  <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>

                                                    <form action="../../controladores/profesorController/eliminarProfesor.php" method="POST" style="display:inline;">
                                                    <input type="hidden" name="id" value="<?= $profesor['id'] ?>">
                                                    <button type="submit" class="btn btn-danger">
                                                         Eliminar
                                                     </button>
                                                     </form>
                                                    </div>

                                                </div>
                                              </div>
                                            </div>
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
<!-- DataTables CSS y JS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>

<script>
$(document).ready(function() {
    $('#datatables').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json"
        },
        "pageLength": 10,
        "responsive": true,
        "columnDefs": [
            { "orderable": false, "targets": [5] } // Desactivar ordenamiento en columna Acciones
        ]
    });
});
</script>

</body>
</html>
<?php require_once __DIR__ . '/../../models/footer.php'; ?>