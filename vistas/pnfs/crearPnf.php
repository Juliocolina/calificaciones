<?php
session_start();
// Verificar sesión y rol admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}
require_once __DIR__ . '/../../models/header.php';
require_once __DIR__ . '/../../config/conexion.php';

$conn = conectar();
$aldeas = $conn->query("SELECT id, nombre FROM aldeas ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
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
                <div class="card-header bg-info text-white text-center">
                    <h3 class="mb-0"><i class="fa fa-certificate"></i> Registrar PNF</h3>
                    <p class="mb-0">Crear nuevo Programa Nacional de Formación</p>
                </div>
                <div class="card-body">
                    <form action="../../controladores/pnfController/crearPnf.php" method="POST" data-validar-form autocomplete="off">
                        <input type="hidden" name="usuario_id" value="<?= htmlspecialchars($_SESSION['usuario_id'] ?? '') ?>">

                        <div class="form-group">
                            <label for="nombre_pnf"><i class="fa fa-book-open"></i> Nombre del PNF *</label>
                            <input type="text" 
                                   name="nombre_pnf" 
                                   id="nombre_pnf" 
                                   class="form-control" 
                                   placeholder="Ej: PNF Informática" 
                                   data-validar='{"tipo":"soloLetras","opciones":{"requerido":true,"minLength":5,"maxLength":100}}'
                                   data-nombre="Nombre del PNF"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="codigo_pnf"><i class="fa fa-barcode"></i> Código del PNF *</label>
                            <input type="text" 
                                   name="codigo_pnf" 
                                   id="codigo_pnf" 
                                   class="form-control" 
                                   placeholder="Ej: PNF-INF-01" 
                                   data-validar='{"tipo":"alfanumerico","opciones":{"requerido":true,"minLength":5,"maxLength":20}}'
                                   data-nombre="Código del PNF"
                                   required>
                            <small class="form-text text-muted">Formato sugerido: PNF-XXX-##</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="aldea_id"><i class="fa fa-university"></i> Aldea *</label>
                            <select name="aldea_id" id="aldea_id" class="form-control" required>
                                <option value="">Seleccione una aldea</option>
                                <?php foreach ($aldeas as $aldea): ?>
                                    <option value="<?= $aldea['id'] ?>"><?= htmlspecialchars($aldea['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">El PNF pertenecerá a esta aldea específica</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="descripcion"><i class="fa fa-info-circle"></i> Descripción (opcional)</label>
                            <textarea name="descripcion" 
                                      id="descripcion" 
                                      class="form-control" 
                                      rows="3" 
                                      placeholder="Descripción del Programa Nacional de Formación"
                                      data-validar='{"tipo":"","opciones":{"maxLength":500}}'
                                      data-nombre="Descripción"></textarea>
                        </div>

                        <div class="text-center">
                            <button type="submit" class="btn btn-info btn-lg mr-2">
                                <i class="fa fa-save"></i> Registrar PNF
                            </button>
                            <a href="verPnfs.php" class="btn btn-secondary btn-lg">
                                <i class="fa fa-arrow-left"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../models/footer.php'; ?>