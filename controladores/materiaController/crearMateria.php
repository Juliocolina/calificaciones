<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php'; 

$conn = conectar();
$redirect_view = 'materias/crearMateria.php'; // Vista de retorno por defecto

if (!$conn) {
    redirigir('error', 'No se pudo conectar con la base de datos.', $redirect_view);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('error', 'Método de solicitud inválido.', $redirect_view);
    exit;
}

// 1. Recibir y limpiar datos
$pnf_id         = intval($_POST['pnf_id'] ?? 0);
$nombre         = trim($_POST['nombre'] ?? '');
$codigo         = trim($_POST['codigo'] ?? '');
$creditos       = intval($_POST['creditos'] ?? 0);
$duracion       = trim($_POST['duracion'] ?? ''); // Campo original

$descripcion    = trim($_POST['descripcion'] ?? '');

// 2. Validación de campos obligatorios
if ($pnf_id <= 0 || empty($nombre) || $creditos <= 0 || empty($duracion)) {
    redirigir('error', 'Faltan campos obligatorios o son inválidos (PNF, Nombre, Créditos o Duración).', $redirect_view);
    exit;
}

// Validación de duracion
$tipos_duracion_validos = ['trimestral', 'bimestral', 'anual'];
if (!in_array($duracion, $tipos_duracion_validos)) {
    redirigir('error', 'La duración no es válida. Opciones: trimestral, bimestral, anual.', $redirect_view);
    exit;
}

// 3. Verificar duplicado por Nombre y Código
try {
    // Verificar nombre duplicado
    $verificar = $conn->prepare("SELECT id FROM materias WHERE nombre = ?");
    $verificar->execute([$nombre]);
    
    if ($verificar->fetch()) {
        redirigir('error', 'Ya existe una materia registrada con ese nombre.', $redirect_view);
        exit;
    }
    
    // Verificar código duplicado si no está vacío
    if (!empty($codigo)) {
        $verificar_codigo = $conn->prepare("SELECT id FROM materias WHERE codigo = ?");
        $verificar_codigo->execute([$codigo]);
        
        if ($verificar_codigo->fetch()) {
            redirigir('error', 'Ya existe una materia registrada con ese código.', $redirect_view);
            exit;
        }
    }
} catch (PDOException $e) {
    redirigir('error', 'Error al validar duplicados en la base de datos.', $redirect_view);
    exit;
}

// 4. Inserción de la nueva materia
try {
    $insertar = $conn->prepare("
        INSERT INTO materias (pnf_id, nombre, codigo, creditos, duracion, descripcion) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $exito = $insertar->execute([
        $pnf_id, 
        $nombre, 
        empty($codigo) ? NULL : $codigo,
        $creditos, 
        $duracion, 
        empty($descripcion) ? NULL : $descripcion,
    ]);
    
    if ($exito) {
        // Redirigir a la lista de materias (vista de éxito)
        redirigir('exito', 'Materia creada exitosamente: ' . $nombre, 'materias/materiasPorPnf.php');
    } else {
        // Error de ejecución sin excepción (raro en PDO, pero posible)
        redirigir('error', 'No se pudo crear la materia.', $redirect_view);
    }
} catch (PDOException $e) {
    // Error si hay un fallo de clave foránea u otro error de BD.
    redirigir('error', 'Error inesperado en la base de datos: ' . $e->getMessage(), $redirect_view);
}

exit;
?>