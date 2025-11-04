<?php
require_once '../../config/conexion.php';
$conn = conectar();

// Consultar todos los trayectos
$consulta = $conn->prepare("SELECT * FROM trayectos");
$consulta->execute();
$trayectos = $consulta->fetchAll(PDO::FETCH_ASSOC);
