<?php
// NOTA: Este require debe cargar el script PHP que CORREGIMOS previamente.
// El script debe poblar las variables: $oferta_info, $estudiantes_inscritos, $materias_oferta, $calificaciones_historial.
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
verificarRol(['admin', 'coordinador', 'profesor']);
require_once __DIR__ . '/../../controladores/calificacionesController/registrarCalificaciones.php';
require_once __DIR__ . '/../../models/header.php';
?>
<div class="container mt-4">
    <?php if (isset($_GET['exito'])): // Mensajes de éxito del procesarCalificacion.php ?>
        <div class="alert alert-success"><i class="fa fa-check-circle"></i> <?= htmlspecialchars($_GET['exito']) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): // Mensajes de error del procesarCalificacion.php ?>
        <div class="alert alert-danger"><i class="fa fa-times-circle"></i> <?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger">
            <h4 class="alert-heading">Error</h4>
            <p><?= htmlspecialchars($error_message) ?></p>
            <hr>
            <a href="../ofertas_academicas/verOfertas.php" class="btn btn-secondary">Volver a Ofertas</a>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h3><i class="fa fa-edit"></i> Registro de Calificaciones</h3>
                <p class="mb-0">
                    <strong>Oferta:</strong> <?= htmlspecialchars($oferta_info['pnf'] . ' - ' . $oferta_info['trayecto'] . ' - ' . $oferta_info['trimestre']) ?>
                </p>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>Cédula</th>
                                <th>Estudiante</th>
                                <?php foreach ($materias_oferta as $materia): ?>
                                    <th class="text-center">
                                        <?= htmlspecialchars($materia['nombre_materia']) ?>
                                        <br><small class="text-muted">(<?= ucfirst($materia['duracion']) ?>)</small>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($estudiantes_inscritos as $estudiante): ?>
                                <tr>
                                    <td class="align-middle"><?= htmlspecialchars($estudiante['cedula'] ?? '') ?></td>
                                    <td class="align-middle"><?= htmlspecialchars(trim(($estudiante['apellido'] ?? '') . ', ' . ($estudiante['nombre'] ?? ''))) ?></td>
                                    
                                    <?php foreach ($materias_oferta as $materia): ?>
                                        <td class="text-center">
                                            <?php
                                            $studentId = $estudiante['estudiante_id'];
                                            // Usamos la nueva variable del script corregido
                                            $historial = $calificaciones_historial[$studentId][$materia['oferta_materia_id']] ?? null;

                                            if ($historial):
                                                $inscripcion_id = $historial['inscripcion_id'];
                                                $estatus_actual = $historial['estatus'];
                                                $notas = $historial['notas_historicas'];
                                                $total_notas = $historial['total_notas'];
                                                $notas_requeridas = $historial['notas_requeridas'];
                                                $duracion = $historial['duracion'];
                                                $clase_estatus = strtolower($estatus_actual);
                                            ?>
                                                <div class="d-flex flex-column align-items-center">
                                                    <span class="badge badge-<?= $clase_estatus == 'aprobada' ? 'success' : ($clase_estatus == 'reprobada' ? 'danger' : 'secondary') ?>">
                                                        <?= $estatus_actual ?>
                                                    </span>
                                                    
                                                    <?php if (!empty($notas)): ?>
                                                        <p class="mb-1 mt-1 small">
                                                            Notas: <strong><?= implode(', ', $notas) ?></strong>
                                                        </p>
                                                    <?php endif; ?>
                                                    
                                                    <p class="mb-1 small text-muted">
                                                        <?= $total_notas ?>/<?= $notas_requeridas ?> notas
                                                    </p>
                                                    
                                                    <a href="cargarNotaForm.php?inscripcion_id=<?= $inscripcion_id ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fa fa-pencil"></i> <?= $total_notas < $notas_requeridas ? 'Cargar Nota' : 'Ver/Editar' ?>
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <div class="d-flex flex-column align-items-center">
                                                    <span class="badge badge-warning mb-2">No Inscrito Aquí</span>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
             <div class="card-footer text-center">
                <a href="../ofertas_academicas/verOfertas.php" class="btn btn-secondary">Finalizar y Volver a Ofertas</a>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../models/footer.php'; ?>