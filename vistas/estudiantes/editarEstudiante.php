<?php
// Incluye los archivos necesarios
require_once __DIR__ . '/../../models/header.php';
require_once __DIR__ . '/../../config/conexion.php';
$conn = conectar();

// 1. Validar que se recibió un ID válido
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo "<div class='alert alert-danger'>ID de estudiante inválido.</div>";
    exit;
}

$id = $_POST['id'];

// 2. Consultar los datos del estudiante y los datos personales desde usuarios
$stmt = $conn->prepare("SELECT e.*, u.cedula, u.nombre, u.apellido, u.correo, u.telefono
    FROM estudiantes e
    LEFT JOIN usuarios u ON e.usuario_id = u.id
    WHERE e.id = ?");
$stmt->execute([$id]);
$estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$estudiante) {
    echo "<div class='alert alert-warning'>Estudiante no encontrado.</div>";
    exit;
}

// 3. Consultar los datos de las tablas relacionadas para las listas desplegables
// Se consultan todas las tablas necesarias para ambos formularios
$sql_aldeas = "SELECT id, nombre FROM aldeas ORDER BY nombre ASC";
$stmt_aldeas = $conn->prepare($sql_aldeas);
$stmt_aldeas->execute();
$aldeas = $stmt_aldeas->fetchAll(PDO::FETCH_ASSOC);

$sql_pnfs = "SELECT id, nombre FROM pnfs ORDER BY nombre ASC";
$stmt_pnfs = $conn->prepare($sql_pnfs);
$stmt_pnfs->execute();
$pnfs = $stmt_pnfs->fetchAll(PDO::FETCH_ASSOC);

$sql_trayectos = "SELECT id, nombre FROM trayectos ORDER BY nombre ASC";
$stmt_trayectos = $conn->prepare($sql_trayectos);
$stmt_trayectos->execute();
$trayectos = $stmt_trayectos->fetchAll(PDO::FETCH_ASSOC);

$sql_trimestres = "SELECT id, nombre FROM trimestres ORDER BY nombre ASC";
$stmt_trimestres = $conn->prepare($sql_trimestres);
$stmt_trimestres->execute();
$trimestres = $stmt_trimestres->fetchAll(PDO::FETCH_ASSOC);

// URL de acción genérica (deberías tener un controlador que maneje ambos sets de datos)
$action_url = "../../controladores/estudianteController/actualizarEstudiante.php"; 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Estudiante</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 900px;
            margin-top: 50px;
            margin-bottom: 50px;
        }
        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            margin-bottom: 30px; /* Espacio entre las tarjetas de formulario */
        }
        .form-label {
            font-weight: 500;
        }
        .card-header-personal {
            background-color: #007bff;
            color: white;
            border-top-left-radius: 1rem;
            border-top-right-radius: 1rem;
            padding: 1rem;
        }
        .card-header-academico {
            background-color: #dc3545; /* Rojo para destacar lo administrativo */
            color: white;
            border-top-left-radius: 1rem;
            border-top-right-radius: 1rem;
            padding: 1rem;
        }
    </style>
</head>
<body>

<div class="container">
    <h2 class="text-center mb-5">Editar Estudiante: <?= htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido']) ?></h2>

    <!-- ============================================== -->
    <!-- FORMULARIO 1: DATOS PERSONALES Y DE ORIGEN -->
    <!-- ============================================== -->
    <div class="card">
        <div class="card-header-personal">
            <h3 class="card-title mb-0"><i class="fa fa-user"></i> Datos Personales y de Origen</h3>
            <small>Información básica del estudiante, incluyendo la aldea.</small>
        </div>
        <div class="card-body p-4">
            <form action="<?= $action_url ?>" method="POST" data-validar-form>
                <input type="hidden" name="id" value="<?= htmlspecialchars($estudiante['id']) ?>">
                <input type="hidden" name="form_type" value="personal"> <!-- Identificador para el controlador -->
                <div class="row g-3">
                    <!-- Fila 1: Cédula y Nombre -->
                    <div class="col-md-6">
                        <label for="cedula" class="form-label">Cédula</label>
                        <input type="text" class="form-control" id="cedula" name="cedula" value="<?= htmlspecialchars($estudiante['cedula']) ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" 
                               data-validar='{"tipo":"soloLetras","opciones":{"requerido":true,"minLength":2,"maxLength":50}}'
                               data-nombre="Nombre"
                               value="<?= htmlspecialchars($estudiante['nombre']) ?>">
                    </div>

                    <!-- Fila 2: Apellido y Fecha de Nacimiento -->
                    <div class="col-md-6">
                        <label for="apellido" class="form-label">Apellido</label>
                        <input type="text" class="form-control" id="apellido" name="apellido" value="<?= htmlspecialchars($estudiante['apellido']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                        <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" value="<?= htmlspecialchars($estudiante['fecha_nacimiento']) ?>">
                    </div>

                    <!-- Fila 3: Contacto -->
                    <div class="col-md-6">
                        <label for="correo" class="form-label">Correo</label>
                        <input type="email" class="form-control" id="correo" name="correo" 
                               data-validar='{"tipo":"email","opciones":{}}'
                               data-nombre="Correo"
                               value="<?= htmlspecialchars($estudiante['correo']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="telefono" class="form-label">Teléfono</label>
                        <input type="tel" class="form-control" id="telefono" name="telefono" 
                               data-validar='{"tipo":"telefono","opciones":{}}'
                               data-nombre="Teléfono"
                               value="<?= htmlspecialchars($estudiante['telefono']) ?>">
                    </div>
                    
                    <!-- Fila 4: Parroquia -->
                    <div class="col-md-12">
                        <label for="parroquia" class="form-label">Parroquia</label>
                        <select class="form-select" id="parroquia" name="parroquia" data-validar='{"tipo":"","opciones":{"requerido":true}}' data-nombre="Parroquia" required>
                            <option value="">Seleccione una parroquia</option>
                            <option value="San Gabriel" <?= (isset($estudiante['parroquia']) && $estudiante['parroquia']==='San Gabriel') ? 'selected' : '' ?>>San Gabriel</option>
                            <option value="Santa Ana" <?= (isset($estudiante['parroquia']) && $estudiante['parroquia']==='Santa Ana') ? 'selected' : '' ?>>Santa Ana</option>
                            <option value="San Antonio" <?= (isset($estudiante['parroquia']) && $estudiante['parroquia']==='San Antonio') ? 'selected' : '' ?>>San Antonio</option>
                            <option value="Río Seco" <?= (isset($estudiante['parroquia']) && $estudiante['parroquia']==='Río Seco') ? 'selected' : '' ?>>Río Seco</option>
                            <option value="Guzmán Guillermo" <?= (isset($estudiante['parroquia']) && $estudiante['parroquia']==='Guzmán Guillermo') ? 'selected' : '' ?>>Guzmán Guillermo</option>
                            <option value="Mitare" <?= (isset($estudiante['parroquia']) && $estudiante['parroquia']==='Mitare') ? 'selected' : '' ?>>Mitare</option>
                            <option value="Sabaneta" <?= (isset($estudiante['parroquia']) && $estudiante['parroquia']==='Sabaneta') ? 'selected' : '' ?>>Sabaneta</option>
                        </select>
                    </div>

                    <!-- Fila 5: Generales -->
                    <div class="col-md-4">
                        <label for="nacionalidad" class="form-label">Nacionalidad</label>
                        <input type="text" class="form-control" id="nacionalidad" name="nacionalidad" value="<?= htmlspecialchars($estudiante['nacionalidad']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="genero" class="form-label">Género</label>
                        <input type="text" class="form-control" id="genero" name="genero" value="<?= htmlspecialchars($estudiante['genero']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="religion" class="form-label">Religión</label>
                        <input type="text" class="form-control" id="religion" name="religion" value="<?= htmlspecialchars($estudiante['religion']) ?>">
                    </div>

                    <!-- Fila 6: Origen y Condición -->
                    <div class="col-md-6">
                        <label for="etnia" class="form-label">Etnia</label>
                        <input type="text" class="form-control" id="etnia" name="etnia" value="<?= htmlspecialchars($estudiante['etnia']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="discapacidad" class="form-label">Discapacidad</label>
                        <input type="text" class="form-control" id="discapacidad" name="discapacidad" value="<?= htmlspecialchars($estudiante['discapacidad']) ?>">
                    </div>

                    <!-- Fila 7: Nivel y Procedencia -->
                    <div class="col-md-6">
                        <label for="nivel_estudio" class="form-label">Nivel de Estudio</label>
                        <input type="text" class="form-control" id="nivel_estudio" name="nivel_estudio" value="<?= htmlspecialchars($estudiante['nivel_estudio']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="institucion_procedencia" class="form-label">Institución de Procedencia</label>
                        <input type="text" class="form-control" id="institucion_procedencia" name="institucion_procedencia" value="<?= htmlspecialchars($estudiante['institucion_procedencia']) ?>">
                    </div>

                    <!-- Fila 8: Aldea (movida a Personal por solicitud) -->
                    <div class="col-md-12">
                        <label for="aldea_id_personal" class="form-label">Aldea</label>
                        <select class="form-select" id="aldea_id_personal" name="aldea_id" 
                                data-validar='{"tipo":"","opciones":{"requerido":true}}'
                                data-nombre="Aldea"
                                required>
                            <option value="">Seleccione una aldea</option>
                            <?php foreach ($aldeas as $aldea): ?>
                                <option value="<?= htmlspecialchars($aldea['id']) ?>" <?= ($aldea['id'] == $estudiante['aldea_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($aldea['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 text-center mt-4">
                        <button type="submit" class="btn btn-primary btn-lg"><i class="fa fa-pencil-alt"></i> Actualizar Datos Personales</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- ============================================== -->
    <!-- FORMULARIO 2: ASIGNACIÓN ACADÉMICA Y ESTADO -->
    <!-- ============================================== -->
    <div class="card">
        <div class="card-header-academico">
            <h3 class="card-title mb-0"><i class="fa fa-graduation-cap"></i> Asignación Académica y Estado</h3>
            <small>Datos exclusivos para asignación de PNF, Trayecto y estado administrativo.</small>
        </div>
        <div class="card-body p-4">
            <form action="<?= $action_url ?>" method="POST" data-validar-form>
                <input type="hidden" name="id" value="<?= htmlspecialchars($estudiante['id']) ?>">
                <input type="hidden" name="form_type" value="academica"> <!-- Identificador para el controlador -->
                <div class="row g-3">
                    
                    <!-- Fila 1: PNF y Trayecto -->
                    <div class="col-md-6">
                        <label for="pnf_id" class="form-label">PNF <span class="text-danger">*</span></label>
                        <select class="form-select" id="pnf_id" name="pnf_id" 
                                data-validar='{"tipo":"","opciones":{"requerido":true}}'
                                data-nombre="PNF"
                                required>
                            <option value="">Seleccione un PNF</option>
                            <?php foreach ($pnfs as $pnf): ?>
                                <option value="<?= htmlspecialchars($pnf['id']) ?>" <?= ($pnf['id'] == $estudiante['pnf_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($pnf['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="trayecto_id" class="form-label">Trayecto <span class="text-danger">*</span></label>
                        <select class="form-select" id="trayecto_id" name="trayecto_id" 
                                data-validar='{"tipo":"","opciones":{"requerido":true}}'
                                data-nombre="Trayecto"
                                required>
                            <option value="">Seleccione un trayecto</option>
                            <?php foreach ($trayectos as $trayecto): ?>
                                <option value="<?= htmlspecialchars($trayecto['id']) ?>" <?= ($trayecto['id'] == $estudiante['trayecto_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($trayecto['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Fila 2: Trimestre y Código de Estudiante -->
                    <div class="col-md-6">
                        <label for="trimestre_id" class="form-label">Trimestre <span class="text-danger">*</span></label>
                        <select class="form-select" id="trimestre_id" name="trimestre_id" 
                                data-validar='{"tipo":"","opciones":{"requerido":true}}'
                                data-nombre="Trimestre"
                                required>
                            <option value="">Seleccione un trimestre</option>
                            <?php foreach ($trimestres as $trimestre): ?>
                                <option value="<?= htmlspecialchars($trimestre['id']) ?>" <?= ($trimestre['id'] == $estudiante['trimestre_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($trimestre['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="codigo_estudiante" class="form-label">Código de Estudiante</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="codigo_estudiante" name="codigo_estudiante" 
                                   value="<?= htmlspecialchars($estudiante['codigo_estudiante']) ?>" 
                                   placeholder="Haz clic en generar" readonly>
                            <button type="button" class="btn btn-outline-primary" id="generarCodigo">
                                <i class="fa fa-refresh"></i> Generar
                            </button>
                        </div>
                        <small class="text-muted">Formato: 0001, 0002, etc.</small>
                    </div>
                    
                    <!-- Fila 3: Estado Académico y Fecha de Ingreso -->
                    <div class="col-md-6">
                        <label for="estado_academico" class="form-label">Estado Académico</label>
                        <select class="form-select" id="estado_academico" name="estado_academico">
                            <option value="activo" <?= ($estudiante['estado_academico'] == 'activo') ? 'selected' : '' ?>>Activo</option>
                            <option value="congelado" <?= ($estudiante['estado_academico'] == 'congelado') ? 'selected' : '' ?>>Congelado</option>
                            <option value="retirado" <?= ($estudiante['estado_academico'] == 'retirado') ? 'selected' : '' ?>>Retirado</option>
                            <option value="graduado" <?= ($estudiante['estado_academico'] == 'graduado') ? 'selected' : '' ?>>Graduado</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="fecha_ingreso" class="form-label">Fecha de Ingreso <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="fecha_ingreso" name="fecha_ingreso" 
                               data-validar='{"tipo":"fecha","opciones":{"requerido":true}}'
                               data-nombre="Fecha de Ingreso"
                               value="<?= htmlspecialchars($estudiante['fecha_ingreso']) ?>" 
                               required>
                        <small class="text-muted">Campo obligatorio para completar la asignación académica</small>
                    </div>
                    
                    <!-- Fila 4: Fecha de Graduación -->
                    <div class="col-md-6">
                        <label for="fecha_graduacion" class="form-label">Fecha de Graduación</label>
                        <input type="date" class="form-control" id="fecha_graduacion" name="fecha_graduacion" 
                               data-validar='{"tipo":"fecha","opciones":{}}'
                               data-nombre="Fecha de Graduación"
                               value="<?= htmlspecialchars($estudiante['fecha_graduacion']) ?>">
                    </div>
                    <!-- Fila 5: Observaciones -->
                    <div class="col-md-12">
                        <label for="observaciones" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="observaciones" name="observaciones" rows="3" 
                                  data-validar='{"tipo":"","opciones":{"maxLength":500}}'
                                  data-nombre="Observaciones"><?= htmlspecialchars($estudiante['observaciones']) ?></textarea>
                    </div>
                    
                    <div class="col-12 text-center mt-4">
                        <button type="submit" class="btn btn-danger btn-lg"><i class="fa fa-upload"></i> Asignar y Guardar Datos Académicos</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- SECCIÓN: HISTORIAL DE GRADUACIONES -->
    <!-- ============================================== -->
    <?php 
    // Obtener graduaciones existentes
    $stmt_graduaciones = $conn->prepare("
        SELECT g.*, p.nombre as pnf_nombre 
        FROM graduaciones g 
        INNER JOIN pnfs p ON g.pnf_id = p.id 
        WHERE g.estudiante_id = ? 
        ORDER BY g.fecha_graduacion DESC
    ");
    $stmt_graduaciones->execute([$estudiante['id']]);
    $graduaciones = $stmt_graduaciones->fetchAll(PDO::FETCH_ASSOC);
    
    $tiene_tsu = false;
    $tiene_licenciado = false;
    foreach ($graduaciones as $grad) {
        if ($grad['tipo_graduacion'] === 'TSU') $tiene_tsu = true;
        if ($grad['tipo_graduacion'] === 'Licenciado') $tiene_licenciado = true;
    }
    ?>
    
    <?php if (!empty($graduaciones)): ?>
    <div class="card">
        <div class="card-header" style="background-color: #6f42c1; color: white; border-top-left-radius: 1rem; border-top-right-radius: 1rem; padding: 1rem;">
            <h3 class="card-title mb-0"><i class="fa fa-history"></i> Historial de Graduaciones</h3>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>PNF</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($graduaciones as $graduacion): ?>
                            <tr>
                                <td>
                                    <?php if ($graduacion['tipo_graduacion'] === 'TSU'): ?>
                                        <span class="badge bg-success">TSU</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary">Licenciado</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($graduacion['pnf_nombre']) ?></td>
                                <td><?= date('d/m/Y', strtotime($graduacion['fecha_graduacion'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ============================================== -->
    <!-- SECCIÓN: GRADUACIÓN MANUAL -->
    <!-- ============================================== -->
    <?php if ($estudiante['estado_academico'] === 'activo' && $estudiante['pnf_id']): ?>
    <div class="card">
        <div class="card-header" style="background-color: #28a745; color: white; border-top-left-radius: 1rem; border-top-right-radius: 1rem; padding: 1rem;">
            <h3 class="card-title mb-0"><i class="fa fa-graduation-cap"></i> Graduación Manual</h3>
            <small>Graduar al estudiante cuando complete los trayectos correspondientes.</small>
        </div>
        <div class="card-body p-4">
            <div class="row">
                <?php if (!$tiene_tsu): ?>
                <div class="col-md-6 text-center">
                    <div class="card border-success">
                        <div class="card-body">
                            <h5 class="card-title text-success"><i class="fa fa-certificate"></i> Técnico Superior Universitario (TSU)</h5>
                            <p class="card-text">Graduar como TSU al completar Trayecto I y II</p>
                            <button type="button" class="btn btn-success" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#modalGraduarTSU">
                                <i class="fa fa-graduation-cap"></i> Graduar como TSU
                            </button>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="col-md-6 text-center">
                    <div class="card border-success bg-light">
                        <div class="card-body">
                            <h5 class="card-title text-muted"><i class="fa fa-check-circle"></i> TSU Completado</h5>
                            <p class="card-text text-muted">Ya graduado como TSU</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!$tiene_licenciado): ?>
                <div class="col-md-6 text-center">
                    <div class="card border-primary">
                        <div class="card-body">
                            <h5 class="card-title text-primary"><i class="fa fa-trophy"></i> Licenciado/Ingeniero</h5>
                            <p class="card-text">Graduar como Licenciado al completar Trayecto III y IV</p>
                            <button type="button" class="btn btn-primary" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#modalGraduarLicenciado">
                                <i class="fa fa-trophy"></i> Graduar como Licenciado
                            </button>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="col-md-6 text-center">
                    <div class="card border-primary bg-light">
                        <div class="card-body">
                            <h5 class="card-title text-muted"><i class="fa fa-check-circle"></i> Licenciado Completado</h5>
                            <p class="card-text text-muted">Ya graduado como Licenciado</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!$tiene_tsu): ?>
    <!-- Modal Confirmación TSU -->
    <div class="modal fade" id="modalGraduarTSU" tabindex="-1" aria-labelledby="modalGraduarTSULabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="modalGraduarTSULabel">Confirmar Graduación TSU</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de graduar a <strong><?= htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido']) ?></strong> como <strong>Técnico Superior Universitario (TSU)</strong>?</p>
                    <p class="text-muted">Esta acción registrará la graduación TSU. El estudiante podrá continuar hacia Licenciatura.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form action="../../controladores/estudianteController/graduarEstudiante.php" method="POST" style="display: inline;">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($estudiante['id']) ?>">
                        <input type="hidden" name="tipo_graduacion" value="TSU">
                        <button type="submit" class="btn btn-success">Sí, Graduar como TSU</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!$tiene_licenciado): ?>
    <!-- Modal Confirmación Licenciado -->
    <div class="modal fade" id="modalGraduarLicenciado" tabindex="-1" aria-labelledby="modalGraduarLicenciadoLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalGraduarLicenciadoLabel">Confirmar Graduación Licenciado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de graduar a <strong><?= htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido']) ?></strong> como <strong>Licenciado/Ingeniero</strong>?</p>
                    <p class="text-muted">Esta acción cambiará su estado a "graduado" y completará su formación académica.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form action="../../controladores/estudianteController/graduarEstudiante.php" method="POST" style="display: inline;">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($estudiante['id']) ?>">
                        <input type="hidden" name="tipo_graduacion" value="Licenciado">
                        <button type="submit" class="btn btn-primary">Sí, Graduar como Licenciado</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('generarCodigo').addEventListener('click', function() {
    const btn = this;
    const input = document.getElementById('codigo_estudiante');
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Generando...';
    
    fetch('../../controladores/estudianteController/generarCodigo.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                input.value = data.codigo;
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error al generar código');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-refresh"></i> Generar';
        });
});
</script>
</body>
</html>
<?php require_once __DIR__ . '/../../models/footer.php'; ?>
