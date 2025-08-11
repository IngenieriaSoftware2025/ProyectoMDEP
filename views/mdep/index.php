<div class="row justify-content-center p-3">
    <div class="col-lg-12">
        <div class="card custom-card shadow-lg" style="border-radius: 10px; border: 1px solid #007bff;">
            <div class="card-body p-3">
                <div class="row mb-3">
                    <h5 class="text-center mb-2">¡Bienvenido a la Aplicación para el registro, modificación y gestión de dependencias!</h5>
                    <h4 class="text-center mb-2 text-primary">GESTIÓN DE DEPENDENCIAS MDEP</h4>
                </div>

                <div class="row mb-3">
                    <div class="col-12 d-flex justify-content-between align-items-center">
                        <h3>DEPENDENCIAS REGISTRADAS</h3>
                        <div>
                            <button class="btn btn-info me-2" id="BtnVerUbicaciones">
                                <i class="bi bi-geo-alt me-1"></i>Ver Ubicaciones
                            </button>
                            <button class="btn btn-success" id="BtnNuevaDependencia">
                                <i class="bi bi-plus-circle me-1"></i>Nueva Dependencia
                            </button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive p-2">
                    <table class="table table-striped table-hover table-bordered w-100 table-sm" id="TableDependencias">
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="modalDependencia" tabindex="-1" aria-labelledby="modalDependenciaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDependenciaLabel">Nueva Dependencia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="FormDependencias" enctype="multipart/form-data">
                    <input type="hidden" id="dep_llave" name="dep_llave">
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="dep_desc_lg" class="form-label">DESCRIPCIÓN LARGA <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="dep_desc_lg" name="dep_desc_lg" 
                                   placeholder="Ingrese la descripción larga (mínimo 10 caracteres)"  required>
                            <div class="form-text">Mínimo 10 caracteres, máximo 100</div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="dep_desc_md" class="form-label">DESCRIPCIÓN MEDIANA <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="dep_desc_md" name="dep_desc_md" 
                                   placeholder="Descripción mediana (mínimo 5 caracteres)" maxlength="35" required>
                            <div class="form-text">Mínimo 5 caracteres, máximo 35</div>
                        </div>
                        <div class="col-md-6">
                            <label for="dep_desc_ct" class="form-label">DESCRIPCIÓN CORTA <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="dep_desc_ct" name="dep_desc_ct" 
                                   placeholder="Descripción corta (mínimo 3 caracteres)" maxlength="15" required>
                            <div class="form-text">Mínimo 3 caracteres, máximo 15</div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="dep_clase" class="form-label">CLASE <span class="text-danger">*</span></label>
                            <select class="form-select" id="dep_clase" name="dep_clase" required>
                                <option value="">Seleccionar clase</option>
                                <option value="A">A - Administrativo</option>
                                <option value="O">O - Operativo</option>
                                <option value="D">D - Docencia</option>
                                <option value="R">R - Rescate</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="dep_imagen" class="form-label">IMAGEN DE DEPENDENCIA</label>
                            <input type="file" class="form-control" id="dep_imagen" name="dep_imagen" accept="image/*">
                            <div class="form-text">Opcional - Formatos: JPG, PNG, GIF</div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="dep_latitud" class="form-label">LATITUD</label>
                            <input type="text" class="form-control" id="dep_latitud" name="dep_latitud" 
                                   placeholder="Ej: 14.6349" pattern="^-?([0-8]?[0-9](\.[0-9]+)?|90(\.0+)?)$">
                            <div class="form-text">Opcional - Coordenada de latitud</div>
                        </div>
                        <div class="col-md-6">
                            <label for="dep_longitud" class="form-label">LONGITUD</label>
                            <input type="text" class="form-control" id="dep_longitud" name="dep_longitud" 
                                   placeholder="Ej: -90.5069" pattern="^-?((1[0-7][0-9])|([0-9]?[0-9]))(\.[0-9]+)?$|^-?180(\.0+)?$">
                            <div class="form-text">Opcional - Coordenada de longitud</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <p class="text-muted"><span class="text-danger">*</span> Campos obligatorios</p>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Cancelar
                </button>
                <button type="button" class="btn btn-success" id="BtnGuardar">
                    <i class="bi bi-save me-1"></i>Guardar
                </button>
                <button type="button" class="btn btn-warning d-none" id="BtnModificar">
                    <i class="bi bi-pencil-square me-1"></i>Modificar
                </button>
            </div>
        </div>
    </div>
</div>

<script src="<?= asset('build/js/mdep/index.js') ?>"></script>