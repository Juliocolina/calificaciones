<?php
session_start();
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
verificarRol(['admin', 'coordinador']);
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/conexion.php';

$conn = conectar();
$seccion_id = intval($_GET['seccion_id'] ?? 0);

if (!$seccion_id) {
    header('Location: gestionarInscripciones.php');
    exit;
}

// Obtener información de la sección
$stmt = $conn->prepare("
    SELECT 
        s.codigo_seccion,
        m.nombre as materia_nombre,
        CONCAT(u.nombre, ' ', u.apellido) as profesor_nombre,
        CONCAT(a.nombre, ' - ', p.nombre, ' - ', t.slug) as oferta_descripcion
    FROM secciones s
    JOIN materias m ON s.materia_id = m.id
    JOIN profesores pr ON s.profesor_id = pr.id
    JOIN usuarios u ON pr.usuario_id = u.id
    JOIN oferta_academica oa ON s.oferta_academica_id = oa.id
    JOIN aldeas a ON oa.aldea_id = a.id
    JOIN pnfs p ON oa.pnf_id = p.id
    JOIN trayectos t ON oa.trayecto_id = t.id
    WHERE s.id = ?
");
$stmt->execute([$seccion_id]);
$seccion_info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$seccion_info) {
    header('Location: gestionarInscripciones.php');
    exit;
}

// Obtener estudiantes inscritos con estado de calificación
$stmt = $conn->prepare("
    SELECT 
        i.id as inscripcion_id,
        u.cedula,
        CONCAT(u.nombre, ' ', u.apellido) as nombre_completo,
        e.codigo_estudiante,
        i.estatus as estado_inscripcion,

        c.nota_numerica,

        CASE 
            WHEN c.nota_numerica IS NOT NULL THEN 'Calificado'
            WHEN i.estatus = 'Cursando' THEN 'Pendiente'
            ELSE 'Sin Calificar'
        END as estado_calificacion
    FROM inscripciones i
    JOIN estudiantes e ON i.estudiante_id = e.id
    JOIN usuarios u ON e.usuario_id = u.id
    LEFT JOIN calificaciones c ON i.id = c.inscripcion_id
    WHERE i.seccion_id = ?
    ORDER BY u.apellido, u.nombre
");
$stmt->execute([$seccion_id]);
$estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-info text-white">
            <h3 class="mb-0"><i class="fa fa-users"></i> Estudiantes de la Sección</h3>
            <p class="mb-0"><?= htmlspecialchars($seccion_info['materia_nombre']) ?> - <?= htmlspecialchars($seccion_info['profesor_nombre']) ?></p>
            <small><?= htmlspecialchars($seccion_info['oferta_descripcion']) ?></small>
        </div>
        
        <div class="card-body">
            <?php if (empty($estudiantes)): ?>
                <div class="alert alert-info text-center">
                    <i class="fa fa-info-circle"></i> No hay estudiantes inscritos en esta sección.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>Cédula</th>
                                <th>Estudiante</th>
                                <th>Código</th>
                                <th>Estado Inscripción</th>

                                <th class="text-center">Nota</th>
                                <th class="text-center">Estado Calificación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($estudiantes as $estudiante): ?>
                                <tr>
                                    <td><?= htmlspecialchars($estudiante['cedula']) ?></td>
                                    <td><?= htmlspecialchars($estudiante['nombre_completo']) ?></td>
                                    <td><?= htmlspecialchars($estudiante['codigo_estudiante']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= 
                                            $estudiante['estado_inscripcion'] === 'Aprobada' ? 'success' : 
                                            ($estudiante['estado_inscripcion'] === 'Reprobada' ? 'danger' : 'primary') 
                                        ?>">
                                            <?= htmlspecialchars($estudiante['estado_inscripcion']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($estudiante['nota_numerica']): ?>
                                            <span class="badge badge-<?= $estudiante['nota_numerica'] >= 12 ? 'success' : 'danger' ?>">
                                                <?= $estudiante['nota_numerica'] ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">S/C</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-<?= 
                                            $estudiante['estado_calificacion'] === 'Calificado' ? 'success' : 
                                            ($estudiante['estado_calificacion'] === 'Pendiente' ? 'warning' : 'info') 
                                        ?>">
                                            <?= $estudiante['estado_calificacion'] ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h4><?= count($estudiantes) ?></h4>
                                    <small>Total Inscritos</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h4><?= count(array_filter($estudiantes, fn($e) => $e['estado_calificacion'] === 'Calificado')) ?></h4>
                                    <small>Calificados</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h4><?= count(array_filter($estudiantes, fn($e) => $e['estado_calificacion'] === 'Pendiente')) ?></h4>
                                    <small>Pendientes</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h4><?= count(array_filter($estudiantes, fn($e) => $e['estado_calificacion'] === 'Sin Calificar')) ?></h4>
                                    <small>Sin Calificar</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="card-footer text-center">
            <a href="gestionarInscripciones.php" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Volver a Secciones
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>