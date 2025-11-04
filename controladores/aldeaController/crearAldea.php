<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
$conn = conectar();


if (!$conn) {
    // Redirige al lugar correcto si la conexión falla
    redirigir('error', 'No se pudo establecer conexión con la base de datos.', 'aldeas/crearAldea.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Redirige al lugar correcto si el método es incorrecto
    redirigir('error', 'Método no permitido.', 'aldeas/crearAldea.php');
    exit;
}

// 📋 Recibir y limpiar datos
$nombre = trim($_POST['nombre_aldea'] ?? '');
$codigo = trim($_POST['codigo_aldea'] ?? '');
$direccion = trim($_POST['direccion_aldea'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? ''); // Limpiar la descripción


// ✅ Validar obligatorios
if (empty($nombre) || empty($codigo) || empty($direccion)) {
    redirigir('error', 'Por favor, completa todos los campos (Nombre, Código y Dirección).', 'aldeas/crearAldea.php');
    exit;
}

// --- Validaciones de longitud corregidas ---
if (strlen($nombre) > 255) {
    redirigir('error', 'El nombre de la aldea supera los 255 caracteres.', 'aldeas/crearAldea.php');
    exit;
}
if (strlen($codigo) > 100) {
    redirigir('error', 'El código es demasiado largo.', 'aldeas/crearAldea.php');
    exit;
}
if (strlen($direccion) > 255) {
    redirigir('error', 'La dirección excede los 255 caracteres.', 'aldeas/crearAldea.php');
    exit;
}
// ------------------------------------------

try {
    // Verificar duplicados por nombre o código
    $verificar = $conn->prepare("SELECT id FROM aldeas WHERE nombre = ? OR codigo = ?");
    $verificar->execute([$nombre, $codigo]);

    if ($verificar->fetch()) {
        redirigir('error', 'Aldea ya registrada con ese nombre o código.', 'aldeas/crearAldea.php');
        exit;
    }

    // Insertar nueva aldea
    $insertar = $conn->prepare("INSERT INTO aldeas (nombre, codigo, direccion, descripcion) VALUES (?, ?, ?, ?)");
    $insertar->execute([$nombre, $codigo, $direccion, $descripcion]);

    redirigir('exito', 'Registro Exitoso..!', 'aldeas/crearAldea.php'); 

} catch (PDOException $e) {
    // Capturar cualquier error de base de datos
    redirigir('error', 'Error al registrar la Aldea: ' . $e->getMessage(), 'aldeas/crearAldea.php'); 
}

exit;