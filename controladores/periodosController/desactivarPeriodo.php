<?php
session_start();
require_once '../../config/conexion.php';
require_once '../hellpers/auth.php';

verificarRol(['admin']);

$codigo = $_GET['codigo'] ?? null;

if (!$codigo) {
    header('Location: ../../vistas/periodos/gestionarPeriodos.php');
    exit;
}

try {
    $pdo = conectar();
    
    $stmt = $pdo->prepare("UPDATE periodos_academicos SET activo = 0 WHERE codigo = ?");
    $stmt->execute([$codigo]);
    
    $_SESSION['mensaje'] = [
        'tipo' => 'success',
        'texto' => "Período $codigo desactivado exitosamente"
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