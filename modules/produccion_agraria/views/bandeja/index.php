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
                        <div class="col-4">
                            <div class="form-check card p-2 text-center">
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

function confirmarProcesar() {
    const id = document.getElementById('procesar-id-transaccion').value;
    const metodoPago = document.querySelector('input[name="metodo_pago"]:checked')?.value;
    const tipoComprobante = document.getElementById('tipo_comprobante').value;
    const serie = document.getElementById('serie_comprobante').value;
    const correlativo = document.getElementById('correlativo_comprobante').value;
    
    if (!metodoPago) {
        Swal.fire('Error', 'Seleccione un método de pago', 'error');
        return;
    }
    if (!tipoComprobante) {
        Swal.fire('Error', 'Seleccione el tipo de comprobante', 'error');
        return;
    }
    if (!serie || !correlativo) {
        Swal.fire('Error', 'Complete la serie y correlativo', 'error');
        return;
    }
    
    const data = {
        id_transaccion: id,
        metodo_pago: metodoPago,
        serie_comprobante: serie,
        correlativo_comprobante: correlativo,
        doc_justificante: `${serie}-${correlativo}`
    };
    
    fetch(`${BASE_URL}/produccion_agraria?action=procesar_proforma`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Procesada!',
                text: 'Proforma procesada correctamente',
                timer: 2000,
                showConfirmButton: false
            }).then(() => location.reload());
        } else {
            Swal.fire('Error', result.message, 'error');
        }
    });
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
                                       class="btn btn-sm btn-info ${!v.url_imagen ? 'disabled' : ''}" 
                                       title="Descargar archivo"
                                       target="_blank">
                                        <i class="ti ti-download"></i>
                                    </a>
                                    ${v.total_proformas === 0 
                                        ? `<button class="btn btn-sm btn-primary" onclick="mostrarModalAsignarVoucher(${v.id_voucher}, ${v.monto_total})">
                                            <i class="ti ti-link me-1"></i>Asignar
                                        </button>` 
                                        : ''}
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

// Cargar proformas disponibles para asignar
function cargarProformasDisponibles() {
    fetch(`${BASE_URL}/produccion_agraria?action=listar_proformas_disponibles`)
        .then(r => r.json())
        .then(data => {
            const tbody = document.getElementById('lista-proformas-asignar');
            if (data.success && data.proformas.length > 0) {
                tbody.innerHTML = data.proformas.map(p => `
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input proforma-check" 
                                   value="${p.id_transaccion}" 
                                   data-monto="${p.total}"
                                   onchange="calcularMontoSeleccionado()">
                        </td>
                        <td><strong>#${p.id_transaccion}</strong></td>
                        <td>${p.nombre_cliente || 'N/A'}</td>
                        <td class="text-end fw-bold">S/ ${parseFloat(p.total).toFixed(2)}</td>
                    </tr>
                `).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center py-3 text-muted">No hay proformas pendientes disponibles</td></tr>';
            }
        });
}

// Calcular monto total seleccionado
function calcularMontoSeleccionado() {
    const checks = document.querySelectorAll('.proforma-check:checked');
    let total = 0;
    checks.forEach(c => total += parseFloat(c.dataset.monto));
    
    const montoTotal = parseFloat(document.getElementById('voucher-monto-total').textContent);
    const disponible = montoTotal - total;
    
    document.getElementById('voucher-monto-disponible').textContent = disponible.toFixed(2);
    document.getElementById('voucher-monto-disponible').className = disponible < 0 ? 'text-danger fw-bold' : 'text-success fw-bold';
}

// Confirmar asignación de voucher
function confirmarAsignacionVoucher() {
    const checks = document.querySelectorAll('.proforma-check:checked');
    if (checks.length === 0) {
        Swal.fire('Error', 'Seleccione al menos una proforma', 'error');
        return;
    }
    
    const disponible = parseFloat(document.getElementById('voucher-monto-disponible').textContent);
    if (disponible < 0) {
        Swal.fire('Error', 'El monto total de proformas excede el voucher', 'error');
        return;
    }
    
    const idsTransacciones = Array.from(checks).map(c => parseInt(c.value));
    
    Swal.fire({
        title: '¿Confirmar asignación?',
        text: `Se asignará el voucher a ${idsTransacciones.length} proforma(s)`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, asignar',
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
                    <div class="mb-3">
                        <label class="form-label">Número de Operación <span class="text-danger">*</span></label>
                        <input type="text" name="num_operation" class="form-control" required placeholder="Ej: 123456789">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Monto Total (S/) <span class="text-danger">*</span></label>
                        <input type="number" name="monto_total" class="form-control" required step="0.01" min="0.01" placeholder="0.00">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha de Depósito <span class="text-danger">*</span></label>
                        <input type="date" name="fecha_deposito" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Archivo (PDF/Imagen)</label>
                        <input type="file" name="archivo" class="form-control" accept=".pdf,.jpg,.jpeg,.png" onchange="previewVoucher(this)">
                        <div id="preview-voucher" class="mt-2"></div>
                        <small class="text-muted">Opcional. Máx 5MB.</small>
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

<!-- MODAL: Asignar Voucher a Proformas -->
<div class="modal fade" id="modal-asignar-voucher" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="ti ti-link me-2"></i>Asignar Voucher a Proformas</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info d-flex justify-content-between">
                    <span><strong>Monto del Voucher:</strong> S/ <span id="voucher-monto-total">0.00</span></span>
                    <span><strong>Disponible:</strong> S/ <span id="voucher-monto-disponible" class="text-success fw-bold">0.00</span></span>
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
