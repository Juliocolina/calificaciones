<?php
session_start();
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
verificarRol(['admin', 'coordinador']);
require_once __DIR__ . '/../../models/header.php';
require_once __DIR__ . '/../../config/conexion.php';

$conn = conectar();

// Obtener estudiantes activos
$stmt = $conn->query("
    SELECT e.id, u.nombre, u.apellido, u.cedula, p.nombre as pnf_nombre, e.pnf_id
    FROM estudiantes e
    JOIN usuarios u ON e.usuario_id = u.id
    LEFT JOIN pnfs p ON e.pnf_id = p.id
    WHERE e.estado_academico = 'activo'
    ORDER BY u.apellido, u.nombre
");
$estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow">
                <div class="card-header bg-primary text-white text-center">
                    <h3 class="mb-0">
                        <i class="fa fa-user-plus"></i> Inscribir Estudiante en Secciones
                    </h3>
                </div>
                
                <div class="card-body">
                    <form id="formInscripcion" method="POST" action="../../controladores/inscripcionController/procesarInscripcionSecciones.php">
                        
                        <div class="form-group">
                            <label for="cedula_estudiante"><i class="fa fa-id-card"></i> Cédula del Estudiante</label>
                            <div class="input-group">
                                <input type="text" name="cedula_estudiante" id="cedula_estudiante" 
                                       class="form-control" placeholder="Ej: 12345678" 
                                       value="<?= htmlspecialchars($_GET['cedula'] ?? '') ?>" required>
                                <div class="input-group-append">
                                    <button type="button" id="buscar_estudiante" class="btn btn-primary">
                                        <i class="fa fa-search"></i> Buscar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div id="datos-estudiante" style="display: none;" class="alert alert-info">
                            <!-- Datos del estudiante se mostrarán aquí -->
                        </div>

                        <input type="hidden" name="estudiante_id" id="estudiante_id">

                        <div id="secciones-container" style="display: none;">
                            <h5 class="text-primary"><i class="fa fa-list"></i> Secciones Disponibles</h5>
                            <div id="secciones-list" class="border rounded p-3 bg-light">
                                <!-- Las secciones se cargarán aquí dinámicamente -->
                            </div>
                        </div>

                        <div class="text-center mt-4" id="botones-container" style="display: none;">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fa fa-save"></i> Inscribir en Secciones Seleccionadas
                            </button>
                            <a href="gestionarInscripciones.php" class="btn btn-secondary btn-lg ml-2">
                                <i class="fa fa-arrow-left"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Buscar automáticamente si viene cédula en URL
    const cedulaInicial = $('#cedula_estudiante').val().trim();
    if (cedulaInicial) {
        $('#buscar_estudiante').click();
    }
    
    $('#buscar_estudiante').click(function() {
        const cedula = $('#cedula_estudiante').val().trim();
        
        if (!cedula) {
            alert('Por favor ingrese una cédula');
            return;
        }
        
        console.log('Buscando estudiante con cédula:', cedula);
        
        // Mostrar loading
        $('#datos-estudiante').html('<p><i class="fa fa-spinner fa-spin"></i> Buscando estudiante...</p>').show();
        $('#secciones-container').hide();
        $('#botones-container').hide();
        
        $.ajax({
            url: '../../api/buscarEstudiantePorCedula.php',
            method: 'GET',
            data: { cedula: cedula },
            dataType: 'json',
            success: function(response) {
                console.log('Respuesta del estudiante:', response);
                
                if (response.success && response.estudiante) {
                    const est = response.estudiante;
                    
                    // Mostrar datos del estudiante
                    let html = `
                        <h6><i class="fa fa-user"></i> Datos del Estudiante</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Nombre:</strong> ${est.nombre} ${est.apellido}<br>
                                <strong>Cédula:</strong> ${est.cedula}<br>
                                <strong>PNF:</strong> ${est.pnf_nombre}
                            </div>
                            <div class="col-md-6">
                                <strong>Trayecto:</strong> ${est.trayecto_nombre}<br>
                                <strong>Trimestre:</strong> ${est.trimestre_nombre}<br>
                                <strong>Aldea:</strong> ${est.aldea_nombre}
                            </div>
                        </div>
                        <div class="mt-2">
                            <strong>Materias Pendientes:</strong> 
                            <span class="badge ${est.materias_pendientes > 3 ? 'badge-danger' : (est.materias_pendientes > 0 ? 'badge-warning' : 'badge-success')}">
                                ${est.materias_pendientes}/3
                            </span>
                            ${est.puede_inscribirse ? 
                                '<span class="badge badge-success ml-2">PUEDE INSCRIBIRSE</span>' : 
                                '<span class="badge badge-danger ml-2">NO PUEDE INSCRIBIRSE</span>'
                            }
                            ${est.materias_reprobadas && est.materias_reprobadas.length > 0 ? 
                                '<div class="mt-1"><small class="text-danger"><strong>Reprobadas:</strong> ' + 
                                est.materias_reprobadas.map(m => m.materia_nombre + ' (' + m.nota_numerica + ')').join(', ') + 
                                '</small></div>' : ''
                            }
                        </div>
                    `;
                    
                    $('#datos-estudiante').html(html).removeClass('alert-danger').addClass('alert-info');
                    $('#estudiante_id').val(est.id);
                    
                    if (est.puede_inscribirse) {
                        cargarSecciones(est.id, est.pnf_id, est.trayecto_id, est.trimestre_id);
                    } else {
                        $('#secciones-container').hide();
                        $('#botones-container').hide();
                    }
                } else {
                    $('#datos-estudiante').html(`
                        <h6><i class="fa fa-exclamation-triangle"></i> Estudiante No Encontrado</h6>
                        <p>No se encontró ningún estudiante con la cédula: <strong>${cedula}</strong></p>
                    `).removeClass('alert-info').addClass('alert-danger');
                    $('#estudiante_id').val('');
                    $('#secciones-container').hide();
                    $('#botones-container').hide();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al buscar estudiante:', error);
                $('#datos-estudiante').html(`
                    <h6><i class="fa fa-times"></i> Error</h6>
                    <p>Error al buscar el estudiante. Intente nuevamente.</p>
                `).removeClass('alert-info').addClass('alert-danger');
            }
        });
    });
    
    // Buscar al presionar Enter
    $('#cedula_estudiante').keypress(function(e) {
        if (e.which == 13) {
            $('#buscar_estudiante').click();
        }
    });
    
    function cargarSecciones(estudianteId, pnfId, trayectoId, trimestreId) {
        $('#secciones-list').html('<p class="text-center"><i class="fa fa-spinner fa-spin"></i> Cargando secciones...</p>');
        $('#secciones-container').show();
        
        $.ajax({
            url: '../../api/getSeccionesByEstudiante.php',
            method: 'GET',
            data: { 
                estudiante_id: estudianteId,
                pnf_id: pnfId,
                trayecto_id: trayectoId,
                trimestre_id: trimestreId
            },
            dataType: 'json',
            success: function(secciones) {
                console.log('Secciones recibidas:', secciones);
                
                if (secciones && secciones.length > 0) {
                    let html = '';
                    
                    secciones.forEach(function(seccion) {
                        const cupoDisponible = seccion.cupo_maximo - seccion.inscritos;
                        const badgeClass = cupoDisponible > 10 ? 'badge-success' : 
                                         cupoDisponible > 0 ? 'badge-warning' : 'badge-danger';
                        const disabled = cupoDisponible <= 0 || seccion.ya_inscrito || seccion.ya_aprobada || seccion.bloqueado_por_proyecto == 1 ? 'disabled' : '';
                        const checked = seccion.ya_inscrito && seccion.estatus_inscripcion === 'Cursando' ? 'checked' : '';
                        
                        let statusBadge = '';
                        let bgClass = '';
                        if (seccion.bloqueado_por_proyecto == 1) {
                            statusBadge = '<span class="badge badge-warning ml-2">PROYECTO REPROBADO - NO PUEDE AVANZAR</span>';
                            bgClass = 'bg-light border-warning';
                        } else if (seccion.ya_aprobada == 1) {
                            statusBadge = '<span class="badge badge-info ml-2">YA APROBADA</span>';
                            bgClass = 'bg-light border-info';
                        } else if (seccion.ya_inscrito == 1) {
                            if (seccion.estatus_inscripcion === 'Cursando') {
                                statusBadge = '<span class="badge badge-success ml-2">YA INSCRITO</span>';
                                bgClass = 'bg-light border-success';
                            } else if (seccion.estatus_inscripcion === 'Aprobada') {
                                statusBadge = '<span class="badge badge-info ml-2">YA APROBADA</span>';
                                bgClass = 'bg-light border-info';
                            } else if (seccion.estatus_inscripcion === 'Reprobada') {
                                statusBadge = '<span class="badge badge-danger ml-2">YA CURSADA (REPROBADA)</span>';
                                bgClass = 'bg-light border-danger';
                            }
                        }
                        
                        html += `
                            <div class="form-check mb-3 p-3 border rounded ${bgClass}">
                                <input class="form-check-input" type="checkbox" 
                                       name="secciones[]" value="${seccion.id}" 
                                       id="seccion_${seccion.id}" ${disabled} ${checked}>
                                <label class="form-check-label" for="seccion_${seccion.id}">
                                    <strong>${seccion.materia_nombre}</strong><br>
                                    <small>
                                        Profesor: ${seccion.profesor_nombre}<br>
                                        Cupo: <span class="badge ${badgeClass}">${seccion.inscritos}/${seccion.cupo_maximo}</span>
                                        ${statusBadge}
                                    </small>
                                </label>
                            </div>
                        `;
                    });
                    
                    $('#secciones-list').html(html);
                    $('#botones-container').show();
                } else {
                    $('#secciones-list').html('<div class="alert alert-warning">No hay secciones disponibles</div>');
                    $('#botones-container').hide();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al cargar secciones:', error);
                $('#secciones-list').html('<div class="alert alert-danger">Error al cargar las secciones</div>');
                $('#botones-container').hide();
            }
        });
    }

});
</script>



<?php require_once __DIR__ . '/../../models/footer.php'; ?>