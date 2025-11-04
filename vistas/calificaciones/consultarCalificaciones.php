<?php
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
verificarRol(['admin', 'coordinador', 'profesor']);
require_once __DIR__ . '/../../controladores/calificacionesController/consultarCalificaciones.php';
require_once __DIR__ . '/../../models/header.php';
?>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-info text-white">
            <h3 class="mb-0"><i class="fa fa-chart-bar"></i> Consultar Calificaciones</h3>
            <p class="mb-0">Consultar notas de estudiantes por oferta académica</p>
        </div>
        
        <div class="card-body">
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
            <?php else: ?>
                <!-- Selector de Oferta -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <form method="GET" action="">
                            <div class="input-group">
                                <select name="oferta_id" class="form-control" required>
                                    <option value="">Seleccionar Oferta Académica...</option>
                                    <?php foreach ($ofertas as $oferta): ?>
                                        <option value="<?= $oferta['id'] ?>" <?= $oferta['id'] == $oferta_seleccionada ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($oferta['nombre_completo']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-search"></i> Consultar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Resultados -->
                <?php if ($oferta_seleccionada > 0): ?>
                    <?php if (empty($calificaciones)): ?>
                        <div class="alert alert-warning text-center">
                            <i class="fa fa-exclamation-triangle"></i> No hay calificaciones registradas para esta oferta.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-sm">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Estudiante</th>
                                        <th>Cédula</th>
                                        <th>Materia</th>
                                        <th class="text-center">Nota</th>
                                        <th class="text-center">Tipo</th>
                                        <th class="text-center">Estatus</th>
                                        <th class="text-center">Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($calificaciones as $calificacion): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($calificacion['apellido'] . ', ' . $calificacion['nombre']) ?></td>
                                            <td><?= htmlspecialchars($calificacion['cedula']) ?></td>
                                            <td><?= htmlspecialchars($calificacion['materia_nombre']) ?></td>
                                            <td class="text-center">
                                                <span class="badge badge-<?= $calificacion['nota_numerica'] >= 12 ? 'success' : 'danger' ?>">
                                                    <?= intval($calificacion['nota_numerica']) ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <small><?= htmlspecialchars($calificacion['tipo_evaluacion']) ?></small>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-<?= $calificacion['estatus'] == 'Aprobada' ? 'success' : ($calificacion['estatus'] == 'Reprobada' ? 'danger' : 'secondary') ?>">
                                                    <?= htmlspecialchars($calificacion['estatus']) ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <small><?= date('d/m/Y', strtotime($calificacion['fecha_registro'])) ?></small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <div class="card-footer text-center">
            <a href="../home.php" class="btn btn-secondary">
                <i class="fa fa-home"></i> Inicio
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../models/footer.php'; ?>