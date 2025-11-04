<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';

$inscripcion_id = isset($_GET['inscripcion_id']) ? intval($_GET['inscripcion_id']) : 0;
$error_message = '';
$inscripcion_data = null;
$historial_notas = [];

if ($inscripcion_id <= 0) {
    $error_message = 'ID de inscripción no válido.';
} else {
    try {
        $conn = conectar();
        
        // Obtener datos de la inscripción y estatus de la oferta
        $stmt = $conn->prepare("
            SELECT 
                i.id as inscripcion_id,
                i.estatus,
                e.id as estudiante_id,
                u.cedula,
                u.nombre,
                u.apellido,
                m.nombre as materia_nombre,
                p.nombre as pnf_nombre,
                t.nombre as trayecto_nombre,
                tr.nombre as trimestre_nombre,
                om.oferta_academica_id,
                oa.estatus as oferta_estatus
            FROM inscripciones i
            JOIN estudiantes e ON i.estudiante_id = e.id
            JOIN usuarios u ON e.usuario_id = u.id
            JOIN oferta_materias om ON i.oferta_materia_id = om.id
            JOIN materias m ON om.materia_id = m.id
            JOIN oferta_academica oa ON om.oferta_academica_id = oa.id
            JOIN pnfs p ON oa.pnf_id = p.id
            JOIN trayectos t ON oa.trayecto_id = t.id
            JOIN trimestres tr ON oa.trimestre_id = tr.id
            WHERE i.id = ?
        ");
        $stmt->execute([$inscripcion_id]);
        $inscripcion_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$inscripcion_data) {
            $error_message = 'Inscripción no encontrada.';
        } else {
            // Validar permisos: solo admin puede modificar notas en ofertas cerradas
            if ($inscripcion_data['oferta_estatus'] !== 'Abierto' && $_SESSION['rol'] !== 'admin') {
                $error_message = 'No puedes cargar notas en ofertas cerradas. Solo los administradores pueden hacerlo.';
            } else {
                // Obtener historial de notas solo si tiene permisos
                $stmt_historial = $conn->prepare("
                    SELECT nota_numerica, tipo_evaluacion, fecha_registro
                    FROM calificaciones 
                    WHERE inscripcion_id = ?
                    ORDER BY fecha_registro DESC
                ");
                $stmt_historial->execute([$inscripcion_id]);
                $historial_notas = $stmt_historial->fetchAll(PDO::FETCH_ASSOC);
            }
        }
        
    } catch (Exception $e) {
        $error_message = 'Error al cargar los datos: ' . $e->getMessage();
    }
}

require_once __DIR__ . '/../../models/header.php';
?>

<div class="container mt-4">
    <?php if (isset($_GET['exito'])): ?>
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
            <a href="registrarCalificaciones.php" class="btn btn-secondary">Volver</a>
        </div>
    <?php else: ?>
        <div class="row">
            <!-- Formulario de Carga -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4><i class="fa fa-edit"></i> Cargar Calificación</h4>
                    </div>
                    <div class="card-body">
                        <!-- Información del Estudiante -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Estudiante:</strong> <?= htmlspecialchars($inscripcion_data['apellido'] . ', ' . $inscripcion_data['nombre']) ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Cédula:</strong> <?= htmlspecialchars($inscripcion_data['cedula']) ?>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Materia:</strong> <?= htmlspecialchars($inscripcion_data['materia_nombre']) ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Estatus Actual:</strong> 
                                <span class="badge badge-<?= $inscripcion_data['estatus'] == 'Aprobada' ? 'success' : ($inscripcion_data['estatus'] == 'Reprobada' ? 'danger' : 'secondary') ?>">
                                    <?= htmlspecialchars($inscripcion_data['estatus']) ?>
                                </span>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <strong>Oferta:</strong> <?= htmlspecialchars($inscripcion_data['pnf_nombre'] . ' - ' . $inscripcion_data['trayecto_nombre'] . ' - ' . $inscripcion_data['trimestre_nombre']) ?>
                            </div>
                            <div class="col-md-4">
                                <strong>Estado Oferta:</strong> 
                                <span class="badge badge-<?= $inscripcion_data['oferta_estatus'] == 'Abierto' ? 'success' : 'danger' ?>">
                                    <?= htmlspecialchars($inscripcion_data['oferta_estatus']) ?>
                                </span>
                            </div>
                        </div>
                        <?php if ($inscripcion_data['oferta_estatus'] !== 'Abierto' && $_SESSION['rol'] !== 'admin'): ?>
                            <div class="alert alert-warning">
                                <i class="fa fa-exclamation-triangle"></i> Esta oferta está cerrada. Solo los administradores pueden modificar notas.
                            </div>
                        <?php endif; ?>

                        <hr>

                        <!-- Formulario -->
                        <form action="../../controladores/calificacionesController/procesarNotaIndividual.php" method="POST">
                            <input type="hidden" name="inscripcion_id" value="<?= $inscripcion_id ?>">
                            <input type="hidden" name="oferta_id" value="<?= $inscripcion_data['oferta_academica_id'] ?>">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="nota_numerica"><strong>Nota Numérica *</strong></label>
                                        <input type="number" 
                                               class="form-control" 
                                               id="nota_numerica" 
                                               name="nota_numerica" 
                                               min="0" 
                                               max="20" 
                                               step="1" 
                                               required>
                                        <small class="form-text text-muted">Rango: 0 - 20 (números enteros)</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="tipo_evaluacion"><strong>Tipo de Evaluación *</strong></label>
                                        <select class="form-control" id="tipo_evaluacion" name="tipo_evaluacion" required>
                                            <option value="">Seleccionar...</option>
                                            <option value="Ordinaria">Ordinaria</option>
                                            <option value="Reparacion">Reparación</option>
                                            <option value="Intensivo">Intensivo</option>
                                            <option value="Especial">Especial</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-success">
                                    <i class="fa fa-save"></i> Guardar Calificación
                                </button>
                                <a href="cargarNotas.php" class="btn btn-secondary">
                                    <i class="fa fa-arrow-left"></i> Cancelar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Historial de Notas -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5><i class="fa fa-history"></i> Historial de Notas</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($historial_notas)): ?>
                            <p class="text-muted">No hay notas registradas.</p>
                        <?php else: ?>
                            <?php foreach ($historial_notas as $nota): ?>
                                <div class="mb-2 p-2 border rounded">
                                    <div class="d-flex justify-content-between">
                                        <strong><?= number_format($nota['nota_numerica'], 2) ?></strong>
                                        <small class="text-muted"><?= date('d/m/Y', strtotime($nota['fecha_registro'])) ?></small>
                                    </div>
                                    <small class="text-muted"><?= htmlspecialchars($nota['tipo_evaluacion']) ?></small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../models/footer.php'; ?>