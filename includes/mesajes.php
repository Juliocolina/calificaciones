<?php
$mensaje = '';
$tipo = 'danger';

if (isset($_GET['error'])) {
    $tipo = 'danger';
    $error = $_GET['error'];
    
    switch ($error) {
        case 'campos':
            $mensaje = 'Todos los campos son obligatorios.';
            break;
        case 'duplicado':
            $mensaje = 'El registro ya existe.';
            break;
        case 'email':
            $mensaje = 'El formato del correo no es válido.';
            break;
        case 'clavecorta':
            $mensaje = 'La contraseña debe tener al menos 6 caracteres.';
            break;
        case 'bd':
            $mensaje = 'Error en la base de datos. Intenta nuevamente.';
            break;
        case 'login':
            $mensaje = 'Usuario o contraseña incorrectos.';
            break;
        case 'conexion':
            $mensaje = 'Error de conexión con la base de datos.';
            break;
        case 'permisos':
            $mensaje = 'No tienes permisos para realizar esta acción.';
            break;
        default:
            // Si hay un mensaje personalizado en la URL
            $mensaje = urldecode($error);
    }
} elseif (isset($_GET['exito'])) {
    $tipo = 'success';
    $exito = $_GET['exito'];
    
    switch ($exito) {
        case 'creado':
            $mensaje = 'Registro creado exitosamente.';
            break;
        case 'actualizado':
            $mensaje = 'Registro actualizado exitosamente.';
            break;
        case 'eliminado':
            $mensaje = 'Registro eliminado exitosamente.';
            break;
        case 'guardado':
            $mensaje = 'Datos guardados exitosamente.';
            break;
        default:
            // Si hay un mensaje personalizado en la URL
            $mensaje = urldecode($exito);
    }
} elseif (isset($_GET['info'])) {
    $tipo = 'info';
    $mensaje = urldecode($_GET['info']);
} elseif (isset($_GET['warning'])) {
    $tipo = 'warning';
    $mensaje = urldecode($_GET['warning']);
}

if (!empty($mensaje)) {
    echo "<div class='alert alert-$tipo alert-dismissible fade show text-center' role='alert'>";
    echo htmlspecialchars($mensaje);
    echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
    echo '<span aria-hidden="true">&times;</span>';
    echo '</button>';
    echo '</div>';
}
?>
