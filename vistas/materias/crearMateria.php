<?php
require_once __DIR__ . '/../../models/header.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../controladores/pnfController/verPnfs.php';

$conn = conectar();
?>
<div class="container mt-4">
    <?php if (isset($_GET['exito'])): ?>
        <div class="alert alert-success"><i class="fa fa-check-circle"></i> <?= htmlspecialchars($_GET['exito']) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger"><i class="fa fa-times-circle"></i> <?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>
    
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow">
                <div class="card-header bg-warning text-dark text-center">
                    <h3 class="mb-0"><i class="fa fa-book"></i> Registrar Materia</h3>
                    <p class="mb-0">Crear nueva unidad curricular</p>
                </div>
                <div class="card-body">
                    <form action="../../controladores/materiaController/crearMateria.php" method="POST" data-validar-form autocomplete="off">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="pnf_id"><i class="fa fa-graduation-cap"></i> PNF *</label>
                                    <select name="pnf_id" id="pnf_id" class="form-control" required>
                                        <option value="">Seleccione un PNF</option>
                                        <?php 
                                        if (isset($pnfs) && is_array($pnfs) && count($pnfs) > 0) {
                                            foreach ($pnfs as $pnf) {
                                                echo "<option value='" . htmlspecialchars($pnf['id']) . "'>" . htmlspecialchars($pnf['nombre']) . "</option>";
                                            }
                                        } else {
                                            echo "<option value=''>No hay PNFs disponibles</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="codigo"><i class="fa fa-barcode"></i> Código *</label>
                                    <input type="text" 
                                           name="codigo" 
                                           id="codigo" 
                                           class="form-control" 
                                           placeholder="Ej: UC-MAT-101" 
                                           data-validar='{"tipo":"alfanumerico","opciones":{"requerido":true,"minLength":3,"maxLength":30}}'
                                           data-nombre="Código"
                                           required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="nombre"><i class="fa fa-book"></i> Nombre de la Unidad Curricular *</label>
                            <input type="text" 
                                   name="nombre" 
                                   id="nombre" 
                                   class="form-control" 
                                   placeholder="Ej: Matemática I" 
                                   data-validar='{"tipo":"soloLetras","opciones":{"requerido":true,"minLength":3,"maxLength":100}}'
                                   data-nombre="Nombre de la materia"
                                   required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="duracion"><i class="fa fa-clock"></i> Duración *</label>
                                    <select name="duracion" id="duracion" class="form-control" required>
                                        <option value="">Seleccione la duración</option>
                                        <option value="trimestral">Trimestral</option>
                                        <option value="bimestral">Bimestral</option>
                                        <option value="anual">Anual</option>
                                    </select>
                                    <small class="form-text text-muted">Define cuántos trimestres dura la materia</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="creditos"><i class="fa fa-star"></i> Créditos *</label>
                                    <input type="number" 
                                           name="creditos" 
                                           id="creditos" 
                                           class="form-control" 
                                           min="1" 
                                           max="10" 
                                           placeholder="Ej: 3" 
                                           data-validar='{"tipo":"soloNumeros","opciones":{"requerido":true}}'
                                           data-nombre="Créditos"
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
                                      placeholder="Descripción de la unidad curricular"
                                      data-validar='{"tipo":"","opciones":{"maxLength":500}}'
                                      data-nombre="Descripción"></textarea>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-warning btn-lg mr-2">
                                <i class="fa fa-save"></i> Registrar Materia
                            </button>
                            <a href="materiasPorPnf.php" class="btn btn-secondary btn-lg">
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