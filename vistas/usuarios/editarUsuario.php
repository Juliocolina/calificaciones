<?php
session_start(); 
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
verificarRol(['admin']);
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/conexion.php';

$conn = conectar();

// --- LECTURA HÍBRIDA DEL ID ---
// 1. Intentar leer de GET (cuando viene de una redirección de error del controlador)
$id_a_editar = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_a_editar = intval($_GET['id']);
// 2. Si no viene en GET, intentar leer de POST (cuando viene de la tabla de usuarios al iniciar la edición)
} elseif (isset($_POST['id']) && is_numeric($_POST['id'])) {
    $id_a_editar = intval($_POST['id']);
}

// 3. Chequeo de ID Válido
if (!$id_a_editar) {
    echo "<div class='alert alert-danger'>ID de usuario inválido.</div>";
    exit;
}

// 4. SEGURIDAD DE PERMISOS (IDOR Prevention)
$current_user_id = $_SESSION['usuario_id'] ?? null;
$current_user_rol = $_SESSION['rol'] ?? '';

// Un administrador puede editar a cualquiera; otros solo a sí mismos.
if ($current_user_rol !== 'admin' && $id_a_editar !== $current_user_id) {
    echo "<div class='alert alert-danger'>ACCESO DENEGADO. No tiene permisos para editar este usuario.</div>";
    exit;
}
// Fin de la capa de seguridad

$id = $id_a_editar; // Usamos $id para el resto del script

// Consultar los datos del usuario
$stmt = $conn->prepare("SELECT nombre, apellido, cedula, correo, rol FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    echo "<div class='alert alert-warning'>Usuario no encontrado.</div>";
    exit;
}
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Editar Usuario - Sistema de Carga de Notas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
        }
        .card {
            margin-top: 60px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(30,60,114,0.15);
        }
        .form-title {
            background: #1e3c72;
            color: #fff;
            padding: 20px 0;
            border-radius: 16px 16px 0 0;
            margin-bottom: 30px;
        }
        .btn-primary {
            background: #2a5298;
            border: none;
        }
        .btn-primary:hover {
            background: #1e3c72;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="form-title text-center">
                    <h3><i class="fa fa-user-edit"></i> Editar Usuario</h3>
                </div>                
                    <div class="card-body">
                    <form action="../../controladores/usuarioController/actualizarUsuario.php" method="POST" data-validar-form autocomplete="off">
    <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">

    <table class="table table-borderless">
        <tbody>
            <tr>
                <th><label for="nombre"><i class="fa fa-user"></i> Nombre</label></th>
                <td>
                    <input type="text" name="nombre" id="nombre" class="form-control" 
                           data-validar='{"tipo":"soloLetras","opciones":{"requerido":true,"minLength":2,"maxLength":50}}'
                           data-nombre="Nombre"
                           value="<?= htmlspecialchars($usuario['nombre'] ?? '') ?>" required>
                </td>
            </tr>
              <tr>
                <th><label for="apellido"><i class="fa fa-user"></i> Apellido</label></th>
                <td>
                    <input type="text" name="apellido" id="apellido" class="form-control" 
                           data-validar='{"tipo":"soloLetras","opciones":{"requerido":true,"minLength":2,"maxLength":50}}'
                           data-nombre="Apellido"
                           value="<?= htmlspecialchars($usuario['apellido'] ?? '') ?>" required>
                </td>
            </tr>
            <tr>
                <th><label for="cedula"><i class="fa fa-id-badge"></i> Cédula</label></th>
                <td><input type="text" name="cedula" id="cedula" class="form-control" 
                           data-validar='{"tipo":"cedula","opciones":{"requerido":true}}'
                           data-nombre="Cédula"
                           value="<?= htmlspecialchars($usuario['cedula']) ?>" required></td>
            </tr>
            <tr>
                <th><label for="correo"><i class="fa fa-envelope"></i> Correo electrónico</label></th>
                <td><input type="email" name="correo" id="correo" class="form-control" 
                           data-validar='{"tipo":"email","opciones":{"requerido":true}}'
                           data-nombre="Correo electrónico"
                           value="<?= htmlspecialchars($usuario['correo'] ?? '') ?>" required></td>
            </tr>
            <tr>
                <th><label for="rol"><i class="fa fa-users-cog"></i> Rol</label></th>
                <td>
                    <select name="rol" id="rol" class="form-control" 
                            data-validar='{"tipo":"","opciones":{"requerido":true}}'
                            data-nombre="Rol"
                            required>
                        <option value="">Seleccione un rol</option>
                        <option value="admin" <?= ($usuario['rol'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrador</option>
                        <option value="profesor" <?= ($usuario['rol'] ?? '') === 'profesor' ? 'selected' : '' ?>>Profesor</option>
                        <option value="coordinador" <?= ($usuario['rol'] ?? '') === 'coordinador' ? 'selected' : '' ?>>Coordinador</option>
                        <option value="estudiante" <?= ($usuario['rol'] ?? '') === 'estudiante' ? 'selected' : '' ?>>Estudiante</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="clave"><i class="fa fa-key"></i> Contraseña</label></th>
                <td>
                    <div class="input-group">
                        <input type="password" name="clave" id="clave" class="form-control" placeholder="Nueva contraseña (mín. 16 caracteres, opcional)" minlength="16" data-validar='{"tipo":"password","opciones":{"requerido":false,"minLength":16}}'>
                        <div class="input-group-append">
                            <span class="input-group-text">
                                <i class="fa fa-eye" id="togglePassword" style="cursor: pointer;"></i>
                            </span>
                        </div>
                    </div>
                    <small class="form-text text-muted">Mínimo 16 caracteres con mayúscula, minúscula, número y carácter especial (@$!%*?&)</small>
                </td>
            </tr>
        </tbody>
    </table>

    <div class="text-right">
        <a href="verUsuario.php" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Guardar Cambios</button>
    </div>
</form>

                    <div class="text-center mt-4">
                        <a href="../home.php" class="text-info"><i class="fa fa-arrow-left"></i> Volver al inicio</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#clave');

    togglePassword.addEventListener('click', function () {
        // Cambia el tipo del input
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        
        // Cambia el icono del ojo
        this.classList.toggle('fa-eye-slash');
    });
</script>

</body>
</html>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>