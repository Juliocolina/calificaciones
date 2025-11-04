<?php
if (isset($_SESSION['mensaje'])) {
    $icon = (isset($_GET['error'])) ? 'error' : 'success';
    $titulo = ($icon === 'error') ? 'Error' : 'Éxito';
    echo "<script>
        Swal.fire({
            icon: '$icon',
            title: '$titulo',
            text: '" . addslashes($_SESSION['mensaje']) . "',
            confirmButtonColor: '#3085d6'
        });
    </script>";
    unset($_SESSION['mensaje']);
}
?>