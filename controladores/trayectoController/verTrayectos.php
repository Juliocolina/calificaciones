<?php
require_once '../../config/conexion.php';
require_once '../../controladores/hellpers/auth.php';

// Proteger vista - Solo admin
verificarRol(['admin']);

$conn = conectar();

// Consultar todos los trayectos
$consulta = $conn->prepare("SELECT * FROM trayectos");
$consulta->execute();
$trayectos = $consulta->fetchAll(PDO::FETCH_ASSOC);
