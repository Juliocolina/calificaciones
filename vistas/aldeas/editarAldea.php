<?php
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/conexion.php';
$conn = conectar();

// Validar que se recibió un ID válido
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo "<div class='alert alert-danger'>ID de usuario inválido.</div>";
    exit;
}

$id = $_POST['id'];

// Consultar los datos de la aldea
$stmt = $conn->prepare("SELECT nombre, codigo, direccion, descripcion FROM aldeas WHERE id = ?");
$stmt->execute([$id]);
// CORREGIDO: Usar una variable más descriptiva como $aldea
$aldea = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$aldea) {
    echo "<div class='alert alert-warning'>Aldea no encontrada.</div>";
    exit;
}
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Edición de Aldea - Sistema de Carga de Notas</title>
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
                    <h3><i class="fa fa-building"></i> Editar Aldea</h3>
                </div>
                <div class="card-body">
                 <form action="../../controladores/aldeaController/actualizarAldea.php" method="POST" data-validar-form autocomplete="off">
                 <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">                        
                 <table class="table table-borderless">
                            <tbody>
                                <tr>
                                    <th style="width: 30%;"><label for="nombre"><i class="fa fa-university"></i> Nombre de la Aldea</label></th>
                                    <td><input type="text" name="nombre" id="nombre_aldea" class="form-control" 
                                             data-validar='{"tipo":"soloLetras","opciones":{"requerido":true,"minLength":5,"maxLength":100}}'
                                             data-nombre="Nombre de la Aldea"
                                             required value="<?= htmlspecialchars($aldea['nombre']) ?>"> </td>
                                </tr>
                                <tr>
                                    <th><label for="codigo"><i class="fa fa-barcode"></i> Código de la Aldea</label></th>
                                    <td><input type="text" name="codigo" id="codigo_aldea" class="form-control" 
                                             data-validar='{"tipo":"","opciones":{"requerido":true,"minLength":5,"maxLength":20}}'
                                             data-nombre="Código de la Aldea"
                                             required value="<?= htmlspecialchars($aldea['codigo']) ?>"></td>
                                </tr>
                                <tr>
                                    <th><label for="direccion"><i class="fa fa-map-marker-alt"></i> Direccion de la Aldea</label></th>
                                    <td><input type="text" name="direccion" id="direccion_aldea" class="form-control" 
                                             data-validar='{"tipo":"","opciones":{"requerido":true,"minLength":10,"maxLength":255}}'
                                             data-nombre="Dirección"
                                             required value="<?= htmlspecialchars($aldea['direccion']) ?>"></td>
                                </tr>
                                <tr>
                                    <th><label for="descripcion"><i class="fa fa-info-circle"></i> Descripción (opcional)</label></th>
                                    <td><textarea name="descripcion" id="descripcion" class="form-control" rows="3" 
                                              data-validar='{"tipo":"","opciones":{"maxLength":500}}'
                                              data-nombre="Descripción"><?= htmlspecialchars($aldea['descripcion']) ?></textarea></td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="text-right">
                            <a href="verAldeas.php" class="btn btn-secondary">Cancelar</a>
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
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>