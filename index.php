<?php
session_start();
?>

<!doctype html>
<html class="no-js" lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Login - Sistema de Carga de Notas</title>
    <meta name="description" content="Sistema de Carga de Notas - Misión Sucre Miranda">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="shortcut icon" href="images/logo_misionsucre.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/normalize.css@8.0.0/normalize.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="assets/css/cs-skin-elastic.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/style_login.css">


    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
</head>
<body>
    <div class="sufee-login d-flex align-content-center flex-wrap" style="width:100vw;min-height:100vh;">
        <div class="container">
            <div class="login-content">
                <div class="login-logo text-center">
                    <img src="images/logo_misionsucre.png" alt="Logo Misión Sucre" class="rounded-circle shadow animate__animated animate__fadeInDown" style="background:#fff;padding:8px;max-width:100px;">
                    <div class="sistema-desc">
                        Aldeas Universitarias Misión Sucre<br>
                        Municipio Miranda, Estado Falcón, Venezuela
                    </div>
                </div>
                <div class="login-form">
                    <?php
                        // Priorizar mensajes de sesión sobre parámetros GET
                        if (isset($_SESSION['mensaje'])) {
                            $mensaje = $_SESSION['mensaje'];
                            $icon = 'error';
                            $title = 'Error';
                            
                            // Detectar tipo de mensaje
                            if (strpos($mensaje, 'bloqueada') !== false) {
                                $icon = 'warning';
                                $title = 'Cuenta Bloqueada';
                            } elseif (strpos($mensaje, 'quedan') !== false) {
                                $icon = 'warning';
                                $title = 'Intento Fallido';
                            }
                            
                            echo "<script>
                                Swal.fire({
                                    icon: '$icon',
                                    title: '$title',
                                    text: '" . addslashes($mensaje) . "',
                                    confirmButtonColor: '#3085d6'
                                });
                            </script>";
                            unset($_SESSION['mensaje']);
                        } elseif (isset($_GET['exito']) && $_GET['exito'] === 'logout') {
                            echo "<script>
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Sesión cerrada',
                                    text: 'Has cerrado sesión correctamente.',
                                    confirmButtonColor: '#3085d6'
                                });
                            </script>";
                        } elseif (isset($_GET['error'])) {
                            $mensaje_error = urldecode($_GET['error']);
                            
                            if (strpos($mensaje_error, 'Ya tienes una sesión activa') !== false) {
                                echo "<script>
                                    Swal.fire({
                                        icon: 'warning',
                                        title: 'Sesión Activa',
                                        text: '" . addslashes($mensaje_error) . "',
                                        confirmButtonColor: '#f39c12',
                                        footer: '<a href=\"controladores/authUsuario/logout.php\">Cerrar sesión actual</a>'
                                    });
                                </script>";
                            } else {
                                echo "<script>
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error de Login',
                                        text: 'Credenciales incorrectas. Verifique su cédula y clave.',
                                        confirmButtonColor: '#d33'
                                    });
                                </script>";
                            }
                        }
                        ?>
                    <form action="controladores/authUsuario/login.php" method="POST" data-validar-form autocomplete="off">
                        <div class="form-group">
                            <label for="cedula"><i class="fa fa-id-card"></i> Cédula</label>
                            <input type="text" 
                                   name="cedula" 
                                   id="cedula" 
                                   class="form-control" 
                                   placeholder="Ingrese su cédula" 
                                   data-validar='{"tipo":"cedula","opciones":{"requerido":true}}'
                                   data-nombre="Cédula"
                                   required autofocus>
                        </div>
                        <div class="form-group">
                            <label for="clave"><i class="fa fa-lock"></i> Contraseña</label>
                            <input type="password" 
                                   name="clave" 
                                   id="clave" 
                                   class="form-control" 
                                   placeholder="Ingrese su contraseña" 
                                   data-validar='{"tipo":"","opciones":{"requerido":true,"minLength":6}}'
                                   data-nombre="Contraseña"
                                   required>
                        </div>
                                <button type="submit" class="btn btn-success btn-block m-b-30 m-t-30">Iniciar Sesión</button>
                    </form>

                    <div class="text-center mt-3">
                        <a href="vistas/recuperar.php" class="text-info"><i class="fa fa-key"></i> ¿Olvidaste tu contraseña?</a>
                    </div>
                    <div class="text-center mt-3 text-muted" style="font-size:0.98em;">
                        Acceso exclusivo para administradores, profesores y coordinadores de las aldeas universitarias.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@2.2.4/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.4/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/js/bootstrap.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
