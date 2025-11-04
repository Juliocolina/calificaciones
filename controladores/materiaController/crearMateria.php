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

// 1. Recibir y limpiar datos (Ajustado a tu estructura SQL)
// CRÍTICO: duracion es NOT NULL en tu BD, debe ser tratada como obligatoria.
$pnf_id      = intval($_POST['pnf_id'] ?? 0); // Convertir a int para validar
$nombre      = trim($_POST['nombre'] ?? '');
$codigo      = trim($_POST['codigo'] ?? ''); // NULLABLE
$creditos    = intval($_POST['creditos'] ?? 0); // Convertir a int para validar
$duracion    = trim($_POST['duracion'] ?? ''); // OBLIGATORIO
$descripcion = trim($_POST['descripcion'] ?? ''); // NULLABLE

// 2. Validación de campos obligatorios y numéricos
// Aseguramos que los IDs y Créditos sean mayores que cero
if ($pnf_id <= 0 || empty($nombre) || $creditos <= 0 || empty($duracion)) {
    redirigir('error', 'Faltan campos obligatorios o son inválidos (PNF, Nombre, Créditos o Duración).', $redirect_view);
    exit;
}

// Validación de duracion contra el ENUM de la BD
$duraciones_validas = ['anual', 'semestral', 'trimestral', 'intensivo'];
if (!in_array($duracion, $duraciones_validas)) {
    redirigir('error', 'El valor de Duración no es válido. Opciones: anual, semestral, trimestral, intensivo.', $redirect_view);
    exit;
}

// 3. Verificar duplicado por Nombre (CRÍTICO: El índice UNIQUE está en `nombre`)
try {
    $verificar = $conn->prepare("SELECT id FROM materias WHERE nombre = ?");
    $verificar->execute([$nombre]);
    
    // NOTA: Tu base de datos no tiene UNIQUE KEY en `codigo`, solo en `nombre`.
    // Por seguridad, si el código no es NULL, también podemos verificarlo, pero
    // priorizamos la validación de la regla UNIQUE de la BD.
    
    if ($verificar->fetch()) {
        redirigir('error', 'Ya existe una materia registrada con ese nombre.', $redirect_view);
        exit;
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
        empty($codigo) ? NULL : $codigo, // Insertar NULL si está vacío
        $creditos, 
        $duracion, 
        empty($descripcion) ? NULL : $descripcion // Insertar NULL si está vacío
    ]);
    
    if ($exito) {
        // Redirigir a la lista de materias (vista de éxito)
        redirigir('exito', 'Materia creada exitosamente: ' . $nombre, 'materias/verMaterias.php');
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