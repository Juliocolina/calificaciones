<?php
require_once '../../config/conexion.php';
$conn = conectar();

// Consultar todos los trimestres
$consulta = $conn->prepare("SELECT * FROM trimestres ORDER BY id DESC");
$consulta->execute();
$trimestres = $consulta->fetchAll(PDO::FETCH_ASSOC);

