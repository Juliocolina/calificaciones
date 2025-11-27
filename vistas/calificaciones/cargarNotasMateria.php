<?php
session_start();
require_once '../../config/conexion.php';
require_once '../../controladores/hellpers/auth.php';

verificarSesion();

$pdo = conectar();
$secciones_ids = $_GET['secciones'] ?? '';
$materia_nombre = $_GET['materia'] ?? '';

if (!$secciones_ids) {
    header('Location: cargarNotasFinal.php');
    exit;
}

// Obtener estudiantes de las secciones
$placeholders = str_repeat('?,', substr_count($secciones_ids, ',') + 1);
$placeholders = rtrim($placeholders, ',');

$stmt = $pdo->prepare("
    SELECT 
        i.id as inscripcion_id,
        u.nombre,
        u.apellido,
        u.cedula,
        s.id as seccion_id,
        m.nombre as materia_nombre,
        c.nota_numerica,
        c.id as calificacion_id
    FROM inscripciones i
    JOIN usuarios u ON i.estudiante_id = (SELECT e.id FROM estudiantes e WHERE e.usuario_id = u.id)
    JOIN secciones s ON i.seccion_id = s.id
    JOIN materias m ON s.materia_id = m.id
    LEFT JOIN calificaciones c ON i.id = c.inscripcion_id
    WHERE s.id IN ($placeholders) AND i.estatus = 'Cursando'
    ORDER BY u.apellido, u.nombre
");

$secciones_array = explode(',', $secciones_ids);
$stmt->execute($secciones_array);
$estudiantes = $stmt->fetchAll();

// Obtener períodos académicos activos
$stmt = $pdo->prepare("
    SELECT codigo, nombre 
    FROM periodos_academicos 
    WHERE activo = 1 
    ORDER BY fecha_inicio DESC
");
$stmt->execute();
$periodos = $stmt->fetchAll();
?>

<!doctype html>
<html class="no-js" lang="es">
<head>
    <meta charset="utf-8">
    <title>Cargar Notas - <?php echo htmlspecialchars($materia_nombre); ?></title>
    <link rel="stylesheet" href="../../assets/css/cs-skin-elastic.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>
    <?php include '../../models/header.php'; ?>

    <div class="content">
        <div class="animated fadeIn">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <strong>Cargar Notas - <?php echo htmlspecialchars($materia_nombre); ?></strong>
                        </div>
                        <div class="card-body">
                            <?php if (empty($estudiantes)): ?>
                                <div class="alert alert-warning">
                                    No hay estudiantes inscritos en esta materia.
                                </div>
                            <?php else: ?>

                                <div class="row">
                                    <?php foreach ($estudiantes as $estudiante): ?>
                                        <div class="col-md-6 mb-4">
                                            <div class="card <?php echo $estudiante['calificacion_id'] ? 'border-success' : 'border-warning'; ?>">
                                                <div class="card-body">
                                                    <h6 class="card-title">
                                                        <?php echo htmlspecialchars($estudiante['apellido'] . ', ' . $estudiante['nombre']); ?>
                                                    </h6>
                                                    <p class="card-text">
                                                        <small class="text-muted">Cédula: <?php echo htmlspecialchars($estudiante['cedula']); ?></small>
                                                    </p>
                                                    
                                                    <?php if ($estudiante['calificacion_id']): ?>
                                                        <div class="alert alert-success mb-2">
                                                            <strong>Nota Final: <?php echo $estudiante['nota_numerica']; ?></strong>
                                                        </div>
                                                        <span class="badge badge-success">Ya Calificado</span>
                                                    <?php else: ?>
                                                        <form method="POST" action="../../controladores/calificacionesController/registrarNotaFinal.php" class="nota-form">
                                                            <input type="hidden" name="inscripcion_id" value="<?php echo $estudiante['inscripcion_id']; ?>">
                                                            
                                                            <div class="form-group">
                                                                <label>Período Académico:</label>
                                                                <select name="periodo_academico" class="form-control form-control-sm" required>
                                                                    <option value="">Seleccionar</option>
                                                                    <?php foreach ($periodos as $periodo): ?>
                                                                        <option value="<?php echo $periodo['codigo']; ?>">
                                                                            <?php echo $periodo['nombre']; ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            
                                                            <div class="input-group mb-2">
                                                                <input type="number" 
                                                                       name="nota_numerica" 
                                                                       class="form-control" 
                                                                       min="0" max="20" 
                                                                       step="0.1" 
                                                                       placeholder="Nota (0-20)" 
                                                                       required>
                                                                <div class="input-group-append">
                                                                    <button type="submit" class="btn btn-success">
                                                                        <i class="fa fa-save"></i> Guardar
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="text-center mt-4">
                                    <a href="cargarNotasFinal.php" class="btn btn-secondary btn-lg">
                                        <i class="fa fa-arrow-left"></i> Volver a Mis Secciones
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../models/footer.php'; ?>
</body>
</html>