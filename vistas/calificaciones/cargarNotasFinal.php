<?php
session_start();
require_once '../../config/conexion.php';
require_once '../../controladores/hellpers/auth.php';
require_once '../../models/PaginationHelper.php';

verificarSesion();

$pdo = conectar();
$usuario_id = $_SESSION['usuario_id'];
$rol = $_SESSION['rol'];

// Obtener ID del profesor
$stmt_prof = $pdo->prepare("SELECT id FROM profesores WHERE usuario_id = ?");
$stmt_prof->execute([$usuario_id]);
$profesor = $stmt_prof->fetch();

if (!$profesor) {
    $secciones = [];
} else {
    // Paginación
    $page = max(1, intval($_GET['page'] ?? 1));
    $records_per_page = 10;
    
    // Contar total de secciones (Contar por la clave de agrupación única, usando el AÑO de la fecha)
    $stmt_count = $pdo->prepare("
        SELECT COUNT(DISTINCT CONCAT(m.id, '-', pnf.id, '-', t.id, '-', oa.tipo_oferta, '-', YEAR(t2.fecha_inicio)))
        FROM secciones s
        JOIN materias m ON s.materia_id = m.id
        JOIN oferta_academica oa ON s.oferta_academica_id = oa.id
        JOIN pnfs pnf ON oa.pnf_id = pnf.id
        JOIN trayectos t ON oa.trayecto_id = t.id
        JOIN trimestres t2 ON oa.trimestre_id = t2.id
        WHERE s.profesor_id = ?
    ");
    $stmt_count->execute([$profesor['id']]);
    $total_secciones = $stmt_count->fetchColumn();
    
    $pagination = PaginationHelper::paginate($total_secciones, $records_per_page, $page);
    
    // Obtener materias agrupadas del profesor (CLAVE: Agrupar por el AÑO ACADÉMICO usando fecha_inicio)
    $stmt = $pdo->prepare("
        SELECT 
            -- Agregaciones de Secciones (Ordenamos por nombre de trimestre descendente para el id más reciente)
            GROUP_CONCAT(s.id ORDER BY t2.nombre DESC) as secciones_ids,
            
            -- Campos Descriptivos (Usamos MAX() para compatibilidad estricta con GROUP BY)
            MAX(m.id) as materia_id,
            MAX(m.nombre) as materia_nombre,
            MAX(m.duracion) as duracion,
            MAX(m.creditos) as creditos,
            MAX(pnf.nombre) as pnf_nombre,
            MAX(t.nombre) as trayecto_nombre,
            MAX(oa.tipo_oferta) as tipo_oferta,
            MAX(oa.trimestre_id) as trimestre_actual_id,
            
            -- Nuevo campo de agrupamiento (Año Académico)
            YEAR(MAX(t2.fecha_inicio)) as anio_academico,
            
            -- Campo del Período de Oferta (Muestra el trimestre más reciente del grupo)
            CONCAT(MAX(oa.tipo_oferta), ' - ', MAX(t2.nombre)) as periodo_oferta,
            
            -- Contadores
            COUNT(DISTINCT s.id) as total_secciones,
            COUNT(DISTINCT i.estudiante_id) as total_estudiantes, 
            COUNT(DISTINCT CASE WHEN c.id IS NOT NULL THEN i.estudiante_id END) as estudiantes_calificados,
            
            -- Lógica de ciclo completo (usa MAX() para duracion)
            CASE 
                WHEN MAX(m.duracion) = 'trimestral' AND COUNT(DISTINCT s.id) >= 1 THEN 1
                WHEN MAX(m.duracion) = 'bimestral' AND COUNT(DISTINCT s.id) >= 2 THEN 1
                WHEN MAX(m.duracion) = 'anual' AND COUNT(DISTINCT s.id) >= 3 THEN 1
                ELSE 0
            END as ciclo_completo
            
        FROM secciones s
        JOIN materias m ON s.materia_id = m.id
        JOIN oferta_academica oa ON s.oferta_academica_id = oa.id
        JOIN pnfs pnf ON oa.pnf_id = pnf.id
        JOIN trayectos t ON oa.trayecto_id = t.id
        LEFT JOIN inscripciones i ON s.id = i.seccion_id
        LEFT JOIN calificaciones c ON i.id = c.inscripcion_id
        -- Usamos JOIN porque toda oferta debe tener un trimestre
        JOIN trimestres t2 ON oa.trimestre_id = t2.id
        
        WHERE s.profesor_id = ?
        
        -- CLAVE: Agrupamos por los IDs de la instancia del curso Y por el AÑO EXTRAÍDO DE LA FECHA
        GROUP BY 
            m.id, 
            pnf.id, 
            t.id, 
            oa.tipo_oferta, 
            YEAR(t2.fecha_inicio) -- Agrupa por el AÑO ACADÉMICO (ej: 2026, 2027)
            
        ORDER BY MAX(pnf.nombre), anio_academico DESC, MAX(t.nombre), MAX(m.nombre), MAX(oa.tipo_oferta)
        LIMIT ? OFFSET ?
    ");
    
    $stmt->execute([$profesor['id'], $pagination['records_per_page'], $pagination['offset']]);
    $secciones = $stmt->fetchAll();
}


// Obtener trimestres disponibles
$stmt = $pdo->prepare("
    SELECT nombre as codigo, id, nombre
    FROM trimestres 
    ORDER BY nombre DESC
");
$stmt->execute();
$periodos = $stmt->fetchAll();
?>

<!doctype html>
<html class="no-js" lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Registrar Notas Finales</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../../assets/css/cs-skin-elastic.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>
    <?php include '../../models/header.php'; ?>

    <div class="breadcrumbs">
        <div class="breadcrumbs-inner">
            <div class="row m-0">
                <div class="col-sm-4">
                    <div class="page-header float-left">
                        <div class="page-title">
                            <h1>Registrar Notas Finales</h1>
                        </div>
                    </div>
                </div>
                <div class="col-sm-8">
                    <div class="page-header float-right">
                        <div class="page-title">
                            <ol class="breadcrumb text-right">
                                <li><a href="../../vistas/home.php">Inicio</a></li>
                                <li class="active">Notas Finales</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="animated fadeIn">
            
            <?php if (isset($_SESSION['mensaje'])): ?>
                <div class="alert alert-<?php echo $_SESSION['mensaje']['tipo']; ?> alert-dismissible fade show">
                    <?php echo $_SESSION['mensaje']['texto']; ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
                <?php unset($_SESSION['mensaje']); ?>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <strong class="card-title">Sistema Simplificado de Notas Finales</strong>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <h5><i class="fa fa-info-circle"></i> Nuevo Sistema:</h5>
                                <ul class="mb-0">
                                    <li><strong>Materias Trimestrales:</strong> 1 nota al final del trimestre</li>
                                    <li><strong>Materias Bimestrales:</strong> 1 nota al final del bimestre</li>
                                    <li><strong>Materias Anuales:</strong> 1 nota al final del año</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <strong>Mis Secciones</strong>
                            <?php if (!empty($secciones)): ?>
                                <small class="text-muted ml-2">(Página <?php echo $pagination['current_page']; ?> de <?php echo $pagination['total_pages']; ?> - <?php echo $pagination['total_records']; ?> secciones)</small>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if (empty($secciones)): ?>
                                <div class="alert alert-warning">
                                    No tienes secciones asignadas actualmente.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Año Académico</th> <th>PNF</th>
                                                <th>Trayecto</th>
                                                <th>Materia</th>
                                                <th>Tipo Oferta</th>
                                                <th>Duración</th>
                                                <th>Secciones</th>
                                                <th>Estudiantes</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($secciones as $seccion): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge badge-secondary"><?php echo htmlspecialchars($seccion['anio_academico']); ?></span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($seccion['pnf_nombre']); ?></td>
                                                    <td><?php echo htmlspecialchars($seccion['trayecto_nombre']); ?></td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($seccion['materia_nombre']); ?></strong>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-<?php 
                                                            echo $seccion['tipo_oferta'] == 'regular' ? 'success' : 
                                                                ($seccion['tipo_oferta'] == 'intensivo' ? 'warning' : 'danger'); 
                                                        ?>">
                                                            <?php echo ucfirst($seccion['tipo_oferta']); ?>
                                                        </span>
                                                        <br>
                                                        <small class="text-muted">Último: <?php echo $seccion['periodo_oferta']; ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-<?php 
                                                            echo $seccion['duracion'] == 'trimestral' ? 'primary' : 
                                                                ($seccion['duracion'] == 'bimestral' ? 'info' : 'success'); 
                                                        ?>">
                                                            <?php echo ucfirst($seccion['duracion']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-success"><?php echo $seccion['total_secciones']; ?></span>
                                                        <small class="text-muted d-block">
                                                            <?php 
                                                                $requeridas = $seccion['duracion'] == 'trimestral' ? 1 : 
                                                                            ($seccion['duracion'] == 'bimestral' ? 2 : 3);
                                                                echo "({$seccion['total_secciones']}/{$requeridas})";
                                                            ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-secondary"><?php echo $seccion['total_estudiantes']; ?></span>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                            $secciones_array = explode(',', $seccion['secciones_ids']);
                                                            // Usamos la primera sección (la más reciente por el ORDER BY en GROUP_CONCAT)
                                                            $seccion_usar = $secciones_array[0]; 
                                                            
                                                            $listo_para_calificar = $seccion['ciclo_completo'] == 1;
                                                        ?>
                                                        
                                                        <?php if ($seccion['total_estudiantes'] > 0): ?>
                                                            <?php if ($listo_para_calificar): ?>
                                                                <a href="registrarNotasSeccionFinal.php?seccion_id=<?php echo $seccion_usar; ?>" class="btn btn-primary btn-sm">
                                                                    <i class="fa fa-edit"></i> Gestionar Calificaciones
                                                                </a>
                                                            <?php else: ?>
                                                                <button class="btn btn-secondary btn-sm" disabled title="Ciclo incompleto">
                                                                    <i class="fa fa-lock"></i> Ciclo Incompleto
                                                                </button>
                                                            <?php endif; ?>

                                                            <?php if ($seccion['estudiantes_calificados'] == $seccion['total_estudiantes'] && $listo_para_calificar): ?>
                                                                <small class="text-success d-block">✓ Todos calificados</small>
                                                            <?php elseif ($seccion['estudiantes_calificados'] > 0): ?>
                                                                <small class="text-warning d-block"><?php echo $seccion['estudiantes_calificados']; ?>/<?php echo $seccion['total_estudiantes']; ?> calificados</small>
                                                            <?php else: ?>
                                                                <small class="text-muted d-block">Pendiente</small>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="badge badge-secondary">Sin Estudiantes</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
                                    <div class="mt-3">
                                        <?php echo PaginationHelper::renderPagination($pagination, 'cargarNotasFinal.php'); ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <strong>Trimestres Disponibles</strong>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($periodos as $periodo): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card border-left-primary">
                                            <div class="card-body">
                                                <h6 class="card-title"><?php echo htmlspecialchars($periodo['codigo']); ?></h6>
                                                <p class="card-text">
                                                    <small class="text-muted">
                                                        ID: <?php echo $periodo['id']; ?>
                                                    </small>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <?php include '../../models/footer.php'; ?>

    <script src="../../assets/js/lib/jquery/jquery.min.js"></script>
    <script src="../../assets/js/lib/bootstrap/js/bootstrap.min.js"></script>
    <script src="../../assets/js/main.js"></script>
    
    <script>
    function cargarNotas(seccionesIds, materiaNombre) {
        // Redirigir a la página de carga de notas con los IDs de las secciones
        const url = `cargarNotasMateria.php?secciones=${seccionesIds}&materia=${encodeURIComponent(materiaNombre)}`;
        window.location.href = url;
    }
    </script>
</body>
</html>