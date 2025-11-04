<?php
// NOTA: Este archivo PHP ya debe contener el script que obtiene los datos
// (que corregimos en la respuesta anterior, el que calcula el arrastre en POST)
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
verificarRol(['admin', 'coordinador', 'profesor']);
require_once __DIR__ . '/../../controladores/inscripcionController/inscribirEstudiantes.php';
require_once __DIR__ . '/../../models/header.php';

// Variables esenciales definidas en el script de inscribirEstudiantes.php:
// $oferta_id, $oferta_info, $materias_oferta, $estudiantes_inscritos, $error_message, 
// $estudiante_a_inscribir, $materias_arrastre_detalle, $LIMITE_ARRRASTRE, $cedula_buscada

?>
<div class="container mt-4">
    <?php if (isset($_GET['exito'])): // Mostrar mensajes de éxito/error de procesarInscripcion.php ?>
        <div class="alert alert-success"><i class="fa fa-check-circle"></i> <?= htmlspecialchars($_GET['exito']) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
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
            <div class="card-header bg-dark text-white">
                <h3><i class="fa fa-user-plus"></i> Proceso de Inscripción</h3>
                <p class="mb-0">
                    <strong>Oferta:</strong> <?= htmlspecialchars($oferta_info['pnf'] . ' - ' . $oferta_info['trayecto'] . ' - ' . $oferta_info['trimestre_tipo']) ?>
                </p>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-5">
                        <h5>1. Buscar Estudiante y Verificar Arrastre</h5>
                        
                        <form method="POST" action="inscribirEstudiantes.php?oferta_id=<?= $oferta_id ?>">
                            <input type="hidden" name="oferta_id" value="<?= $oferta_id ?>">
                            <div class="form-group">
                                <label for="cedula">Cédula del Estudiante</label>
                                <input type="text" id="cedula" name="cedula" class="form-control" 
                                    placeholder="Ingrese la cédula para buscar..." required 
                                    value="<?= htmlspecialchars($cedula_buscada) ?>">
                            </div>
                            <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-search"></i> Buscar Historial</button>
                        </form>
                        <hr>
                        
                        <?php if ($estudiante_a_inscribir): ?>
                            <?php 
                                $count_reprobadas = count($materias_arrastre_detalle);
                                $puede_inscribir = $count_reprobadas <= $LIMITE_ARRRASTRE;
                            ?>
                            <div class="mt-4 p-3 border rounded <?= $puede_inscribir ? 'border-success bg-light' : 'border-danger bg-warning-light' ?>">
                                <p class="mb-1">Estudiante: <strong><?= htmlspecialchars($estudiante_a_inscribir['nombre_completo']) ?></strong></p>
                                <p class="mb-1 small text-muted">
                                    <i class="fa fa-map-marker"></i> <strong>Aldea:</strong> <?= htmlspecialchars($estudiante_a_inscribir['aldea_nombre']) ?> | 
                                    <i class="fa fa-graduation-cap"></i> <strong>PNF:</strong> <?= htmlspecialchars($estudiante_a_inscribir['pnf_nombre']) ?> | 
                                    <strong>Trayecto:</strong> <?= htmlspecialchars($estudiante_a_inscribir['trayecto_slug']) ?> | 
                                    <strong>Trimestre:</strong> <?= htmlspecialchars($estudiante_a_inscribir['trimestre_nombre']) ?>
                                </p>
                                
                                <?php if ($count_reprobadas > 0): ?>
                                    <p class="text-danger font-weight-bold">
                                        ⚠️ Arrastre Actual: Debe **<?= $count_reprobadas ?>** materia(s). (Límite: <?= $LIMITE_ARRRASTRE ?>)
                                    </p>
                                    <ul class="list-unstyled small">
                                        <?php foreach ($materias_arrastre_detalle as $m): ?>
                                            <li class="text-danger"><i class="fa fa-minus-circle"></i> <?= htmlspecialchars($m['materia_nombre']) ?> (Nota Máx: <?= number_format($m['nota_final_max'], 2) ?>)</li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="text-success font-weight-bold">✅ Estudiante al día. Sin materias reprobadas.</p>
                                <?php endif; ?>

                                <?php if (!$puede_inscribir): ?>
                                    <p class="text-danger font-weight-bold mt-2">
                                        ❌ INSCRIPCIÓN BLOQUEADA POR REGLAMENTO. (Supera el límite de arrastre).
                                    </p>
                                <?php else: ?>
                                    <form action="../../controladores/inscripcionController/procesarInscripcion.php" method="POST" class="mt-3">
                                        <input type="hidden" name="oferta_id" value="<?= $oferta_id ?>">
                                        <input type="hidden" name="cedula" value="<?= htmlspecialchars($estudiante_a_inscribir['cedula']) ?>">
                                        <input type="hidden" name="accion" value="inscribir">
                                        <button type="submit" class="btn btn-success btn-block">
                                            <i class="fa fa-user-plus"></i> CONFIRMAR Inscripción Completa
                                        </button>
                                    </form>
                                <?php endif; ?>

                            </div>
                        <?php elseif (!empty($cedula_buscada) && $error_message === ''): ?>
                             <div class="alert alert-warning mt-4">No se encontró un estudiante con la cédula **<?= htmlspecialchars($cedula_buscada) ?>**.</div>
                        <?php endif; ?>
                        
                        <hr>
                        <small class="form-text text-muted">
                            Materias de la oferta:
                            <ul>
                                <?php foreach ($materias_oferta as $materia): ?>
                                    <li><?= htmlspecialchars($materia['nombre']) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </small>
                    </div>

                    <div class="col-md-7">
                        <h5>Estudiantes Inscritos (<?= count($estudiantes_inscritos) ?>)</h5>
                        <?php if (empty($estudiantes_inscritos)): ?>
                            <div class="alert alert-info">Aún no hay estudiantes inscritos en esta oferta.</div>
                        <?php else: ?>
                            <ul class="list-group">
                                <?php foreach ($estudiantes_inscritos as $estudiante): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <?= htmlspecialchars($estudiante['apellido'] . ', ' . $estudiante['nombre']) ?> (C.I: <?= htmlspecialchars($estudiante['cedula']) ?>)
                                        <form action="../../controladores/inscripcionController/procesarInscripcion.php" method="POST" onsubmit="return confirm('¿Seguro que desea retirar a este estudiante de TODAS las materias de esta oferta?');">
                                            <input type="hidden" name="oferta_id" value="<?= $oferta_id ?>">
                                            <input type="hidden" name="estudiante_id" value="<?= $estudiante['estudiante_id'] ?>"> 
                                            <button type="submit" name="accion" value="retirar" class="btn btn-sm btn-danger"><i class="fa fa-user-minus"></i> Retirar</button>
                                        </form>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
             <div class="card-footer text-center">
                <a href="../estudiantes/verEstudiantes.php" class="btn btn-primary mr-2">
                    <i class="fa fa-users"></i> Ir a Estudiantes
                </a>
                <a href="../ofertas_academicas/verOfertas.php" class="btn btn-secondary">Finalizar y Volver a Ofertas</a>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../models/footer.php'; ?>