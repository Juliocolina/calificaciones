<?php
require_once __DIR__ . '/../../models/header.php';
require_once __DIR__ . '/../../config/conexion.php';
$conn = conectar();
// 2. OBTENER DATOS DEL PERFIL DE FORMA DINÁMICA
$usuario_id = $_SESSION['usuario_id'];
$rol = $_SESSION['rol']; // Obtenemos el rol de la sesión

// Construimos la consulta SQL dinámicamente según el rol
$sql = "SELECT * FROM usuarios u ";
switch ($rol) {
    case 'coordinador':
        $sql .= "LEFT JOIN coordinadores c ON u.id = c.usuario_id ";
        break;
    case 'profesor':
        $sql .= "LEFT JOIN profesores p ON u.id = p.usuario_id ";
        break;
    case 'estudiante':
        $sql .= "LEFT JOIN estudiantes e ON u.id = e.usuario_id ";
        break;
}
$sql .= "WHERE u.id = ?";

$stmt = $conn->prepare($sql);
$stmt->execute([$usuario_id]);
$perfil = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$perfil) {
    exit("Error: No se pudieron cargar los datos del usuario.");
}

// 3. Obtener datos adicionales si es necesario (ej: lista de aldeas para coordinadores)
if ($rol === 'coordinador') {
    $aldeas = $conn->query("SELECT id, nombre FROM aldeas ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
}
if ($rol === 'profesor') {
    $aldeas = $conn->query("SELECT id, nombre FROM aldeas ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
}
if ($rol === 'estudiante') {
    $aldeas = $conn->query("SELECT id, nombre FROM aldeas ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
}

?>
<div class="container mt-4">
    <?php if (isset($_GET['exito'])): ?>
        <div class="alert alert-success"><i class="fa fa-check-circle"></i> <?= htmlspecialchars($_GET['exito']) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger"><i class="fa fa-times-circle"></i> <?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>
    
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow">
                <div class="card-header bg-primary text-white text-center">
                    <h3 class="mb-0"><i class="fa fa-user-edit"></i> Mi Perfil</h3>
                    <p class="mb-0">Actualizar mis datos personales</p>
                </div>
                <div class="card-body">
                 <form action="../../controladores/usuarioController/actualizarPerfil.php" method="POST" data-validar-form autocomplete="off">
                        <h5 class="text-primary mb-3"><i class="fa fa-user-circle"></i> Datos Personales</h5>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fa fa-id-badge"></i> Cédula</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($perfil['cedula']) ?>" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fa fa-users-cog"></i> Rol</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars(ucfirst($perfil['rol'])) ?>" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nombre"><i class="fa fa-user"></i> Nombre *</label>
                                    <input type="text" 
                                           name="nombre" 
                                           id="nombre" 
                                           class="form-control" 
                                           value="<?= htmlspecialchars($perfil['nombre'] ?? '') ?>" 
                                           data-validar='{"tipo":"soloLetras","opciones":{"requerido":true,"minLength":2,"maxLength":50}}'
                                           data-nombre="Nombre"
                                           required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="apellido"><i class="fa fa-user"></i> Apellido *</label>
                                    <input type="text" 
                                           name="apellido" 
                                           id="apellido" 
                                           class="form-control" 
                                           value="<?= htmlspecialchars($perfil['apellido'] ?? '') ?>" 
                                           data-validar='{"tipo":"soloLetras","opciones":{"requerido":true,"minLength":2,"maxLength":50}}'
                                           data-nombre="Apellido"
                                           required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="correo"><i class="fa fa-envelope"></i> Correo Electrónico</label>
                                    <input type="email" 
                                           name="correo" 
                                           id="correo" 
                                           class="form-control" 
                                           value="<?= htmlspecialchars($perfil['correo'] ?? '') ?>"
                                           data-validar='{"tipo":"email","opciones":{"requerido":false}}'
                                           data-nombre="Correo electrónico">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="telefono"><i class="fa fa-phone"></i> Teléfono</label>
                                    <input type="text" 
                                           name="telefono" 
                                           id="telefono" 
                                           class="form-control" 
                                           value="<?= htmlspecialchars($perfil['telefono'] ?? '') ?>"
                                           placeholder="04121234567"
                                           data-validar='{"tipo":"telefono","opciones":{"requerido":false}}'
                                           data-nombre="Teléfono">
                                </div>
                            </div>
                        </div>

                        <?php if ($perfil['rol'] === 'coordinador'): ?>
                            <h5><i class="fa fa-briefcase"></i> Datos de Gestión</h5>
                            <table class="table table-borderless">
                                 <tbody>
                                    <tr>
                                        <th style="width: 30%;"><label for="aldea_readonly"><i class="fa fa-university"></i> Aldea Asignada</label></th>
                                        <td>
                                            <?php 
                                            $aldea_nombre = 'No asignada';
                                            foreach ($aldeas as $aldea) {
                                                if ($aldea['id'] == $perfil['aldea_id']) {
                                                    $aldea_nombre = $aldea['nombre'];
                                                    break;
                                                }
                                            }
                                            ?>
                                            <input type="text" class="form-control" value="<?= htmlspecialchars($aldea_nombre) ?>" readonly>
                                            <input type="hidden" name="aldea_id" value="<?= htmlspecialchars($perfil['aldea_id']) ?>">
                                            <small class="text-muted"><i class="fa fa-info-circle"></i> Solo el administrador puede cambiar la aldea asignada</small>
                                        </td>
                                    </tr>
                                    
                                </tbody>
                            </table>
                        <?php endif; ?>
                        

                        <?php if ($perfil['rol'] === 'profesor'): ?>
                            <h5><i class="fa fa-graduation-cap"></i> Datos de Profesor</h5>
                            <table class="table table-borderless">
                                 <tbody>
                                       
                                    <tr>
                                        <th style="width: 30%;"><label for="especialidad"><i class="fa fa-book"></i> Especialidad</label></th>
                                        <td><input type="text" name="especialidad" id="especialidad" class="form-control" value="<?= htmlspecialchars($perfil['especialidad'] ?? '') ?>" required></td>
                                    </tr>
                                    <tr>
                                        <th><label for="titulo"><i class="fa fa-award"></i> Título Universitario</label></th>
                                        <td><input type="text" name="titulo" id="titulo" class="form-control" value="<?= htmlspecialchars($perfil['titulo'] ?? '') ?>" required></td>
                                    </tr>
                                    </tbody>
                            </table>
                        <?php endif; ?>

                        <?php if ($perfil['rol'] === 'estudiante'): ?>
    <h5><i class="fa fa-user-graduate"></i> Datos de Estudiante</h5>
    <table class="table table-borderless">
         <tbody>
            <tr>
                <th style="width: 30%;"><label for="aldea_id"><i class="fa fa-university"></i> Aldea *</label></th>
                <td>
                    <select name="aldea_id" id="aldea_id" class="form-control" required data-validar='{"tipo":"","opciones":{"requerido":true}}' data-nombre="Aldea">
                        <option value="">Seleccione una aldea</option>
                        <?php foreach ($aldeas as $aldea): ?>
                            <option value="<?= htmlspecialchars($aldea['id']) ?>" <?= (isset($perfil['aldea_id']) && $perfil['aldea_id'] == $aldea['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($aldea['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
             <tr>
                <th><label><i class="fa fa-calendar-alt"></i> Fecha de Nacimiento (Opcional)</label></th>
                <td>
                    <div class="row">
                        <div class="col-md-4">
                            <select name="dia_nacimiento" class="form-control">
                                <option value="">Día</option>
                                <?php 
                                $dia_actual = '';
                                if (!empty($perfil['fecha_nacimiento'])) {
                                    $dia_actual = date('d', strtotime($perfil['fecha_nacimiento']));
                                }
                                for ($i = 1; $i <= 31; $i++): 
                                    $selected = ($dia_actual == sprintf('%02d', $i)) ? 'selected' : '';
                                ?>
                                    <option value="<?= sprintf('%02d', $i) ?>" <?= $selected ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select name="mes_nacimiento" class="form-control">
                                <option value="">Mes</option>
                                <?php 
                                $mes_actual = '';
                                if (!empty($perfil['fecha_nacimiento'])) {
                                    $mes_actual = date('m', strtotime($perfil['fecha_nacimiento']));
                                }
                                $meses = ['01'=>'Enero', '02'=>'Febrero', '03'=>'Marzo', '04'=>'Abril', '05'=>'Mayo', '06'=>'Junio',
                                         '07'=>'Julio', '08'=>'Agosto', '09'=>'Septiembre', '10'=>'Octubre', '11'=>'Noviembre', '12'=>'Diciembre'];
                                foreach ($meses as $num => $nombre): 
                                    $selected = ($mes_actual == $num) ? 'selected' : '';
                                ?>
                                    <option value="<?= $num ?>" <?= $selected ?>><?= $nombre ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select name="ano_nacimiento" class="form-control">
                                <option value="">Año</option>
                                <?php 
                                $ano_actual = '';
                                if (!empty($perfil['fecha_nacimiento'])) {
                                    $ano_actual = date('Y', strtotime($perfil['fecha_nacimiento']));
                                }
                                $ano_min = date('Y') - 80;
                                $ano_max = date('Y') - 16;
                                for ($i = $ano_max; $i >= $ano_min; $i--): 
                                    $selected = ($ano_actual == $i) ? 'selected' : '';
                                ?>
                                    <option value="<?= $i ?>" <?= $selected ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <th><label for="parroquia"><i class="fa fa-map-marker-alt"></i> Parroquia *</label></th>
                <td>
                    <select name="parroquia" id="parroquia" class="form-control" required data-validar='{"tipo":"","opciones":{"requerido":true}}' data-nombre="Parroquia">
                        <option value="">Seleccione una parroquia</option>
                        <option value="San Gabriel" <?= ($perfil['parroquia'] ?? '') === 'San Gabriel' ? 'selected' : '' ?>>San Gabriel</option>
                        <option value="Santa Ana" <?= ($perfil['parroquia'] ?? '') === 'Santa Ana' ? 'selected' : '' ?>>Santa Ana</option>
                        <option value="San Antonio" <?= ($perfil['parroquia'] ?? '') === 'San Antonio' ? 'selected' : '' ?>>San Antonio</option>
                        <option value="Río Seco" <?= ($perfil['parroquia'] ?? '') === 'Río Seco' ? 'selected' : '' ?>>Río Seco</option>
                        <option value="Guzmán Guillermo" <?= ($perfil['parroquia'] ?? '') === 'Guzmán Guillermo' ? 'selected' : '' ?>>Guzmán Guillermo</option>
                        <option value="Mitare" <?= ($perfil['parroquia'] ?? '') === 'Mitare' ? 'selected' : '' ?>>Mitare</option>
                        <option value="Sabaneta" <?= ($perfil['parroquia'] ?? '') === 'Sabaneta' ? 'selected' : '' ?>>Sabaneta</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="nivel_estudio"><i class="fa fa-layer-group"></i> Nivel de Estudio (Opcional)</label></th>
                <td>
                    <select name="nivel_estudio" id="nivel_estudio" class="form-control" data-validar='{"tipo":"","opciones":{"requerido":false}}' data-nombre="Nivel de estudio">
                        <option value="">Seleccione nivel de estudio</option>
                        <option value="Bachillerato" <?= ($perfil['nivel_estudio'] ?? '') === 'Bachillerato' ? 'selected' : '' ?>>Bachillerato</option>
                        <option value="Técnico Superior" <?= ($perfil['nivel_estudio'] ?? '') === 'Técnico Superior' ? 'selected' : '' ?>>Técnico Superior</option>
                        <option value="Universitario" <?= ($perfil['nivel_estudio'] ?? '') === 'Universitario' ? 'selected' : '' ?>>Universitario</option>
                        <option value="Postgrado" <?= ($perfil['nivel_estudio'] ?? '') === 'Postgrado' ? 'selected' : '' ?>>Postgrado</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="institucion_procedencia"><i class="fa fa-school"></i> Institución de Procedencia *</label></th>
                <td><input type="text" name="institucion_procedencia" id="institucion_procedencia" class="form-control" value="<?= htmlspecialchars($perfil['institucion_procedencia'] ?? '') ?>" required data-validar='{"tipo":"","opciones":{"requerido":true,"minLength":3}}' data-nombre="Institución de procedencia"></td>
            </tr>
            <tr>
                <th><label for="nacionalidad"><i class="fa fa-flag"></i> Nacionalidad *</label></th>
                <td>
                    <select name="nacionalidad" id="nacionalidad" class="form-control" required data-validar='{"tipo":"","opciones":{"requerido":true}}' data-nombre="Nacionalidad">
                        <option value="">Seleccione nacionalidad</option>
                        <option value="Venezolana" <?= ($perfil['nacionalidad'] ?? '') === 'Venezolana' ? 'selected' : '' ?>>Venezolana</option>
                        <option value="Colombiana" <?= ($perfil['nacionalidad'] ?? '') === 'Colombiana' ? 'selected' : '' ?>>Colombiana</option>
                        <option value="Ecuatoriana" <?= ($perfil['nacionalidad'] ?? '') === 'Ecuatoriana' ? 'selected' : '' ?>>Ecuatoriana</option>
                        <option value="Peruana" <?= ($perfil['nacionalidad'] ?? '') === 'Peruana' ? 'selected' : '' ?>>Peruana</option>
                        <option value="Otra" <?= ($perfil['nacionalidad'] ?? '') === 'Otra' ? 'selected' : '' ?>>Otra</option>
                    </select>
                </td>
            </tr>
             <tr>
                <th><label for="genero"><i class="fa fa-venus-mars"></i> Género *</label></th>
                <td>
                    <select name="genero" id="genero" class="form-control" required data-validar='{"tipo":"","opciones":{"requerido":true}}' data-nombre="Género">
                        <option value="">Seleccione género</option>
                        <option value="Masculino" <?= ($perfil['genero'] ?? '') === 'Masculino' ? 'selected' : '' ?>>Masculino</option>
                        <option value="Femenino" <?= ($perfil['genero'] ?? '') === 'Femenino' ? 'selected' : '' ?>>Femenino</option>
                        <option value="Otro" <?= ($perfil['genero'] ?? '') === 'Otro' ? 'selected' : '' ?>>Otro</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="religion"><i class="fa fa-pray"></i> Religión *</label></th>
                <td>
                    <select name="religion" id="religion" class="form-control" required data-validar='{"tipo":"","opciones":{"requerido":true}}' data-nombre="Religión">
                        <option value="">Seleccione religión</option>
                        <option value="Católica" <?= ($perfil['religion'] ?? '') === 'Católica' ? 'selected' : '' ?>>Católica</option>
                        <option value="Evangélica" <?= ($perfil['religion'] ?? '') === 'Evangélica' ? 'selected' : '' ?>>Evangélica</option>
                        <option value="Protestante" <?= ($perfil['religion'] ?? '') === 'Protestante' ? 'selected' : '' ?>>Protestante</option>
                        <option value="Judía" <?= ($perfil['religion'] ?? '') === 'Judía' ? 'selected' : '' ?>>Judía</option>
                        <option value="Islámica" <?= ($perfil['religion'] ?? '') === 'Islámica' ? 'selected' : '' ?>>Islámica</option>
                        <option value="Otra" <?= ($perfil['religion'] ?? '') === 'Otra' ? 'selected' : '' ?>>Otra</option>
                        <option value="Ninguna" <?= ($perfil['religion'] ?? '') === 'Ninguna' ? 'selected' : '' ?>>Ninguna</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="etnia"><i class="fa fa-users"></i> Etnia (Opcional)</label></th>
                <td><input type="text" name="etnia" id="etnia" class="form-control" value="<?= htmlspecialchars($perfil['etnia'] ?? '') ?>" data-validar='{"tipo":"soloLetras","opciones":{"requerido":false}}' data-nombre="Etnia"></td>
            </tr>
            <tr>
                <th><label for="discapacidad"><i class="fa fa-wheelchair"></i> Discapacidad (Opcional)</label></th>
                <td>
                    <select name="discapacidad" id="discapacidad" class="form-control" data-validar='{"tipo":"","opciones":{"requerido":false}}' data-nombre="Discapacidad">
                        <option value="">Seleccione si tiene alguna discapacidad</option>
                        <option value="Ninguna" <?= ($perfil['discapacidad'] ?? '') === 'Ninguna' ? 'selected' : '' ?>>Ninguna</option>
                        <option value="Visual" <?= ($perfil['discapacidad'] ?? '') === 'Visual' ? 'selected' : '' ?>>Visual</option>
                        <option value="Auditiva" <?= ($perfil['discapacidad'] ?? '') === 'Auditiva' ? 'selected' : '' ?>>Auditiva</option>
                        <option value="Motora" <?= ($perfil['discapacidad'] ?? '') === 'Motora' ? 'selected' : '' ?>>Motora</option>
                        <option value="Intelectual" <?= ($perfil['discapacidad'] ?? '') === 'Intelectual' ? 'selected' : '' ?>>Intelectual</option>
                        <option value="Otra" <?= ($perfil['discapacidad'] ?? '') === 'Otra' ? 'selected' : '' ?>>Otra</option>
                    </select>
                </td>
            </tr>
        </tbody>
    </table>
<?php endif; ?>
 
                        <button type="submit" class="btn btn-primary btn-block mt-3"><i class="fa fa-save"></i> Guardar Cambios</button>
                    </form>

                    <div class="text-center mt-4">
                        <a href="../home.php" class="text-secondary"><i class="fa fa-arrow-left"></i> Volver al inicio</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
<?php require_once __DIR__ . '/../../models/footer.php'; ?>