<?php
session_start();

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../modelos/AuthModel.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar sesión activa
    if (isset($_SESSION['usuario_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
            session_unset();
            session_destroy();
            session_start();
        } else {
            $mensaje = "Ya tienes una sesión activa. Cierra la sesión actual antes de iniciar otra.";
            header("Location: ../../index.php?error=" . urlencode($mensaje));
            exit;
        }
    }
    
    $cedula = trim($_POST['cedula'] ?? '');
    $clave = trim($_POST['clave'] ?? '');
    
    if (empty($cedula) || empty($clave)) {
        header("Location: ../../index.php?error=campos");
        exit;
    }

    $conn = conectar();
    if (!$conn) {
        header("Location: ../../index.php?error=conexion");
        exit;
    }

    $authModel = new AuthModel($conn);
    
    try {
        $user = $authModel->obtenerUsuarioPorCedula($cedula);
        
        if ($user && $authModel->estaBloqueado($user)) {
            $tiempo_restante = $authModel->getTiempoRestanteBloqueo($user['bloqueado_hasta']);
            $_SESSION['mensaje'] = "Cuenta bloqueada por múltiples intentos fallidos. Intente nuevamente en $tiempo_restante.";
            header("Location: ../../index.php?error=bloqueado");
            exit;
        }

        if ($user && $authModel->verificarClave($clave, $user['clave'])) {
            if (!$authModel->estaActivo($user)) {
                $_SESSION['mensaje'] = "Su cuenta no está activa. Contacte al administrador.";
                header("Location: ../../index.php?error=login");
                exit;
            }

            $authModel->resetearIntentosFallidos($user['id']);

            // Crear sesión
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
            if ($user) {
                $intentos = $authModel->incrementarIntentosFallidos($user['id']);
                
                if ($intentos >= 3) {
                    $authModel->bloquearUsuario($user['id'], 15);
                    $_SESSION['mensaje'] = "Cuenta bloqueada por 15 minutos debido a múltiples intentos fallidos.";
                    header("Location: ../../index.php?error=bloqueado");
                } else {
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
        
    } catch (PDOException $e) {
        $_SESSION['mensaje'] = "Error de conexión. Intente nuevamente.";
        header("Location: ../../index.php?error=conexion");
        exit;
    }
} else {
    header("Location: ../../index.php");
    exit;
}