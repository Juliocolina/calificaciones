<?php
session_start();
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
verificarRol(['admin', 'coordinador']);
require_once __DIR__ . '/../../models/header.php';
require_once __DIR__ . '/../../config/conexion.php';

$conn = conectar();

// Obtener filtros
$oferta_id = intval($_GET['oferta_id'] ?? 0);

// Obtener ofertas académicas para el filtro
if ($_SESSION['rol'] === 'coordinador') {
    $stmt = $conn->prepare("
        SELECT oa.id, CONCAT(a.nombre, ' - ', p.nombre, ' - ', t.slug, ' - ', tr.nombre, ' (', UPPER(oa.tipo_oferta), ')') as descripcion
        FROM oferta_academica oa
        JOIN aldeas a ON oa.aldea_id = a.id
        JOIN pnfs p ON oa.pnf_id = p.id
        JOIN trayectos t ON oa.trayecto_id = t.id
        JOIN trimestres tr ON oa.trimestre_id = tr.id
        JOIN coordinadores c ON oa.aldea_id = c.aldea_id
        WHERE oa.estatus = 'Abierto' AND c.usuario_id = ?
        ORDER BY a.nombre, p.nombre, t.slug, oa.tipo_oferta
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
} else {
    $stmt = $conn->query("
        SELECT oa.id, CONCAT(a.nombre, ' - ', p.nombre, ' - ', t.slug, ' - ', tr.nombre, ' (', UPPER(oa.tipo_oferta), ')') as descripcion
        FROM oferta_academica oa
        JOIN aldeas a ON oa.aldea_id = a.id
        JOIN pnfs p ON oa.pnf_id = p.id
        JOIN trayectos t ON oa.trayecto_id = t.id
        JOIN trimestres tr ON oa.trimestre_id = tr.id
        WHERE oa.estatus = 'Abierto'
        ORDER BY a.nombre, p.nombre, t.slug, oa.tipo_oferta
    ");
}
$ofertas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener secciones con inscripciones filtradas por rol
if ($_SESSION['rol'] === 'coordinador') {
    $stmt = $conn->prepare("
        SELECT 
            s.id,
            s.cupo_maximo,
            m.nombre as materia_nombre,
            CONCAT(u.nombre, ' ', u.apellido) as profesor_nombre,
            CONCAT(a.nombre, ' - ', p.nombre, ' - ', t.slug, ' - ', tr.nombre) as oferta_descripcion,
            COUNT(i.id) as total_inscritos
        FROM secciones s
        JOIN materias m ON s.materia_id = m.id
        JOIN profesores pr ON s.profesor_id = pr.id
        JOIN usuarios u ON pr.usuario_id = u.id
        JOIN oferta_academica oa ON s.oferta_academica_id = oa.id
        JOIN aldeas a ON oa.aldea_id = a.id
        JOIN pnfs p ON oa.pnf_id = p.id
        JOIN trayectos t ON oa.trayecto_id = t.id
        JOIN trimestres tr ON oa.trimestre_id = tr.id
        JOIN coordinadores c ON oa.aldea_id = c.aldea_id
        LEFT JOIN inscripciones i ON s.id = i.seccion_id
        WHERE oa.estatus = 'Abierto' AND c.usuario_id = ?
        " . ($oferta_id > 0 ? " AND oa.id = $oferta_id" : "") . "
        GROUP BY s.id, s.cupo_maximo, m.nombre, u.nombre, u.apellido, a.nombre, p.nombre, t.slug, tr.nombre
        ORDER BY a.nombre, p.nombre, m.nombre
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
} else {
    $stmt = $conn->query("
        SELECT 
            s.id,
            s.cupo_maximo,
            m.nombre as materia_nombre,
            CONCAT(u.nombre, ' ', u.apellido) as profesor_nombre,
            CONCAT(a.nombre, ' - ', p.nombre, ' - ', t.slug, ' - ', tr.nombre) as oferta_descripcion,
            COUNT(i.id) as total_inscritos
        FROM secciones s
        JOIN materias m ON s.materia_id = m.id
        JOIN profesores pr ON s.profesor_id = pr.id
        JOIN usuarios u ON pr.usuario_id = u.id
        JOIN oferta_academica oa ON s.oferta_academica_id = oa.id
        JOIN aldeas a ON oa.aldea_id = a.id
        JOIN pnfs p ON oa.pnf_id = p.id
        JOIN trayectos t ON oa.trayecto_id = t.id
        JOIN trimestres tr ON oa.trimestre_id = tr.id
        LEFT JOIN inscripciones i ON s.id = i.seccion_id
        WHERE oa.estatus = 'Abierto'
        " . ($oferta_id > 0 ? " AND oa.id = $oferta_id" : "") . "
        GROUP BY s.id, s.cupo_maximo, m.nombre, u.nombre, u.apellido, a.nombre, p.nombre, t.slug, tr.nombre
        ORDER BY a.nombre, p.nombre, m.nombre
    ");
}
$secciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fa fa-check-circle"></i> <?= htmlspecialchars($_GET['success']) ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>
    
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0"><i class="fa fa-users"></i> Gestionar Inscripciones por Secciones</h3>
            <p class="mb-0">Administrar estudiantes inscritos en cada sección</p>
        </div>
        
        <div class="card-body">
            <form method="GET" class="mb-3">
                <div class="row">
                    <div class="col-md-8">
                        <select name="oferta_id" class="form-control" onchange="this.form.submit()">
                            <option value="">Todas las ofertas académicas</option>
                            <?php foreach ($ofertas as $oferta): ?>
                                <option value="<?= $oferta['id'] ?>" <?= $oferta['id'] == $oferta_id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($oferta['descripcion']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 text-right">
                        <a href="inscribirEnSecciones.php" class="btn btn-success">
                            <i class="fa fa-user-plus"></i> Inscribir Estudiante
                        </a>
                    </div>
                </div>
            </form>
            
            <?php if (empty($secciones)): ?>
                <div class="alert alert-info text-center">
                    <i class="fa fa-info-circle"></i> No hay secciones activas para gestionar.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>Sección</th>
                                <th>Materia</th>
                                <th>Profesor</th>
                                <th class="text-center">Inscritos</th>
                                <th class="text-center">Cupo</th>

                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($secciones as $seccion): ?>
                                <tr>
                                    <td>
                                        <small class="text-muted"><?= htmlspecialchars($seccion['oferta_descripcion']) ?></small>
                                    </td>
                                    <td><strong><?= htmlspecialchars($seccion['materia_nombre']) ?></strong></td>
                                    <td><?= htmlspecialchars($seccion['profesor_nombre']) ?></td>
                                    <td class="text-center">
                                        <span class="badge badge-primary"><?= $seccion['total_inscritos'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-secondary"><?= $seccion['cupo_maximo'] ?></span>
                                    </td>

                                    <td class="text-center">
                                        <a href="verEstudiantesSeccion.php?seccion_id=<?= $seccion['id'] ?>" class="btn btn-sm btn-primary">
                                            <i class="fa fa-eye"></i> Ver Estudiantes
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="card-footer text-center">
            <a href="../secciones/verSecciones.php" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Ver Secciones
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../models/footer.php'; ?>