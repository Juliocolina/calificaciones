<?php
session_start();
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
verificarRol(['estudiante', 'admin', 'coordinador']);

// Determinar ID del estudiante
if ($_SESSION['rol'] === 'estudiante') {
    $id_param = '';
} else {
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        echo "<div class='alert alert-danger'>ID de estudiante inválido.</div>";
        exit;
    }
    $id_param = '?id=' . $_GET['id'];
}

// Redireccionar directamente al PDF
header("Location: generarHistorialPDF.php" . $id_param);
exit;
?>