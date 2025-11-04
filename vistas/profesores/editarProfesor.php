<?php
require_once __DIR__ . '/../../models/header.php';
require_once __DIR__ . '/../../config/conexion.php';
$conn = conectar();

// Validar que se recibió un ID válido
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo "<div class='alert alert-danger'>ID de profesor inválido.</div>";
    exit;
}

$id = $_POST['id'];

// Consultar los datos del profesor
$stmt = $conn->prepare("SELECT p.*, u.cedula AS usuario_cedula, u.nombre AS usuario_nombre, u.apellido AS usuario_apellido, u.correo AS usuario_correo, u.telefono AS usuario_telefono
    FROM profesores p
    LEFT JOIN usuarios u ON p.usuario_id = u.id
    WHERE p.id = ?");
$stmt->execute([$id]);
$profesor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profesor) {
    echo "<div class='alert alert-warning'>Profesor no encontrado.</div>";
    exit;
}

// Consultar todas las aldeas para el menú desplegable
$aldeas = $conn->query("SELECT id, nombre FROM aldeas ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

// Consultar todos los PNFs para el menú desplegable
$pnfs = $conn->query("SELECT id, nombre FROM pnfs ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Registro de Usuario - Sistema de Carga de Notas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
        }
        .card {
            margin-top: 60px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(30,60,114,0.15);
        }
        .table th, .table td {
            vertical-align: middle !important;
        }
        .form-title {
            background: #1e3c72;
            color: #fff;
            padding: 20px 0;
            border-radius: 16px 16px 0 0;
            margin-bottom: 30px;
        }
        .btn-success {
            background: #2a5298;
            border: none;
        }
        .btn-success:hover {
            background: #1e3c72;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="form-title text-center">
                    <h3><i class="fa fa-user-plus"></i> Editar perfil de Profesor</h3>
                </div>
                <div class="card-body">
                 <form action="../../controladores/profesorController/actualizarProfesor.php" method="POST" autocomplete="off">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">

    <table class="table table-borderless">
        <tbody>
            <tr>
                <th style="width: 30%;"><label for="nombre_prof"><i class="fa fa-user"></i> Nombre</label></th>
                <td><input type="text" name="nombre" id="nombre_prof" class="form-control" value="<?= htmlspecialchars($profesor['usuario_nombre'] ?? $profesor['nombre'] ?? '') ?>" required></td>
            </tr>
            <tr>
                <th><label for="apellido_prof"><i class="fa fa-user"></i> Apellido</label></th>
                <td><input type="text" name="apellido" id="apellido_prof" class="form-control" value="<?= htmlspecialchars($profesor['usuario_apellido'] ?? $profesor['apellido'] ?? '') ?>" required></td>
            </tr>
            <tr>
                <th><label for="cedula_prof"><i class="fa fa-id-badge"></i> Cédula</label></th>
                <td><input type="text" name="cedula" id="cedula_prof" class="form-control" value="<?= htmlspecialchars($profesor['usuario_cedula'] ?? $profesor['cedula'] ?? '') ?>" required></td>
            </tr>
            <tr>
                <th><label for="correo_prof"><i class="fa fa-envelope"></i> Correo electrónico</label></th>
                <td><input type="email" name="correo" id="correo_prof" class="form-control" value="<?= htmlspecialchars($profesor['usuario_correo'] ?? $profesor['correo'] ?? '') ?>"  required></td>
            </tr>
            <tr>
                <th><label for="telefono_prof"><i class="fa fa-phone"></i> Teléfono</label></th>
                <td><input type="text" name="telefono" id="telefono_prof" class="form-control" value="<?= htmlspecialchars($profesor['usuario_telefono'] ?? $profesor['telefono'] ?? '') ?>" ></td>
            </tr>
            <tr>
                <th><label for="titulo_prof"><i class="fa fa-graduation-cap"></i> Título</label></th>
                <td><input type="text" name="titulo" id="titulo_prof" class="form-control" value="<?= htmlspecialchars($profesor['titulo'] ?? '') ?>" ></td>
            </tr>
            <tr>
                <th><label for="especialidad_prof"><i class="fa fa-briefcase"></i> Especialidad</label></th>
                <td><input type="text" name="especialidad" id="especialidad_prof" class="form-control" value="<?= htmlspecialchars($profesor['especialidad'] ?? '') ?>" ></td>
            </tr>
            <tr>
                <th><label for="aldea_id"><i class="fa fa-university"></i> Aldea</label></th>
                <td>
                    <select name="aldea_id" id="aldea_id" class="form-control" required>
                        <option value="">Seleccione una aldea</option>
                        <?php foreach ($aldeas as $aldea): ?>
                            <option value="<?= htmlspecialchars($aldea['id']) ?>" <?= ($profesor['aldea_id'] == $aldea['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($aldea['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="pnf_id"><i class="fa fa-graduation-cap"></i> PNF</label></th>
                <td>
                    <select name="pnf_id" id="pnf_id" class="form-control">
                        <option value="">Seleccione un PNF</option>
                        <?php foreach ($pnfs as $pnf): ?>
                            <option value="<?= htmlspecialchars($pnf['id']) ?>" <?= ($profesor['pnf_id'] == $pnf['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pnf['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </tbody>
    </table>

                        <div class="text-right">
                            <a href="verProfesores.php" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-success">Guardar Cambios</button>
                        </div>

   
</form>

<div class="text-center mt-4">
    <a href="../home.php" class="text-info"><i class="fa fa-arrow-left"></i> Volver al inicio</a>
</div>

                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
<?php require_once __DIR__ . '/../../models/footer.php'; ?>