<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';

$conn = conectar();

// Validar que se recibió el ID
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    redirigir('error', 'ID inválido.', 'profesores/verProfesores.php');
    exit;
}

$id = $_POST['id'];

// Inicialización de variables
$cedula       = trim($_POST['cedula'] ?? '');
$nombre       = trim($_POST['nombre'] ?? '');
$apellido     = trim($_POST['apellido'] ?? '');
$correo       = trim($_POST['correo'] ?? '');
$telefono     = trim($_POST['telefono'] ?? '');
$titulo       = trim($_POST['titulo'] ?? '');
$especialidad = trim($_POST['especialidad'] ?? '');
$aldea_id     = intval($_POST['aldea_id'] ?? 0);
$pnf_id       = intval($_POST['pnf_id'] ?? 0);

$telefono     = empty($telefono) ? null : $telefono;
$titulo       = empty($titulo) ? null : $titulo;
$especialidad = empty($especialidad) ? null : $especialidad;
$pnf_id       = ($pnf_id > 0) ? $pnf_id : null;

// Validación básica
if (empty($nombre) || empty($apellido) || empty($cedula) || empty($correo) || empty($aldea_id)) {
    redirigir('error', 'Los campos Nombre, Apellido, Cédula, Correo y Aldea son obligatorios.', 'profesores/editarProfesor.php?id=' . $id);
    exit;
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    redirigir('error', 'Correo electrónico inválido.', 'profesores/editarProfesor.php?id=' . $id);
    exit;
}

// Buscar usuario_id asociado al profesor
$stmt_user = $conn->prepare("SELECT usuario_id FROM profesores WHERE id = ?");
$stmt_user->execute([$id]);
$row = $stmt_user->fetch(PDO::FETCH_ASSOC);
if (!$row || empty($row['usuario_id'])) {
    redirigir('error', 'No se encontró el usuario asociado al profesor.', 'profesores/editarProfesor.php?id=' . $id);
    exit;
}
$usuario_id = intval($row['usuario_id']);

// Verificar duplicados en tabla usuarios
$stmt_dup = $conn->prepare("SELECT id FROM usuarios WHERE (cedula = ? OR correo = ?) AND id != ?");
$stmt_dup->execute([$cedula, $correo, $usuario_id]);
if ($stmt_dup->rowCount() > 0) {
    redirigir('error', 'Cédula o correo ya registrados en otro usuario.', 'profesores/editarProfesor.php?id=' . $id);
    exit;
}

// Actualizar tabla usuarios con datos personales
$sql_user = "UPDATE usuarios SET cedula = ?, nombre = ?, apellido = ?, correo = ?, telefono = ? WHERE id = ?";
$stmt_user_upd = $conn->prepare($sql_user);
$ok1 = $stmt_user_upd->execute([$cedula, $nombre, $apellido, $correo, $telefono, $usuario_id]);

// Actualizar tabla profesores con datos específicos
$sql_prof = "UPDATE profesores SET aldea_id = ?, titulo = ?, especialidad = ?, pnf_id = ? WHERE id = ?";
$stmt_prof_upd = $conn->prepare($sql_prof);
$ok2 = $stmt_prof_upd->execute([$aldea_id, $titulo, $especialidad, $pnf_id, $id]);

if ($ok1 && $ok2) {
    redirigir('exito', 'Profesor actualizado correctamente.', 'profesores/verProfesores.php');
} else {
    redirigir('error', 'Error al actualizar el profesor.', 'profesores/editarProfesor.php?id=' . $id);
}
exit;