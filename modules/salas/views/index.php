<div class="page-header d-print-none">
  <div class="container-xl">
    <div class="row g-2 align-items-center">
      <div class="col">
        <div class="page-pretitle">UI Kit & Design System</div>
        <h2 class="page-title">Galería de Elementos Tabler.io</h2>
      </div>
    </div>
  </div>
</div>

<div class="page-body">
  <div class="container-xl">
    
    <div class="row row-cards mb-5">
        <div class="col-12"><h3>1. Variaciones de Tarjetas</h3></div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Card Simple</h3></div>
                <div class="card-body">Contenido básico de una tarjeta. Ideal para contenedores generales.</div>
                <div class="card-footer">Pie de página</div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-status-top bg-green"></div> <div class="card-body">
                    <h3 class="card-title">Card con Estado (Top)</h3>
                    <p class="text-muted">Útil para indicar estados (Verde=Ok, Rojo=Error).</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-status-start bg-blue"></div> <div class="card-body">
                    <h3 class="card-title">Card con Estado (Lateral)</h3>
                    <p class="text-muted">Variación estética para listas laterales.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row row-cards mb-5">
        <div class="col-12"><h3>2. Elementos de Formulario</h3></div>
        
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Inputs Clásicos</h3></div>
                <div class="card-body">
                    
                    <div class="mb-3">
                        <label class="form-label required">Nombre Completo</label>
                        <input type="text" class="form-control" name="example-text-input" placeholder="Escribe algo...">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Buscar (Con Icono)</label>
                        <div class="input-icon">
                            <span class="input-icon-addon">
                              <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="10" cy="10" r="7" /><line x1="21" y1="21" x2="15" y2="15" /></svg>
                            </span>
                            <input type="text" class="form-control" placeholder="Buscar...">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Precio / Moneda</label>
                        <div class="input-group">
                            <span class="input-group-text">S/</span>
                            <input type="text" class="form-control" placeholder="0.00">
                            <span class="input-group-text">PEN</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Selector Simple</label>
                        <select class="form-select">
                            <option>Opción 1</option>
                            <option>Opción 2</option>
                        </select>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Selectores Avanzados (Radio/Check)</h3></div>
                <div class="card-body">
                    
                    <div class="mb-3">
                        <label class="form-label">Selección de Iconos (SelectGroup)</label>
                        <div class="form-selectgroup">
                            <label class="form-selectgroup-item">
                                <input type="radio" name="icons" value="home" class="form-selectgroup-input" checked>
                                <span class="form-selectgroup-label">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="5 12 3 12 12 3 21 12 19 12" /><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" /><path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" /></svg>
                                </span>
                            </label>
                            <label class="form-selectgroup-item">
                                <input type="radio" name="icons" value="user" class="form-selectgroup-input">
                                <span class="form-selectgroup-label">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="7" r="4" /><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /></svg>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Switches & Checks</label>
                        <label class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" checked>
                            <span class="form-check-label">Activar Notificaciones (Switch)</span>
                        </label>
                        <label class="form-check">
                            <input class="form-check-input" type="checkbox">
                            <span class="form-check-label">Acepto términos (Checkbox)</span>
                        </label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Selector de Color</label>
                        <div class="row g-2">
                            <div class="col-auto">
                                <label class="form-colorinput">
                                    <input name="color" type="radio" value="dark" class="form-colorinput-input" checked>
                                    <span class="form-colorinput-color bg-dark"></span>
                                </label>
                            </div>
                            <div class="col-auto">
                                <label class="form-colorinput">
                                    <input name="color" type="radio" value="purple" class="form-colorinput-input">
                                    <span class="form-colorinput-color bg-purple"></span>
                                </label>
                            </div>
                            <div class="col-auto">
                                <label class="form-colorinput">
                                    <input name="color" type="radio" value="blue" class="form-colorinput-input">
                                    <span class="form-colorinput-color bg-blue"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="row row-cards mb-5">
        <div class="col-12"><h3>3. Botones y Badges</h3></div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <p class="text-muted">Colores sólidos:</p>
                    <div class="btn-list">
                        <a href="#" class="btn btn-primary">Primary</a>
                        <a href="#" class="btn btn-secondary">Secondary</a>
                        <a href="#" class="btn btn-success">Success</a>
                        <a href="#" class="btn btn-warning">Warning</a>
                        <a href="#" class="btn btn-danger">Danger</a>
                    </div>
                    <br>
                    <p class="text-muted">Estilo Ghost (Transparente):</p>
                    <div class="btn-list">
                        <a href="#" class="btn btn-ghost-primary">Primary</a>
                        <a href="#" class="btn btn-ghost-danger">Danger</a>
                    </div>
                    <br>
                    <p class="text-muted">Con Iconos:</p>
                    <div class="btn-list">
                        <a href="#" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" /></svg>
                            Nuevo
                        </a>
                        <a href="#" class="btn btn-danger btn-icon" aria-label="Button">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7h16" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <p class="text-muted">Badges (Etiquetas):</p>
                    <div>
                        <span class="badge bg-blue">Blue</span>
                        <span class="badge bg-green">Green</span>
                        <span class="badge bg-red">Red</span>
                        <span class="badge bg-yellow">Yellow</span>
                    </div>
                    <br>
                    <p class="text-muted">Badges Light (Suaves):</p>
                    <div>
                        <span class="badge bg-blue-lt">Blue</span>
                        <span class="badge bg-green-lt">Green</span>
                        <span class="badge bg-red-lt">Red</span>
                    </div>
                    <br>
                    <p class="text-muted">Badges Outline (Borde):</p>
                    <div>
                        <span class="badge badge-outline text-blue">Blue</span>
                        <span class="badge badge-outline text-green">Green</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row row-cards mb-5">
        <div class="col-12"><h3>4. Alertas</h3></div>
        
        <div class="col-md-6">
            <div class="alert alert-success" role="alert">
                <h4 class="alert-title">¡Operación Exitosa!</h4>
                <div class="text-muted">Los datos se han guardado correctamente en el sistema.</div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="alert alert-danger" role="alert">
                <h4 class="alert-title">Hubo un error</h4>
                <div class="text-muted">No se pudo conectar con el servidor. Intente más tarde.</div>
            </div>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-12 text-center">
            <h3>5. Modales</h3>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-ejemplo">
                Abrir Modal de Ejemplo
            </button>
        </div>
    </div>

  </div>
</div>

<div class="modal modal-blur fade" id="modal-ejemplo" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Título del Modal</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Lorem ipsum dolor sit amet, consectetur adipisicing elit. Adipisci animi beatae delectus deleniti dolorem eveniet id igitur iste molestiae.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Guardar Cambios</button>
      </div>
    </div>
  </div>
</div>