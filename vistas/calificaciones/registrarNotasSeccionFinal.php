<?php
session_start();
require_once '../../config/conexion.php';
require_once '../../controladores/hellpers/auth.php';

verificarSesion();

$pdo = conectar();
$seccion_id = $_GET['seccion_id'] ?? null;

if (!$seccion_id) {
    header('Location: cargarNotasFinal.php');
    exit;
}

// Obtener información de la sección CON VALIDACIÓN DE AUTORIZACIÓN
$stmt = $pdo->prepare("
    SELECT 
        s.id,
        m.nombre as materia_nombre,
        m.duracion,
        m.id as materia_id,
        CONCAT(u.nombre, ' ', u.apellido) as profesor_nombre,
        p.usuario_id as profesor_usuario_id
    FROM secciones s
    JOIN materias m ON s.materia_id = m.id
    JOIN profesores p ON s.profesor_id = p.id
    JOIN usuarios u ON p.usuario_id = u.id
    WHERE s.id = ?
");
$stmt->execute([$seccion_id]);
$seccion = $stmt->fetch();

if (!$seccion) {
    $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'Sección no encontrada.'];
    header('Location: cargarNotasFinal.php');
    exit;
}

// VALIDACIÓN CRÍTICA: Solo el profesor dueño puede acceder (excepto admin/coordinador)
if ($_SESSION['rol'] === 'profesor' && $seccion['profesor_usuario_id'] != $_SESSION['usuario_id']) {
    $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'No tienes permisos para acceder a esta sección.'];
    header('Location: cargarNotasFinal.php');
    exit;
}

// Obtener estudiantes con sus calificaciones
$stmt = $pdo->prepare("
    SELECT 
        e.id as estudiante_id,
        u.nombre,
        u.apellido,
        u.cedula,
        i.id as inscripcion_id, /* 🚩 CORRECCIÓN 1: AGREGAR ESTE ID */
        COALESCE(c.nota_numerica, NULL) as nota_numerica,
        COALESCE(c.id, NULL) as calificacion_id,
        COALESCE(c.periodo_academico, NULL) as periodo_academico,
        (
            SELECT GROUP_CONCAT(i2.id)
            FROM inscripciones i2
            JOIN secciones s2 ON i2.seccion_id = s2.id
            JOIN oferta_academica oa2 ON s2.oferta_academica_id = oa2.id
            WHERE i2.estudiante_id = e.id 
              AND s2.materia_id = ? /* Usamos el ID de la materia de la sección */
              AND oa2.tipo_oferta = (SELECT oa.tipo_oferta FROM oferta_academica oa WHERE oa.id = s.oferta_academica_id)
        ) as inscripciones_ids,
        (
            SELECT COUNT(i2.id)
            FROM inscripciones i2
            JOIN secciones s2 ON i2.seccion_id = s2.id
            JOIN oferta_academica oa2 ON s2.oferta_academica_id = oa2.id
            WHERE i2.estudiante_id = e.id 
              AND s2.materia_id = ? /* Usamos el ID de la materia de la sección */
              AND oa2.tipo_oferta = (SELECT oa.tipo_oferta FROM oferta_academica oa WHERE oa.id = s.oferta_academica_id)
        ) as total_inscripciones,
        t.nombre as periodo_estudiante
    FROM inscripciones i
    JOIN estudiantes e ON i.estudiante_id = e.id
    JOIN usuarios u ON e.usuario_id = u.id
    JOIN secciones s ON i.seccion_id = s.id
    JOIN materias m ON s.materia_id = m.id
    JOIN oferta_academica oa ON s.oferta_academica_id = oa.id
    JOIN trimestres t ON oa.trimestre_id = t.id
    LEFT JOIN calificaciones c ON i.id = c.inscripcion_id
    WHERE s.id = ?
    ORDER BY u.apellido, u.nombre
");

$stmt->execute([
    $seccion['materia_id'], 
    $seccion['materia_id'], 
    $seccion_id
]);
$estudiantes = $stmt->fetchAll();

// Agrupar estudiantes únicos (solo si tienes un problema de duplicados)
$estudiantes_unicos = [];
foreach ($estudiantes as $est) {
    $estudiantes_unicos[$est['estudiante_id']] = $est;
}
$estudiantes = array_values($estudiantes_unicos);

// El período se determinará individualmente por cada estudiante según su trimestre
$periodo_actual = null;
?>

<!doctype html>
<html class="no-js" lang="es">
<head>
    <meta charset="utf-8">
    <title>Registrar Notas - <?php echo htmlspecialchars($seccion['materia_nombre']); ?></title>
    <link rel="stylesheet" href="../../assets/css/cs-skin-elastic.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>
    <?php include '../../models/header.php'; ?>

    <div class="content">
        <div class="animated fadeIn">
            <?php if (isset($_SESSION['mensaje'])): ?>
                <div class="alert alert-<?php echo $_SESSION['mensaje']['tipo']; ?> alert-dismissible fade show">
                    <?php echo $_SESSION['mensaje']['texto']; ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
                <?php unset($_SESSION['mensaje']); ?>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <strong>Registrar Notas - <?php echo htmlspecialchars($seccion['materia_nombre']); ?></strong>
                            <small class="text-muted ml-2">(<?php echo ucfirst($seccion['duracion']); ?>)</small>
                        </div>
                        <div class="card-body">
                            <?php if (empty($estudiantes)): ?>
                                <div class="alert alert-warning">
                                    No hay estudiantes inscritos en esta sección.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Estudiante</th>
                                                <th>Cédula</th>
                                                <th>Estado Actual</th>
                                                <th>Nota</th>
                                                <th>Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($estudiantes as $estudiante): ?>
                                                <?php 
                                                    $rowClass = '';
                                                    $esProyecto = (strpos(strtolower($seccion['materia_nombre']), 'proyecto socio tecnológico') !== false);
                                                    
                                                    if ($estudiante['calificacion_id']) {
                                                        if ($esProyecto) {
                                                            // Para Proyecto: Rojo si < 16, Verde si >= 16
                                                            if ($estudiante['nota_numerica'] < 16) {
                                                                $rowClass = 'table-danger'; // Rojo para Proyecto < 16
                                                            } else {
                                                                $rowClass = 'table-success'; // Verde para Proyecto >= 16
                                                            }
                                                        } else {
                                                            // Para otras materias: Rojo si < 12, Verde si >= 12
                                                            if ($estudiante['nota_numerica'] < 12) {
                                                                $rowClass = 'table-danger'; // Rojo para reprobados
                                                            } else {
                                                                $rowClass = 'table-success'; // Verde para aprobados
                                                            }
                                                        }
                                                    }
                                                    
                                                    // Validar si se puede registrar nota según duración y número de inscripciones
                                                    $puede_registrar = false;
                                                    
                                                    if ($seccion['duracion'] == 'trimestral') {
                                                        // Trimestral: 1 inscripción = puede recibir nota final
                                                        $puede_registrar = ($estudiante['total_inscripciones'] >= 1);
                                                    } elseif ($seccion['duracion'] == 'bimestral') {
                                                        // Bimestral: 2 inscripciones = puede recibir nota final
                                                        $puede_registrar = ($estudiante['total_inscripciones'] >= 2);
                                                    } elseif ($seccion['duracion'] == 'anual') {
                                                        // Anual: 3 inscripciones = puede recibir nota final
                                                        $puede_registrar = ($estudiante['total_inscripciones'] >= 3);
                                                    }
                                                ?>
                                                <tr class="<?php echo $rowClass; ?>">
                                                    <td><?php echo htmlspecialchars($estudiante['apellido'] . ', ' . $estudiante['nombre']); ?></td>
                                                    <td><?php echo htmlspecialchars($estudiante['cedula']); ?></td>
                                                    <td>
                                                        <?php if ($estudiante['calificacion_id']): ?>
                                                            <span class="badge badge-success"><?php echo $estudiante['periodo_academico'] ?: $estudiante['periodo_estudiante']; ?></span>
                                                        <?php else: ?>
                                                            <form method="POST" action="../../controladores/calificacionesController/registrarNotaFinal.php" style="display: inline;">
                                                                <input type="hidden" name="inscripciones_ids" value="<?php echo $estudiante['inscripciones_ids']; ?>">
                                                                <input type="hidden" name="estudiante_id" value="<?php echo $estudiante['estudiante_id']; ?>">
                                                                
                                                                <input type="hidden" name="inscripcion_id_final" value="<?php echo $estudiante['inscripcion_id']; ?>">
                                                                <input type="hidden" name="seccion_id_redirect" value="<?php echo $seccion_id; ?>">
                                                                
                                                                <input type="hidden" name="periodo_academico" value="<?php echo $estudiante['periodo_estudiante']; ?>">
                                                                <span class="badge badge-info"><?php echo $estudiante['periodo_estudiante']; ?></span>
                                                                <small class="text-muted d-block">(<?php echo $estudiante['total_inscripciones']; ?> inscripciones)</small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($estudiante['calificacion_id']): ?>
                                                            <?php 
                                                                $limite = $esProyecto ? 16 : 12;
                                                                $badgeClass = ($estudiante['nota_numerica'] >= $limite) ? 'badge-success' : 'badge-danger';
                                                            ?>
                                                            <span class="badge <?php echo $badgeClass; ?>">
                                                                <?php echo $estudiante['nota_numerica']; ?>
                                                                <?php if ($esProyecto): ?>
                                                                    <small class="d-block"><?php echo ($estudiante['nota_numerica'] >= 16) ? 'PUEDE AVANZAR' : 'NO PUEDE AVANZAR'; ?></small>
                                                                <?php endif; ?>
                                                            </span>
                                                        <?php else: ?>
                                                                <input type="number" 
                                                                       name="nota_numerica" 
                                                                       class="form-control form-control-sm" 
                                                                       style="width: 80px; display: inline-block;"
                                                                       min="0" max="20" 
                                                                       step="0.1" 
                                                                       placeholder="0.0" 
                                                                       required>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($estudiante['calificacion_id']): ?>
                                                            <span class="badge badge-info">Ya Registrado</span>
                                                        <?php elseif (!$puede_registrar): ?>
                                                            <span class="badge badge-warning" title="Faltan inscripciones">
                                                                <?php if ($seccion['duracion'] == 'bimestral'): ?>
                                                                    Faltan inscripciones (<?= $estudiante['total_inscripciones'] ?>/2)
                                                                <?php elseif ($seccion['duracion'] == 'anual'): ?>
                                                                    Faltan inscripciones (<?= $estudiante['total_inscripciones'] ?>/3)
                                                                <?php else: ?>
                                                                    Falta inscripción (<?= $estudiante['total_inscripciones'] ?>/1)
                                                                <?php endif; ?>
                                                            </span>
                                                        <?php else: ?>
                                                                <button type="submit" class="btn btn-success btn-sm">
                                                                    <i class="fa fa-save"></i> Guardar
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>

                            <div class="text-center mt-4">
                                <a href="cargarNotasFinal.php" class="btn btn-secondary">
                                    <i class="fa fa-arrow-left"></i> Volver
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../models/footer.php'; ?>
</body>
</html>