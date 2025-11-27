<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';

// Proteger vista - Solo admin
verificarRol(['admin']);

$conn = conectar();

// Verificar si la conexión es exitosa
if (!$conn) {
    // Manejar el error de conexión si es necesario
    exit("Error de conexión a la base de datos.");
}

// Obtener la lista de coordinadores, uniendo con 'usuarios' para los datos personales
$consulta = $conn->prepare("
    SELECT 
        c.id, 
        u.cedula, 
        u.nombre,
        u.apellido, 
        u.telefono,
        c.fecha_inicio_gestion,
        c.fecha_fin_gestion,
        c.descripcion,
        c.usuario_id,
        c.aldea_id,
        a.nombre AS nombre_aldea
    FROM coordinadores c
    INNER JOIN aldeas a ON c.aldea_id = a.id
    INNER JOIN usuarios u ON c.usuario_id = u.id
");

$consulta->execute();

$coordinadores = $consulta->fetchAll(PDO::FETCH_ASSOC);