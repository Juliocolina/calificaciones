<?php
session_start();
require_once '../config/conexion.php';

$conn = conectar();
$paso = $_GET['paso'] ?? 1;
$error = '';
$success = '';

if ($_POST) {
    if ($paso == 1) {
        // Verificar cédula
        $cedula = trim($_POST['cedula']);
        $stmt = $conn->prepare("SELECT id, nombre, apellido FROM usuarios WHERE cedula = ? AND activo = 1");
        $stmt->execute([$cedula]);
        $usuario = $stmt->fetch();
        
        if ($usuario) {
            // Verificar si tiene preguntas configuradas
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM respuestas_seguridad WHERE usuario_id = ?");
            $stmt->execute([$usuario['id']]);
            $tiene_preguntas = $stmt->fetch()['total'] > 0;
            
            if ($tiene_preguntas) {
                $_SESSION['recuperar_usuario_id'] = $usuario['id'];
                header("Location: recuperar.php?paso=2");
                exit;
            } else {
                $error = "No tienes preguntas de seguridad configuradas. Contacta al administrador.";
            }
        } else {
            $error = "Cédula no encontrada o usuario inactivo.";
        }
        
    } elseif ($paso == 2) {
        // Verificar respuestas
        if (!isset($_SESSION['recuperar_usuario_id'])) {
            header("Location: recuperar.php");
            exit;
        }
        
        $usuario_id = $_SESSION['recuperar_usuario_id'];
        $respuestas_correctas = 0;
        $total_preguntas = 0;
        
        foreach ($_POST['respuestas'] as $pregunta_id => $respuesta) {
            $stmt = $conn->prepare("SELECT respuesta FROM respuestas_seguridad WHERE usuario_id = ? AND pregunta_id = ?");
            $stmt->execute([$usuario_id, $pregunta_id]);
            $respuesta_correcta = $stmt->fetch();
            
            if ($respuesta_correcta && strtolower(trim($respuesta)) === $respuesta_correcta['respuesta']) {
                $respuestas_correctas++;
            }
            $total_preguntas++;
        }
        
        if ($respuestas_correctas === $total_preguntas && $total_preguntas > 0) { // TODAS las respuestas deben ser correctas
            $_SESSION['puede_cambiar_clave'] = true;
            header("Location: recuperar.php?paso=3");
            exit;
        } else {
            $error = "Una o más respuestas son incorrectas. Intenta nuevamente.";
        }
        
    } elseif ($paso == 3) {
        // Cambiar contraseña
        if (!isset($_SESSION['puede_cambiar_clave'])) {
            header("Location: recuperar.php");
            exit;
        }
        
        $nueva_clave = $_POST['nueva_clave'];
        $confirmar_clave = $_POST['confirmar_clave'];
        
        if ($nueva_clave !== $confirmar_clave) {
            $error = "Las contraseñas no coinciden.";
        } elseif (strlen($nueva_clave) < 16) {
            $error = "La contraseña debe tener al menos 16 caracteres.";
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/', $nueva_clave)) {
            $error = "La contraseña debe contener al menos: 1 mayúscula, 1 minúscula, 1 número y 1 carácter especial (@$!%*?&).";
        } else {
            $usuario_id = $_SESSION['recuperar_usuario_id'];
            $clave_hash = password_hash($nueva_clave, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("UPDATE usuarios SET clave = ? WHERE id = ?");
            $stmt->execute([$clave_hash, $usuario_id]);
            
            // Limpiar sesión
            unset($_SESSION['recuperar_usuario_id']);
            unset($_SESSION['puede_cambiar_clave']);
            
            $success = "Contraseña cambiada exitosamente. Ya puedes iniciar sesión.";
        }
    }
}

// Obtener preguntas para el paso 2
if ($paso == 2 && isset($_SESSION['recuperar_usuario_id'])) {
    $stmt = $conn->prepare("
        SELECT cp.id, cp.texto 
        FROM catalogo_preguntas cp
        JOIN respuestas_seguridad rs ON cp.id = rs.pregunta_id
        WHERE rs.usuario_id = ? AND cp.activo = 1
        ORDER BY cp.id
    ");
    $stmt->execute([$_SESSION['recuperar_usuario_id']]);
    $preguntas = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - SICAN</title>
    <link rel="shortcut icon" href="../images/logo_misionsucre.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-gradient-primary" style="min-height: 100vh;">
    <div class="container">
        <!-- Header con logo -->
        <div class="row justify-content-center pt-4 pb-2">
            <div class="col-auto text-center">
                <img src="../images/logo_misionsucre.png" alt="Logo Misión Sucre" 
                     class="rounded-circle shadow-lg animate__animated animate__fadeInDown" 
                     style="background:#fff;padding:12px;width:80px;height:80px;">
                <div class="text-white mt-2">
                    <h5 class="mb-0">SICAN</h5>
                    <small class="opacity-75">Sistema Integral de Calificaciones</small>
                </div>
            </div>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7">
                <div class="card shadow-lg border-0 animate__animated animate__fadeInUp">
                    <div class="card-header bg-white border-0 text-center py-4">
                        <div class="d-flex align-items-center justify-content-center mb-3">
                            <div class="progress-container d-flex align-items-center">
                                <?php for($i = 1; $i <= 3; $i++): ?>
                                    <div class="step-circle <?= $i <= $paso ? 'active' : '' ?> <?= $i < $paso ? 'completed' : '' ?>">
                                        <?php if($i < $paso): ?>
                                            <i class="fa fa-check"></i>
                                        <?php else: ?>
                                            <?= $i ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php if($i < 3): ?>
                                        <div class="step-line <?= $i < $paso ? 'completed' : '' ?>"></div>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <h4 class="text-primary mb-2">
                            <i class="fa fa-key mr-2"></i> Recuperar Contraseña
                        </h4>
                        
                        <?php 
                        $pasos_texto = [
                            1 => 'Verificación de Identidad',
                            2 => 'Preguntas de Seguridad', 
                            3 => 'Nueva Contraseña'
                        ];
                        ?>
                        <p class="text-muted mb-0">
                            <strong>Paso <?= $paso ?>:</strong> <?= $pasos_texto[$paso] ?>
                        </p>
                    </div>
                    
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger border-left-danger" role="alert">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-exclamation-triangle fa-2x text-danger mr-3"></i>
                                    <div>
                                        <h6 class="alert-heading mb-1">Error</h6>
                                        <p class="mb-0"><?= htmlspecialchars($error) ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success border-left-success text-center" role="alert">
                                <div class="success-animation mb-3">
                                    <i class="fa fa-check-circle fa-4x text-success animate__animated animate__bounceIn"></i>
                                </div>
                                <h5 class="alert-heading text-success mb-2">¡Contraseña cambiada exitosamente!</h5>
                                <p class="mb-3"><?= htmlspecialchars($success) ?></p>
                                <a href="../index.php" class="btn btn-success btn-lg px-4">
                                    <i class="fa fa-sign-in mr-2"></i> Iniciar Sesión
                                </a>
                            </div>
                        
                        <?php elseif ($paso == 1): ?>
                            <div class="step-content">
                                <div class="text-center mb-4">
                                    <div class="icon-circle bg-primary text-white mx-auto mb-3">
                                        <i class="fa fa-id-card fa-2x"></i>
                                    </div>
                                    <h5 class="text-primary">Verificación de Identidad</h5>
                                    <p class="text-muted">Ingresa tu cédula para verificar tu identidad</p>
                                </div>
                                
                                <form method="POST" class="needs-validation" novalidate>
                                    <div class="form-group">
                                        <label for="cedula" class="font-weight-bold">
                                            <i class="fa fa-id-badge mr-2"></i>Número de Cédula
                                        </label>
                                        <div class="input-group input-group-lg">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text bg-light">
                                                    <i class="fa fa-user text-muted"></i>
                                                </span>
                                            </div>
                                            <input type="text" class="form-control" id="cedula" name="cedula" 
                                                   placeholder="Ejemplo: 12345678" 
                                                   pattern="[0-9]{7,8}"
                                                   title="Ingresa tu cédula sin puntos ni espacios"
                                                   required>
                                            <div class="invalid-feedback">
                                                Por favor, ingresa una cédula válida.
                                            </div>
                                        </div>
                                        <small class="text-muted">
                                            <i class="fa fa-info-circle"></i> 
                                            Ingresa tu cédula sin puntos ni espacios
                                        </small>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary btn-lg btn-block mt-4">
                                        <i class="fa fa-arrow-right mr-2"></i> Verificar Identidad
                                    </button>
                                </form>
                            </div>
                        
                        <?php elseif ($paso == 2): ?>
                            <div class="step-content">
                                <div class="text-center mb-4">
                                    <div class="icon-circle bg-warning text-white mx-auto mb-3">
                                        <i class="fa fa-question-circle fa-2x"></i>
                                    </div>
                                    <h5 class="text-primary">Preguntas de Seguridad</h5>
                                    <p class="text-muted">Responde correctamente tus preguntas de seguridad</p>
                                </div>
                                
                                <div class="alert alert-warning border-left-warning mb-4">
                                    <i class="fa fa-exclamation-triangle mr-2"></i>
                                    <strong>Importante:</strong> Debes responder correctamente TODAS las preguntas para continuar
                                </div>
                                
                                <form method="POST" class="needs-validation" novalidate>
                                    <?php foreach ($preguntas as $index => $pregunta): ?>
                                        <div class="form-group mb-4">
                                            <div class="question-card border rounded p-3">
                                                <div class="d-flex align-items-center mb-2">
                                                    <div class="question-number bg-primary text-white rounded-circle mr-3">
                                                        <?= $index + 1 ?>
                                                    </div>
                                                    <label for="respuesta_<?= $pregunta['id'] ?>" class="font-weight-bold mb-0">
                                                        <?= htmlspecialchars($pregunta['texto']) ?>
                                                    </label>
                                                </div>
                                                
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text bg-light">
                                                            <i class="fa fa-edit text-muted"></i>
                                                        </span>
                                                    </div>
                                                    <input type="text" class="form-control form-control-lg" 
                                                           id="respuesta_<?= $pregunta['id'] ?>"
                                                           name="respuestas[<?= $pregunta['id'] ?>]"
                                                           placeholder="Escribe tu respuesta aquí..."
                                                           required>
                                                    <div class="invalid-feedback">
                                                        Esta respuesta es requerida.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <button type="submit" class="btn btn-warning btn-lg btn-block mt-4">
                                        <i class="fa fa-check mr-2"></i> Verificar Respuestas
                                    </button>
                                </form>
                            </div>
                        
                        <?php elseif ($paso == 3): ?>
                            <div class="step-content">
                                <div class="text-center mb-4">
                                    <div class="icon-circle bg-success text-white mx-auto mb-3">
                                        <i class="fa fa-lock fa-2x"></i>
                                    </div>
                                    <h5 class="text-success">Verificación Exitosa</h5>
                                    <p class="text-muted">Ahora puedes establecer tu nueva contraseña</p>
                                </div>
                                
                                <div class="alert alert-success border-left-success mb-4">
                                    <i class="fa fa-check-circle mr-2"></i>
                                    <strong>¡Perfecto!</strong> Tus respuestas son correctas. Procede a cambiar tu contraseña.
                                </div>
                                
                                <form method="POST" class="needs-validation" novalidate>
                                    <div class="form-group mb-3">
                                        <label for="nueva_clave" class="font-weight-bold">
                                            <i class="fa fa-key mr-2"></i>Nueva Contraseña
                                        </label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text bg-light">
                                                    <i class="fa fa-lock text-muted"></i>
                                                </span>
                                            </div>
                                            <input type="password" class="form-control form-control-lg" id="nueva_clave" 
                                                   name="nueva_clave" minlength="16" 
                                                   pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{16,}$"
                                                   title="Mínimo 16 caracteres con mayúscula, minúscula, número y carácter especial"
                                                   required>
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                                    <i class="fa fa-eye"></i>
                                                </button>
                                            </div>
                                            <div class="invalid-feedback">
                                                La contraseña no cumple con los requisitos.
                                            </div>
                                        </div>
                                        <div class="password-requirements mt-2">
                                            <small class="text-muted">
                                                <i class="fa fa-info-circle mr-1"></i>
                                                <strong>Requisitos:</strong> Mínimo 16 caracteres, 1 mayúscula, 1 minúscula, 1 número y 1 carácter especial (@$!%*?&)
                                            </small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group mb-4">
                                        <label for="confirmar_clave" class="font-weight-bold">
                                            <i class="fa fa-check-circle mr-2"></i>Confirmar Contraseña
                                        </label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text bg-light">
                                                    <i class="fa fa-check text-muted"></i>
                                                </span>
                                            </div>
                                            <input type="password" class="form-control form-control-lg" id="confirmar_clave" 
                                                   name="confirmar_clave" minlength="16" required>
                                            <div class="invalid-feedback">
                                                Las contraseñas no coinciden.
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-success btn-lg btn-block mt-4">
                                        <i class="fa fa-save mr-2"></i> Cambiar Contraseña
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                        
                        <div class="text-center mt-4 pt-3 border-top">
                            <a href="../index.php" class="btn btn-link text-muted">
                                <i class="fa fa-arrow-left mr-2"></i> Volver al inicio de sesión
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    .bg-gradient-primary {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    }
    
    .progress-container {
        width: 100%;
        max-width: 300px;
    }
    
    .step-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #e9ecef;
        color: #6c757d;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        border: 2px solid #e9ecef;
        transition: all 0.3s ease;
    }
    
    .step-circle.active {
        background: #007bff;
        color: white;
        border-color: #007bff;
    }
    
    .step-circle.completed {
        background: #28a745;
        color: white;
        border-color: #28a745;
    }
    
    .step-line {
        flex: 1;
        height: 2px;
        background: #e9ecef;
        margin: 0 10px;
        transition: all 0.3s ease;
    }
    
    .step-line.completed {
        background: #28a745;
    }
    
    .icon-circle {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .question-number {
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 14px;
    }
    
    .question-card {
        background: #f8f9fa;
        transition: all 0.2s ease;
    }
    
    .question-card:hover {
        background: #e9ecef;
    }
    
    .border-left-danger {
        border-left: 4px solid #dc3545 !important;
    }
    
    .border-left-success {
        border-left: 4px solid #28a745 !important;
    }
    
    .border-left-info {
        border-left: 4px solid #17a2b8 !important;
    }
    
    .border-left-warning {
        border-left: 4px solid #ffc107 !important;
    }
    
    .step-content {
        animation: fadeInUp 0.5s ease;
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .opacity-75 {
        opacity: 0.75;
    }
    </style>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/js/bootstrap.min.js"></script>
    
    <script>
    // Validación de formulario Bootstrap
    (function() {
        'use strict';
        window.addEventListener('load', function() {
            var forms = document.getElementsByClassName('needs-validation');
            var validation = Array.prototype.filter.call(forms, function(form) {
                form.addEventListener('submit', function(event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);
    })();
    
    // Toggle mostrar/ocultar contraseña
    document.addEventListener('DOMContentLoaded', function() {
        const toggleButton = document.getElementById('togglePassword');
        if (toggleButton) {
            toggleButton.addEventListener('click', function() {
                const passwordField = document.getElementById('nueva_clave');
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
        }
        
        // Validar que las contraseñas coincidan
        const nuevaClave = document.getElementById('nueva_clave');
        const confirmarClave = document.getElementById('confirmar_clave');
        
        if (nuevaClave && confirmarClave) {
            function validarCoincidencia() {
                if (confirmarClave.value !== nuevaClave.value) {
                    confirmarClave.setCustomValidity('Las contraseñas no coinciden');
                } else {
                    confirmarClave.setCustomValidity('');
                }
            }
            
            nuevaClave.addEventListener('input', validarCoincidencia);
            confirmarClave.addEventListener('input', validarCoincidencia);
        }
        
        // Convertir respuestas a minúsculas
        document.querySelectorAll('input[name^="respuestas"]').forEach(function(input) {
            input.addEventListener('input', function() {
                this.value = this.value.toLowerCase();
            });
        });
    });
    </script>
</body>
</html>