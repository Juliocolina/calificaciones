<?php
require_once __DIR__ . '/../../controladores/hellpers/auth.php';
verificarRol(['admin']);
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="container mt-4">
    <?php if (isset($_GET['exito'])): ?>
        <div class="alert alert-success"><i class="fa fa-check-circle"></i> <?= htmlspecialchars($_GET['exito']) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger"><i class="fa fa-times-circle"></i> <?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>
    
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white text-center">
                    <h3 class="mb-0"><i class="fa fa-user-plus"></i> Registro de Nuevo Usuario</h3>
                    <p class="mb-0">Crear cuenta de acceso al sistema</p>
                </div>
                <div class="card-body">
                 <form action="../../controladores/usuarioController/crearUsuario.php" method="POST" data-validar-form autocomplete="off">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="cedula"><i class="fa fa-id-badge"></i> Cédula *</label>
                                    <input type="text" 
                                           name="cedula" 
                                           id="cedula" 
                                           class="form-control" 
                                           placeholder="12345678" 
                                           data-validar='{"tipo":"cedula","opciones":{"requerido":true}}'
                                           data-nombre="Cédula"
                                           required>
                                    <small class="form-text text-muted">La cédula será el usuario para iniciar sesión</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="rol"><i class="fa fa-users-cog"></i> Rol *</label>
                                    <select name="rol" id="rol" class="form-control" required>
                                        <option value="">Seleccione un rol</option>
                                        <option value="admin">Administrador</option>
                                        <option value="profesor">Profesor</option>
                                        <option value="coordinador">Coordinador</option>
                                        <option value="estudiante">Estudiante</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="clave"><i class="fa fa-key"></i> Contraseña *</label>
                            <div class="input-group">
                                <input type="password" 
                                       name="clave" 
                                       id="clave" 
                                       class="form-control" 
                                       placeholder="Mínimo 16 caracteres, 1 mayúscula, 1 número, 1 especial" 
                                       minlength="16"
                                       data-validar='{"tipo":"password","opciones":{"requerido":true,"minLength":16}}'
                                       data-nombre="Contraseña"
                                       required>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-info" id="generarPassword" title="Generar contraseña segura">
                                        <i class="fa fa-magic"></i>
                                    </button>
                                </div>
                            </div>
                            <small class="form-text text-muted">Mínimo 16 caracteres con mayúscula, minúscula, número y carácter especial (@$!%*?&)</small>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i> 
                            <strong>Importante:</strong> El usuario recibirá estas credenciales para su primer acceso.
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-success btn-lg mr-2">
                                <i class="fa fa-save"></i> Registrar Usuario
                            </button>
                            <a href="verUsuario.php" class="btn btn-secondary btn-lg">
                                <i class="fa fa-arrow-left"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle mostrar/ocultar contraseña
document.getElementById('togglePassword').addEventListener('click', function() {
    const passwordField = document.getElementById('clave');
    const icon = this.querySelector('i');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordField.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});

// Generar contraseña segura
document.getElementById('generarPassword').addEventListener('click', function() {
    const passwordField = document.getElementById('clave');
    const nuevaPassword = ValidacionesUtils.generarPasswordSegura();
    
    passwordField.value = nuevaPassword;
    passwordField.type = 'text'; // Mostrar la contraseña generada
    
    // Actualizar icono del toggle
    const toggleIcon = document.querySelector('#togglePassword i');
    toggleIcon.classList.remove('fa-eye');
    toggleIcon.classList.add('fa-eye-slash');
    
    // Validar el campo
    validarCampo(passwordField, 'password', {requerido: true});
    
    // Mostrar alerta con la contraseña
    Swal.fire({
        icon: 'success',
        title: 'Contraseña generada',
        html: `<strong>Contraseña segura:</strong><br><code style="font-size: 18px; color: #007bff;">${nuevaPassword}</code><br><br><small>Cópiela y compártala de forma segura con el usuario</small>`,
        confirmButtonText: 'Entendido'
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>