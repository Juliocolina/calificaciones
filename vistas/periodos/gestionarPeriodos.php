<?php
session_start();
require_once '../../config/conexion.php';
require_once '../../controladores/hellpers/auth.php';

verificarRol(['admin']);

$pdo = conectar();

// Obtener todos los períodos
$stmt = $pdo->prepare("
    SELECT p.codigo, p.trayecto_id, p.año, p.activo, t.nombre as trayecto_nombre
    FROM periodos_academicos p
    JOIN trayectos t ON p.trayecto_id = t.id
    ORDER BY p.año DESC, p.trayecto_id ASC
");
$stmt->execute();
$periodos = $stmt->fetchAll();

// Obtener trayectos para el formulario
$stmt = $pdo->prepare("SELECT id, nombre, slug FROM trayectos ORDER BY id");
$stmt->execute();
$trayectos = $stmt->fetchAll();
?>

<!doctype html>
<html class="no-js" lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Gestionar Períodos Académicos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../../assets/css/cs-skin-elastic.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>
    <?php include '../../includes/header.php'; ?>

    <div class="breadcrumbs">
        <div class="breadcrumbs-inner">
            <div class="row m-0">
                <div class="col-sm-4">
                    <div class="page-header float-left">
                        <div class="page-title">
                            <h1>Períodos Académicos</h1>
                        </div>
                    </div>
                </div>
                <div class="col-sm-8">
                    <div class="page-header float-right">
                        <div class="page-title">
                            <ol class="breadcrumb text-right">
                                <li><a href="../home.php">Inicio</a></li>
                                <li class="active">Períodos</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                            <strong>Períodos Académicos</strong>
                            <button class="btn btn-primary btn-sm float-right" data-toggle="modal" data-target="#nuevoPeriodoModal">
                                <i class="fa fa-plus"></i> Nuevo Período
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Código</th>
                                            <th>Trayecto</th>
                                            <th>Año</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($periodos as $periodo): ?>
                                            <tr>
                                                <td><strong><?php echo $periodo['codigo']; ?></strong></td>
                                                <td><?php echo $periodo['trayecto_nombre']; ?></td>
                                                <td><?php echo $periodo['año']; ?></td>
                                                <td>
                                                    <?php if ($periodo['activo']): ?>
                                                        <span class="badge badge-success">Activo</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-secondary">Inactivo</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!$periodo['activo']): ?>
                                                        <button class="btn btn-success btn-sm" onclick="activarPeriodo('<?php echo $periodo['codigo']; ?>')">
                                                            <i class="fa fa-play"></i> Activar
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn btn-warning btn-sm" onclick="desactivarPeriodo('<?php echo $periodo['codigo']; ?>')">
                                                            <i class="fa fa-pause"></i> Desactivar
                                                        </button>
                                                    <?php endif; ?>
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

    <!-- Modal Nuevo Período -->
    <div class="modal fade" id="nuevoPeriodoModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="../../controladores/periodosController/crearPeriodo.php">
                    <div class="modal-header">
                        <h5 class="modal-title">Nuevo Período Académico</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Trayecto</label>
                            <select name="trayecto_id" class="form-control" required>
                                <option value="">Seleccionar trayecto</option>
                                <?php foreach ($trayectos as $trayecto): ?>
                                    <option value="<?php echo $trayecto['id']; ?>"><?php echo $trayecto['nombre']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Año</label>
                            <input type="number" name="año" class="form-control" placeholder="2027" min="2026" max="2030" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear Período</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>

    <script src="../../assets/js/lib/jquery/jquery.min.js"></script>
    <script src="../../assets/js/lib/bootstrap/js/bootstrap.min.js"></script>
    <script src="../../assets/js/main.js"></script>
    
    <script>
    function activarPeriodo(codigo) {
        if (confirm('¿Activar este período? Esto desactivará otros períodos del mismo tipo.')) {
            window.location.href = '../../controladores/periodosController/activarPeriodo.php?codigo=' + codigo;
        }
    }
    
    function desactivarPeriodo(codigo) {
        if (confirm('¿Desactivar este período?')) {
            window.location.href = '../../controladores/periodosController/desactivarPeriodo.php?codigo=' + codigo;
        }
    }
    </script>
</body>
</html>