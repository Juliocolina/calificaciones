<?php
require_once __DIR__ . '/../controladores/hellpers/auth.php';
verificarSesion();

// --- OPTIMIZACIÓN MENOR: Definir el título de la página ---
// Esto permite que cada vista (ej. verAldeas.php) defina su propio título antes de incluir el header.
// Ejemplo de uso: $pageTitle = "Gestión de Aldeas"; require_once 'header.php';
$defaultTitle = "Sistema de Carga de Notas - Misión Sucre Miranda";
$pageTitle = isset($pageTitle) ? $pageTitle . ' | ' . $defaultTitle : $defaultTitle;
?>
<!doctype html>
<html class="no-js" lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <meta name="description" content="Sistema de Carga de Notas para las aldeas universitarias de Misión Sucre, Municipio Miranda, Falcón, Venezuela">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="shortcut icon" href="<?= base_url() ?>images/logo.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/normalize.css@8.0.0/normalize.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/lykmapipo/themify-icons@0.1.2/css/themify-icons.css">
    <link rel="stylesheet" href="<?= base_url() ?>assets/css/cs-skin-elastic.css">
    <link rel="stylesheet" href="<?= base_url() ?>assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/chartist@0.11.0/dist/chartist.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@3.9.0/dist/fullcalendar.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

    <style>
        #weatherWidget .currentDesc { color: #ffffff!important; }
        .traffic-chart { min-height: 335px; }
        #flotPie1  { height: 150px; }
        #flotPie1 td { padding:3px; }
        #flotPie1 table { top: 20px!important; right: -10px!important; }
        .chart-container { display: table; min-width: 270px ; text-align: left; padding-top: 10px; padding-bottom: 10px; }
        #flotLine5  { height: 105px; }
        #flotBarChart { height: 150px; }
        #cellPaiChart{ height: 160px; }
    </style>
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script src="<?= base_url() ?>assets/js/validaciones.js"></script>
</head>
<body>
    <aside id="left-panel" class="left-panel">
        <nav class="navbar navbar-expand-sm navbar-default">
            <div id="main-menu" class="main-menu collapse navbar-collapse">
                <ul class="nav navbar-nav">
                    <li class="active">
                        <a href="<?= base_url() ?>vistas/home.php"><i class="menu-icon fa fa-home"></i>Inicio</a>
                    </li>

                    <?php if ($_SESSION['rol'] === 'admin'): ?>
                        <li><a href="<?= base_url() ?>vistas/usuarios/miPerfil.php"><i class="menu-icon fa fa-user"></i> Mi Perfil</a></li>
                        
                        <li class="menu-item-has-children dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="menu-icon fa fa-users"></i>Gestión Personas</a>
                            <ul class="sub-menu children dropdown-menu">
                                <li><a href="<?= base_url() ?>vistas/usuarios/verUsuario.php"> Usuarios</a></li>
                                <li><a href="<?= base_url() ?>vistas/estudiantes/verEstudiantes.php"> Estudiantes</a></li>
                                <li><a href="<?= base_url() ?>vistas/profesores/verProfesores.php">Profesores</a></li>
                                <li><a href="<?= base_url() ?>vistas/coordinadores/verCoordinadores.php">Coordinadores</a></li>
                            </ul>
                        </li>
                        
                        <li class="menu-item-has-children dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="menu-icon fa fa-cogs"></i>Configuración</a>
                            <ul class="sub-menu children dropdown-menu">
                                <li><a href="<?= base_url() ?>vistas/aldeas/verAldeas.php"> Aldeas</a></li>
                                <li><a href="<?= base_url() ?>vistas/pnfs/verPnfs.php">PNFs</a></li>
                                <li><a href="<?= base_url() ?>vistas/trayectos/verTrayectos.php">Trayectos</a></li>
                                <li><a href="<?= base_url() ?>vistas/trimestres/verTrimestres.php">Trimestres</a></li>
                                <li><a href="<?= base_url() ?>vistas/materias/verMaterias.php">Materias</a></li>
                            </ul>
                        </li>
                        
                        <li><a href="<?= base_url() ?>vistas/reportes.php"><i class="menu-icon fa fa-bar-chart"></i> Reportes</a></li>
                        
                        <li><a href="<?= base_url() ?>vistas/ofertas_academicas/verOfertas.php"><i class="menu-icon fa fa-graduation-cap"></i> Ofertas Académicas</a></li>
                        
                        <li><a href="<?= base_url() ?>vistas/calificaciones/consultarCalificaciones.php"><i class="menu-icon fa fa-tasks"></i>Calificaciones</a></li>

                    <?php elseif ($_SESSION['rol'] === 'coordinador'): ?>
                        <li><a href="<?= base_url() ?>vistas/usuarios/miPerfil.php"><i class="menu-icon fa fa-user"></i> Mi Perfil</a></li>
                        <li><a href="<?= base_url() ?>vistas/estudiantes/verEstudiantes.php"><i class="menu-icon fa fa-users"></i> Estudiantes</a></li>
                        <li><a href="<?= base_url() ?>vistas/profesores/verProfesores.php"><i class="menu-icon fa fa-address-card"></i> Profesores</a></li>
                        <li><a href="<?= base_url() ?>vistas/ofertas_academicas/verOfertas.php"><i class="menu-icon fa fa-graduation-cap"></i> Ofertas Académicas</a></li>
                        
                        <li class="menu-item-has-children dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="menu-icon fa fa-tasks"></i>Gestión Académica</a>
                            <ul class="sub-menu children dropdown-menu">
                                <li><a href="<?= base_url() ?>vistas/inscripciones/gestionarInscripciones.php">Gestionar Inscripciones</a></li>
                                <li><a href="<?= base_url() ?>vistas/calificaciones/consultarCalificaciones.php">Consultar Calificaciones</a></li>
                            </ul>
                        </li>
                        
                    <?php elseif ($_SESSION['rol'] === 'profesor'): ?>
                        <li><a href="<?= base_url() ?>vistas/usuarios/miPerfil.php"><i class="menu-icon fa fa-user"></i> Mi Perfil</a></li>                        
                    <li class="menu-item-has-children dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                         <i class="menu-icon fa fa-graduation-cap"></i>Mis Clases</a>
                            <ul class="sub-menu children dropdown-menu">
                                <li><a href="<?= base_url() ?>vistas/inscripciones/misEstudiantesInscritos.php"> Mis Estudiantes Inscritos</a></li>
                                <li><a href="<?= base_url() ?>vistas/calificaciones/cargarNotas.php"> Cargar Notas</a></li>
                             </ul>
                    </li>                        
                    <?php elseif ($_SESSION['rol'] === 'estudiante'): ?>
                        <li><a href="<?= base_url() ?>vistas/usuarios/miPerfil.php"><i class="menu-icon fa fa-user"></i> Mi Perfil</a></li>
                        <li><a href="<?= base_url() ?>vistas/inscripciones/misInscripciones.php"><i class="menu-icon fa fa-list"></i> Mis Materias</a></li>
                        <li><a href="<?= base_url() ?>vistas/calificaciones/misCalificaciones.php"><i class="menu-icon fa fa-list"></i> Mis Notas</a></li>
                    <?php endif; ?>
                    
                    <li>
                        <a href="<?= base_url()  ?>controladores/salir.php"><i class="menu-icon fa fa-sign-out"></i>Cerrar Sesión</a></li>
                        
                </ul>
            </div>
        </nav>
    </aside>
    <div id="right-panel" class="right-panel">
        <header id="header" class="header">
            <div class="top-left">
                <div class="navbar-header">
                    <a class="navbar-brand" href="home.php"><img src="<?= base_url() ?>images/logo_misionsucre.png" alt="Logo" style="max-height:50px;">SICAN</a>
                    <a id="menuToggle" class="menutoggle"><i class="fa fa-bars"></i></a>
                </div>
            </div>
        </header>
        <?php require_once __DIR__ . '/../alerta/alerta.php'; ?>