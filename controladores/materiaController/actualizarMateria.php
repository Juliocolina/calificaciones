<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';

$conn = conectar();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('error', 'Método de solicitud inválido.', 'materias/verMaterias.php');
    exit;
}

// 1. Validar ID y obtener ID (CRÍTICO)
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    redirigir('error', 'ID de materia inválido o faltante.', 'materias/verMaterias.php');
    exit;
}

$id = intval($_POST['id']);
$redirect_url_error = 'materias/editarMateria.php?id=' . $id;

// 2. Recibir y limpiar datos (Alineado con tu BD)
// Los campos numéricos y obligatorios deben ser tratados como números.
$pnf_id      = intval($_POST['pnf_id'] ?? 0);
$nombre      = trim($_POST['nombre'] ?? '');
$codigo      = trim($_POST['codigo'] ?? '');
$creditos    = intval($_POST['creditos'] ?? 0);
$duracion    = trim($_POST['duracion'] ?? ''); // <--- ¡CAMPO OBLIGATORIO DE TU BD!
$descripcion = trim($_POST['descripcion'] ?? '');

// 3. Validar campos obligatorios y numéricos
// Aseguramos que los IDs y Créditos sean mayores que cero
if ($pnf_id <= 0 || empty($nombre) || $creditos <= 0 || empty($duracion)) {
    redirigir('error', 'Faltan campos obligatorios o son inválidos (PNF, Nombre, Créditos o Duración).', $redirect_url_error);
    exit;
}

// Validación del ENUM 'duracion'
$duraciones_validas = ['anual', 'semestral', 'trimestral', 'intensivo'];
if (!in_array($duracion, $duraciones_validas)) {
    redirigir('error', 'El valor de Duración no es válido.', $redirect_url_error);
    exit;
}

// 4. Validar Duplicados por Nombre (CRÍTICO: Nombre es UNIQUE)
// Debe verificar que el nuevo nombre no esté siendo usado por *otra* materia.
try {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM materias WHERE nombre = ? AND id != ?");
    $stmt->execute([$nombre, $id]);
    
    if ($stmt->fetchColumn() > 0) {
        redirigir('error', 'El nombre "' . htmlspecialchars($nombre) . '" ya está registrado en otra materia.', $redirect_url_error);
        exit;
    }
} catch (PDOException $e) {
    redirigir('error', 'Error al validar duplicados: ' . $e->getMessage(), $redirect_url_error);
    exit;
}

// 5. Convertir vacíos a NULL en campos opcionales
$codigo      = empty($codigo) ? NULL : $codigo;
$descripcion = empty($descripcion) ? NULL : $descripcion;

// 6. Actualizar datos de la materia
try {
    $stmt = $conn->prepare("
        UPDATE materias SET 
            pnf_id = ?, 
            nombre = ?, 
            codigo = ?, 
            creditos = ?, 
            duracion = ?,  
            descripcion = ?
        WHERE id = ?
    ");

    $exito = $stmt->execute([
        $pnf_id,
        $nombre,
        $codigo,
        $creditos,
        $duracion, 
        $descripcion,
        $id
    ]);

    // 7. Manejar el resultado de la operación
    if ($exito) {
        redirigir('exito', 'Materia actualizada correctamente.', 'materias/verMaterias.php');
    } else {
        redirigir('error', 'Error al actualizar la materia. Ningún cambio realizado.', $redirect_url_error);
    }
} catch (PDOException $e) {
    // Error de clave foránea (pnf_id no existe) u otro error de BD
    redirigir('error', 'Error inesperado en la base de datos: ' . $e->getMessage(), $redirect_url_error);
}

exit;