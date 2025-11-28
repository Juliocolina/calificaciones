<?php
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/conexion.php';
$conn = conectar();
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
                    <h3><i class="fa fa-route"></i> Registrar Trayecto</h3>
                </div>
                <div class="card-body">
                    <form action="../../controladores/trayectoController/crearTrayecto.php" method="POST" data-validar-form autocomplete="off">
                        <table class="table table-borderless">
                            <tbody>
                                <tr>
                                    <th style="width: 30%;"><label for="nombre_trayecto"><i class="fa fa-road"></i> Nombre del Trayecto</label></th>
                                    <td><input type="text" name="nombre_trayecto" id="nombre_trayecto" class="form-control" 
                                             data-validar='{"tipo":"soloLetras","opciones":{"requerido":true,"minLength":5,"maxLength":100}}'
                                             data-nombre="Nombre del Trayecto"
                                             required placeholder="Ej: Trayecto numero uno"> </td>
                                </tr>
                                <tr>
                                    <th><label for="slug_trayecto"><i class="fa fa-barcode"></i> Código del Trayecto</label></th>
                                    <td><input type="text" name="slug_trayecto" id="slug_trayecto" class="form-control" 
                                             data-validar='{"tipo":"alfanumerico","opciones":{"requerido":true,"minLength":3,"maxLength":20}}'
                                             data-nombre="Código del Trayecto"
                                             required placeholder="Ej: TRAY-1"></td>
                                </tr>
                                <tr>
                                    <th><label for="descripcion"><i class="fa fa-info-circle"></i> Descripción (opcional)</label></th>
                                    <td><textarea name="descripcion" id="descripcion" class="form-control" rows="3" 
                                              data-validar='{"tipo":"","opciones":{"maxLength":500}}'
                                              data-nombre="Descripción"
                                              placeholder="Descripción del trayecto"></textarea></td>
                                </tr>
                            </tbody>
                        </table>

                        <button type="submit" class="btn btn-success btn-block mt-3">
                            <i class="fa fa-save"></i> Registrar Trayecto
                        </button>
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
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>