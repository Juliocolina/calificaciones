<?php
if (isset($_SESSION['mensaje'])) {
    $icon = (isset($_GET['error'])) ? 'error' : 'success';
    $titulo = ($icon === 'error') ? 'Error' : 'Éxito';
    
    // Convertir mensaje a string si es array
    $mensaje = $_SESSION['mensaje'];
    if (is_array($mensaje)) {
        $mensaje = implode(', ', $mensaje);
    }
    
    echo "<script>
        Swal.fire({
            icon: '$icon',
            title: '$titulo',
            text: '" . addslashes($mensaje) . "',
            confirmButtonColor: '#3085d6'
        });
    </script>";
    unset($_SESSION['mensaje']);
}
?>