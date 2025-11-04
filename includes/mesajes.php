<?php
$mensaje = ''; // Variable para guardar el texto del mensaje
$tipo = 'danger'; // Tipo de alerta (por defecto es error)

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'campos':
            $mensaje = 'Todos los campos son obligatorios.';
            break;
        case 'duplicado':
            $mensaje = 'El correo ya está registrado.';
            break;
        case 'email':
            $mensaje = 'El formato del correo no es válido.';
            break;
        case 'clavecorta':
            $mensaje = 'La contraseña debe tener al menos 6 caracteres.';
            break;
        case 'bd':
            $mensaje = 'Error al registrar el usuario. Intenta nuevamente.';
            break;
        case 'login':
            $mensaje = 'Usuario o contraseña incorrectos.';
            break;
            
        default:
            $mensaje = 'Ha ocurrido un error desconocido.';
    }
} elseif (isset($_GET['exito'])) {
    $mensaje = 'Usuario registrado exitosamente.';
    $tipo = 'success';
}

if (!empty($mensaje)) {
    echo "<div class='alert alert-$tipo text-center' role='alert'>" . htmlspecialchars($mensaje) . "</div>";
}
?>
