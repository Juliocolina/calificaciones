<?php
require_once '../controladores/hellpers/auth.php';
verificarSesion();
require_once '../includes/header.php';
?>

<!-- Content -->
<div class="content">
    <div class="animated fadeIn">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card mt-5">
                    <div class="card-header text-center">
                        <h2 class="card-title">Bienvenido al Sistema de Carga de Notas</h2>
                        <p class="text-muted">
                            Usuario: <strong><?php echo htmlspecialchars($_SESSION['nombre_completo']); ?></strong> |
                            Rol: <strong><?php echo htmlspecialchars($_SESSION['rol']); ?></strong>
                        </p>
                    </div>
                    <div class="card-body text-center">
                        <img src="../images/logo_misionsucre.png" alt="Misión Sucre" style="max-width:120px; margin-bottom:20px;">
                        <p>
                            Este sistema está diseñado para la gestión y carga de notas de los estudiantes de las diferentes aldeas universitarias de <strong>Misión Sucre</strong> en el <strong>Municipio Miranda, Estado Falcón, Venezuela</strong>.
                        </p>
                        <p>
                            Aquí podrás registrar, consultar y administrar las calificaciones de los estudiantes de manera sencilla y segura.
                        </p>
                        <hr>
                        <h5>¿Qué deseas hacer?</h5>

                        <?php if ($_SESSION['rol'] === 'profesor'): ?>
                            <a href="calificaciones/cargarNotasFinal.php" class="btn btn-primary m-2">Registrar Notas</a>

                        <?php elseif ($_SESSION['rol'] === 'coordinador'): ?>
                            <a href="reportes.php" class="btn btn-info m-2">Ver Reportes</a>
                        <?php elseif ($_SESSION['rol'] === 'admin'): ?>
                            <a href="usuarios/verUsuario.php" class="btn btn-warning m-2">Gestionar Usuarios</a>
                            <a href="reportes.php" class="btn btn-info m-2">Ver Reportes</a>
                            <a href="../assets/manuales/manual_software.pdf" class="btn btn-warning m-2" target="_blank" rel="noopener">Manual de software</a>
                            <a href="../assets/manuales/manual_usuario.pdf" class="btn btn-info m-2" target="_blank" rel="noopener">Manual de usuario</a>
                        <?php elseif ($_SESSION['rol'] === 'estudiante'): ?>
                            <a href="calificaciones/misCalificaciones.php" class="btn btn-secondary m-2">Ver Mis Notas</a>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-muted text-center">
                        Sistema exclusivo para uso de las aldeas de Misión Sucre - Municipio Miranda, Falcón.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /.content -->

<?php require_once '../includes/footer.php'; ?>
