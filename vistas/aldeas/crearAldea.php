<?php
require_once __DIR__ . '/../../models/header.php';
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
                <div class="card-header bg-success text-white text-center">
                    <h3 class="mb-0"><i class="fa fa-map-marker"></i> Registrar Aldea</h3>
                    <p class="mb-0">Crear nueva aldea universitaria</p>
                </div>
                <div class="card-body">
                 <form action="../../controladores/aldeaController/crearAldea.php" method="POST" data-validar-form autocomplete="off">
                        <input type="hidden" name="usuario_id" value="<?= htmlspecialchars($_SESSION['usuario_id']) ?>">
                        
                        <div class="form-group">
                            <label for="nombre_aldea"><i class="fa fa-university"></i> Nombre de la Aldea *</label>
                            <input type="text" 
                                   name="nombre_aldea" 
                                   id="nombre_aldea" 
                                   class="form-control" 
                                   placeholder="Ej: Aldea Universitaria Miranda" 
                                   data-validar='{"tipo":"soloLetras","opciones":{"requerido":true,"minLength":5,"maxLength":100}}'
                                   data-nombre="Nombre de la Aldea"
                                   required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="codigo_aldea"><i class="fa fa-barcode"></i> Código de la Aldea *</label>
                                    <input type="text" 
                                           name="codigo_aldea" 
                                           id="codigo_aldea" 
                                           class="form-control" 
                                           placeholder="Ej: ALD-MIR-001" 
                                           data-validar='{"tipo":"alfanumerico","opciones":{"requerido":true,"minLength":5,"maxLength":20}}'
                                           data-nombre="Código de la Aldea"
                                           required>
                                    <small class="form-text text-muted">Formato sugerido: ALD-XXX-###</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="direccion_aldea"><i class="fa fa-map-marker-alt"></i> Dirección *</label>
                                    <input type="text" 
                                           name="direccion_aldea" 
                                           id="direccion_aldea" 
                                           class="form-control" 
                                           placeholder="Ej: Av. Principal, Sector Centro" 
                                           data-validar='{"tipo":"","opciones":{"requerido":true,"minLength":10,"maxLength":255}}'
                                           data-nombre="Dirección"
                                           required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="descripcion"><i class="fa fa-info-circle"></i> Descripción (opcional)</label>
                            <textarea name="descripcion" 
                                      id="descripcion" 
                                      class="form-control" 
                                      rows="3" 
                                      placeholder="Descripción adicional de la aldea"
                                      data-validar='{"tipo":"","opciones":{"maxLength":500}}'
                                      data-nombre="Descripción"></textarea>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-success btn-lg mr-2">
                                <i class="fa fa-save"></i> Registrar Aldea
                            </button>
                            <a href="verAldeas.php" class="btn btn-secondary btn-lg">
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