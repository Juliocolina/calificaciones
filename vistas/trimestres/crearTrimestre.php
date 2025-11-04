<?php
require_once __DIR__ . '/../../models/header.php';
require_once __DIR__ . '/../../config/conexion.php';
$conn = conectar();
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Registro de Trimestre - Sistema de Carga de Notas</title>
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
                    <h3><i class="fa fa-calendar-alt"></i> Registrar Trimestre</h3>
                </div>
                <div class="card-body">
                    <form action="../../controladores/trimestreController/crearTrimestre.php" method="POST" data-validar-form autocomplete="off">
                        <table class="table table-borderless">
                            <tbody>
                                <tr>
                                    <th style="width: 30%;"><label for="nombre"><i class="fa fa-pencil-alt"></i> Nombre del Trimestre</label></th>
                                    <td><input type="text" name="nombre" id="nombre" class="form-control" 
                                             data-validar='{"tipo":"alfanumerico","opciones":{"requerido":true,"minLength":5,"maxLength":20}}'
                                             data-nombre="Nombre del Trimestre"
                                             required placeholder="Ej: Trimestre 2026-1" maxlength="20"></td>
                                </tr>
                                <tr>
                                    <th><label for="fecha_inicio"><i class="fa fa-calendar-alt"></i> Fecha de inicio</label></th>
                                    <td><input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" 
                                             data-validar='{"tipo":"fecha","opciones":{"requerido":true}}'
                                             data-nombre="Fecha de Inicio"
                                             required></td>
                                </tr>
                                <tr>
                                    <th><label for="fecha_fin"><i class="fa fa-calendar-check"></i> Fecha de fin</label></th>
                                    <td><input type="date" name="fecha_fin" id="fecha_fin" class="form-control" 
                                             data-validar='{"tipo":"fecha","opciones":{"requerido":true}}'
                                             data-nombre="Fecha de Fin"
                                             required></td>
                                </tr>
                                <tr>
                                    <th><label for="descripcion"><i class="fa fa-info-circle"></i> Descripción (opcional)</label></th>
                                    <td><textarea name="descripcion" id="descripcion" class="form-control" rows="3" 
                                              data-validar='{"tipo":"","opciones":{"maxLength":500}}'
                                              data-nombre="Descripción"
                                              placeholder="Notas o detalles del trimestre"></textarea></td>
                                </tr>
                            </tbody>
                        </table>

                        <button type="submit" class="btn btn-success btn-block mt-3">
                            <i class="fa fa-save"></i> Registrar Trimestre
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
<?php require_once __DIR__ . '/../../models/footer.php'; ?>