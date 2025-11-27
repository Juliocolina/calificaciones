<?php
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
verificarRol(['admin', 'coordinador']);
require_once __DIR__ . '/../../models/header.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/estudianteController/verEstudiantes.php';
?>


<style>
    .table th, .table td {
        vertical-align: middle;
    }
    .acciones a, .acciones button {
        margin-right: 6px;
    }
    .table-responsive {
        overflow: visible !important;
    }
    .dropdown-menu {
        z-index: 1050;
        min-width: 180px;
    }
</style>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-11">
            <div class="card shadow">
                <div class="card-header text-center">
                    <h3 class="mb-0"><i class="fa fa-chalkboard-teacher"></i> Estudiantes Registrados</h3>
                </div>

                <div class="card-body">
                <p class="text-center mb-3">
                    <i class="fa fa-info-circle text-info"></i>
                        Lista de estudiantes registrados en el sistema.
                </p>
                
                <!-- Filtros y búsqueda -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="filtroPnf"><i class="fa fa-filter"></i> Filtrar por PNF:</label>
                        <select id="filtroPnf" class="form-control">
                            <option value="">Todos los PNFs</option>
                            <?php 
                            $conn = conectar();
                            $stmt_pnfs = $conn->query("SELECT DISTINCT p.nombre FROM pnfs p INNER JOIN estudiantes e ON p.id = e.pnf_id ORDER BY p.nombre");
                            while ($pnf = $stmt_pnfs->fetch(PDO::FETCH_ASSOC)) {
                                echo '<option value="' . htmlspecialchars($pnf['nombre']) . '">' . htmlspecialchars($pnf['nombre']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="filtroAldea"><i class="fa fa-map-marker"></i> Filtrar por Aldea:</label>
                        <select id="filtroAldea" class="form-control">
                            <option value="">Todas las Aldeas</option>
                            <?php 
                            if ($_SESSION['rol'] === 'admin') {
                                $stmt_aldeas = $conn->query("SELECT nombre FROM aldeas ORDER BY nombre");
                            } else {
                                $stmt_aldeas = $conn->query("SELECT DISTINCT a.nombre FROM aldeas a INNER JOIN estudiantes e ON a.id = e.aldea_id ORDER BY a.nombre");
                            }
                            while ($aldea = $stmt_aldeas->fetch(PDO::FETCH_ASSOC)) {
                                echo '<option value="' . htmlspecialchars($aldea['nombre']) . '">' . htmlspecialchars($aldea['nombre']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="busquedaGeneral"><i class="fa fa-search"></i> Búsqueda general:</label>
                        <input type="text" id="busquedaGeneral" class="form-control" placeholder="Buscar por nombre, cédula...">
                    </div>
                </div>
                

            <!-- Sección de Estudiantes con Perfil Completo -->
                    <?php if (isset($estudiantes_completos) && count($estudiantes_completos) > 0): ?>
                        <h5 class="text-success"><i class="fa fa-check-circle"></i> Estudiantes con Perfil Completo (<?= count($estudiantes_completos) ?>)</h5>
                        <div class="table-responsive mb-4">
                            <table id="tablaCompletos" class="table table-bordered table-hover table-striped align-middle">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Cédula</th>
                                        <th>Nombre</th>
                                        <th>Apellido</th>
                                        <th>PNF</th>
                                        <th>Aldea</th>
                                        <th class="text-center">Perfil Personal</th>
                                        <th class="text-center">Datos Académicos</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($estudiantes_completos as $estudiante): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($estudiante['usuario_cedula']) ?></td>
                                        <td><?= htmlspecialchars($estudiante['usuario_nombre']) ?></td>
                                        <td><?= htmlspecialchars($estudiante['usuario_apellido']) ?></td>
                                        <td><?= htmlspecialchars($estudiante['nombre_pnf']) ?></td>
                                        <td><?= htmlspecialchars($estudiante['nombre_aldea']) ?></td>
                                        <td class="text-center"><span class="badge badge-success"><i class="fa fa-check"></i> Completo</span></td>
                                        <td class="text-center"><span class="badge badge-success"><i class="fa fa-check"></i> Completo</span></td>
                                        <td class="acciones text-center">
                                            <div class="btn-group dropleft" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-toggle="dropdown">
                                                    Acciones
                                                </button>
                                                <div class="dropdown-menu">
                                                    <button class="dropdown-item" data-toggle="modal" data-target="#modalEstudiante<?= $estudiante['estudiante_id'] ?>">
                                                        Ver Detalles
                                                    </button>
                                                    <a class="dropdown-item" href="../inscripciones/inscribirEnSecciones.php?cedula=<?= htmlspecialchars($estudiante['usuario_cedula']) ?>">
                                                        Inscribir en Secciones
                                                    </a>

                                                    <div class="dropdown-divider"></div>
                                                    <form action="editarEstudiante.php" method="POST" style="display: inline-block; width: 100%;">
                                                        <input type="hidden" name="id" value="<?= htmlspecialchars($estudiante['estudiante_id']) ?>">
                                                        <button type="submit" class="dropdown-item">
                                                            Editar Estudiante
                                                        </button>
                                                    </form>
                                                    <form action="../../controladores/estudianteController/eliminarEstudiante.php" method="POST" style="display: inline-block; width: 100%;">
                                                        <input type="hidden" name="id" value="<?= $estudiante['estudiante_id'] ?>">
                                                        <button type="submit" class="dropdown-item text-danger" 
                                                                onclick="return confirm('¿Estás seguro de eliminar al estudiante <?= htmlspecialchars($estudiante['usuario_nombre']) ?> <?= htmlspecialchars($estudiante['usuario_apellido']) ?>? Esta acción no se puede deshacer.')">
                                                            Eliminar
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>

                                            <!-- Modal Ver Detalles -->
<div class="modal fade" id="modalEstudiante<?= $estudiante['estudiante_id'] ?>" tabindex="-1" role="dialog" aria-labelledby="modalEstudianteLabel<?= $estudiante['estudiante_id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h6 class="modal-title" id="modalEstudianteLabel<?= $estudiante['estudiante_id'] ?>">Detalles del Estudiante</h6>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <ul class="list-group">
                    <li class="list-group-item list-group-item-primary"><?= htmlspecialchars($estudiante['usuario_nombre']) ?> <?= htmlspecialchars($estudiante['usuario_apellido']) ?></li>
                    <li class="list-group-item">Cédula: <?= htmlspecialchars($estudiante['usuario_cedula']) ?></li>
                    <li class="list-group-item">Correo: <?= htmlspecialchars($estudiante['usuario_correo']) ?></li>
                    <li class="list-group-item">Teléfono: <?= htmlspecialchars($estudiante['usuario_telefono']) ?></li>
                    <li class="list-group-item">Fecha de Nacimiento: <?= htmlspecialchars($estudiante['fecha_nacimiento']) ?></li>
                    <li class="list-group-item">Aldea: <?= htmlspecialchars($estudiante['nombre_aldea']) ?></li>
                    <li class="list-group-item">PNF: <?= htmlspecialchars($estudiante['nombre_pnf']) ?></li>
                    <li class="list-group-item">Trayecto: <?= htmlspecialchars($estudiante['nombre_trayecto']) ?></li>
                    <li class="list-group-item">Trimestre: <?= htmlspecialchars($estudiante['nombre_trimestre']) ?></li>
                    <li class="list-group-item">Codigo: <?= htmlspecialchars($estudiante['codigo_estudiante']) ?></li>
                    <li class="list-group-item">Estado Académico: <?= htmlspecialchars($estudiante['estado_academico']) ?></li>
                    <li class="list-group-item">Observaciones: <?= htmlspecialchars($estudiante['observaciones']) ?></li>
                    <li class="list-group-item">Fecha de Ingreso: <?= htmlspecialchars($estudiante['fecha_ingreso']) ?></li>
                    <li class="list-group-item">Fecha de Graduación: <?= htmlspecialchars($estudiante['fecha_graduacion']) ?></li>
                    <li class="list-group-item">Parroquia: <?= htmlspecialchars($estudiante['parroquia'] ?? '') ?></li>
                    <li class="list-group-item">Nacionalidad: <?= htmlspecialchars($estudiante['nacionalidad']) ?></li>
                    <li class="list-group-item">Género: <?= htmlspecialchars($estudiante['genero']) ?></li>
                    <li class="list-group-item">Religión: <?= htmlspecialchars($estudiante['religion']) ?></li>
                    <li class="list-group-item">Etnia: <?= htmlspecialchars($estudiante['etnia']) ?></li>
                    <li class="list-group-item">Discapacidad: <?= htmlspecialchars($estudiante['discapacidad']) ?></li>
                    <li class="list-group-item">Nivel de Estudio: <?= htmlspecialchars($estudiante['nivel_estudio']) ?></li>
                    <li class="list-group-item">Institución de Procedencia: <?= htmlspecialchars($estudiante['institucion_procedencia']) ?></li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Paginación -->
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                <span id="infoCompletos">Mostrando 1-10 de X estudiantes</span>
                            </div>
                            <div>
                                <button id="anteriorCompletos" class="btn btn-sm btn-outline-primary" disabled>
                                    <i class="fa fa-chevron-left"></i> Anterior
                                </button>
                                <span id="paginaCompletos" class="mx-2">Página 1</span>
                                <button id="siguienteCompletos" class="btn btn-sm btn-outline-primary">
                                    Siguiente <i class="fa fa-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info text-center">No hay estudiantes con perfil completo.</div>
                    <?php endif; ?>

                    <!-- Sección de Usuarios Estudiantes Pendientes -->
                    <?php if (isset($estudiantes_pendientes) && count($estudiantes_pendientes) > 0): ?>
                        <h5 class="text-warning"><i class="fa fa-clock"></i> Usuarios Estudiantes Pendientes de Completar Perfil (<?= count($estudiantes_pendientes) ?>)</h5>
                        <div class="table-responsive">
                            <table id="tablaPendientes" class="table table-bordered table-hover table-striped align-middle">
                                <thead class="thead-warning">
                                    <tr>
                                        <th>Cédula</th>
                                        <th>Nombre</th>
                                        <th>Apellido</th>
                                        <th>Correo</th>
                                        <th class="text-center">Perfil Personal</th>
                                        <th class="text-center">Datos Académicos</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($estudiantes_pendientes as $pendiente): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($pendiente['usuario_cedula']) ?></td>
                                        <td><?= htmlspecialchars($pendiente['usuario_nombre']) ?></td>
                                        <td><?= htmlspecialchars($pendiente['usuario_apellido']) ?></td>
                                        <td><?= htmlspecialchars($pendiente['usuario_correo']) ?></td>
                                        <td class="text-center">
                                            <?php 
                                            $estado_personal = $pendiente['estado_perfil_personal'];
                                            if ($estado_personal === 'Perfil Completo') {
                                                echo '<span class="badge badge-success"><i class="fa fa-check"></i> Completo</span>';
                                            } elseif ($estado_personal === 'Sin Perfil') {
                                                echo '<span class="badge badge-secondary"><i class="fa fa-user-slash"></i> Sin Perfil</span>';
                                            } else {
                                                echo '<span class="badge badge-warning"><i class="fa fa-clock"></i> Pendiente</span>';
                                            }
                                            ?>
                                        </td>
                                        <td class="text-center">
                                            <?php 
                                            $estado_acad = $pendiente['estado_academico'];
                                            if ($estado_acad === 'Académico Completo') {
                                                echo '<span class="badge badge-success"><i class="fa fa-graduation-cap"></i> Completo</span>';
                                            } elseif ($estado_acad === 'Sin Asignar') {
                                                echo '<span class="badge badge-secondary"><i class="fa fa-times"></i> Sin Asignar</span>';
                                            } else {
                                                echo '<span class="badge badge-danger"><i class="fa fa-exclamation-triangle"></i> Pendiente</span>';
                                            }
                                            ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($pendiente['estudiante_id']): ?>
                                                <form action="editarEstudiante.php" method="POST" style="display: inline-block;">
                                                    <input type="hidden" name="id" value="<?= htmlspecialchars($pendiente['estudiante_id']) ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-primary" title="Completar Datos Académicos">
                                                        <i class="fa fa-edit"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <small class="text-muted">Esperando perfil</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success text-center">Todos los usuarios estudiantes han completado su perfil.</div>
                    <?php endif; ?>
                    

                </div>





                <div class="card-footer text-muted text-center small">
                    <i class="fa fa-lock"></i> Sistema exclusivo para uso de las aldeas de Misión Sucre - Municipio Miranda, Falcón.
                </div>
            </div>
        </div>
    </div>
</div>


<script>
// Variables globales
let paginaActual = 1;
const filasPorPagina = 10;
let todasLasFilas = [];

// Función para aplicar todos los filtros
function aplicarFiltros() {
    const busqueda = document.getElementById('busquedaGeneral').value.toLowerCase();
    const filtroPnf = document.getElementById('filtroPnf').value.toLowerCase();
    const filtroAldea = document.getElementById('filtroAldea').value.toLowerCase();
    
    todasLasFilas.forEach(fila => {
        const texto = fila.textContent.toLowerCase();
        const pnf = fila.cells[3] ? fila.cells[3].textContent.toLowerCase() : '';
        const aldea = fila.cells[4] ? fila.cells[4].textContent.toLowerCase() : '';
        
        const coincideBusqueda = !busqueda || texto.includes(busqueda);
        const coincidePnf = !filtroPnf || pnf.includes(filtroPnf);
        const coincideAldea = !filtroAldea || aldea.includes(filtroAldea);
        
        if (coincideBusqueda && coincidePnf && coincideAldea) {
            fila.classList.remove('filtrado');
        } else {
            fila.classList.add('filtrado');
        }
    });
    
    // Reiniciar a página 1 después de filtrar
    paginaActual = 1;
    paginarTabla();
}

// Función de paginación
function paginarTabla() {
    const filasVisibles = todasLasFilas.filter(fila => !fila.classList.contains('filtrado'));
    const totalFilas = filasVisibles.length;
    const totalPaginas = Math.ceil(totalFilas / filasPorPagina) || 1;
    
    // Asegurar que la página actual esté en rango válido
    if (paginaActual > totalPaginas) {
        paginaActual = totalPaginas;
    }
    if (paginaActual < 1) {
        paginaActual = 1;
    }
    
    const inicio = (paginaActual - 1) * filasPorPagina;
    const fin = inicio + filasPorPagina;
    
    // Ocultar todas las filas primero
    todasLasFilas.forEach(fila => {
        fila.style.display = 'none';
    });
    
    // Mostrar solo las filas de la página actual
    filasVisibles.slice(inicio, fin).forEach(fila => {
        fila.style.display = '';
    });
    
    // Actualizar información
    const infoElement = document.getElementById('infoCompletos');
    const paginaElement = document.getElementById('paginaCompletos');
    const anteriorBtn = document.getElementById('anteriorCompletos');
    const siguienteBtn = document.getElementById('siguienteCompletos');
    
    if (infoElement) {
        const mostrandoInicio = totalFilas > 0 ? inicio + 1 : 0;
        const mostrandoFin = Math.min(fin, totalFilas);
        infoElement.textContent = `Mostrando ${mostrandoInicio}-${mostrandoFin} de ${totalFilas} estudiantes`;
    }
    
    if (paginaElement) {
        paginaElement.textContent = `Página ${paginaActual} de ${totalPaginas}`;
    }
    
    // Actualizar botones
    if (anteriorBtn) {
        anteriorBtn.disabled = paginaActual <= 1;
    }
    if (siguienteBtn) {
        siguienteBtn.disabled = paginaActual >= totalPaginas;
    }
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Obtener todas las filas al cargar
    const tabla = document.getElementById('tablaCompletos');
    if (tabla) {
        todasLasFilas = Array.from(tabla.querySelectorAll('tbody tr'));
    }
    
    // Filtros
    const busquedaInput = document.getElementById('busquedaGeneral');
    const filtroPnfSelect = document.getElementById('filtroPnf');
    const filtroAldeaSelect = document.getElementById('filtroAldea');
    
    if (busquedaInput) {
        busquedaInput.addEventListener('input', aplicarFiltros);
    }
    if (filtroPnfSelect) {
        filtroPnfSelect.addEventListener('change', aplicarFiltros);
    }
    if (filtroAldeaSelect) {
        filtroAldeaSelect.addEventListener('change', aplicarFiltros);
    }
    
    // Botones de paginación
    const anteriorBtn = document.getElementById('anteriorCompletos');
    const siguienteBtn = document.getElementById('siguienteCompletos');
    
    if (anteriorBtn) {
        anteriorBtn.addEventListener('click', function() {
            if (paginaActual > 1) {
                paginaActual--;
                paginarTabla();
            }
        });
    }
    
    if (siguienteBtn) {
        siguienteBtn.addEventListener('click', function() {
            const filasVisibles = todasLasFilas.filter(fila => !fila.classList.contains('filtrado'));
            const totalPaginas = Math.ceil(filasVisibles.length / filasPorPagina) || 1;
            if (paginaActual < totalPaginas) {
                paginaActual++;
                paginarTabla();
            }
        });
    }
    
    // Inicializar paginación
    if (todasLasFilas.length > 0) {
        paginarTabla();
    }
});
</script>

<?php require_once __DIR__ . '/../../models/footer.php'; ?>