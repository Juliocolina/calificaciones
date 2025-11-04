<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php'; 

$conn = conectar();
if (!$conn) {
    redirigir('error', 'Error de conexión a la base de datos.', 'ofertas_academicas/verOfertas.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('error', 'Acceso no permitido.', 'ofertas_academicas/verOfertas.php');
    exit;
}

// 1. Validar el ID de la oferta
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    redirigir('error', 'ID de oferta inválido.', 'ofertas_academicas/verOfertas.php');
    exit;
}
$id_oferta = intval($_POST['id']);

// 2. Validar el NUEVO ESTATUS recibido del formulario
if (!isset($_POST['nuevo_estatus']) || empty($_POST['nuevo_estatus'])) {
    redirigir('error', 'No se especificó el nuevo estatus.', 'ofertas_academicas/verOfertas.php');
    exit;
}

// Lista de estatus permitidos para seguridad
$estatus_permitidos = ['Planificado', 'Abierto', 'Inactivo', 'Cerrado'];
$nuevo_estatus = $_POST['nuevo_estatus'];

if (!in_array($nuevo_estatus, $estatus_permitidos)) {
    redirigir('error', 'El estatus proporcionado no es válido.', 'ofertas_academicas/verOfertas.php');
    exit;
}

try {
    // 3. Verificar que la oferta realmente existe
    $stmt_check = $conn->prepare("SELECT id FROM oferta_academica WHERE id = ?");
    $stmt_check->execute([$id_oferta]);
    if ($stmt_check->fetch(PDO::FETCH_ASSOC) === false) {
        redirigir('error', 'La oferta que intentas actualizar no existe.', 'ofertas_academicas/verOfertas.php');
        exit;
    }
    
    // 4. Preparar y ejecutar la actualización con los datos del formulario
    $sql = "UPDATE oferta_academica SET estatus = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    $exito = $stmt->execute([
        $nuevo_estatus,
        $id_oferta
    ]);

    // 5. Redirigir con un mensaje genérico de éxito o error
    if ($exito) {
        redirigir('exito', 'El estatus de la oferta ha sido actualizado correctamente.', 'ofertas_academicas/verOfertas.php');
    } else {
        $errorInfo = $stmt->errorInfo();
        redirigir('error', 'No se pudo actualizar el estatus. Error: ' . $errorInfo[2], 'ofertas_academicas/verOfertas.php');
    }

} catch (PDOException $e) {
    redirigir('error', 'Error en la base de datos: ' . $e->getMessage(), 'ofertas_academicas/verOfertas.php');
}

exit;