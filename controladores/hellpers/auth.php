<?php
session_start();
   /*


function base_url() {
    $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    return $protocolo . $host . '/';
}

 */


//Verifica si hay una sesión activa
function base_url() {
    $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    // Detecta la carpeta raíz del proyecto automáticamente
    $script_name = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
    $root_folder = explode('/', trim($script_name, '/'))[0];
    // Si está en la raíz del dominio, $root_folder será el archivo, no la carpeta
    $carpeta = ($root_folder !== basename($script_name)) ? '/' . $root_folder . '/' : '/';
    return $protocolo . $host . $carpeta;
}



function verificarSesion() {
    if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        // Redirige a la página de inicio de sesión si no hay sesión activa
        header("Location: " . base_url() . "index.php?error=sesion");
        exit;
    }
    
    // Verificar timeout de inactividad
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        // Sesión expirada - cerrar y redirigir
        session_unset();
        session_destroy();
        header("Location: " . base_url() . "index.php?error=" . urlencode("Tu sesión expiró por inactividad. Inicia sesión nuevamente."));
        exit;
    }
    
    // Actualizar timestamp de actividad
    $_SESSION['last_activity'] = time();
}

function bloquearMultiplesSesiones() {
    // Verificar si ya hay una sesión activa
    if (isset($_SESSION['usuario_id']) && isset($_SESSION['rol'])) {
        $mensaje = "Ya tienes una sesión activa como " . $_SESSION['rol'] . ". Cierra la sesión actual antes de iniciar otra.";
        header("Location: " . base_url() . "index.php?error=" . urlencode($mensaje));
        exit;
    }
}

function forzarCierreSesion() {
    session_unset();
    session_destroy();
    session_start(); // Reiniciar para mostrar mensaje
    $_SESSION['mensaje'] = 'Sesión cerrada para permitir nuevo login.';
    header("Location: " . base_url() . "index.php?info=sesion_cerrada");
    exit;
}

function verificarRol($roles_permitidos = []) {
    verificarSesion();
    
    if (empty($roles_permitidos)) {
        return; // Si no se especifican roles, solo verifica sesión
    }
    
    $rol_usuario = $_SESSION['rol'] ?? '';
    
    if (!in_array($rol_usuario, $roles_permitidos)) {
        // Redirige con mensaje de acceso denegado
        header("Location: " . base_url() . "vistas/home.php?error=" . urlencode('Acceso denegado. No tienes permisos para esta sección.'));
        exit;
    }
}

function esAdmin() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}

function esCoordinador() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'coordinador';
}

function esProfesor() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'profesor';
}

function esEstudiante() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'estudiante';
}


function cerrarSesion() {
    session_unset();
    session_destroy();
    $_SESSION['mensaje'] = 'Has cerrado sesión exitosamente.';
    // Redirige a la página de inicio o de login
    header("Location: " . base_url() . "index.php?exito=logout");
    exit;
}

function redirigir($tipo, $mensaje, $vista = "home.php") {
    $_SESSION['mensaje'] = $mensaje;
    $_SESSION['tipo_mensaje'] = $tipo; // Es buena práctica guardar el tipo también

    // 1. Detectar si la vista ya tiene parámetros para usar el separador correcto
    $separador = (strpos($vista, '?') === false) ? '?' : '&';

    // 2. Construir la URL final de forma segura
    // Usamos urlencode() en el mensaje para manejar espacios y caracteres especiales.
    $url_final = base_url() . "vistas/" . $vista . $separador . $tipo . "=" . urlencode($mensaje);
    
    // 3. Ejecutar la redirección
    header("Location: " . $url_final);
    exit;
}

function tiempoRestanteSesion() {
    if (!isset($_SESSION['last_activity'])) {
        return 0;
    }
    
    $tiempo_transcurrido = time() - $_SESSION['last_activity'];
    $tiempo_restante = 1800 - $tiempo_transcurrido; // 30 minutos = 1800 segundos
    return max(0, round($tiempo_restante / 60)); // en minutos
}