<?php
require_once '../../config/conexion.php';
$conn = conectar();

// Consultar todas las aldeas
$consulta = $conn->prepare("SELECT * FROM aldeas");
$consulta->execute();
$aldeas = $consulta->fetchAll(PDO::FETCH_ASSOC);

