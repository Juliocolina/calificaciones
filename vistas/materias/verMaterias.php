<?php
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
verificarRol(['admin', 'coordinador']);
require_once __DIR__ . '/../../models/header.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/materiaController/verMaterias.php';
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Listado de Materias</title>
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
                    <h3 class="mb-0"><i class="fa fa-book"></i> Materias Registradas</h3>
                </div>

                <div class="card-body">
                <p class="text-center mb-3">
                    <i class="fa fa-info-circle text-info"></i>
                        Lista de materias registradas en el sistema.
                </p>

                <div class="mb-3 text-right">
                    <a href="crearMateria.php" class="btn btn-primary"><i class="fa fa-plus"></i> Nueva Materia</a>
                </div>

                    <?php if (isset($materias) && count($materias) > 0): ?>
                <div class="table-responsive">
                <table id="datatables" class="table table-bordered table-hover table-striped align-middle">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Código</th>
                                    <th>Nombre</th>
                                    <th>Créditos</th>
                                    <th>Duración</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($materias as $materia): ?>
                                <tr>
                                    <td><?= htmlspecialchars($materia['codigo'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($materia['nombre']) ?></td>
                                    <td><?= htmlspecialchars($materia['creditos']) ?></td>
                                    <td><?= htmlspecialchars(ucfirst($materia['duracion'] ?? 'N/A')) ?></td>
                                        <td class="acciones text-center">
                                        
                                        <button type="button"
                                            class="btn btn-sm btn-outline-info"
                                            title="Ver"
                                            data-toggle="modal"
                                            data-target="#modalMateria<?= $materia['id'] ?>">
                                            <i class="fa fa-eye"></i>
                                        </button>

                                        <form action="editarMateria.php" method="POST" style="display: inline-block; margin: 0; padding: 0;">
                                                <input type="hidden" name="id" value="<?= htmlspecialchars($materia['id']) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-primary mx-1" title="Editar materia">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                        </form>

                                        <button class="btn btn-sm btn-outline-danger mx-1" 
                                                data-toggle="modal" 
                                                data-target="#modalEliminar<?= $materia['id'] ?>" 
                                                title="Eliminar">
                                            <i class="fa fa-trash"></i>
                                        </button>

                                        <div class="modal fade" id="modalMateria<?= $materia['id'] ?>" tabindex="-1" role="dialog" aria-labelledby="modalMateriaLabel<?= $materia['id'] ?>" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-info text-white">
                                                        <h6 class="modal-title" id="modalMateriaLabel<?= $materia['id'] ?>">Detalles de la Materia</h6>
                                                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <ul class="list-group">
                                                            <li class="list-group-item list-group-item-primary">PNF: <?= htmlspecialchars($materia['pnf_nombre'] ?? 'N/A') ?></li>
                                                            <li class="list-group-item">Nombre: <?= htmlspecialchars($materia['nombre']) ?></li>
                                                            <li class="list-group-item">Código: <?= htmlspecialchars($materia['codigo'] ?? 'N/A') ?></li>
                                                            <li class="list-group-item">Créditos: <?= htmlspecialchars($materia['creditos']) ?></li>
                                                            <li class="list-group-item">Duración: <?= htmlspecialchars(ucfirst($materia['duracion'] ?? 'N/A')) ?></li>
                                                            <li class="list-group-item">Descripción: <?= htmlspecialchars($materia['descripcion'] ?? 'Sin descripción') ?></li>
                                                        </ul>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="modal fade" id="modalEliminar<?= $materia['id'] ?>" tabindex="-1" role="dialog" aria-labelledby="modalLabel<?= $materia['id'] ?>" aria-hidden="true">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-danger text-white">
                                                        <h5 class="modal-title" id="modalLabel<?= $materia['id'] ?>">Eliminar Materia</h5>
                                                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        ¿Estás seguro de que deseas eliminar la materia <strong><?= htmlspecialchars($materia['nombre']) ?></strong>?
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                                        <form action="../../controladores/materiaController/eliminarMateria.php" method="POST" style="display:inline;">
                                                            <input type="hidden" name="id" value="<?= $materia['id'] ?>">
                                                            <button type="submit" class="btn btn-danger">Eliminar</button>
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
                        <div class="alert alert-info text-center">No hay materias registradas.</div>
                    <?php endif; ?>
                </div>

                <div class="card-footer text-muted text-center small">
                    <i class="fa fa-lock"></i> Sistema exclusivo para uso de las aldeas de Misión Sucre - Municipio Miranda, Falcón.
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
<?php require_once __DIR__ . '/../../models/footer.php'; ?>