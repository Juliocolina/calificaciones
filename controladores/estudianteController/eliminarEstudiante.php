<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
$conn = conectar();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
	if ($id <= 0) {
		redirigir('error', 'ID inválido.', 'estudiantes/verEstudiantes.php');
		exit();
	}

	try {
		$stmt = $conn->prepare('DELETE FROM estudiantes WHERE id = ?');
		if ($stmt->execute([$id])) {
			redirigir('exito', 'Estudiante eliminado exitosamente.', 'estudiantes/verEstudiantes.php');
		} else {
			redirigir('error', 'No se pudo eliminar el estudiante.', 'estudiantes/verEstudiantes.php');
		}
	} catch (Exception $e) {
		redirigir('error', 'Error al eliminar el estudiante: ' . $e->getMessage(), 'estudiantes/verEstudiantes.php');
	}
	exit();
} else {
	redirigir('error', 'Método no permitido.', 'estudiantes/verEstudiantes.php');
	exit();
}
?>
