<?php
session_start();
require_once '../../controladores/hellpers/auth.php';
verificarSesion();
require_once '../../config/conexion.php';

$conn = conectar();
$usuario_id = $_SESSION['usuario_id'];

// Obtener preguntas activas
$preguntas = $conn->query("SELECT id, texto FROM catalogo_preguntas WHERE activo = 1 ORDER BY id")->fetchAll();

// Obtener respuestas existentes
$stmt = $conn->prepare("SELECT pregunta_id, respuesta FROM respuestas_seguridad WHERE usuario_id = ?");
$stmt->execute([$usuario_id]);
$respuestas_existentes = [];
foreach ($stmt->fetchAll() as $row) {
    $respuestas_existentes[$row['pregunta_id']] = $row['respuesta'];
}

if ($_POST) {
    try {
        $conn->beginTransaction();
        
        // Eliminar respuestas existentes
        $stmt = $conn->prepare("DELETE FROM respuestas_seguridad WHERE usuario_id = ?");
        $stmt->execute([$usuario_id]);
        
        // Insertar nuevas respuestas
        $stmt = $conn->prepare("INSERT INTO respuestas_seguridad (usuario_id, pregunta_id, respuesta) VALUES (?, ?, ?)");
        
        foreach ($_POST['respuestas'] as $pregunta_id => $respuesta) {
            if (!empty(trim($respuesta))) {
                $stmt->execute([$usuario_id, $pregunta_id, trim(strtolower($respuesta))]);
            }
        }
        
        $conn->commit();
        header("Location: configurarSeguridad.php?success=" . urlencode("Preguntas de seguridad configuradas correctamente"));
        exit;
        
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error al guardar: " . $e->getMessage();
    }
}
?>

<?php
$pageTitle = "Configurar Preguntas de Seguridad";
include '../../includes/header.php';
?>

<div class="breadcrumbs">
    <div class="breadcrumbs-inner">
        <div class="row m-0">
            <div class="col-sm-4">
                <div class="page-header float-left">
                    <div class="page-title">
                        <h1>Preguntas de Seguridad</h1>
                    </div>
                </div>
            </div>
            <div class="col-sm-8">
                <div class="page-header float-right">
                    <div class="page-title">
                        <ol class="breadcrumb text-right">
                            <li><a href="../home.php">Inicio</a></li>
                            <li><a href="miPerfil.php">Mi Perfil</a></li>
                            <li class="active">Preguntas de Seguridad</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="animated fadeIn">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-lg">
                    <div class="card-header bg-gradient-primary text-white">
                        <div class="row align-items-center">
                            <div class="col">
                                <h4 class="mb-0"><i class="fa fa-shield mr-2"></i> Configurar Preguntas de Seguridad</h4>
                                <p class="mb-0 opacity-75">Configura tus preguntas de seguridad para recuperar tu contraseña de forma segura</p>
                            </div>
                            <div class="col-auto">
                                <div class="icon-circle bg-white text-primary">
                                    <i class="fa fa-lock fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body p-4">
                        <?php if (isset($_GET['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-check-circle fa-2x text-success mr-3"></i>
                                    <div>
                                        <h6 class="alert-heading mb-1">¡Configuración exitosa!</h6>
                                        <p class="mb-0"><?= htmlspecialchars($_GET['success']) ?></p>
                                    </div>
                                </div>
                                <button type="button" class="close" data-dismiss="alert">
                                    <span>&times;</span>
                                </button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-exclamation-triangle fa-2x text-danger mr-3"></i>
                                    <div>
                                        <h6 class="alert-heading mb-1">Error en la configuración</h6>
                                        <p class="mb-0"><?= htmlspecialchars($error) ?></p>
                                    </div>
                                </div>
                                <button type="button" class="close" data-dismiss="alert">
                                    <span>&times;</span>
                                </button>
                            </div>
                        <?php endif; ?>
                        
                        <div class="alert alert-info border-left-info">
                            <div class="d-flex align-items-center">
                                <i class="fa fa-info-circle fa-2x text-info mr-3"></i>
                                <div>
                                    <h6 class="mb-1">Instrucciones importantes</h6>
                                    <ul class="mb-0 pl-3">
                                        <li>Responde todas las preguntas con información que recuerdes fácilmente</li>
                                        <li>Escribe las respuestas en <strong>minúsculas</strong> y sin tildes</li>
                                        <li>Estas preguntas te permitirán recuperar tu contraseña si la olvidas</li>
                                        <li>Mantén tus respuestas en privado y seguras</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="row">
                                <?php foreach ($preguntas as $index => $pregunta): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card border-left-primary h-100">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center mb-3">
                                                    <div class="icon-circle bg-primary text-white mr-3">
                                                        <i class="fa fa-question"></i>
                                                    </div>
                                                    <h6 class="mb-0">Pregunta <?= $index + 1 ?></h6>
                                                </div>
                                                
                                                <label for="respuesta_<?= $pregunta['id'] ?>" class="font-weight-bold text-dark mb-2">
                                                    <?= htmlspecialchars($pregunta['texto']) ?>
                                                </label>
                                                
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text bg-light">
                                                            <i class="fa fa-edit text-muted"></i>
                                                        </span>
                                                    </div>
                                                    <input type="text" 
                                                           class="form-control form-control-lg" 
                                                           id="respuesta_<?= $pregunta['id'] ?>"
                                                           name="respuestas[<?= $pregunta['id'] ?>]"
                                                           value="<?= htmlspecialchars($respuestas_existentes[$pregunta['id']] ?? '') ?>"
                                                           placeholder="Escribe tu respuesta aquí..."
                                                           required>
                                                    <div class="invalid-feedback">
                                                        Por favor, responde esta pregunta.
                                                    </div>
                                                </div>
                                                
                                                <small class="text-muted mt-1">
                                                    <i class="fa fa-lightbulb-o"></i> 
                                                    Ejemplo: si la respuesta es "María José", escribe "maria jose"
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary btn-lg px-5 mr-3">
                                    <i class="fa fa-save mr-2"></i> Guardar Configuración
                                </button>
                                <a href="miPerfil.php" class="btn btn-outline-secondary btn-lg px-4">
                                    <i class="fa fa-arrow-left mr-2"></i> Volver al Perfil
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.border-left-primary {
    border-left: 4px solid #007bff !important;
}

.border-left-info {
    border-left: 4px solid #17a2b8 !important;
}

.icon-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
}

.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
}

.form-control-lg {
    font-size: 1.1rem;
}

.opacity-75 {
    opacity: 0.75;
}
</style>

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

// Convertir respuestas a minúsculas automáticamente
document.querySelectorAll('input[name^="respuestas"]').forEach(function(input) {
    input.addEventListener('input', function() {
        this.value = this.value.toLowerCase();
    });
});
</script>

<?php include '../../includes/footer.php'; ?>