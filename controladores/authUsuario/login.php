<?php
// CRÍTICO: Iniciar sesión al inicio
session_start();

// CRÍTICO: Asegúrate de que esta ruta a la conexión sea correcta
require_once __DIR__ . '/../../config/conexion.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // VALIDACIÓN DE SESIÓN MÚTIPLE (ANTES DE PROCESAR)
    // Verificar si hay sesión realmente activa
    if (isset($_SESSION['usuario_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        // Verificar timeout de inactividad (30 minutos = 1800 segundos)
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
            // Sesión expirada por inactividad - limpiar y permitir login
            session_unset();
            session_destroy();
            session_start(); // Reiniciar para nueva sesión
        } else {
            // Sesión activa y válida - bloquear
            $mensaje = "Ya tienes una sesión activa. Cierra la sesión actual antes de iniciar otra.";
            header("Location: ../../index.php?error=" . urlencode($mensaje));
            exit;
        }
    }
    
    $cedula = trim($_POST['cedula'] ?? '');
    $clave = trim($_POST['clave'] ?? '');
    
    // 1. VALIDACIÓN BÁSICA
    if (empty($cedula) || empty($clave)) {
        header("Location: ../../index.php?error=campos");
        exit;
    }

    $conn = conectar();
    
    // 2. CONSULTA CON CAMPOS DE BLOQUEO
    $stmt = $conn->prepare("
        SELECT 
            id, cedula, clave, rol, activo, nombre, apellido, intentos_fallidos, bloqueado_hasta
        FROM 
            usuarios 
        WHERE 
            cedula = ?
    ");
    $stmt->execute([$cedula]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 3. VERIFICAR SI ESTÁ BLOQUEADO
    if ($user && $user['bloqueado_hasta'] && new DateTime() < new DateTime($user['bloqueado_hasta'])) {
        $tiempo_restante = (new DateTime($user['bloqueado_hasta']))->diff(new DateTime())->format('%i minutos %s segundos');
        $_SESSION['mensaje'] = "Cuenta bloqueada por múltiples intentos fallidos. Intente nuevamente en $tiempo_restante.";
        header("Location: ../../index.php?error=bloqueado");
        exit;
    }

    // 4. VERIFICACIÓN DE CLAVE Y ESTADO ACTIVO
    if ($user && password_verify($clave, $user['clave'])) {
        
        // Verificación de Activo
        if ($user['activo'] != 1) {
            $_SESSION['mensaje'] = "Su cuenta no está activa. Contacte al administrador.";
            header("Location: ../../index.php?error=login");
            exit;
        }

        // RESETEAR INTENTOS FALLIDOS AL LOGIN EXITOSO
        $stmt_reset = $conn->prepare("UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = ?");
        $stmt_reset->execute([$user['id']]);

        // CREACIÓN DE SESIÓN
        $_SESSION['usuario'] = [
            'id' => $user['id'],
            'cedula' => $user['cedula'],
            'nombre' => $user['nombre'] ?? '',
            'apellido' => $user['apellido'] ?? '',
            'rol' => $user['rol']
        ];
        
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['cedula'] = $user['cedula'];
        $_SESSION['rol'] = $user['rol'];
        $_SESSION['nombre'] = $user['nombre'] ?? '';
        $_SESSION['apellido'] = $user['apellido'] ?? '';
        $_SESSION['nombre_completo'] = trim(($user['nombre'] ?? '') . ' ' . ($user['apellido'] ?? ''));
        $_SESSION['logged_in'] = true;
        $_SESSION['last_activity'] = time();
        $_SESSION['tiempo_expiracion'] = time() + (45 * 60);
        
        header("Location: ../../vistas/home.php");
        exit;
        
    } else {
        // INCREMENTAR INTENTOS FALLIDOS
        if ($user) {
            $intentos = $user['intentos_fallidos'] + 1;
            
            if ($intentos >= 3) {
                // BLOQUEAR POR 15 MINUTOS
                $bloqueo_hasta = date('Y-m-d H:i:s', time() + (15 * 60));
                $stmt_bloqueo = $conn->prepare("UPDATE usuarios SET intentos_fallidos = ?, bloqueado_hasta = ? WHERE id = ?");
                $stmt_bloqueo->execute([$intentos, $bloqueo_hasta, $user['id']]);
                
                $_SESSION['mensaje'] = "Cuenta bloqueada por 15 minutos debido a múltiples intentos fallidos.";
                header("Location: ../../index.php?error=bloqueado");
            } else {
                // INCREMENTAR CONTADOR
                $stmt_incrementar = $conn->prepare("UPDATE usuarios SET intentos_fallidos = ? WHERE id = ?");
                $stmt_incrementar->execute([$intentos, $user['id']]);
                
                $intentos_restantes = 3 - $intentos;
                $_SESSION['mensaje'] = "Credenciales incorrectas. Le quedan $intentos_restantes intentos antes del bloqueo.";
                header("Location: ../../index.php?error=login");
            }
        } else {
            $_SESSION['mensaje'] = "Credenciales incorrectas. Verifique su cédula y clave.";
            header("Location: ../../index.php?error=login");
        }
        exit;
    }
} else {
    // Si no es POST, redirecciona al índice
    header("Location: ../../index.php");
    exit;
}