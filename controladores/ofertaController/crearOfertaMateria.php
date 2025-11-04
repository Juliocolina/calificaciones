<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';

$conn = conectar();
if (!$conn) {
    // Si no hay conexión, es un error crítico.
    die("Error de conexión a la base de datos.");
}

// 1. Seguridad: Solo permitir el método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Si el acceso es indebido, redirigimos a la lista principal de ofertas.
    redirigir('error', 'Acceso no permitido.', 'ofertas_academicas/verOfertas.php');
    exit;
}

// 2. Validar los datos recibidos del formulario
$oferta_id = isset($_POST['oferta_id']) ? intval($_POST['oferta_id']) : 0;
$materia_id = isset($_POST['materia_id']) ? intval($_POST['materia_id']) : 0;
$duracion_oferta = isset($_POST['duracion_oferta']) ? trim($_POST['duracion_oferta']) : '';

// La URL a la que siempre vamos a redirigir
$redirect_url = "ofertas_materias/verOfertasMaterias.php?id=" . $oferta_id;

if ($oferta_id <= 0 || $materia_id <= 0 || empty($duracion_oferta)) {
    redirigir('error', 'Datos inválidos. Por favor, complete todos los campos.', $redirect_url);
    exit;
}

// 3. Preparar y ejecutar la inserción en la tabla puente
try {
    $sql = "INSERT INTO oferta_materias (oferta_academica_id, materia_id, duracion) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);    
    $exito = $stmt->execute([
        $oferta_id,
        $materia_id,
        $duracion_oferta
    ]);

    // 4. Redirigir con el mensaje de éxito o error
    if ($exito) {
        redirigir('exito', 'Materia asignada a la oferta correctamente.', $redirect_url);
    } else {
        $errorInfo = $stmt->errorInfo();
        redirigir('error', 'No se pudo asignar la materia. Error: ' . $errorInfo[2], $redirect_url);
    }

} catch (PDOException $e) {
    // Manejo de error específico para cuando la materia ya está asignada (duplicado)
    if ($e->errorInfo[1] == 1062) { // 1062 es el código de error de MySQL para 'Duplicate entry'
        redirigir('error', 'Error: Esta materia ya ha sido asignada a esta oferta.', $redirect_url);
    } else {
        redirigir('error', 'Error en la base de datos: ' . $e->getMessage(), $redirect_url);
    }
}

exit;