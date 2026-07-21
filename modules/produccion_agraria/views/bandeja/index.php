<div class="breadcrumb">
    <div class="container-xl">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?php echo BASE_URL; ?>/produccion_agraria">Prod. Agraria</a>
            </li>
            <li class="breadcrumb-item active">Bandeja de Proformas</li>
        </ol>
    </div>
</div>

<div class="container-xl">
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body py-2 px-3">
            <div class="text-uppercase text-muted fw-bold fs-4">
                <i class="ti ti-leaf me-2 text-primary"></i>
                Sistema de Seguimiento y control de Productos Agricolas
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="ti ti-inbox me-2"></i>Bandeja de Proformas</h2>
        <div class="btn-group">
            <a href="<?php echo BASE_URL; ?>/produccion_agraria?action=punto_venta" class="btn btn-success">
                <i class="ti ti-plus me-1"></i>Nueva Proforma
            </a>
            <button class="btn btn-primary" onclick="mostrarModalVouchers()">
                <i class="ti ti-file-invoice me-1"></i>Vouchers de Pago
            </button>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="<?php echo BASE_URL; ?>/produccion_agraria" class="row g-3">
                <input type="hidden" name="action" value="bandeja">
                <div class="col-md-2">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select" onchange="this.form.submit()">
                        <option value="">Pendientes (default)</option>
                        <option value="PENDIENTE" <?php echo ($_GET['estado'] ?? '') == 'PENDIENTE' ? 'selected' : ''; ?>>Pendiente</option>
                        <option value="PROCESADO" <?php echo ($_GET['estado'] ?? '') == 'PROCESADO' ? 'selected' : ''; ?>>Procesado</option>
                        <option value="RECHAZADO" <?php echo ($_GET['estado'] ?? '') == 'RECHAZADO' ? 'selected' : ''; ?>>Rechazado</option>
                        <option value="TODAS" <?php echo ($_GET['estado'] ?? '') == 'TODAS' ? 'selected' : ''; ?>>TODAS</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Desde</label>
                    <input type="date" name="fecha_desde" class="form-control" value="<?php echo $_GET['fecha_desde'] ?? ''; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control" value="<?php echo $_GET['fecha_hasta'] ?? ''; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Cliente</label>
                    <input type="text" name="cliente" class="form-control" placeholder="Nombre o documento..." value="<?php echo $_GET['cliente'] ?? ''; ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ti ti-search me-1"></i>Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Proformas -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table" id="tabla-proformas">
                <thead>
                    <tr>
                        <th>N° Proforma</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Centro</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Comprobante</th>
                        <th class="w-1 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($proformas as $p): ?>
                    <tr data-id="<?php echo $p['id_transaccion']; ?>">
                        <td>
                            <div class="font-weight-medium">#<?php echo $p['id_transaccion']; ?></div>
                            <div class="text-muted small"><?php echo $p['responsable_venta'] ?? 'Sistema'; ?></div>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($p['fecha_creacion'])); ?></td>
                        <td>
                            <div class="font-weight-medium"><?php echo htmlspecialchars($p['nombre_cliente'] ?? 'Sin cliente'); ?></div>
                            <div class="text-muted small"><?php echo $p['tipo_documento'] ?? ''; ?> <?php echo $p['documento_cliente'] ?? ''; ?></div>
                        </td>
                        <td><?php echo htmlspecialchars($p['nombre_centro'] ?? '-'); ?></td>
                        <td class="text-end font-weight-bold">S/ <?php echo number_format($p['total'], 2); ?></td>
                        <td>
                            <?php
                            $estado = $p['estado'] ?? null;
                            $badgeClass = match($estado) {
                                'PENDIENTE' => 'bg-warning',
                                'PROCESO' => 'bg-info',
                                'COMPLETADA' => 'bg-success',
                                'ANULADA' => 'bg-danger',
                                default => 'bg-secondary'
                            };
                            $estadoTexto = $estado ?: 'SIN ESTADO';
                            ?>
                            <span class="badge <?php echo $badgeClass; ?>"><?php echo $estadoTexto; ?></span>
                        </td>
                        <td>
                            <?php if ($p['serie_comprobante']): ?>
                            <div class="font-weight-medium"><?php echo $p['serie_comprobante']; ?>-<?php echo $p['correlativo_comprobante']; ?></div>
                            <div class="text-muted small"><?php echo $p['metodo_pago'] ?? '-'; ?></div>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-info me-1" onclick="verDetalle(<?php echo $p['id_transaccion']; ?>)" title="Ver detalle">
                                <i class="ti ti-eye"></i>
                            </button>
                            <?php 
                            $estadoActual = $p['estado'] ?? '';
                            $puedeProcesar = !in_array($estadoActual, ['PROCESADO', 'RECHAZADO']);
                            if ($puedeProcesar): 
                            ?>
                            <button class="btn btn-sm btn-success me-1" onclick="procesarProforma(<?php echo $p['id_transaccion']; ?>)" title="Procesar">
                                <i class="ti ti-check"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="anularProforma(<?php echo $p['id_transaccion']; ?>)" title="Anular">
                                <i class="ti ti-x"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($proformas)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="ti ti-inbox-off mb-2" style="font-size: 2rem; display: block;"></i>
                            <?php if (!empty($_GET['estado']) || !empty($_GET['cliente']) || !empty($_GET['fecha_desde'])): ?>
                                No se encontraron proformas con los filtros seleccionados
                            <?php else: ?>
                                No hay proformas pendientes<br>
                                <small class="text-muted">Las nuevas ventas aparecerán aquí automáticamente</small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Ver Detalle -->
<div class="modal fade" id="modal-detalle" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-file-invoice me-2"></i>Detalle de Proforma #<span id="detalle-id"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalle-contenido">
                <!-- Contenido dinámico -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="btn-imprimir" onclick="imprimirProforma()">
                    <i class="ti ti-printer me-1"></i>Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Procesar Proforma -->
<div class="modal fade" id="modal-procesar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="ti ti-check me-2"></i>Procesar Proforma #<span id="procesar-id"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="procesar-id-transaccion">
                
                <div class="mb-3">
                    <label class="form-label required">Método de Pago</label>
                    <div class="row g-2">
                        <?php foreach ($metodosPago as $mp): ?>
                        <div class="col-6">
                            <div class="form-check card p-2 text-center select-pagocard">
                                <input class="form-check-input" type="radio" name="metodo_pago" id="mp_<?php echo $mp['codigo']; ?>" value="<?php echo $mp['codigo']; ?>">
                                <label class="form-check-label d-block" for="mp_<?php echo $mp['codigo']; ?>">
                                    <i class="ti <?php echo $mp['icono']; ?> d-block mb-1" style="font-size: 1.5rem;"></i>
                                    <small><?php echo $mp['nombre']; ?></small>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Contenedor dinámico de validación -->
                <div id="container-validacion-pago" class="card p-3 mb-3 bg-light border-0 shadow-sm rounded-3">
                    <!-- Validación de VENTA (Boucher) -->
                    <div id="seccion-venta-boucher" style="display: none;">
                        <h6 class="text-primary border-bottom pb-2 mb-3 fw-bold"><i class="ti ti-upload me-2"></i>Validación de Venta</h6>
                        <div class="mb-3">
                            <label class="form-label required">Imagen o PDF del Boucher</label>
                            <input type="file" class="form-control" id="procesar_boucher_file" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="text-muted">Cargue el comprobante de depósito o transferencia.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Número de Operación</label>
                            <input type="text" class="form-control" id="procesar_num_operacion" placeholder="Ej: 12345678">
                        </div>
                    </div>
                    
                    <!-- Validación de DONACION (Resolución) -->
                    <div id="seccion-donacion-resolucion" style="display: none;">
                        <h6 class="text-success border-bottom pb-2 mb-3 fw-bold"><i class="ti ti-file-text me-2"></i>Resolución de Donación</h6>
                        <div class="mb-3">
                            <label class="form-label required">Número de Resolución</label>
                            <input type="text" class="form-control" id="procesar_num_resolucion" placeholder="Ej: R.D. N° 045-2026-CH">
                            <small class="text-muted">Ingrese el código identificador de la resolución.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">Documento de Resolución (PDF/Imagen)</label>
                            <input type="file" class="form-control" id="procesar_resolucion_file" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="text-muted">Cargue el PDF o imagen que autoriza la donación.</small>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label required">Tipo de Comprobante</label>
                    <select class="form-select" id="tipo_comprobante">
                        <option value="">Seleccionar...</option>
                        <option value="BOLETA">Boleta de Venta</option>
                        <option value="FACTURA">Factura</option>
                    </select>
                </div>

                <div class="row mb-3" id="campos-comprobante" style="display: none;">
                    <div class="col-6">
                        <label class="form-label required">Serie</label>
                        <select class="form-select" id="serie_comprobante">
                            <option value="">Seleccionar...</option>
                            <option value="B001">B001 (Boletas)</option>
                            <option value="F001">F001 (Facturas)</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label required">Correlativo</label>
                        <input type="text" class="form-control" id="correlativo_comprobante" readonly>
                    </div>
                </div>

                <div class="alert alert-info">
                    <div class="d-flex justify-content-between">
                        <span>Total a pagar:</span>
                        <span class="font-weight-bold" id="procesar-total">S/ 0.00</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btn-confirmar-procesar" onclick="confirmarProcesar()">
                    <i class="ti ti-check me-1"></i>Procesar Venta
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Anular -->
<div class="modal fade" id="modal-anular" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="ti ti-alert-triangle me-2"></i>Anular Proforma #<span id="anular-id"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="anular-id-transaccion">
                <div class="alert alert-warning">
                    <i class="ti ti-info-circle me-2"></i>
                    Esta acción revertirá el stock de los lotes descontados.
                </div>
                <div class="mb-3">
                    <label class="form-label">Motivo de anulación</label>
                    <textarea class="form-control" id="motivo-anulacion" rows="3" placeholder="Ingrese el motivo..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" onclick="confirmarAnular()">
                    <i class="ti ti-x me-1"></i>Anular Proforma
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Estilos compartidos del módulo -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/produccion_agraria/assets/css/variables.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/produccion_agraria/assets/css/components.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/produccion_agraria/assets/css/common.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/produccion_agraria/assets/css/responsive.css">

<script>
const BASE_URL = '<?php echo BASE_URL; ?>';

// Ver detalle de proforma
function verDetalle(id) {
    fetch(`${BASE_URL}/produccion_agraria?action=obtener_proforma&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                Swal.fire('Error', 'No se pudo cargar la proforma', 'error');
                return;
            }
            mostrarDetalle(data);
            new bootstrap.Modal(document.getElementById('modal-detalle')).show();
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Error', 'Error al cargar proforma', 'error');
        });
}

function mostrarDetalle(p) {
    document.getElementById('detalle-id').textContent = p.id_transaccion;
    
    let html = `
        <div class="row mb-3">
            <div class="col-md-6">
                <h6>Cliente</h6>
                <p>${p.nombre_cliente || 'Sin cliente'}<br>
                <small class="text-muted">${p.tipo_documento || ''} ${p.documento_cliente || ''}</small></p>
            </div>
            <div class="col-md-6 text-md-end">
                <h6>Información</h6>
                <p><span class="badge bg-${getEstadoColor(p.estado)}">${p.estado}</span><br>
                <small class="text-muted">${p.fecha_creacion}</small></p>
            </div>
        </div>
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th class="text-center">Cantidad</th>
                    <th class="text-end">P.Unit</th>
                    <th class="text-end">Subtotal</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    p.detalles.forEach(d => {
        html += `
            <tr>
                <td>${d.nombre_producto}<br><small class="text-muted">Lote: ${d.codigo_lote}</small></td>
                <td class="text-center">${d.cantidad} ${d.unidad_medida}</td>
                <td class="text-end">S/ ${parseFloat(d.precio_unitario).toFixed(2)}</td>
                <td class="text-end">S/ ${parseFloat(d.subtotal).toFixed(2)}</td>
            </tr>
        `;
    });
    
    html += `
            </tbody>
            <tfoot>
                <tr class="table-active">
                    <th colspan="3" class="text-end">TOTAL:</th>
                    <th class="text-end">S/ ${parseFloat(p.total).toFixed(2)}</th>
                </tr>
            </tfoot>
        </table>
        ${p.serie_comprobante ? `
        <div class="alert alert-success mt-3">
            <strong>Comprobante:</strong> ${p.serie_comprobante}-${p.correlativo_comprobante}<br>
            <strong>Método de Pago:</strong> ${p.metodo_pago || '-'}
        </div>
        ` : ''}
        ${p.id_voucher ? `
        <div class="alert alert-info mt-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <strong><i class="ti ti-file-invoice me-1"></i>Voucher de Pago:</strong> #${p.id_voucher}
                </div>
                <div class="btn-group">
                    <a href="${BASE_URL}/produccion_agraria?action=descargar_voucher&id=${p.id_voucher}" 
                       class="btn btn-sm btn-primary" target="_blank">
                        <i class="ti ti-download me-1"></i>Descargar
                    </a>
                    <button class="btn btn-sm btn-warning" onclick="desasignarVoucherDesdeProforma(${p.id_voucher})" title="Des-asignar voucher">
                        <i class="ti ti-link-off me-1"></i>Des-asignar
                    </button>
                </div>
            </div>
            <small class="text-muted">Documento adjunto al procesar esta proforma</small>
        </div>
        ` : ''}
    `;
    
    document.getElementById('detalle-contenido').innerHTML = html;
}

function getEstadoColor(estado) {
    if (!estado) return 'secondary';
    return {
        'PENDIENTE': 'warning',
        'PROCESADO': 'success',
        'RECHAZADO': 'danger'
    }[estado] || 'secondary';
}

// Auxiliar para convertir archivo a base64
function fileToBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = () => resolve(reader.result.split(',')[1]);
        reader.onerror = error => reject(error);
    });
}

// Procesar proforma
function procesarProforma(id) {
    fetch(`${BASE_URL}/produccion_agraria?action=obtener_proforma&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                Swal.fire('Error', 'No se pudo cargar la proforma', 'error');
                return;
            }
            document.getElementById('procesar-id').textContent = id;
            document.getElementById('procesar-id-transaccion').value = id;
            document.getElementById('procesar-total').textContent = 'S/ ' + parseFloat(data.total).toFixed(2);
            
            // Reset form
            document.querySelectorAll('input[name="metodo_pago"]').forEach(el => el.checked = false);
            document.getElementById('tipo_comprobante').value = '';
            document.getElementById('serie_comprobante').value = '';
            document.getElementById('correlativo_comprobante').value = '';
            document.getElementById('campos-comprobante').style.display = 'none';
            
            // Limpiar inputs de validación
            document.getElementById('procesar_boucher_file').value = '';
            document.getElementById('procesar_num_operacion').value = '';
            document.getElementById('procesar_num_resolucion').value = '';
            document.getElementById('procesar_resolucion_file').value = '';
            
            // Ocultar paneles inicialmente
            const seccionVenta = document.getElementById('seccion-venta-boucher');
            const seccionDonacion = document.getElementById('seccion-donacion-resolucion');
            if (seccionVenta) seccionVenta.style.display = 'none';
            if (seccionDonacion) seccionDonacion.style.display = 'none';
            
            // Pre-seleccionar método guardado (VENTA o DONACION)
            const metodoPre = data.metodo_pago || 'VENTA';
            const radioEl = document.getElementById(`mp_${metodoPre}`);
            if (radioEl) {
                radioEl.checked = true;
                // Disparar evento change manual
                radioEl.dispatchEvent(new Event('change', { bubbles: true }));
            }
            
            new bootstrap.Modal(document.getElementById('modal-procesar')).show();
        });
}

// Mostrar campos de comprobante según tipo
document.getElementById('tipo_comprobante')?.addEventListener('change', function() {
    const campos = document.getElementById('campos-comprobante');
    const serie = document.getElementById('serie_comprobante');
    
    if (this.value) {
        campos.style.display = 'flex';
        serie.value = this.value === 'BOLETA' ? 'B001' : 'F001';
        obtenerSiguienteCorrelativo();
    } else {
        campos.style.display = 'none';
    }
});

document.getElementById('serie_comprobante')?.addEventListener('change', obtenerSiguienteCorrelativo);

function obtenerSiguienteCorrelativo() {
    const serie = document.getElementById('serie_comprobante').value;
    if (!serie) return;
    
    fetch(`${BASE_URL}/produccion_agraria?action=siguiente_correlativo&serie=${serie}`)
        .then(r => r.json())
        .then(data => {
            document.getElementById('correlativo_comprobante').value = data.siguiente;
        });
}

async function confirmarProcesar() {
    const id = document.getElementById('procesar-id-transaccion').value;
    const metodoPago = document.querySelector('input[name="metodo_pago"]:checked')?.value;
    const totalVenta = parseFloat(document.getElementById('procesar-total').textContent.replace('S/ ', ''));
    
    if (!metodoPago) {
        Swal.fire('Error', 'Seleccione un método de pago', 'error');
        return;
    }
    
    let idVoucher = null;
    let docJustificante = '';
    let tipoComprobante = '';
    let serie = '';
    let correlativo = '';
    
    // Mostrar spinner
    Swal.fire({
        title: 'Guardando datos...',
        text: 'Registrando comprobante y procesando proforma',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    try {
        if (metodoPago === 'VENTA') {
            const boucherFile = document.getElementById('procesar_boucher_file').files[0];
            const numOperacion = document.getElementById('procesar_num_operacion').value.trim();
            tipoComprobante = document.getElementById('tipo_comprobante').value;
            serie = document.getElementById('serie_comprobante').value;
            correlativo = document.getElementById('correlativo_comprobante').value;
            
            if (!boucherFile) {
                Swal.fire('Error', 'Debe cargar el comprobante del boucher', 'error');
                return;
            }
            if (!tipoComprobante) {
                Swal.fire('Error', 'Seleccione el tipo de comprobante', 'error');
                return;
            }
            if (!serie || !correlativo) {
                Swal.fire('Error', 'Complete la serie y correlativo del comprobante', 'error');
                return;
            }
            
            // Subir boucher
            const base64 = await fileToBase64(boucherFile);
            const voucherPayload = {
                num_operation: numOperacion || ('OP-' + id),
                monto_total: totalVenta,
                fecha_deposito: new Date().toISOString().substring(0, 10),
                archivo_base64: base64,
                archivo_nombre: boucherFile.name
            };
            
            const resV = await fetch(`${BASE_URL}/produccion_agraria?action=guardar_voucher`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(voucherPayload)
            });
            const resultV = await resV.json();
            if (!resultV.success) {
                throw new Error(resultV.message || 'Error al registrar boucher de pago');
            }
            idVoucher = resultV.id_voucher;
            docJustificante = numOperacion || `${serie}-${correlativo}`;
            
        } else if (metodoPago === 'DONACION') {
            const resolucionFile = document.getElementById('procesar_resolucion_file').files[0];
            const numResolucion = document.getElementById('procesar_num_resolucion').value.trim();
            
            if (!numResolucion) {
                Swal.fire('Error', 'Ingrese el número de resolución de donación', 'error');
                return;
            }
            if (!resolucionFile) {
                Swal.fire('Error', 'Cargue el documento digitalizado de la resolución', 'error');
                return;
            }
            
            // Subir resolución
            const base64 = await fileToBase64(resolucionFile);
            const voucherPayload = {
                num_operation: numResolucion,
                monto_total: totalVenta,
                fecha_deposito: new Date().toISOString().substring(0, 10),
                archivo_base64: base64,
                archivo_nombre: resolucionFile.name
            };
            
            const resV = await fetch(`${BASE_URL}/produccion_agraria?action=guardar_voucher`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(voucherPayload)
            });
            const resultV = await resV.json();
            if (!resultV.success) {
                throw new Error(resultV.message || 'Error al registrar el documento de resolución');
            }
            idVoucher = resultV.id_voucher;
            docJustificante = numResolucion;
        }
        
        // Confirmar procesamiento final de la proforma
        const procesarPayload = {
            id_transaccion: id,
            metodo_pago: metodoPago,
            serie_comprobante: serie || null,
            correlativo_comprobante: correlativo || null,
            doc_justificante: docJustificante,
            id_voucher: idVoucher
        };
        
        const resP = await fetch(`${BASE_URL}/produccion_agraria?action=procesar_proforma`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(procesarPayload)
        });
        const resultP = await resP.json();
        
        if (resultP.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Procesada!',
                text: 'Proforma validada correctamente',
                timer: 2000,
                showConfirmButton: false
            }).then(() => location.reload());
        } else {
            throw new Error(resultP.message || 'Error al procesar la proforma');
        }
        
    } catch (err) {
        Swal.fire('Error', err.message, 'error');
    }
}

// Anular proforma
function anularProforma(id) {
    document.getElementById('anular-id').textContent = id;
    document.getElementById('anular-id-transaccion').value = id;
    document.getElementById('motivo-anulacion').value = '';
    new bootstrap.Modal(document.getElementById('modal-anular')).show();
}

function confirmarAnular() {
    const id = document.getElementById('anular-id-transaccion').value;
    const motivo = document.getElementById('motivo-anulacion').value;
    
    if (!motivo.trim()) {
        Swal.fire('Error', 'Ingrese el motivo de anulación', 'error');
        return;
    }
    
    Swal.fire({
        title: '¿Confirmar anulación?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, anular',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`${BASE_URL}/produccion_agraria?action=anular_proforma`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_transaccion: id, motivo: motivo })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Anulada', 'Proforma anulada correctamente', 'success')
                        .then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        }
    });
}

// Imprimir proforma
function imprimirProforma() {
    window.print();
}

// ========================================
// GESTIÓN DE VOUCHERS DE PAGO
// ========================================

let voucherActualId = null;

// Mostrar modal de Vouchers
function mostrarModalVouchers() {
    const modal = new bootstrap.Modal(document.getElementById('modal-vouchers'));
    modal.show();
    cargarVouchers();
}

// Cargar lista de vouchers
function cargarVouchers() {
    const tbody = document.getElementById('tabla-vouchers');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-3"><div class="spinner-border text-primary"></div></td></tr>';
    
    fetch(`${BASE_URL}/produccion_agraria?action=listar_vouchers`)
        .then(r => r.json())
        .then(data => {
            if (data.success && data.vouchers.length > 0) {
                tbody.innerHTML = data.vouchers.map(v => {
                    const asignadoClass = v.total_proformas > 0 ? 'text-success' : 'text-muted';
                    return `
                        <tr>
                            <td><strong>#${v.id_voucher}</strong></td>
                            <td>${v.num_operation || '-'}</td>
                            <td class="text-end fw-bold">S/ ${parseFloat(v.monto_total).toFixed(2)}</td>
                            <td class="text-center ${asignadoClass}">
                                ${v.total_proformas > 0 
                                    ? `<span class="badge bg-success">${v.total_proformas} proforma(s)</span>` 
                                    : '<span class="badge bg-warning">Pendiente</span>'}
                            </td>
                            <td class="text-end">S/ ${parseFloat(v.monto_asignado || 0).toFixed(2)}</td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="${BASE_URL}/produccion_agraria?action=descargar_voucher&id=${v.id_voucher}" 
                                       class="btn btn-sm btn-info ${!v.tiene_archivo ? 'disabled' : ''}" 
                                       title="Descargar archivo"
                                       target="_blank">
                                        <i class="ti ti-download"></i>
                                    </a>
                                    <button class="btn btn-sm btn-secondary" onclick="editarVoucher(${v.id_voucher}, '${(v.num_operation || '').replace(/'/g, "\\'")}', ${v.monto_total}, '${v.fecha_deposito || ''}')" title="Editar voucher">
                                        <i class="ti ti-pencil"></i>
                                    </button>
                                    ${v.total_proformas > 0 
                                        ? `<button class="btn btn-sm btn-warning" onclick="desasignarVoucher(${v.id_voucher}, ${v.total_proformas})" title="Des-asignar de proformas">
                                            <i class="ti ti-link-off"></i>
                                        </button>` 
                                        : ''}
                                    <button class="btn btn-sm btn-primary" onclick="mostrarModalAsignarVoucher(${v.id_voucher}, ${v.monto_total})" title="Asignar a proformas">
                                        <i class="ti ti-link"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="eliminarVoucher(${v.id_voucher}, ${v.total_proformas})" title="Eliminar voucher">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No hay vouchers registrados</td></tr>';
            }
        })
        .catch(err => {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-3 text-danger">Error al cargar vouchers</td></tr>';
        });
}

// Mostrar modal para subir nuevo voucher
function mostrarModalNuevoVoucher() {
    document.getElementById('form-voucher').reset();
    document.getElementById('preview-voucher').innerHTML = '';
    new bootstrap.Modal(document.getElementById('modal-nuevo-voucher')).show();
}

// Previsualizar archivo de voucher
function previewVoucher(input) {
    const preview = document.getElementById('preview-voucher');
    if (input.files && input.files[0]) {
        const file = input.files[0];
        preview.innerHTML = `<div class="alert alert-info"><i class="ti ti-file me-2"></i>${file.name} (${(file.size/1024).toFixed(1)} KB)</div>`;
    }
}

// Guardar nuevo voucher (con archivo BLOB)
function guardarVoucher() {
    const form = document.getElementById('form-voucher');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const formData = new FormData(form);
    const fileInput = form.querySelector('input[type="file"]');
    const file = fileInput.files[0];
    
    // Función para enviar datos al servidor
    const enviarDatos = (data) => {
        fetch(`${BASE_URL}/produccion_agraria?action=guardar_voucher`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(result => {
            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Guardado!',
                    text: 'Voucher registrado correctamente',
                    timer: 1500,
                    showConfirmButton: false
                });
                bootstrap.Modal.getInstance(document.getElementById('modal-nuevo-voucher')).hide();
                cargarVouchers();
            } else {
                Swal.fire('Error', result.message, 'error');
            }
        });
    };
    
    // Preparar datos base
    const data = {
        num_operation: formData.get('num_operation'),
        monto_total: parseFloat(formData.get('monto_total')),
        fecha_deposito: formData.get('fecha_deposito')
    };
    
    // Si hay archivo, convertir a base64
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            // Obtener solo la parte base64 (quitar el prefijo "data:...;base64,")
            const base64String = e.target.result.split(',')[1];
            data.archivo_base64 = base64String;
            data.archivo_nombre = file.name;
            enviarDatos(data);
        };
        reader.readAsDataURL(file);
    } else {
        enviarDatos(data);
    }
}

// Mostrar modal para asignar voucher a proformas
function mostrarModalAsignarVoucher(idVoucher, montoTotal) {
    voucherActualId = idVoucher;
    document.getElementById('voucher-monto-total').textContent = montoTotal.toFixed(2);
    document.getElementById('voucher-monto-disponible').textContent = montoTotal.toFixed(2);
    document.getElementById('lista-proformas-asignar').innerHTML = '<tr><td colspan="4" class="text-center py-3"><div class="spinner-border"></div></td></tr>';
    
    new bootstrap.Modal(document.getElementById('modal-asignar-voucher')).show();
    cargarProformasDisponibles();
}

// Cargar proformas disponibles para asignar (agrupadas por num_grupo)
function cargarProformasDisponibles() {
    fetch(`${BASE_URL}/produccion_agraria?action=listar_proformas_disponibles`)
        .then(r => r.json())
        .then(data => {
            const tbody = document.getElementById('lista-proformas-asignar');
            if (!data.success || !data.proformas.length) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center py-3 text-muted">No hay proformas pendientes disponibles</td></tr>';
                return;
            }

            const proformas = data.proformas;
            let html = '';

            // Separar: agrupadas por num_grupo y sueltas
            const grupos = {};
            const sueltas = [];
            proformas.forEach(p => {
                if (p.num_grupo && p.num_grupo.trim() !== '') {
                    if (!grupos[p.num_grupo]) grupos[p.num_grupo] = [];
                    grupos[p.num_grupo].push(p);
                } else {
                    sueltas.push(p);
                }
            });

            // Renderizar grupos (ventas masivas)
            const clavesGrupo = Object.keys(grupos).sort();
            clavesGrupo.forEach(grupo => {
                const items = grupos[grupo];
                const totalGrupo = items.reduce((s, p) => s + parseFloat(p.total), 0);
                const ids = items.map(p => p.id_transaccion);
                const fechaVenta = items[0].fecha_creacion ? new Date(items[0].fecha_creacion).toLocaleDateString('es-PE', {day:'2-digit',month:'2-digit',year:'numeric'}) : '';

                html += `
                    <tr class="table-success" style="background-color: rgba(40, 167, 69, 0.08);">
                        <td>
                            <input type="checkbox" class="form-check-input grupo-check"
                                   data-grupo="${grupo}"
                                   data-ids="${ids.join(',')}"
                                   data-monto="${totalGrupo.toFixed(2)}"
                                   onchange="toggleGrupo(this)">
                        </td>
                        <td colspan="2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-success"><i class="ti ti-package me-1"></i>Venta Masiva</span>
                                <strong class="text-dark">${items.length} ventas</strong>
                                <span class="text-muted">— ${fechaVenta}</span>
                                <code class="ms-2" style="font-size:0.7rem;">${grupo}</code>
                            </div>
                        </td>
                        <td class="text-end fw-bold text-success">S/ ${totalGrupo.toFixed(2)}</td>
                    </tr>`;

                // Filas individuales del grupo (ocultas por defecto)
                items.forEach(p => {
                    const nombre = p.nombre_cliente || 'N/A';
                    html += `
                        <tr class="grupo-item-${grupo}" style="display:none; background-color: rgba(40, 167, 69, 0.03);">
                            <td>
                                <input type="checkbox" class="form-check-input proforma-check item-grupo-${grupo}"
                                       value="${p.id_transaccion}"
                                       data-monto="${p.total}"
                                       onchange="calcularMontoSeleccionado()">
                            </td>
                            <td><strong>#${p.id_transaccion}</strong></td>
                            <td>${nombre}</td>
                            <td class="text-end fw-bold">S/ ${parseFloat(p.total).toFixed(2)}</td>
                        </tr>`;
                });
            });

            // Renderizar proformas sueltas (individuales)
            sueltas.forEach(p => {
                const nombre = p.nombre_cliente || 'N/A';
                html += `
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input proforma-check"
                                   value="${p.id_transaccion}"
                                   data-monto="${p.total}"
                                   onchange="calcularMontoSeleccionado()">
                        </td>
                        <td><strong>#${p.id_transaccion}</strong></td>
                        <td>${nombre}</td>
                        <td class="text-end fw-bold">S/ ${parseFloat(p.total).toFixed(2)}</td>
                    </tr>`;
            });

            tbody.innerHTML = html;
        });
}

// Toggle: expandir/colapsar items de un grupo
function toggleGrupo(chk) {
    const grupo = chk.dataset.grupo;
    const items = document.querySelectorAll('.grupo-item-' + CSS.escape(grupo));
    const itemChecks = document.querySelectorAll('.item-grupo-' + CSS.escape(grupo));

    if (chk.checked) {
        // Seleccionar todos los items del grupo
        items.forEach(row => row.style.display = '');
        itemChecks.forEach(c => c.checked = true);
    } else {
        // Deseleccionar y ocultar
        items.forEach(row => row.style.display = 'none');
        itemChecks.forEach(c => c.checked = false);
    }
    calcularMontoSeleccionado();
}

// Calcular monto total seleccionado
function calcularMontoSeleccionado() {
    const checks = document.querySelectorAll('.proforma-check:checked');
    let total = 0;
    checks.forEach(c => total += parseFloat(c.dataset.monto));
    
    const montoTotal = parseFloat(document.getElementById('voucher-monto-total').textContent);
    const disponible = montoTotal - total;
    
    const inputSeleccionado = document.getElementById('voucher-monto-seleccionado');
    if (inputSeleccionado) {
        inputSeleccionado.textContent = total.toFixed(2);
    }
    
    document.getElementById('voucher-monto-disponible').textContent = disponible.toFixed(2);
    
    const container = document.getElementById('voucher-diferencia-container');
    if (container) {
        if (disponible < 0) {
            container.className = 'h3 mb-0 text-danger fw-bold';
        } else {
            container.className = 'h3 mb-0 text-success fw-bold';
        }
    }
}

// Confirmar asignación de voucher
function confirmarAsignacionVoucher() {
    const checks = document.querySelectorAll('.proforma-check:checked');
    if (checks.length === 0) {
        Swal.fire('Error', 'Seleccione al menos una proforma', 'error');
        return;
    }
    
    const idsTransacciones = Array.from(checks).map(c => parseInt(c.value));
    const totalSeleccionado = Array.from(checks).reduce((sum, c) => sum + parseFloat(c.dataset.monto), 0);
    const montoVoucher = parseFloat(document.getElementById('voucher-monto-total').textContent);
    
    Swal.fire({
        title: '¿Confirmar asignación?',
        html: `Se asignará el documento a <strong>${idsTransacciones.length} proforma(s)</strong>.<br><br>
               Monto de Documento: <strong>S/ ${montoVoucher.toFixed(2)}</strong><br>
               Total de Proformas: <strong>S/ ${totalSeleccionado.toFixed(2)}</strong>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, asignar y validar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`${BASE_URL}/produccion_agraria?action=asignar_voucher_proformas`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_voucher: voucherActualId,
                    ids_transacciones: idsTransacciones
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Asignado!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    bootstrap.Modal.getInstance(document.getElementById('modal-asignar-voucher')).hide();
                    cargarVouchers();
                    // Recargar proformas para actualizar estados
                    setTimeout(() => location.reload(), 1500);
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        }
    });
}

// Des-asignar voucher de todas las proformas vinculadas
function desasignarVoucher(idVoucher, totalProformas) {
    Swal.fire({
        title: '¿Des-asignar voucher?',
        html: `El voucher <strong>#${idVoucher}</strong> será desvinculado de <strong>${totalProformas} proforma(s)</strong>.<br><br>
               Las proformas volverán al estado <strong>PENDIENTE</strong>.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, des-asignar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#f59e0b'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`${BASE_URL}/produccion_agraria?action=desasignar_voucher`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_voucher: idVoucher })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Des-asignado!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    cargarVouchers();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        }
    });
}

// Eliminar voucher completamente
function eliminarVoucher(idVoucher, totalProformas) {
    let mensajeExtra = '';
    if (totalProformas > 0) {
        mensajeExtra = `<br><br><span class="text-danger"><strong>Advertencia:</strong> Este voucher está asignado a ${totalProformas} proforma(s). Al eliminarlo, las proformas volverán a estado PENDIENTE.</span>`;
    }
    
    Swal.fire({
        title: '¿Eliminar voucher?',
        html: `Se eliminará permanentemente el voucher <strong>#${idVoucher}</strong> y su archivo adjunto.${mensajeExtra}`,
        icon: 'error',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc3545'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`${BASE_URL}/produccion_agraria?action=eliminar_voucher`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_voucher: idVoucher })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Eliminado!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    cargarVouchers();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        }
    });
}

// Des-asignar voucher desde el detalle de una proforma
function desasignarVoucherDesdeProforma(idVoucher) {
    Swal.fire({
        title: '¿Des-asignar voucher?',
        html: `Se desvinculará el voucher <strong>#${idVoucher}</strong> de esta proforma.<br>La proforma volverá al estado <strong>PENDIENTE</strong>.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, des-asignar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#f59e0b'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`${BASE_URL}/produccion_agraria?action=desasignar_voucher`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_voucher: idVoucher })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Des-asignado!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    bootstrap.Modal.getInstance(document.getElementById('modal-detalle')).hide();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        }
    });
}

// Editar voucher (abrir modal con datos)
function editarVoucher(idVoucher, numOperation, montoTotal, fechaDeposito) {
    document.getElementById('editar-voucher-id').textContent = idVoucher;
    document.getElementById('editar-id-voucher').value = idVoucher;
    document.getElementById('editar-num-operation').value = numOperation;
    document.getElementById('editar-monto-total').value = parseFloat(montoTotal).toFixed(2);
    document.getElementById('editar-fecha-deposito').value = fechaDeposito ? fechaDeposito.substring(0, 10) : '';
    new bootstrap.Modal(document.getElementById('modal-editar-voucher')).show();
}

// Guardar edición de voucher
function guardarEdicionVoucher() {
    const idVoucher = document.getElementById('editar-id-voucher').value;
    const numOperation = document.getElementById('editar-num-operation').value.trim();
    const montoTotal = parseFloat(document.getElementById('editar-monto-total').value);
    const fechaDeposito = document.getElementById('editar-fecha-deposito').value;
    
    if (!numOperation || !montoTotal || !fechaDeposito) {
        Swal.fire('Error', 'Complete todos los campos obligatorios', 'error');
        return;
    }
    
    Swal.fire({
        title: 'Guardando cambios...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    fetch(`${BASE_URL}/produccion_agraria?action=actualizar_voucher`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            id_voucher: idVoucher,
            num_operation: numOperation,
            monto_total: montoTotal,
            fecha_deposito: fechaDeposito
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Actualizado!',
                text: data.message,
                timer: 1500,
                showConfirmButton: false
            });
            bootstrap.Modal.getInstance(document.getElementById('modal-editar-voucher')).hide();
            cargarVouchers();
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire('Error', 'Error al conectar con el servidor', 'error');
    });
}

// Listeners dinámicos de interfaz para Venta vs Donación
document.addEventListener('change', function(e) {
    // 1. Toggler para Modal Procesar Proforma
    if (e.target && e.target.name === 'metodo_pago') {
        const metodo = e.target.value;
        const seccionVenta = document.getElementById('seccion-venta-boucher');
        const seccionDonacion = document.getElementById('seccion-donacion-resolucion');
        const selectComprobante = document.getElementById('tipo_comprobante');
        const divComprobante = selectComprobante ? selectComprobante.closest('.mb-3') : null;
        const camposComprobante = document.getElementById('campos-comprobante');
        
        if (metodo === 'VENTA') {
            if (seccionVenta) seccionVenta.style.display = 'block';
            if (seccionDonacion) seccionDonacion.style.display = 'none';
            if (divComprobante) divComprobante.style.display = 'block';
            if (selectComprobante) selectComprobante.value = '';
            if (camposComprobante) camposComprobante.style.display = 'none';
        } else if (metodo === 'DONACION') {
            if (seccionVenta) seccionVenta.style.display = 'none';
            if (seccionDonacion) seccionDonacion.style.display = 'block';
            if (divComprobante) divComprobante.style.display = 'none';
            if (camposComprobante) camposComprobante.style.display = 'none';
            if (selectComprobante) selectComprobante.value = ''; // Donaciones no emiten comprobantes comerciales
        }
    }
    
    // 2. Toggler para Formulario de Subir Nuevo Voucher/Resolución
    if (e.target && e.target.name === 'tipo_doc_subir') {
        const type = e.target.value;
        const numLabel = document.getElementById('form-voucher-num-label');
        const numInput = document.getElementById('form-voucher-num-input');
        const totalLabel = document.getElementById('form-voucher-monto-label');
        const dateLabel = document.getElementById('form-voucher-fecha-label');
        const fileLabel = document.getElementById('form-voucher-archivo-label');
        
        if (type === 'VENTA') {
            if (numLabel) numLabel.innerHTML = 'Número de Operación <span class="text-danger">*</span>';
            if (numInput) numInput.placeholder = 'Ej: 123456789';
            if (totalLabel) totalLabel.innerHTML = 'Monto del Voucher (S/) <span class="text-danger">*</span>';
            if (dateLabel) dateLabel.innerHTML = 'Fecha de Depósito <span class="text-danger">*</span>';
            if (fileLabel) fileLabel.innerHTML = 'Imagen/PDF del Boucher <span class="text-danger">*</span>';
        } else {
            if (numLabel) numLabel.innerHTML = 'Número de Resolución <span class="text-danger">*</span>';
            if (numInput) numInput.placeholder = 'Ej: R.D. N° 045-2026-CH';
            if (totalLabel) totalLabel.innerHTML = 'Valor de la Donación (S/) <span class="text-danger">*</span>';
            if (dateLabel) dateLabel.innerHTML = 'Fecha de Resolución <span class="text-danger">*</span>';
            if (fileLabel) fileLabel.innerHTML = 'Documento de Resolución (PDF/Imagen) <span class="text-danger">*</span>';
        }
    }
});
</script>

<!-- MODAL: Vouchers de Pago -->
<div class="modal fade" id="modal-vouchers" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="ti ti-file-invoice me-2"></i>Vouchers de Pago</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between mb-3">
                    <p class="text-muted mb-0">Gestione los vouchers de pago y asígnelos a proformas para validarlas.</p>
                    <button class="btn btn-success" onclick="mostrarModalNuevoVoucher()">
                        <i class="ti ti-plus me-1"></i>Subir Voucher
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>N° Operación</th>
                                <th class="text-end">Monto Total</th>
                                <th class="text-center">Estado</th>
                                <th class="text-end">Monto Asignado</th>
                                <th class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-vouchers">
                            <tr><td colspan="6" class="text-center py-3 text-muted">Cargando vouchers...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Subir Nuevo Voucher -->
<div class="modal fade" id="modal-nuevo-voucher" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="ti ti-upload me-2"></i>Subir Voucher de Pago</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="form-voucher" onsubmit="event.preventDefault(); guardarVoucher();">
                    <div class="mb-3 border-bottom pb-3">
                        <label class="form-label required">Tipo de Documento</label>
                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo_doc_subir" id="tds_venta" value="VENTA" checked>
                                <label class="form-check-label fw-bold text-primary" for="tds_venta">
                                    <i class="ti ti-shopping-cart me-1"></i>Boucher de Venta
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo_doc_subir" id="tds_donacion" value="DONACION">
                                <label class="form-check-label fw-bold text-success" for="tds_donacion">
                                    <i class="ti ti-gift me-1"></i>Resolución de Donación
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" id="form-voucher-num-label">Número de Operación <span class="text-danger">*</span></label>
                        <input type="text" name="num_operation" id="form-voucher-num-input" class="form-control" required placeholder="Ej: 123456789">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" id="form-voucher-monto-label">Monto del Voucher (S/) <span class="text-danger">*</span></label>
                        <input type="number" name="monto_total" class="form-control" required step="0.01" min="0.01" placeholder="0.00">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" id="form-voucher-fecha-label">Fecha de Depósito <span class="text-danger">*</span></label>
                        <input type="date" name="fecha_deposito" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" id="form-voucher-archivo-label">Imagen/PDF del Boucher <span class="text-danger">*</span></label>
                        <input type="file" name="archivo" class="form-control" required accept=".pdf,.jpg,.jpeg,.png" onchange="previewVoucher(this)">
                        <div id="preview-voucher" class="mt-2"></div>
                        <small class="text-muted">Obligatorio. Máx 5MB.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="guardarVoucher()">
                    <i class="ti ti-device-floppy me-1"></i>Guardar Voucher
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Editar Voucher -->
<div class="modal fade" id="modal-editar-voucher" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title"><i class="ti ti-pencil me-2"></i>Editar Voucher #<span id="editar-voucher-id"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="form-editar-voucher" onsubmit="event.preventDefault(); guardarEdicionVoucher();">
                    <input type="hidden" id="editar-id-voucher">
                    <div class="mb-3">
                        <label class="form-label">Número de Operación / Resolución <span class="text-danger">*</span></label>
                        <input type="text" id="editar-num-operation" class="form-control" required placeholder="Ej: 123456789">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Monto (S/) <span class="text-danger">*</span></label>
                        <input type="number" id="editar-monto-total" class="form-control" required step="0.01" min="0.01" placeholder="0.00">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha de Depósito / Resolución <span class="text-danger">*</span></label>
                        <input type="date" id="editar-fecha-deposito" class="form-control" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" onclick="guardarEdicionVoucher()">
                    <i class="ti ti-device-floppy me-1"></i>Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Asignar Voucher a Proformas -->
<div class="modal fade" id="modal-asignar-voucher" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="ti ti-link me-2"></i>Asignar Voucher a Proformas</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="card bg-primary-lt border-0 shadow-sm mb-3">
                    <div class="card-body p-3">
                        <div class="row text-center">
                            <div class="col-4 border-end">
                                <div class="text-muted-lt small mb-1 fw-bold text-uppercase">Monto de Documento</div>
                                <div class="h3 mb-0 text-primary fw-bold">S/ <span id="voucher-monto-total">0.00</span></div>
                            </div>
                            <div class="col-4 border-end">
                                <div class="text-muted-lt small mb-1 fw-bold text-uppercase">Total Seleccionado</div>
                                <div class="h3 mb-0 text-success fw-bold">S/ <span id="voucher-monto-seleccionado">0.00</span></div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted-lt small mb-1 fw-bold text-uppercase">Diferencia / Restante</div>
                                <div class="h3 mb-0 text-success fw-bold" id="voucher-diferencia-container">S/ <span id="voucher-monto-disponible">0.00</span></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <p class="text-muted mb-2">Seleccione las proformas que serán validadas con este voucher:</p>
                
                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-striped table-sm">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th width="40"></th>
                                <th>N° Proforma</th>
                                <th>Cliente</th>
                                <th class="text-end">Monto</th>
                            </tr>
                        </thead>
                        <tbody id="lista-proformas-asignar">
                            <tr><td colspan="4" class="text-center py-3 text-muted">Cargando proformas...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="confirmarAsignacionVoucher()">
                    <i class="ti ti-check me-1"></i>Asignar y Validar
                </button>
            </div>
        </div>
    </div>
</div>
