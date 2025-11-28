<?php
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
verificarRol(['admin']);
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/conexion.php';
$conn = conectar();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='alert alert-danger'>ID de estudiante inválido.</div>";
    exit;
}

$id_estudiante = $_GET['id'];

// Obtener datos del estudiante
$stmt_estudiante = $conn->prepare("
    SELECT u.nombre, u.apellido, u.cedula 
    FROM estudiantes e 
    INNER JOIN usuarios u ON e.usuario_id = u.id 
    WHERE e.id = ?
");
$stmt_estudiante->execute([$id_estudiante]);
$estudiante = $stmt_estudiante->fetch(PDO::FETCH_ASSOC);

if (!$estudiante) {
    echo "<div class='alert alert-warning'>Estudiante no encontrado.</div>";
    exit;
}

// Obtener historial de graduaciones
$stmt_graduaciones = $conn->prepare("
    SELECT g.*, p.nombre as pnf_nombre 
    FROM graduaciones g 
    INNER JOIN pnfs p ON g.pnf_id = p.id 
    WHERE g.estudiante_id = ? 
    ORDER BY g.fecha_graduacion DESC
");
$stmt_graduaciones->execute([$id_estudiante]);
$graduaciones = $stmt_graduaciones->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Graduaciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="container mt-5">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h3><i class="fa fa-graduation-cap"></i> Historial de Graduaciones</h3>
            <p class="mb-0">Estudiante: <?= htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido']) ?> (<?= htmlspecialchars($estudiante['cedula']) ?>)</p>
        </div>
        <div class="card-body">
            <?php if (empty($graduaciones)): ?>
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> Este estudiante no tiene graduaciones registradas.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Tipo de Graduación</th>
                                <th>PNF</th>
                                <th>Fecha de Graduación</th>
                                <th>Registrado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($graduaciones as $graduacion): ?>
                                <tr>
                                    <td>
                                        <?php if ($graduacion['tipo_graduacion'] === 'TSU'): ?>
                                            <span class="badge bg-success"><i class="fa fa-certificate"></i> TSU</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary"><i class="fa fa-trophy"></i> Licenciado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($graduacion['pnf_nombre']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($graduacion['fecha_graduacion'])) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($graduacion['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <div class="text-center mt-3">
                <a href="verEstudiantes.php" class="btn btn-secondary">
                    <i class="fa fa-arrow-left"></i> Volver a Estudiantes
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>