<?php
session_start();
require_once '../../config/conexion.php';

$pdo = conectar();
$usuario_id = $_SESSION['usuario_id'];

// Obtener información del estudiante incluyendo trayecto actual
$stmt = $pdo->prepare("
    SELECT 
        e.id, 
        u.nombre, 
        u.apellido, 
        u.cedula,
        t.slug as trayecto_actual,
        tr.nombre as trimestre_actual,
        p.nombre as pnf_nombre
    FROM estudiantes e
    JOIN usuarios u ON e.usuario_id = u.id
    LEFT JOIN trayectos t ON e.trayecto_id = t.id
    LEFT JOIN trimestres tr ON e.trimestre_id = tr.id
    LEFT JOIN pnfs p ON e.pnf_id = p.id
    WHERE u.id = ?
");
$stmt->execute([$usuario_id]);
$estudiante = $stmt->fetch();

// Obtener materias con calificaciones (SOLO la inscripción más reciente por materia)
$stmt = $pdo->prepare("
    SELECT 
        sub.materia,
        sub.creditos,
        sub.duracion,
        sub.profesor,
        sub.periodo_academico,
        sub.calificacion,
        sub.trayecto,
        sub.tipo_oferta,
        sub.trimestre_nombre,
        sub.fecha_calificacion,
        sub.estatus_inscripcion
    FROM (
        SELECT 
            m.nombre as materia,
            m.creditos,
            m.duracion,
            CONCAT(up.nombre, ' ', up.apellido) as profesor,
            c.periodo_academico,
            c.nota_numerica as calificacion,
            t.slug as trayecto,
            oa.tipo_oferta,
            tr.nombre as trimestre_nombre,
            c.fecha_registro as fecha_calificacion,
            i.estatus as estatus_inscripcion,
            
            -- CLAVE: Asigna un número de fila a cada inscripción.
            -- PARTITION BY m.id: Reinicia el conteo para cada MATERIA.
            -- ORDER BY i.id DESC: Ordena por el ID de Inscripción más alto (el más reciente).
            ROW_NUMBER() OVER (
                PARTITION BY m.id 
                ORDER BY i.id DESC
            ) as rn
        FROM inscripciones i
        JOIN secciones s ON i.seccion_id = s.id
        JOIN materias m ON s.materia_id = m.id
        JOIN profesores p ON s.profesor_id = p.id
        JOIN usuarios up ON p.usuario_id = up.id
        JOIN oferta_academica oa ON s.oferta_academica_id = oa.id
        JOIN trayectos t ON oa.trayecto_id = t.id
        JOIN trimestres tr ON oa.trimestre_id = tr.id
        LEFT JOIN calificaciones c ON c.inscripcion_id = i.id
        WHERE i.estudiante_id = ?
    ) as sub
    -- Filtra para que solo se muestre el registro con el número de fila 1 (el más reciente)
    WHERE sub.rn = 1
    ORDER BY sub.materia ASC
");

// Solo necesitamos el ID del estudiante una vez
$stmt->execute([$estudiante['id']]);

$materias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// **NUEVO BLOQUE:** Recalculamos estadísticas sin post-filtrar, usando $materias directamente
$materias_unicas = [];
foreach ($materias as $materia) {
    // Usamos el nombre de la materia como clave. Ya está filtrado a ser único por SQL.
    $materias_unicas[$materia['materia']] = $materia; 
}


$aprobadas = 0;
$reprobadas = 0;
$sin_calificar = 0;
$suma_notas = 0;
$materias_con_nota = 0;

foreach ($materias_unicas as $materia) { // Usamos $materias_unicas que ahora es el mismo $materias
    $es_proyecto = (strpos(strtolower($materia['materia']), 'proyecto socio tecnológico') !== false);
    $limite_aprobacion = $es_proyecto ? 16 : 12;
    
    if ($materia['calificacion'] >= $limite_aprobacion) {
        $aprobadas++;
        $suma_notas += $materia['calificacion'];
        $materias_con_nota++;
    } elseif ($materia['calificacion'] > 0) {
        $reprobadas++;
        $suma_notas += $materia['calificacion'];
        $materias_con_nota++;
    } else {
        $sin_calificar++;
    }
}

$promedio = $materias_con_nota > 0 ? round($suma_notas / $materias_con_nota, 2) : 0;
// FIN DEL BLOQUE RECALCULADO
?>
<!doctype html>
<html class="no-js" lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Mis Materias</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../../assets/css/cs-skin-elastic.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>
    <?php include '../../models/header.php'; ?>

    <div class="content">
        <div class="animated fadeIn">
            <div class="row">
                <div class="col-lg-12">
        <h1>Mis Materias</h1>
                    <div class="card mb-4">
                        <div class="card-header">
                            <strong>Información del Estudiante</strong>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Nombre:</strong> <?= htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido']) ?>
                                </div>
                                <div class="col-md-6">
                                    <strong>Cédula:</strong> <?= htmlspecialchars($estudiante['cedula']) ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resumen Académico -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <strong>Resumen Académico</strong>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <strong>Promedio General:</strong><br>
                                    <?php if ($promedio > 0): ?>
                                        <?= $promedio ?>
                                    <?php else: ?>
                                        Sin promedio
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-3">
                                    <strong>Aprobadas:</strong><br>
                                    <?= $aprobadas ?> materias
                                </div>
                                <div class="col-md-3">
                                    <strong>Reprobadas:</strong><br>
                                    <?= $reprobadas ?> materias
                                </div>
                                <div class="col-md-3">
                                    <strong>Sin Calificar:</strong><br>
                                    <?= $sin_calificar ?> materias
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <strong>Mis Materias</strong>
                            <small class="text-muted ml-2">(Total: <?= count($materias) ?>)</small>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>Materia</th>
                            <th>Profesor</th>
                            <th>Trayecto</th>
                            <th>Tipo Oferta</th>
                            <th>Período</th>
                            <th>Calificación</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($materias as $materia): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($materia['materia']) ?></strong></td>
                                <td><?= htmlspecialchars($materia['profesor']) ?></td>
                                <td><?= htmlspecialchars($materia['trayecto']) ?></td>
                                <td>
                                    <span class="badge badge-<?= $materia['tipo_oferta'] == 'reparacion' ? 'warning' : 'success' ?>">
                                        <?= ucfirst($materia['tipo_oferta']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($materia['periodo_academico']) ?></td>
                                <td class="text-center">
                                    <strong><?= $materia['calificacion'] ?></strong>
                                </td>
                                <td class="text-center">
                                    <?php 
                                        $es_proyecto = (strpos(strtolower($materia['materia']), 'proyecto socio tecnológico') !== false);
                                        $limite_aprobacion = $es_proyecto ? 16 : 12;
                                        
                                        if ($materia['calificacion'] >= $limite_aprobacion) {
                                            echo '<span class="badge badge-success">Aprobada</span>';
                                        } else {
                                            echo '<span class="badge badge-danger">Reprobada</span>';
                                        }
                                    ?>
                                </td>
                                <td class="text-center">
                                    <small class="text-muted">
                                        <?= $materia['fecha_calificacion'] ? date('d/m/Y', strtotime($materia['fecha_calificacion'])) : '-' ?>
                                    </small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../models/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../../assets/js/lib/bootstrap/js/bootstrap.min.js"></script>
    <script src="../../assets/js/main.js"></script>
</body>
</html>