<?php
session_start();
require_once '../../config/conexion.php';
require_once '../../controladores/hellpers/auth.php';

verificarRol(['admin', 'coordinador']);

$pdo = conectar();
$usuario_id = $_SESSION['usuario_id'];
$rol = $_SESSION['rol'];

// Obtener filtros
$aldea_id = intval($_GET['aldea_id'] ?? 0);
$pnf_id = intval($_GET['pnf_id'] ?? 0);
$materia_id = intval($_GET['materia_id'] ?? 0);
$periodo_academico = $_GET['periodo_academico'] ?? '';
$estado = $_GET['estado'] ?? '';
$cedula = trim($_GET['cedula'] ?? '');

// Restricción para coordinadores
$aldea_coordinador = null;
if ($rol === 'coordinador') {
    $stmt = $pdo->prepare("SELECT aldea_id FROM coordinadores WHERE usuario_id = ?");
    $stmt->execute([$usuario_id]);
    $coord_data = $stmt->fetch();
    $aldea_coordinador = $coord_data['aldea_id'] ?? null;
    $aldea_id = $aldea_coordinador; // Forzar aldea del coordinador
}

// Obtener aldeas
$aldeas = [];
if ($rol === 'admin') {
    $aldeas = $pdo->query("SELECT id, nombre FROM aldeas ORDER BY nombre")->fetchAll();
} elseif ($aldea_coordinador) {
    $stmt = $pdo->prepare("SELECT id, nombre FROM aldeas WHERE id = ?");
    $stmt->execute([$aldea_coordinador]);
    $aldeas = $stmt->fetchAll();
}

// Obtener PNFs
$pnfs = [];
if ($aldea_id > 0) {
    $stmt = $pdo->prepare("SELECT id, nombre FROM pnfs WHERE aldea_id = ? ORDER BY nombre");
    $stmt->execute([$aldea_id]);
    $pnfs = $stmt->fetchAll();
}

// Obtener materias
$materias = [];
if ($pnf_id > 0) {
    $stmt = $pdo->prepare("
        SELECT DISTINCT m.id, m.nombre, m.duracion
        FROM materias m
        WHERE m.pnf_id = ?
        ORDER BY m.nombre
    ");
    $stmt->execute([$pnf_id]);
    $materias = $stmt->fetchAll();
}

// Obtener períodos académicos desde trimestres
$periodos_lista = $pdo->query("
    SELECT nombre as codigo
    FROM trimestres 
    ORDER BY nombre DESC
")->fetchAll();

// Obtener datos del reporte
$estudiantes = [];

if ($aldea_id > 0 && $pnf_id > 0) {
    $where_conditions = ["oa.aldea_id = ?", "oa.pnf_id = ?"];
    $params = [$aldea_id, $pnf_id];
    
    if ($materia_id > 0) {
        $where_conditions[] = "m.id = ?";
        $params[] = $materia_id;
    }
    
    if (!empty($periodo_academico)) {
        // Mapear formato: "Trimestre 2026-1" -> "2026-T1"
        $periodo_mapeado = $periodo_academico;
        if (preg_match('/Trimestre (\d{4})-(\d)/', $periodo_academico, $matches)) {
            $periodo_mapeado = $matches[1] . '-T' . $matches[2];
        }
        
        $where_conditions[] = "(c.periodo_academico = ? OR c.periodo_academico = ?)";
        $params[] = $periodo_academico;  // Formato nuevo
        $params[] = $periodo_mapeado;    // Formato antiguo
    }
    
    if (!empty($cedula)) {
        $where_conditions[] = "u.cedula LIKE ?";
        $params[] = "%$cedula%";
    }
    
    if ($estado === 'aprobados') {
        $where_conditions[] = "(
            (m.nombre LIKE '%proyecto socio tecnol%' AND c.nota_numerica >= 16) OR 
            (m.nombre NOT LIKE '%proyecto socio tecnol%' AND c.nota_numerica >= 12)
        )";
    } elseif ($estado === 'reprobados') {
        $where_conditions[] = "(
            (m.nombre LIKE '%proyecto socio tecnol%' AND c.nota_numerica < 16 AND c.nota_numerica > 0) OR 
            (m.nombre NOT LIKE '%proyecto socio tecnol%' AND c.nota_numerica < 12 AND c.nota_numerica > 0)
        )";
    }
    
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
    
    $stmt = $pdo->prepare("
        SELECT 
            MAX(u.cedula) as cedula,
            MAX(CONCAT(u.nombre, ' ', u.apellido)) as nombre_completo,
            MAX(e.codigo_estudiante) as codigo_estudiante,
            MAX(m.nombre) as materia_nombre,
            MAX(m.duracion) as duracion,
            MAX(t.nombre) as trayecto_nombre,
            MAX(p.nombre) as pnf_nombre,
            MAX(CONCAT(up.nombre, ' ', up.apellido)) as profesor_nombre,
            MAX(c.nota_numerica) as nota_numerica,
            MAX(c.periodo_academico) as periodo_academico,
            MAX(i.estatus) as estatus,
            CASE 
                WHEN (MAX(m.nombre) LIKE '%proyecto socio tecnol%' AND MAX(c.nota_numerica) >= 16) THEN 'Aprobado'
                WHEN (MAX(m.nombre) NOT LIKE '%proyecto socio tecnol%' AND MAX(c.nota_numerica) >= 12) THEN 'Aprobado'
                WHEN MAX(c.nota_numerica) > 0 THEN 'Reprobado'
                ELSE 'Sin Calificar'
            END as estado_final
        FROM inscripciones i
        JOIN estudiantes e ON i.estudiante_id = e.id
        JOIN usuarios u ON e.usuario_id = u.id
        JOIN secciones s ON i.seccion_id = s.id
        JOIN materias m ON s.materia_id = m.id
        JOIN oferta_academica oa ON s.oferta_academica_id = oa.id
        JOIN trayectos t ON oa.trayecto_id = t.id
        JOIN pnfs p ON oa.pnf_id = p.id
        JOIN profesores pr ON s.profesor_id = pr.id
        JOIN usuarios up ON pr.usuario_id = up.id
        LEFT JOIN calificaciones c ON i.id = c.inscripcion_id
        $where_clause
        GROUP BY e.id, m.id
        ORDER BY MAX(m.nombre), MAX(u.apellido), MAX(u.nombre)
    ");
    $stmt->execute($params);
    $estudiantes = $stmt->fetchAll();

}
?>

<!doctype html>
<html class="no-js" lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Listado de Aprobados/Reprobados</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../../assets/css/cs-skin-elastic.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>
    <?php include '../../models/header.php'; ?>

    <div class="breadcrumbs">
        <div class="breadcrumbs-inner">
            <div class="row m-0">
                <div class="col-sm-4">
                    <div class="page-header float-left">
                        <div class="page-title">
                            <h1>Aprobados/Reprobados</h1>
                        </div>
                    </div>
                </div>
                <div class="col-sm-8">
                    <div class="page-header float-right">
                        <div class="page-title">
                            <ol class="breadcrumb text-right">
                                <li><a href="../home.php">Inicio</a></li>
                                <li class="active">Listado Aprobados/Reprobados</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="animated fadeIn">
            
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <strong>Filtros de Consulta</strong>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="mb-4">
                                <div class="row">
                                    <?php if ($rol === 'admin'): ?>
                                    <div class="col-md-3">
                                        <label for="aldea_id">Aldea:</label>
                                        <select name="aldea_id" id="aldea_id" class="form-control" required>
                                            <option value="">Seleccione aldea</option>
                                            <?php foreach ($aldeas as $aldea): ?>
                                                <option value="<?= $aldea['id'] ?>" <?= $aldea['id'] == $aldea_id ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($aldea['nombre']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <?php else: ?>
                                        <input type="hidden" name="aldea_id" value="<?= $aldea_coordinador ?>">
                                    <?php endif; ?>
                                    
                                    <div class="col-md-3">
                                        <label for="pnf_id">PNF:</label>
                                        <select name="pnf_id" id="pnf_id" class="form-control" required>
                                            <option value="">Seleccione PNF</option>
                                            <?php foreach ($pnfs as $pnf): ?>
                                                <option value="<?= $pnf['id'] ?>" <?= $pnf['id'] == $pnf_id ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($pnf['nombre']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <label for="materia_id">Materia:</label>
                                        <select name="materia_id" id="materia_id" class="form-control">
                                            <option value="">Todas las materias</option>
                                            <?php foreach ($materias as $materia): ?>
                                                <option value="<?= $materia['id'] ?>" <?= $materia['id'] == $materia_id ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($materia['nombre']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <label for="periodo_academico">Período:</label>
                                        <select name="periodo_academico" id="periodo_academico" class="form-control">
                                            <option value="">Todos los períodos</option>
                                            <?php foreach ($periodos_lista as $periodo): ?>
                                                <option value="<?= $periodo['codigo'] ?>" <?= $periodo['codigo'] == $periodo_academico ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($periodo['codigo']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row mt-2">
                                    <div class="col-md-3">
                                        <label for="estado">Estado:</label>
                                        <select name="estado" id="estado" class="form-control">
                                            <option value="">Todos</option>
                                            <option value="aprobados" <?= $estado === 'aprobados' ? 'selected' : '' ?>>Solo Aprobados</option>
                                            <option value="reprobados" <?= $estado === 'reprobados' ? 'selected' : '' ?>>Solo Reprobados</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <label for="cedula">Cédula:</label>
                                        <input type="text" name="cedula" id="cedula" class="form-control" 
                                               placeholder="Buscar por cédula" value="<?= htmlspecialchars($cedula) ?>">
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <label>&nbsp;</label>
                                        <button type="submit" class="btn btn-primary form-control">
                                            <i class="fa fa-search"></i> Consultar
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($estudiantes)): ?>
            <!-- Listado -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between">
                            <strong>Listado de Estudiantes</strong>
                            <form method="POST" action="../../controladores/reportes/estudiantesInscritosPDF.php" target="_blank" style="display: inline;">
                                <input type="hidden" name="aldea_id" value="<?= $aldea_id ?>">
                                <input type="hidden" name="pnf_id" value="<?= $pnf_id ?>">
                                <input type="hidden" name="trayecto_id" value="">
                                <input type="hidden" name="materia_id" value="<?= $materia_id ?>">
                                <input type="hidden" name="periodo_academico" value="<?= $periodo_academico ?>">
                                <input type="hidden" name="estado" value="<?= $estado ?>">
                                <input type="hidden" name="cedula" value="<?= $cedula ?>">
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="fa fa-file-pdf-o"></i> PDF
                                </button>
                            </form>
                        </div>
                        <div class="card-body">
                            <!-- Encabezado del Reporte -->
                            <?php if (!empty($estudiantes)): ?>
                                <div class="mb-4">
                                    <div class="text-center mb-3">
                                        <h4 class="text-primary mb-1">SICAN - Sistema Integral de Calificaciones Académicas</h4>
                                        <p class="text-muted mb-0"><em>Misión Sucre | Municipio Miranda, Estado Falcón, Venezuela</em></p>
                                        <hr class="my-3">
                                        <h5 class="mb-3">Reporte de Rendimiento Académico</h5>
                                    </div>
                                    
                                    <div class="row p-3 bg-light rounded">
                                        <div class="col-md-3">
                                            <strong>Trayecto:</strong><br>
                                            <span class="text-info"><?= htmlspecialchars($estudiantes[0]['trayecto_nombre']) ?></span>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Período Académico:</strong><br>
                                            <span class="text-info"><?= htmlspecialchars($periodo_academico ?: 'Todos los períodos') ?></span>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Total de Registros:</strong><br>
                                            <span class="text-success"><?= count($estudiantes) ?> estudiantes</span>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Fecha de Consulta:</strong><br>
                                            <span class="text-secondary"><?= date('d/m/Y H:i') ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>Cédula</th>
                                            <th>Estudiante</th>
                                            <th>Código</th>
                                            <th>Materia</th>
                                            <th>PNF</th>
                                            <th>Profesor</th>
                                            <th class="text-center">Nota</th>
                                            <th class="text-center">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($estudiantes as $estudiante): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($estudiante['cedula']) ?></td>
                                                <td><?= htmlspecialchars($estudiante['nombre_completo']) ?></td>
                                                <td><?= htmlspecialchars($estudiante['codigo_estudiante']) ?></td>
                                                <td>
                                                    <?= htmlspecialchars($estudiante['materia_nombre']) ?>
                                                    <small class="text-muted">(<?= ucfirst($estudiante['duracion']) ?>)</small>
                                                </td>
                                                <td><?= htmlspecialchars($estudiante['pnf_nombre']) ?></td>
                                                <td><?= htmlspecialchars($estudiante['profesor_nombre']) ?></td>
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
                                                        $estudiante['estado_final'] === 'Aprobado' ? 'success' : 
                                                        ($estudiante['estado_final'] === 'Reprobado' ? 'danger' : 'warning') 
                                                    ?>">
                                                        <?= $estudiante['estado_final'] ?>
                                                    </span>
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
            <?php elseif ($aldea_id > 0 && $pnf_id > 0): ?>
            <div class="alert alert-info">
                No se encontraron estudiantes con los filtros seleccionados.
            </div>
            <?php endif; ?>

        </div>
    </div>

    <?php include '../../models/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../../assets/js/lib/bootstrap/js/bootstrap.min.js"></script>
    <script src="../../assets/js/main.js"></script>
    
    <script>
    $(document).ready(function() {
        // Cargar PNFs cuando se selecciona aldea
        $('#aldea_id').change(function() {
            const aldeaId = $(this).val();
            
            if (aldeaId) {
                $.ajax({
                    url: '../../api/getPnfsByAldea.php',
                    method: 'GET',
                    data: { aldea_id: aldeaId },
                    dataType: 'json',
                    success: function(pnfs) {
                        let options = '<option value="">Seleccione PNF</option>';
                        pnfs.forEach(function(pnf) {
                            options += `<option value="${pnf.id}">${pnf.nombre}</option>`;
                        });
                        $('#pnf_id').html(options);
                        $('#materia_id').html('<option value="">Todas las materias</option>');
                    }
                });
            } else {
                $('#pnf_id').html('<option value="">Seleccione PNF</option>');
                $('#materia_id').html('<option value="">Todas las materias</option>');
            }
        });
        
        // Cargar materias cuando se selecciona PNF
        $('#pnf_id').change(function() {
            const pnfId = $(this).val();
            
            if (pnfId) {
                $.ajax({
                    url: '../../api/getMateriasByPnf.php',
                    method: 'GET',
                    data: { pnf_id: pnfId },
                    dataType: 'json',
                    success: function(materias) {
                        let options = '<option value="">Todas las materias</option>';
                        materias.forEach(function(materia) {
                            options += `<option value="${materia.id}">${materia.nombre}</option>`;
                        });
                        $('#materia_id').html(options);
                    }
                });
            } else {
                $('#materia_id').html('<option value="">Todas las materias</option>');
            }
        });
    });
    </script>
    
    <style>
    @media print {
        .card-header .btn, .breadcrumbs, aside, header, form, .alert {
            display: none !important;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        body {
            background: white !important;
        }
    }
    </style>
</body>
</html>