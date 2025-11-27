<?php
session_start();
require_once '../../config/conexion.php';
require_once '../hellpers/auth.php';

verificarRol(['admin']);

$codigo = $_GET['codigo'] ?? null;

if (!$codigo) {
    $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'Código de período no válido'];
    header('Location: ../../vistas/periodos/gestionarPeriodos.php');
    exit;
}

try {
    $pdo = conectar();
    
    // Obtener tipo del período a activar
    $stmt = $pdo->prepare("SELECT tipo_periodo FROM periodos_academicos WHERE codigo = ?");
    $stmt->execute([$codigo]);
    $periodo = $stmt->fetch();
    
    if (!$periodo) {
        throw new Exception('Período no encontrado');
    }
    
    // Desactivar otros períodos del mismo tipo
    $stmt = $pdo->prepare("UPDATE periodos_academicos SET activo = 0 WHERE tipo_periodo = ?");
    $stmt->execute([$periodo['tipo_periodo']]);
    
    // Activar el período seleccionado
    $stmt = $pdo->prepare("UPDATE periodos_academicos SET activo = 1 WHERE codigo = ?");
    $stmt->execute([$codigo]);
    
    $_SESSION['mensaje'] = ['tipo' => 'success', 'texto' => "Período {$codigo} activado correctamente"];
    
} catch (Exception $e) {
    $_SESSION['mensaje'] = ['tipo' => 'error', 'texto' => 'Error: ' . $e->getMessage()];
}

header('Location: ../../vistas/periodos/gestionarPeriodos.php');
exit;
?>