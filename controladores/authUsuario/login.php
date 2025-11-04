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
    
    // 2. CONSULTA FINAL: Trae todos los datos esenciales, incluyendo nombre y apellido
    $stmt = $conn->prepare("
        SELECT 
            id, cedula, clave, rol, activo, nombre, apellido
        FROM 
            usuarios 
        WHERE 
            cedula = ?
    ");
    $stmt->execute([$cedula]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 3. VERIFICACIÓN DE CLAVE Y ESTADO ACTIVO
    // Se añade el chequeo de activo antes de verificar la clave si la BD lo permite, pero
    // se hace después para evitar la enumeración de usuarios.
    if ($user && password_verify($clave, $user['clave'])) {
        
        // Verificación de Activo (Doble chequeo)
        if ($user['activo'] != 1) {
            $_SESSION['mensaje'] = "Su cuenta no está activa. Contacte al administrador.";
            header("Location: ../../index.php?error=login");
            exit;
        }

        // CREACIÓN DE SESIÓN (DOBLE FORMATO PARA COMPATIBILIDAD)
        // Formato nuevo para el sistema de filtrado
        $_SESSION['usuario'] = [
            'id' => $user['id'],
            'cedula' => $user['cedula'],
            'nombre' => $user['nombre'] ?? '',
            'apellido' => $user['apellido'] ?? '',
            'rol' => $user['rol']
        ];
        
        // Formato original para llaves foráneas existentes
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['cedula'] = $user['cedula'];
        $_SESSION['rol'] = $user['rol'];
        $_SESSION['nombre_completo'] = trim(($user['nombre'] ?? '') . ' ' . ($user['apellido'] ?? ''));
        $_SESSION['logged_in'] = true;
        $_SESSION['last_activity'] = time(); // Timestamp para control de inactividad 
        
        // REDIRECCIÓN EXITOSA
        header("Location: ../../vistas/home.php");
        exit;
        
    } else {
        // ERROR: Mensaje genérico por seguridad
        $_SESSION['mensaje'] = "Credenciales incorrectas. Verifique su cédula y clave.";
        header("Location: ../../index.php?error=login");
        exit;
    }
} else {
    // Si no es POST, redirecciona al índice
    header("Location: ../../index.php");
    exit;
}