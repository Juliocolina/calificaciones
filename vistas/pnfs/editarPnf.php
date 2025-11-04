<?php
require_once __DIR__ . '/../../models/header.php';
require_once __DIR__ . '/../../config/conexion.php';
$conn = conectar();

// 1. Validar que se recibió un ID válido para editar
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo "<div class='alert alert-danger'>ID de PNF inválido.</div>";
    exit;
}

$id = $_POST['id'];

// 2. Consultar los datos del PNF de la base de datos
$stmt = $conn->prepare("SELECT nombre, codigo, descripcion FROM pnfs WHERE id = ?");
$stmt->execute([$id]);
$pnf = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pnf) {
    echo "<div class='alert alert-warning'>PNF no encontrado.</div>";
    exit;
}
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Edición de PNF - Sistema de Carga de Notas</title>
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
                    <h3><i class="fa fa-graduation-cap"></i> Editar PNF</h3>
                </div>
                <div class="card-body">
                 <form action="../../controladores/pnfController/actualizarPnf.php" method="POST" autocomplete="off">
                 <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
                 <table class="table table-borderless">
                            <tbody>
                                <tr>
                                    <th style="width: 30%;"><label for="nombre"><i class="fa fa-book"></i> Nombre del PNF</label></th>
                                    <td><input type="text" name="nombre" id="nombre" class="form-control" required value="<?= htmlspecialchars($pnf['nombre']) ?>"></td>
                                </tr>
                                <tr>
                                    <th><label for="codigo"><i class="fa fa-barcode"></i> Código del PNF</label></th>
                                    <td><input type="text" name="codigo" id="codigo" class="form-control" required value="<?= htmlspecialchars($pnf['codigo']) ?>"></td>
                                </tr>
                                <tr>
                                    <th><label for="descripcion"><i class="fa fa-align-left"></i> Descripción (opcional)</label></th>
                                    <td><textarea name="descripcion" id="descripcion" class="form-control" rows="3"><?= htmlspecialchars($pnf['descripcion']) ?></textarea></td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="text-right">
                            <a href="verPnfs.php" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-success">Guardar Cambios</button>
                        </div>                    </form>

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