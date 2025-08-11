
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css" />

<div class="row justify-content-center p-3">
    <div class="col-lg-12">
        <div class="card custom-card shadow-lg" style="border-radius: 10px; border: 1px solid #007bff;">
            <div class="card-body p-3">
                <div class="row mb-3">
                    <div class="col-md-8">
                        <h5 class="text-center mb-2">UBICACIONES DE DEPENDENCIAS EN GUATEMALA</h5>
                        <h4 class="text-center mb-2 text-primary">MAPA INTERACTIVO MDEP</h4>
                    </div>
                    <div class="col-md-4 d-flex justify-content-end align-items-center">
                        <button class="btn btn-secondary me-2" id="BtnVolver">
                            <i class="bi bi-arrow-left me-1"></i>Volver
                        </button>
                        <button class="btn btn-info" id="BtnActualizar">
                            <i class="bi bi-arrow-clockwise me-1"></i>Actualizar
                        </button>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-12">
                        <div class="alert alert-info" role="alert">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Instrucciones:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Los marcadores muestran las dependencias con coordenadas registradas</li>
                                <li>Haz clic en cualquier marcador para ver información de la dependencia</li>
                                <li>Haz clic en cualquier punto del mapa para obtener las coordenadas</li>
                                <li>Los marcadores con imagen aparecen con el logo de la dependencia</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h4 id="totalDependencias">0</h4>
                                <small>Total Dependencias</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h4 id="conUbicacion">0</h4>
                                <small>Con Ubicación</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <h4 id="conImagen">0</h4>
                                <small>Con Logo</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h4 id="activas">0</h4>
                                <small>Activas</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body p-0">
                                <div id="mapa" style="height: 600px; width: 100%;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="loadingOverlay" class="position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center" 
                     style="background-color: rgba(0,0,0,0.5); z-index: 9999; display: none !important;">
                    <div class="text-center text-white">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-3">Cargando ubicaciones...</p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCoordenadas" tabindex="-1" aria-labelledby="modalCoordenadasLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCoordenadasLabel">Coordenadas del Punto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label"><strong>Latitud:</strong></label>
                        <input type="text" class="form-control" id="coordLatitud" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><strong>Longitud:</strong></label>
                        <input type="text" class="form-control" id="coordLongitud" readonly>
                    </div>
                </div>
                <div class="mt-3">
                    <small class="text-muted">
                        Puedes copiar estas coordenadas para usar en el registro de dependencias.
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="copiarCoordenadas">
                    <i class="bi bi-clipboard me-1"></i>Copiar Coordenadas
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
<script src="<?= asset('build/js/ubicaciones/index.js') ?>"></script>