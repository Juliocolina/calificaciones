<?php
session_start();
require_once '../../config/conexion.php';
require_once '../hellpers/auth.php';

verificarRol(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../vistas/periodos/gestionarPeriodos.php');
    exit;
}

try {
    $pdo = conectar();
    
    $trayecto_id = $_POST['trayecto_id'] ?? null;
    $año = $_POST['año'] ?? null;
    
    if (!$trayecto_id || !$año) {
        throw new Exception('Todos los campos son obligatorios');
    }
    
    // Obtener información del trayecto
    $stmt = $pdo->prepare("SELECT slug FROM trayectos WHERE id = ?");
    $stmt->execute([$trayecto_id]);
    $trayecto = $stmt->fetch();
    
    if (!$trayecto) {
        throw new Exception('Trayecto no válido');
    }
    
    // Generar código automáticamente
    $codigo = $año . '-' . str_replace('TRAY-', 'T', $trayecto['slug']);
    if ($trayecto['slug'] == 'TRAY-4') {
        $codigo = $año . '-A';
    }
    
    // Verificar si ya existe
    $stmt = $pdo->prepare("SELECT id FROM periodos_academicos WHERE codigo = ?");
    $stmt->execute([$codigo]);
    if ($stmt->fetch()) {
        throw new Exception('Ya existe un período con ese código');
    }
    
    // Insertar período
    $stmt = $pdo->prepare("
        INSERT INTO periodos_academicos (codigo, trayecto_id, año, activo) 
        VALUES (?, ?, ?, 1)
    ");
    $stmt->execute([$codigo, $trayecto_id, $año]);
    
    $_SESSION['mensaje'] = [
        'tipo' => 'success',
        'texto' => "Período $codigo creado exitosamente"
    ];
    
} catch (Exception $e) {
    $_SESSION['mensaje'] = [
        'tipo' => 'danger',
        'texto' => 'Error: ' . $e->getMessage()
    ];
}

header('Location: ../../vistas/periodos/gestionarPeriodos.php');
exit;
?>