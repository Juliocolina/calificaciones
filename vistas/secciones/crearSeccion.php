<?php
session_start();
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
verificarSesion();
require_once __DIR__ . '/../../models/header.php';
require_once __DIR__ . '/../../config/conexion.php';

$conn = conectar();

// Obtener aldea del coordinador si es coordinador
$aldea_coordinador = null;
if ($_SESSION['rol'] === 'coordinador') {
    $stmt_coord = $conn->prepare("SELECT aldea_id FROM coordinadores WHERE usuario_id = ?");
    $stmt_coord->execute([$_SESSION['usuario_id']]);
    $coord_data = $stmt_coord->fetch(PDO::FETCH_ASSOC);
    $aldea_coordinador = $coord_data['aldea_id'] ?? null;
}

// Obtener ofertas académicas abiertas (filtradas por aldea si es coordinador)
$sql = "
    SELECT oa.id, CONCAT(a.nombre, ' - ', p.nombre, ' - ', t.slug, ' - ', tr.nombre, ' - ', COALESCE(oa.tipo_oferta, 'Regular')) as descripcion
    FROM oferta_academica oa
    JOIN aldeas a ON oa.aldea_id = a.id
    JOIN pnfs p ON oa.pnf_id = p.id
    JOIN trayectos t ON oa.trayecto_id = t.id
    JOIN trimestres tr ON oa.trimestre_id = tr.id
    WHERE oa.estatus = 'Abierto'";

$params = [];
if ($_SESSION['rol'] === 'coordinador' && $aldea_coordinador) {
    $sql .= " AND oa.aldea_id = ?";
    $params[] = $aldea_coordinador;
}

$sql .= " ORDER BY a.nombre, p.nombre";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$ofertas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .card {
        margin-top: 20px;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(30,60,114,0.15);
    }
    .form-title {
        background: #1e3c72;
        color: #fff;
        padding: 20px 0;
        border-radius: 16px 16px 0 0;
        margin-bottom: 30px;
    }
</style>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="form-title text-center">
                    <h3><i class="fa fa-plus-circle"></i> Crear Nueva Sección</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fa fa-exclamation-triangle"></i> <?= htmlspecialchars($_GET['error']) ?>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fa fa-check-circle"></i> <?= htmlspecialchars($_GET['success']) ?>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="alert alert-info" role="alert">
                        <i class="fa fa-info-circle"></i> <strong>💡 Información importante:</strong><br>
                        Para crear múltiples secciones de la misma materia, debe seleccionar un profesor diferente para cada sección.
                    </div>
                    
                    <form action="../../controladores/seccionController/crearSeccion.php" method="POST" id="formSeccion">
                        
                        <div class="form-group">
                            <label for="oferta_academica_id"><i class="fa fa-university"></i> Oferta Académica</label>
                            <select name="oferta_academica_id" id="oferta_academica_id" class="form-control" required>
                                <option value="">Seleccione una oferta académica</option>
                                <?php foreach ($ofertas as $oferta): ?>
                                    <option value="<?= $oferta['id'] ?>"><?= htmlspecialchars($oferta['descripcion']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="materia_id"><i class="fa fa-book"></i> Materia</label>
                            <select name="materia_id" id="materia_id" class="form-control" required disabled>
                                <option value="">Primero seleccione una oferta académica</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="profesor_id"><i class="fa fa-chalkboard-teacher"></i> Profesor</label>
                            <select name="profesor_id" id="profesor_id" class="form-control" required disabled>
                                <option value="">Primero seleccione una materia</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="cupo_maximo"><i class="fa fa-users"></i> Cupo Máximo</label>
                            <input type="number" name="cupo_maximo" id="cupo_maximo" class="form-control" 
                                   value="30" min="1" max="50" required>
                            <small class="form-text text-muted">Número máximo de estudiantes (recomendado: 30)</small>
                        </div>

                        <div class="text-right">
                            <a href="verSecciones.php" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-success">
                                <i class="fa fa-save"></i> Crear Sección
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../models/footer.php'; ?>

<script>
$(document).ready(function() {
    // Cargar materias cuando se selecciona oferta académica
    $('#oferta_academica_id').change(function() {
        const ofertaId = $(this).val();
        
        if (ofertaId) {
            $('#materia_id').html('<option value="">Cargando materias...</option>').prop('disabled', true);
            
            $.ajax({
                url: '../../api/getMateriasByOferta.php',
                method: 'GET',
                data: { oferta_id: ofertaId },
                dataType: 'json',
                success: function(materias) {
                    let options = '<option value="">Seleccione una materia</option>';
                    
                    if (materias && materias.length > 0) {
                        materias.forEach(function(materia) {
                            options += `<option value="${materia.id}">${materia.nombre} (${materia.creditos} créditos - ${materia.duracion})</option>`;
                        });
                    } else {
                        options = '<option value="">No hay materias disponibles para este PNF</option>';
                    }
                    
                    $('#materia_id').html(options).prop('disabled', false);
                    $('#profesor_id').html('<option value="">Primero seleccione una materia</option>').prop('disabled', true);
                },
                error: function() {
                    $('#materia_id').html('<option value="">Error al cargar materias</option>').prop('disabled', true);
                }
            });
        } else {
            $('#materia_id').html('<option value="">Primero seleccione una oferta académica</option>').prop('disabled', true);
            $('#profesor_id').html('<option value="">Primero seleccione una materia</option>').prop('disabled', true);
        }
    });
    
    // Cargar profesores cuando se selecciona materia
    $('#materia_id').change(function() {
        const materiaId = $(this).val();
        
        if (materiaId) {
            $('#profesor_id').html('<option value="">Cargando profesores...</option>').prop('disabled', true);
            
            $.ajax({
                url: '../../api/getProfesoresByMateria.php',
                method: 'GET',
                data: { materia_id: materiaId },
                dataType: 'json',
                success: function(profesores) {
                    let options = '<option value="">Seleccione un profesor</option>';
                    
                    if (profesores && profesores.length > 0) {
                        profesores.forEach(function(profesor) {
                            options += `<option value="${profesor.id}">${profesor.nombre} ${profesor.apellido}</option>`;
                        });
                    } else {
                        options = '<option value="">No hay profesores asignados a esta materia</option>';
                    }
                    
                    $('#profesor_id').html(options).prop('disabled', false);
                },
                error: function() {
                    $('#profesor_id').html('<option value="">Error al cargar profesores</option>').prop('disabled', true);
                }
            });
        } else {
            $('#profesor_id').html('<option value="">Primero seleccione una materia</option>').prop('disabled', true);
        }
    });
});
</script>