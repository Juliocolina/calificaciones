<?php
require_once '../config/conexion.php';
require_once '../controladores/hellpers/auth.php';

verificarRol(['admin', 'coordinador']);
require_once '../models/header.php';

$rol = $_SESSION['rol'];
?>

<div class="content">
    <div class="animated fadeIn">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow">
                    <div class="card-header bg-info text-white text-center">
                        <h3 class="mb-0">
                            <i class="fa fa-bar-chart"></i> Centro de Reportes
                        </h3>
                        <p class="mb-0">Seleccione el reporte que desea generar</p>
                    </div>
                    
                    <div class="card-body">
                        <div class="row">
                            
                            <?php if ($rol === 'coordinador'): ?>
                                <!-- Reportes para Coordinadores -->
                                <div class="col-md-6 mb-4">
                                    <div class="card border-primary">
                                        <div class="card-body text-center">
                                            <i class="fa fa-users fa-3x text-primary mb-3"></i>
                                            <h5 class="card-title">Aprobados y Reprobados</h5>
                                            <p class="card-text">Consultar estudiantes aprobados y reprobados por materia y período</p>
                                            <a href="reportes/listadoAprobadosReprobados.php" class="btn btn-primary">
                                                <i class="fa fa-eye"></i> Ver Reporte
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-4">
                                    <div class="card border-danger">
                                        <div class="card-body text-center">
                                            <i class="fa fa-file-pdf-o fa-3x text-danger mb-3"></i>
                                            <h5 class="card-title">Lista de Estudiantes PDF</h5>
                                            <p class="card-text">Generar listado oficial de estudiantes por aldea y PNF</p>
                                            <a href="reportes/listaEstudiantes.php" class="btn btn-danger">
                                                <i class="fa fa-file-pdf-o"></i> Generar PDF
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-4">
                                    <div class="card border-success">
                                        <div class="card-body text-center">
                                            <i class="fa fa-address-card fa-3x text-success mb-3"></i>
                                            <h5 class="card-title">Nómina de Profesores PDF</h5>
                                            <p class="card-text">Generar nómina de profesores con materias y estudiantes</p>
                                            <a href="reportes/nominaProfesores.php" class="btn btn-success">
                                                <i class="fa fa-file-pdf-o"></i> Generar PDF
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-4">
                                    <div class="card border-warning">
                                        <div class="card-body text-center">
                                            <i class="fa fa-graduation-cap fa-3x text-warning mb-3"></i>
                                            <h5 class="card-title">Reporte de Graduados</h5>
                                            <p class="card-text">Consultar y generar PDF de estudiantes graduados (TSU/Licenciado)</p>
                                            <a href="reportes/reporteGraduados.php" class="btn btn-warning">
                                                <i class="fa fa-graduation-cap"></i> Ver Reporte
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                

                                
                            <?php elseif ($rol === 'admin'): ?>
                                <!-- Reportes para Administradores -->
                                <div class="col-md-4 mb-4">
                                    <div class="card border-danger">
                                        <div class="card-body text-center">
                                            <i class="fa fa-file-pdf-o fa-3x text-danger mb-3"></i>
                                            <h5 class="card-title">Lista de Estudiantes PDF</h5>
                                            <p class="card-text">Generar listado oficial de estudiantes</p>
                                            <a href="reportes/listaEstudiantes.php" class="btn btn-danger">
                                                <i class="fa fa-file-pdf-o"></i> Generar PDF
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-4">
                                    <div class="card border-success">
                                        <div class="card-body text-center">
                                            <i class="fa fa-address-card fa-3x text-success mb-3"></i>
                                            <h5 class="card-title">Nómina de Profesores PDF</h5>
                                            <p class="card-text">Generar nómina de profesores</p>
                                            <a href="reportes/nominaProfesores.php" class="btn btn-success">
                                                <i class="fa fa-file-pdf-o"></i> Generar PDF
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-4">
                                    <div class="card border-primary">
                                        <div class="card-body text-center">
                                            <i class="fa fa-users fa-3x text-primary mb-3"></i>
                                            <h5 class="card-title">Aprobados y Reprobados</h5>
                                            <p class="card-text">Consultar estudiantes por estado académico</p>
                                            <a href="reportes/listadoAprobadosReprobados.php" class="btn btn-primary">
                                                <i class="fa fa-eye"></i> Ver Reporte
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-4">
                                    <div class="card border-warning">
                                        <div class="card-body text-center">
                                            <i class="fa fa-graduation-cap fa-3x text-warning mb-3"></i>
                                            <h5 class="card-title">Reporte de Graduados</h5>
                                            <p class="card-text">Consultar y generar PDF de graduados</p>
                                            <a href="reportes/reporteGraduados.php" class="btn btn-warning">
                                                <i class="fa fa-graduation-cap"></i> Ver Reporte
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                

                            <?php endif; ?>
                            
                        </div>
                        
                        <div class="alert alert-info mt-4">
                            <i class="fa fa-info-circle"></i> 
                            <strong>Información:</strong> Los reportes se generan en tiempo real con los datos más actualizados del sistema.
                        </div>
                    </div>
                    
                    <div class="card-footer text-center">
                        <a href="home.php" class="btn btn-secondary">
                            <i class="fa fa-arrow-left"></i> Volver al Inicio
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../models/footer.php'; ?>