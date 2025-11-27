<?php
session_start();
require_once '../../config/conexion.php';
require_once '../../controladores/hellpers/auth.php';

verificarRol(['admin', 'coordinador', 'profesor']);

$pdo = conectar();
$usuario_id = $_SESSION['usuario_id'];
$rol = $_SESSION['rol'];



// Obtener materias según el rol
$materias = [];
if ($rol === 'profesor') {
    // Solo materias del profesor
    $stmt = $pdo->prepare("
        SELECT DISTINCT m.id, m.nombre, m.duracion
        FROM materias m
        JOIN secciones s ON m.id = s.materia_id
        JOIN profesores p ON s.profesor_id = p.id
        WHERE p.usuario_id = ?
        ORDER BY m.nombre
    ");
    $stmt->execute([$usuario_id]);
    $materias = $stmt->fetchAll();
} else {
    // Todas las materias para admin/coordinador
    $stmt = $pdo->prepare("
        SELECT DISTINCT m.id, m.nombre, m.duracion
        FROM materias m
        JOIN secciones s ON m.id = s.materia_id
        ORDER BY m.nombre
    ");
    $stmt->execute();
    $materias = $stmt->fetchAll();
}

// Obtener años académicos desde trimestres
$anos = $pdo->query("
    SELECT DISTINCT SUBSTRING(nombre, -6, 4) as ano
    FROM trimestres 
    WHERE nombre LIKE 'Trimestre %'
    ORDER BY ano DESC
")->fetchAll();


?>

<!doctype html>
<html class="no-js" lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Acta de Calificaciones</title>
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
                            <h1>Acta de Calificaciones</h1>
                        </div>
                    </div>
                </div>
                <div class="col-sm-8">
                    <div class="page-header float-right">
                        <div class="page-title">
                            <ol class="breadcrumb text-right">
                                <li><a href="../home.php">Inicio</a></li>
                                <li class="active">Acta de Calificaciones</li>
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
                            <strong>Generar Acta de Calificaciones</strong>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="../../controladores/reportes/generarActaPDF.php" target="_blank" class="mb-4">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="materia_id">Materia:</label>
                                        <select name="materia_id" id="materia_id" class="form-control" required>
                                            <option value="">Seleccione una materia</option>
                                            <?php foreach ($materias as $materia): ?>
                                                <option value="<?= $materia['id'] ?>">
                                                    <?= htmlspecialchars($materia['nombre']) ?> (<?= ucfirst($materia['duracion']) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label for="periodo_academico">Año Académico:</label>
                                        <select name="periodo_academico" id="periodo_academico" class="form-control" required>
                                            <option value="">Seleccione un año</option>
                                            <?php foreach ($anos as $ano): ?>
                                                <option value="<?= $ano['ano'] ?>">
                                                    Año <?= $ano['ano'] ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <label>&nbsp;</label>
                                        <button type="submit" class="btn btn-success form-control">
                                            <i class="fa fa-file-pdf-o"></i> Generar PDF
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i> Seleccione una materia y año académico, luego haga clic en "Generar PDF" para crear el acta oficial.
            </div>

        </div>
    </div>

    <?php include '../../models/footer.php'; ?>

    <script src="../../assets/js/lib/jquery/jquery.min.js"></script>
    <script src="../../assets/js/lib/bootstrap/js/bootstrap.min.js"></script>
    <script src="../../assets/js/main.js"></script>
</body>
</html>