<?php
// Incluye los archivos necesarios
require_once __DIR__ . '/../../config/conexion.php';
// Asegúrate de que este path sea correcto para la función redirigir
require_once __DIR__ . '/../../controladores/hellpers/auth.php';

// 🔌 Conectar a la base de datos
$conn = conectar();
if (!$conn) {
    redirigir('error', 'No se pudo establecer conexión con la base de datos.', 'estudiantes/verEstudiantes.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('error', 'Método no permitido.', 'estudiantes/verEstudiantes.php');
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$form_type = trim($_POST['form_type'] ?? '');

if ($id <= 0) {
    redirigir('error', 'ID de estudiante inválido.', 'estudiantes/verEstudiantes.php');
    exit;
}

try {
    $conn->beginTransaction();

    if ($form_type === 'personal') {
        $cedula = trim($_POST['cedula'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $fecha_nacimiento = trim($_POST['fecha_nacimiento'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $parroquia = trim($_POST['parroquia'] ?? '');
        $nacionalidad = trim($_POST['nacionalidad'] ?? '');
        $genero = trim($_POST['genero'] ?? '');
        $religion = trim($_POST['religion'] ?? '');
        $etnia = trim($_POST['etnia'] ?? '');
        $discapacidad = trim($_POST['discapacidad'] ?? '');
        $nivel_estudio = trim($_POST['nivel_estudio'] ?? ''); 
        $institucion_procedencia = trim($_POST['institucion_procedencia'] ?? '');
    $aldea_id = isset($_POST['aldea_id']) ? intval($_POST['aldea_id']) : 0;
        
        if (empty($nombre) || empty($apellido) || empty($correo) || $aldea_id <= 0) {
            throw new Exception("Nombre, Apellido, Correo y Aldea son obligatorios para actualizar el Perfil.");
        }

        // Obtener usuario_id relacionado al estudiante
        $stmt_user = $conn->prepare("SELECT usuario_id FROM estudiantes WHERE id = ?");
        $stmt_user->execute([$id]);
        $row = $stmt_user->fetch(PDO::FETCH_ASSOC);
        if (!$row || empty($row['usuario_id'])) {
            throw new Exception('No se encontró el usuario relacionado al estudiante.');
        }
        $usuario_id = intval($row['usuario_id']);

        // Verificación de Duplicados (Cédula y Correo) en tabla usuarios
        $stmt_duplicado = $conn->prepare("SELECT id FROM usuarios WHERE (cedula = ? OR correo = ?) AND id != ?");
        $stmt_duplicado->execute([$cedula, $correo, $usuario_id]);
        if ($stmt_duplicado->rowCount() > 0) {
            throw new Exception('La cédula o correo ya existe para otro usuario.');
        }

        // 1) Actualizar tabla usuarios con los datos personales
        $sql_user = "UPDATE usuarios SET cedula = ?, nombre = ?, apellido = ?, correo = ?, telefono = ? WHERE id = ?";
        $stmt_user_upd = $conn->prepare($sql_user);
        $ok1 = $stmt_user_upd->execute([$cedula, $nombre, $apellido, $correo, $telefono, $usuario_id]);

        // 2) Actualizar tabla estudiantes con los datos específicos
        $sql_est = "UPDATE estudiantes SET 
            fecha_nacimiento = ?, parroquia = ?, nacionalidad = ?, genero = ?, religion = ?, etnia = ?, 
            discapacidad = ?, nivel_estudio = ?, institucion_procedencia = ?, aldea_id = ? 
            WHERE id = ?";
        $stmt_est = $conn->prepare($sql_est);
        $ok2 = $stmt_est->execute([
            $fecha_nacimiento, $parroquia, $nacionalidad, $genero, $religion, $etnia,
            $discapacidad, $nivel_estudio, $institucion_procedencia, $aldea_id, $id
        ]);

        $exito = ($ok1 && $ok2);
        
        $mensaje_exito = 'Datos personales del estudiante actualizados exitosamente.';
        $mensaje_error = 'Error al actualizar los datos personales: ';

    } elseif ($form_type === 'academica') {
        $pnf_id = isset($_POST['pnf_id']) ? intval($_POST['pnf_id']) : 0;
        $trayecto_id = isset($_POST['trayecto_id']) ? intval($_POST['trayecto_id']) : 0;
        $trimestre_id = isset($_POST['trimestre_id']) ? intval($_POST['trimestre_id']) : 0;
        $codigo_estudiante = trim($_POST['codigo_estudiante'] ?? '');
        $estado_academico = trim($_POST['estado_academico'] ?? '');
        $fecha_ingreso = trim($_POST['fecha_ingreso'] ?? '');
        $fecha_graduacion = trim($_POST['fecha_graduacion'] ?? '');
        $observaciones = trim($_POST['observaciones'] ?? '');

        if ($pnf_id <= 0 || $trayecto_id <= 0 || $trimestre_id <= 0) {
            throw new Exception("PNF, Trayecto y Trimestre son obligatorios para la Asignación Académica.");
        }
        
        if (!empty($codigo_estudiante)) {
            $stmt_duplicado = $conn->prepare("SELECT id FROM estudiantes WHERE codigo_estudiante = ? AND id != ?");
            $stmt_duplicado->execute([$codigo_estudiante, $id]);
            if ($stmt_duplicado->rowCount() > 0) {
                throw new Exception('El código de estudiante ya existe para otro registro.');
            }
        }
        
        $observaciones = empty($observaciones) ? null : $observaciones;
        $fecha_ingreso = empty($fecha_ingreso) ? null : $fecha_ingreso;
        $fecha_graduacion = empty($fecha_graduacion) ? null : $fecha_graduacion;

        $sql = "UPDATE estudiantes SET 
            pnf_id = ?, trayecto_id = ?, trimestre_id = ?, codigo_estudiante = ?, 
            estado_academico = ?, observaciones = ?, fecha_ingreso = ?, fecha_graduacion = ? 
            WHERE id = ?";

        $stmt = $conn->prepare($sql);

        $exito = $stmt->execute([
            $pnf_id, $trayecto_id, $trimestre_id, $codigo_estudiante,
            $estado_academico, $observaciones,
            $fecha_ingreso, $fecha_graduacion,
            $id 
        ]);
        
        $mensaje_exito = 'Datos académicos y administrativos actualizados exitosamente.';
        $mensaje_error = 'Error al actualizar los datos académicos: ';

    } else {
        throw new Exception('Tipo de formulario no reconocido. Asegúrate de que el campo form_type esté presente.');
    }
    
    if (!$exito) {
         $errorInfo = $stmt->errorInfo();
         throw new Exception($mensaje_error . $errorInfo[2]);
    }

    $conn->commit();
    redirigir('exito', $mensaje_exito, 'estudiantes/verEstudiantes.php?id=' . $id);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    redirigir('error', $e->getMessage(), 'estudiantes/verEstudiantes.php');
}
exit;
