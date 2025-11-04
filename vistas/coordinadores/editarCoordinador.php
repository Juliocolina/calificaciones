<?php
require_once __DIR__ . '/../../models/header.php';
require_once __DIR__ . '/../../config/conexion.php';
$conn = conectar();

// Validar que se recibió un ID de coordinador válido
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo "<div class='alert alert-danger'>ID de coordinador inválido.</div>";
    exit;
}

$id_coordinador = $_POST['id'];

// Consulta con JOIN para obtener datos del coordinador y del usuario asociado
$stmt = $conn->prepare("
    SELECT 
        c.aldea_id, 
        c.fecha_inicio_gestion, 
        c.fecha_fin_gestion, 
        c.descripcion,
        c.usuario_id,
        u.nombre,
        u.apellido,
        u.cedula,
        u.telefono
    FROM 
        coordinadores c
    INNER JOIN 
        usuarios u ON c.usuario_id = u.id
    WHERE 
        c.id = ?
");
$stmt->execute([$id_coordinador]);
$coordinador = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$coordinador) {
    echo "<div class='alert alert-warning'>Coordinador no encontrado.</div>";
    exit;
}

// Consultar todas las aldeas para el menú desplegable
$aldeas = $conn->query("SELECT id, nombre FROM aldeas ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Editar Coordinador</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { background: #f4f6f9; }
        .card { margin-top: 50px; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .card-header { background: linear-gradient(90deg, #1e3c72, #2a5298); color: #fff; border-radius: 15px 15px 0 0; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card">
                <div class="card-header text-center">
                    <h4><i class="fa fa-user-edit"></i> Editar Perfil de Coordinador</h4>
                </div>
                <div class="card-body p-4">
                    <form action="../../controladores/coordinadorController/actualizarCoordinador.php" method="POST">
                        
                        <input type="hidden" name="id" value="<?= htmlspecialchars($id_coordinador) ?>">
                        <input type="hidden" name="usuario_id" value="<?= htmlspecialchars($coordinador['usuario_id']) ?>">

                        <h5>Datos Personales</h5>
                        <hr>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="nombre"><i class="fa fa-user"></i> Nombre</label>
                                <input type="text" name="nombre" id="nombre" class="form-control" value="<?= htmlspecialchars($coordinador['nombre']) ?>" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="apellido"><i class="fa fa-user"></i> Apellido</label>
                                <input type="text" name="apellido" id="apellido" class="form-control" value="<?= htmlspecialchars($coordinador['apellido']) ?>" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="cedula"><i class="fa fa-id-badge"></i> Cédula</label>
                                <input type="text" name="cedula" id="cedula" class="form-control" value="<?= htmlspecialchars($coordinador['cedula']) ?>" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="telefono"><i class="fa fa-phone"></i> Teléfono</label>
                                <input type="text" name="telefono" id="telefono" class="form-control" value="<?= htmlspecialchars($coordinador['telefono']) ?>">
                            </div>
                        </div>

                        <h5 class="mt-4">Datos de Gestión</h5>
                        <hr>
                        <div class="form-group">
                            <label for="aldea_id"><i class="fa fa-university"></i> Aldea</label>
                            <select name="aldea_id" id="aldea_id" class="form-control" required>
                                <option value="">Seleccione una aldea</option>
                                <?php foreach ($aldeas as $aldea): ?>
                                    <option value="<?= htmlspecialchars($aldea['id']) ?>" <?= ($coordinador['aldea_id'] == $aldea['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($aldea['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="fecha_inicio_gestion"><i class="fa fa-calendar"></i> Fecha Inicio Gestión</label>
                                <input type="date" name="fecha_inicio_gestion" id="fecha_inicio_gestion" class="form-control" value="<?= htmlspecialchars($coordinador['fecha_inicio_gestion']) ?>">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="fecha_fin_gestion"><i class="fa fa-calendar"></i> Fecha Fin Gestión</label>
                                <input type="date" name="fecha_fin_gestion" id="fecha_fin_gestion" class="form-control" value="<?= htmlspecialchars($coordinador['fecha_fin_gestion']) ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="descripcion"><i class="fa fa-file-text"></i> Descripción</label>
                            <textarea name="descripcion" id="descripcion" class="form-control" rows="3"><?= htmlspecialchars($coordinador['descripcion']) ?></textarea>
                        </div>


                        <div class="text-right mt-4">
                            <a href="verCoordinadores.php" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
<?php require_once __DIR__ . '/../../models/footer.php'; ?>