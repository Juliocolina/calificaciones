<?php
require_once '../../config/conexion.php';
require_once '../../controladores/hellpers/auth.php';

verificarRol(['admin', 'coordinador']);

$pdo = conectar();
$materia_id = $_GET['materia_id'] ?? null;

if (!$materia_id) {
    header('Location: materiasPorPnf.php');
    exit;
}

// Obtener información de la materia
$stmt = $pdo->prepare("
    SELECT m.*, p.nombre as pnf_nombre 
    FROM materias m 
    JOIN pnfs p ON m.pnf_id = p.id 
    WHERE m.id = ?
");
$stmt->execute([$materia_id]);
$materia = $stmt->fetch();

if (!$materia) {
    header('Location: materiasPorPnf.php');
    exit;
}

// Obtener profesores ya asignados a esta materia
$stmt = $pdo->prepare("
    SELECT pr.id, CONCAT(u.nombre, ' ', u.apellido) as nombre_completo, u.cedula
    FROM materia_profesor mp
    JOIN profesores pr ON mp.profesor_id = pr.id
    JOIN usuarios u ON pr.usuario_id = u.id
    WHERE mp.materia_id = ?
");
$stmt->execute([$materia_id]);
$profesores_asignados = $stmt->fetchAll();

require_once '../../models/header.php';
?>

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
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4><i class="fa fa-user-plus"></i> Asignar Profesor a Materia</h4>
                    </div>
                    <div class="card-body">
                        
                        <!-- Información de la materia -->
                        <div class="alert alert-info">
                            <h5><i class="fa fa-book"></i> <?php echo $materia['nombre']; ?></h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>PNF:</strong> <?php echo $materia['pnf_nombre']; ?><br>
                                    <strong>Código:</strong> <?php echo $materia['codigo'] ?: 'N/A'; ?>
                                </div>
                                <div class="col-md-6">
                                    <strong>Créditos:</strong> <?php echo $materia['creditos']; ?><br>
                                    <strong>Duración:</strong> <?php echo ucfirst($materia['duracion']); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Formulario para buscar y asignar profesor -->
                        <form method="POST" action="../../controladores/profesorController/asignarMateria.php">
                            <input type="hidden" name="materia_id" value="<?php echo $materia_id; ?>">
                            
                            <div class="form-group">
                                <label for="cedula_profesor"><strong>Buscar Profesor por Cédula</strong></label>
                                <div class="input-group">
                                    <input type="text" name="cedula_profesor" id="cedula_profesor" 
                                           class="form-control" placeholder="Ingrese cédula del profesor" required>
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fa fa-plus"></i> Asignar
                                        </button>
                                    </div>
                                </div>
                                <small class="form-text text-muted">
                                    Ingrese la cédula del profesor que desea asignar a esta materia
                                </small>
                            </div>
                        </form>

                        <div class="mt-3">
                            <a href="materiasPorPnf.php" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5><i class="fa fa-users"></i> Profesores Asignados</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($profesores_asignados)): ?>
                            <div class="alert alert-warning">
                                <i class="fa fa-info-circle"></i> No hay profesores asignados a esta materia.
                            </div>
                        <?php else: ?>
                            <?php foreach ($profesores_asignados as $profesor): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                                    <div>
                                        <strong><?php echo $profesor['nombre_completo']; ?></strong><br>
                                        <small class="text-muted">CI: <?php echo $profesor['cedula']; ?></small>
                                    </div>
                                    <form method="POST" action="../../controladores/profesorController/quitarMateria.php" style="display: inline;">
                                        <input type="hidden" name="profesor_id" value="<?php echo $profesor['id']; ?>">
                                        <input type="hidden" name="materia_id" value="<?php echo $materia_id; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" 
                                                onclick="return confirm('¿Quitar este profesor de la materia?')">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once '../../models/footer.php'; ?>