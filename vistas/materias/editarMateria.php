<?php
require_once __DIR__ . '/../../models/header.php';
require_once __DIR__ . '/../../config/conexion.php';
$conn = conectar();

// --- LECTURA HÍBRIDA DEL ID (POST o GET) ---
$id_a_editar = null;
if (isset($_POST['id']) && is_numeric($_POST['id'])) {
    $id_a_editar = intval($_POST['id']);
} elseif (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_a_editar = intval($_GET['id']);
}

// Validar que se recibió un ID válido
if (!$id_a_editar) {
    echo "<div class='alert alert-danger'>ID de materia inválido.</div>";
    exit;
}
$id = $id_a_editar;
// ---------------------------------------------


// Consultar los datos de la materia
// CRÍTICO: Se añadió 'duracion' a la consulta
$stmt_materia = $conn->prepare("SELECT id, nombre, codigo, creditos, duracion, pnf_id, descripcion FROM materias WHERE id = ?");
$stmt_materia->execute([$id]);
$materia = $stmt_materia->fetch(PDO::FETCH_ASSOC);

if (!$materia) {
    echo "<div class='alert alert-warning'>Materia no encontrada.</div>";
    exit;
}

// Consultar los PNF para la lista desplegable
$stmt_pnf = $conn->query("SELECT id, nombre FROM pnfs ORDER BY nombre");
$pnfs = $stmt_pnf->fetchAll(PDO::FETCH_ASSOC);

?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Editar Materia - Sistema de Carga de Notas</title>
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
                    <h3><i class="fa fa-book"></i> Editar Materia</h3>
                </div>
                <div class="card-body">
                    <form action="../../controladores/materiaController/actualizarMateria.php" method="POST" data-validar-form autocomplete="off">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
                        <table class="table table-borderless">
                            <tbody>
                                <tr>
                                    <th style="width: 30%;"><label for="nombre"><i class="fa fa-book"></i> Nombre de la unidad curricular (*)</label></th>
                                    <td><input type="text" name="nombre" id="nombre" class="form-control" 
                                             data-validar='{"tipo":"soloLetras","opciones":{"requerido":true,"minLength":3,"maxLength":100}}'
                                             data-nombre="Nombre de la materia"
                                             required value="<?= htmlspecialchars($materia['nombre']) ?>"></td>
                                </tr>
                                <tr>
                                    <th><label for="codigo"><i class="fa fa-barcode"></i> Código</label></th>
                                    <td><input type="text" name="codigo" id="codigo" class="form-control" 
                                             data-validar='{"tipo":"","opciones":{"maxLength":20}}'
                                             data-nombre="Código"
                                             value="<?= htmlspecialchars($materia['codigo'] ?? '') ?>"></td>
                                </tr>
                                <tr>
                                    <th><label for="duracion"><i class="fa fa-clock"></i> Duración (*)</label></th>
                                    <td>
                                        <select name="duracion" id="duracion" class="form-control" 
                                                data-validar='{"tipo":"","opciones":{"requerido":true}}'
                                                data-nombre="Duración"
                                                required>
                                            <option value="">Seleccione la duración</option>
                                            <?php 
                                            $duraciones = ['trimestral', 'bimestral', 'anual'];
                                            foreach ($duraciones as $d): ?>
                                                <option value="<?= $d ?>" 
                                                    <?= $d == $materia['duracion'] ? 'selected' : '' ?>>
                                                    <?= ucfirst($d) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="creditos"><i class="fa fa-star"></i> Créditos (*)</label></th>
                                    <td><input type="number" name="creditos" id="creditos" class="form-control" 
                                             data-validar='{"tipo":"soloNumeros","opciones":{"requerido":true}}'
                                             data-nombre="Créditos"
                                             required min="1" value="<?= htmlspecialchars($materia['creditos']) ?>"></td>
                                </tr>
                                <tr>
                                    <th><label for="pnf_id"><i class="fa fa-graduation-cap"></i> PNF (*)</label></th>
                                    <td>
                                        <select name="pnf_id" id="pnf_id" class="form-control" 
                                                data-validar='{"tipo":"","opciones":{"requerido":true}}'
                                                data-nombre="PNF"
                                                required>
                                            <option value="">Seleccione un PNF</option>
                                            <?php foreach ($pnfs as $pnf): ?>
                                                <option value="<?= htmlspecialchars($pnf['id']) ?>" <?= $pnf['id'] == $materia['pnf_id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($pnf['nombre']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="descripcion"><i class="fa fa-info-circle"></i> Descripción (opcional)</label></th>
                                    <td><textarea name="descripcion" id="descripcion" class="form-control" rows="3" 
                                              data-validar='{"tipo":"","opciones":{"maxLength":500}}'
                                              data-nombre="Descripción"><?= htmlspecialchars($materia['descripcion'] ?? '') ?></textarea></td>
                                </tr>
                                <tr>
                                    <td colspan="2"><small>(*) Campos obligatorios.</small></td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="text-right">
                            <a href="materiasPorPnf.php" class="btn btn-secondary">Cancelar</a>
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