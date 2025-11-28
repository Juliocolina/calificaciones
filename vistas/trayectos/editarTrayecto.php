<?php
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/conexion.php';
$conn = conectar();

// Validar que se recibió un ID válido
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo "<div class='alert alert-danger'>El ID del trayecto es inválido.</div>";
    exit;
}

$id = $_POST['id'];

// Consultar los datos del usuario
$stmt = $conn->prepare("SELECT nombre, slug, descripcion FROM trayectos WHERE id = ?");
$stmt->execute([$id]);
$trayecto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trayecto) {
    echo "<div class='alert alert-warning'>Trayecto no encontrado.</div>";
    exit;
}
?>


<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Registro de Trayecto - Sistema de Carga de Notas</title>
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
                    <h3><i class="fa fa-route"></i> Editar Trayecto</h3>
                </div>
                <div class="card-body">
                    <form action="../../controladores/trayectoController/actualizarTrayecto.php" method="POST" autocomplete="off">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
                            <table class="table table-borderless">
                            <tbody>
                                <tr>
                                    <th style="width: 30%;"><label for="nombre_trayecto"><i class="fa fa-road"></i> Nombre del Trayecto</label></th>
                                    <td><input type="text" name="nombre_trayecto" id="nombre_trayecto" class="form-control"  value="<?= htmlspecialchars($trayecto['nombre'])?>" required ></td>
                                </tr>
                                <tr>
                                    <th><label for="slug"><i class="fa fa-barcode"></i> Código del Trayecto</label></th>
                                <td><input type="text" name="slug" id="slug" class="form-control" required value="<?=htmlspecialchars($trayecto['slug'])?> "></td>
                                </tr>
                                <tr>
                                    <th><label for="descripcion"><i class="fa fa-info-circle"></i> Descripción (opcional)</label></th>
                                    <td><textarea name="descripcion" id="descripcion" class="form-control" rows="3" value="<?=htmlspecialchars($trayecto['descripcion'])?>" >  </textarea></td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="text-right">
                            <a href="verTrayectos.php" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-success">Guardar Cambios</button>
                        </div>
                                        </form>

                    <div class="text-center mt-4">
                        <a href="../index.php" class="text-info"><i class="fa fa-arrow-left"></i> Volver al inicio</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>