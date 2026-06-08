<style>
    .doc-slider {
        display: flex;
        overflow-x: auto;
        gap: 1rem;
        padding: 0.5rem 0.5rem 1.5rem 0.5rem;
        scroll-snap-type: x mandatory;
        -webkit-overflow-scrolling: touch;
    }
    .doc-slider::-webkit-scrollbar {
        height: 8px;
    }
    .doc-slider::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    .doc-card {
        flex: 0 0 180px;
        scroll-snap-align: start;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        overflow: hidden;
        background: #fff;
        transition: transform 0.2s;
    }
    .doc-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .doc-preview {
        height: 140px;
        background: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border-bottom: 1px solid #dee2e6;
        position: relative;
    }
    .doc-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .doc-info {
        padding: 0.75rem;
        text-align: center;
    }
    .doc-desc {
        font-size: 0.85rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 0.25rem;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .doc-meta {
        font-size: 0.7rem;
        color: #6c757d;
    }
</style>
<?php
if ($user['role']=="supervisor_general"):
    echo '<div class="card bg-dark border-secondary p-4 mb-4">';
    if (!$validacion_usuario){
        echo '
        Usuario sin permisos
        ';
    } else{
        foreach ($validacion_usuario as $dato_usuario){
        $nombre_supervisor = $dato_usuario['nombre_supervisor'];
        $usuario_supervisor = $dato_usuario['usuario_supervisor'];
        $monto_autorizado  = number_format($dato_usuario['monto_autorizado']??0,2,'.',',');
        $fecha_autorizado  = $dato_usuario['fecha_autorizacion'];
        $fecha_vencimiento = $dato_usuario['fecha_vencimiento'];
        $nombre_admin      = $dato_usuario['nombre_admin'];
        $fecha_autorizacion= $dato_usuario['fecha_autorizacion'];
        $fecha_vencimiento = $dato_usuario['fecha_vencimiento'];
        $observacion       = $dato_usuario['observacion'];
        $estado            = $dato_usuario['estado'];

        echo "💵Monto autorizado:Q$monto_autorizado 
        📅Fecha autorizado:$fecha_autorizacion 🔚Vencimiento:$fecha_vencimiento 🔝Atorizado por:$nombre_admin";
        ?>

        <?php
        }
    }
    endif;
    echo '</div>';
?>
<!-- views/pagos/validar.php -->
<div class="card bg-dark border-secondary p-4 mb-4">
    <h4 class="mb-3 fw-bold">✅ Validación de Pagos</h4>

    <p class="text-secondary small">
        Valide los pagos reportados por los gestores (PAGG) ingresando la referencia bancaria.
    </p>

    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?= $tipoMensaje ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($mensaje) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ✅ Filtros Avanzados -->
    <form method="GET"  class="row g-3 mb-4">
        <input type="hidden" name="action" value="validar_pagos">
        <div class="col-md-3">
            <label class="form-label small text-secondary">Gestor</label>
            <select name="gestor_id" class="form-select bg-dark text-white border-secondary">
                <option value="">Todos los gestores</option>
                <?php foreach($listaGestores as $g): ?>
                    <option value="<?= $g['id'] ?>" <?= ($filtroGestor == $g['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($g['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small text-secondary">Supervisor</label>
            <select name="supervisor_id" class="form-select bg-dark text-white border-secondary">
                <option value="">Todos los supervisores</option>
                <?php foreach($listaSupervisores as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= ($filtroSupervisor == $s['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small text-secondary">Desde</label>
            <input type="date" name="fecha_inicio" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($filtroFechaInicio) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label small text-secondary">Hasta</label>
            <input type="date" name="fecha_fin" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($filtroFechaFin) ?>">
        </div>
        <div class="col-md-2">

            <label class="form-label small text-secondary">Buscar</label>
            <input type="text" name="buscar" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($buscar) ?>">
        </div>
        
        <div class="col-md-2 d-flex align-items-end gap-2">

            <button type="submit" class="btn btn-lex-primary flex-grow-1">🔍 Filtrar</button>
            
            <a href="?action=validar_pagos" class="btn btn-outline-secondary" title="Limpiar filtros">🔄</a>
        </div>
    </form>
    
</div>

<!-- ✅ Tabla de Pendientes -->
<div class="card bg-dark border-secondary p-4">
    <h5 class="mb-3 fw-bold">
        <button class="btn btn-success"
                onclick="exportarTablaExcel(
                    'validacion-pagos',
                    'validacion_pagos_pendientes_validar',
                    'Detalle de pagos pendientes de validar'
                    )">
                Exportar Excel
            </button>
        📋 Pagos Pendientes de Validar                
</h5>
    
    <?php if (!empty($pendientes)): ?>
    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle mb-0" id ="validacion-pagos">
            <thead>
                <tr>
                    <th class="text-secondary text-uppercase small">Cliente</th>
                    <th class="text-secondary text-uppercase small">Identificación</th>
                    <th class="text-secondary text-uppercase small">Saldo</th>
                    <th class="text-secondary text-uppercase small">Gestor</th>
                    <th class="text-secondary text-uppercase small">Supervisor</th>
                    <th class="text-secondary text-uppercase small">Fecha</th>
                    <th class="text-secondary text-uppercase small">Monto</th>
                    <th class="text-secondary text-uppercase small">Comentario</th>
                    <th class="text-center text-secondary text-uppercase small">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($pendientes as $p): ?>
                <tr>
                    <td class="fw-medium"><?= htmlspecialchars($p['nombre']) ?></td>
                    <td><?= htmlspecialchars($p['identificacion']) ?></td>
                    <td class="text-warning">Q<?= number_format($p['saldo'], 2) ?></td>
                    <td><?= htmlspecialchars($p['gestor']) ?></td>
                    <td><?= htmlspecialchars($p['supervisor'] ?? '-') ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($p['fecha_gestion'])) ?></td>
                    <td class="text-success fw-bold">Q<?= number_format($p['monto'], 2) ?></td>
                    <td><?= htmlspecialchars($p['comentario']) ?></td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-success" 
                                onclick="abrirModalValidar(<?= $p['pago_id'] ?>, '<?= addslashes($p['nombre']) ?>', <?= $p['monto'] ?>, '<?= addslashes($p['comentario']) ?>')">
                            ✅ Validar
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="alert alert-info border-secondary bg-dark text-light text-center py-4">
        <i class="bi bi-check-circle me-2"></i>
        No hay pagos pendientes de validación con los filtros actuales.
    </div>
    <?php endif; ?>
</div>

<!-- ✅ Modal de Validación Individual -->
<div class="modal fade" id="modalValidarPago" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border-secondary">
            <form id="formValidarPago">
                <input type="hidden" id="pagoIdInput" name="pago_id">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title text-white">✅ Validar Pago</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-secondary small mb-3">
                        Cliente: <strong id="modalClienteNombre" class="text-info"></strong><br>
                        Monto: <strong class="text-success" id="modalMonto"></strong><br>
                        Comentario: <span class="text-secondary" id="modalComentario"></span>
                    </p>
                    
                    <div class="mb-3">
                        <label class="form-label text-secondary">🏦 Referencia Bancaria *</label>
                        <input type="text" name="referencia_bancaria" id="inputReferencia" 
                               class="form-control bg-dark text-white border-secondary" 
                               placeholder="Ej: Boleta #12345, Banco XYZ, Transferencia ABC"
                               required maxlength="100">
                        <small class="text-secondary">Número de boleta, referencia de transferencia o comprobante</small>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <!-- ✅ NUEVO: Botón de Soportes -->
                    <button type="button" id="btnSoportesModal"
                            class="btn btn-sm btn-info text-white d-inline-flex align-items-center gap-1">
                        <i class="bi bi-folder2-open"></i> 
                        <span>Soportes</span>
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success fw-bold">💾 Confirmar Validación</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ✅ NUEVO: Modal de Soportes -->
<div class="modal fade" id="modalSoportes" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-folder2-open me-2"></i>Gestión de Soportes</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Resumen del pago -->
                <div class="alert alert-light border mb-3 d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Cliente</small>
                        <div class="fw-semibold" id="modalClienteSoporte">-</div>
                    </div>
                    <div class="text-end">
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Monto</small>
                        <div class="fw-semibold text-success fs-5" id="modalMontoSoporte">-</div>
                    </div>
                </div>

                <!-- SECCIÓN 1: CARRUSEL DE DOCUMENTOS EXISTENTES -->
                <h6 class="border-bottom pb-2 mb-3"><i class="bi bi-images me-1"></i> Documentos Adjuntos</h6>
                
                <div id="contenedorDocumentos" class="doc-slider">
                    <div class="text-center w-100 py-4 text-muted">
                        <div class="spinner-border spinner-border-sm" role="status"></div> Cargando...
                    </div>
                </div>

                <hr class="my-4">

                <!-- SECCIÓN 2: FORMULARIO DE SUBIDA -->
                <h6 class="border-bottom pb-2 mb-3"><i class="bi bi-upload me-1"></i> Agregar Nuevo Soporte</h6>
                
                <form id="formSubirSoporte" enctype="multipart/form-data" class="row g-3">
                    <input type="hidden" name="id_pago" id="inputIdPago">
                    
                    <div class="col-md-8">
                        <label class="form-label small fw-bold">Descripción</label>
                        <input type="text" class="form-control form-control-sm" name="descripcion" id="descripcion" placeholder="Ej: Transferencia bancaria" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Archivo (Max 5MB)</label>
                        <input type="file" class="form-control form-control-sm" name="archivo" id="archivo" accept=".pdf,.jpg,.jpeg,.png" required>
                    </div>
                    
                    <div class="col-12">
                        <div id="mensajeAlerta" class="d-none"></div>
                    </div>
                    
                    <div class="col-12 text-end">
                        <button type="button" class="btn btn-primary btn-sm" onclick="enviarSoporte()">
                            <i class="bi bi-check-circle me-1"></i> Subir Documento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ✅ Script para Modal y AJAX -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalEl = document.getElementById('modalValidarPago');
    if (!modalEl) return;
    const modal = new bootstrap.Modal(modalEl);
    const form = document.getElementById('formValidarPago');
    const btnSoportes = document.getElementById('btnSoportesModal');

    // Función global para abrir modal
    window.abrirModalValidar = function(pagoId, clienteNombre, monto, comentario) {
        document.getElementById('pagoIdInput').value = pagoId;
        document.getElementById('modalClienteNombre').textContent = clienteNombre;
        document.getElementById('modalMonto').textContent = 'Q' + parseFloat(monto).toFixed(2);
        document.getElementById('modalComentario').textContent = comentario;
        document.getElementById('inputReferencia').value = comentario;
        
        // ✅ NUEVO: Configurar el botón de soportes con los datos correctos
        btnSoportes.onclick = function() {
            gestionarSoportes(pagoId, clienteNombre, 'Q' + parseFloat(monto).toFixed(2));
        };
        
        modal.show();
    };

    // Submit del formulario vía AJAX
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const btn = form.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '⏳ Validando...';

        fetch('?action=validar_pago', {
            method: 'POST',
            body: new FormData(form)
        })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            
            if (res.success) {
                modal.hide();
                // Mostrar mensaje y recargar
                const alert = document.createElement('div');
                alert.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3';
                alert.innerHTML = res.message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                document.body.appendChild(alert);
                setTimeout(() => location.reload(), 1500);
            } else {
                alert('❌ ' + res.message);
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            alert('❌ Error de conexión con el servidor');
        });
    });
});
</script>

<!-- ✅ NUEVO: Scripts para Soportes -->
<script>
function gestionarSoportes(id_pago, cliente, monto) {
    document.getElementById('modalClienteSoporte').textContent = cliente;
    document.getElementById('modalMontoSoporte').textContent = monto;
    document.getElementById('inputIdPago').value = id_pago;
    
    document.getElementById('formSubirSoporte').reset();
    document.getElementById('mensajeAlerta').className = 'd-none';

    const modal = new bootstrap.Modal(document.getElementById('modalSoportes'));
    modal.show();

    cargarDocumentosExistentes(id_pago);
}

function cargarDocumentosExistentes(id_pago) {
    const contenedor = document.getElementById('contenedorDocumentos');
    contenedor.innerHTML = '<div class="text-center w-100 py-4 text-muted"><div class="spinner-border spinner-border-sm"></div> Cargando...</div>';

    fetch(`?action=obtener_documentos&id_pago=${id_pago}`)
        .then(res => res.json())
        .then(docs => {
            if (docs.length === 0) {
                contenedor.innerHTML = `
                    <div class="text-center w-100 py-4 text-muted bg-light rounded">
                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                        <small>No hay documentos adjuntos aún.</small>
                    </div>`;
                return;
            }

            let html = '';
            docs.forEach(doc => {
                const esImagen = doc.tipo_archivo.startsWith('image/');
                const urlDescarga = `?action=descargar_pago&id=${doc.id}`;
                const tamanoMB = (doc.tamano / (1024 * 1024)).toFixed(2);
                const fecha = new Date(doc.fecha_subida).toLocaleDateString();
                
                const descEscape = escapeHtml(doc.descripcion);
                const nombreEscape = escapeHtml(doc.nombre_original);

                html += `
                <div class="doc-card">
                    <a href="${urlDescarga}" 
                    onclick="abrirPopup(this.href); return false;" 
                    class="text-decoration-none d-block">
                        <div class="doc-preview">
                            ${esImagen 
                                ? `<img src="${urlDescarga}" alt="Vista previa">` 
                                : `<i class="bi bi-file-earmark-pdf text-danger" style="font-size: 3rem;"></i>`
                            }
                        </div>
                    </a>
                    <div class="doc-info">
                        <div class="doc-desc" title="${descEscape}">${descEscape}</div>
                        <div class="doc-meta">
                            ${nombreEscape}<br>
                            ${tamanoMB} MB • ${fecha}
                        </div>
                    </div>
                </div>`;            });
            contenedor.innerHTML = html;
        })
        .catch(err => {
            console.error('Error al cargar documentos:', err);
            contenedor.innerHTML = '<div class="text-center w-100 py-4 text-danger">Error al cargar documentos.</div>';
        });
}

function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
        '\n': ' ',
        '\r': ' '
    };
    return String(text).replace(/[&<>"'\n\r]/g, m => map[m]);
}

function enviarSoporte() {
    const form = document.getElementById('formSubirSoporte');
    const formData = new FormData(form);
    const alertDiv = document.getElementById('mensajeAlerta');
    const btnSubmit = document.querySelector('#modalSoportes .btn-primary');

    if (!formData.get('descripcion').trim() || !formData.get('archivo').name) {
        mostrarAlerta('Por favor completa la descripción y selecciona un archivo.', 'danger');
        return;
    }

    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Subiendo...';

    fetch('?action=subir_soporte', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarAlerta(data.message, 'success');
            cargarDocumentosExistentes(document.getElementById('inputIdPago').value);
            document.getElementById('formSubirSoporte').reset();
        } else {
            mostrarAlerta(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarAlerta('Ocurrió un error de red. Inténtalo de nuevo.', 'danger');
    })
    .finally(() => {
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = '<i class="bi bi-check-circle me-1"></i> Subir Documento';
    });
}

function mostrarAlerta(mensaje, tipo) {
    const alertDiv = document.getElementById('mensajeAlerta');
    alertDiv.className = `alert alert-${tipo} mt-2`;
    alertDiv.textContent = mensaje;
    alertDiv.classList.remove('d-none');
}

function abrirPopup(url) {
    const width = 800;
    const height = 600;
    const left = (screen.width - width) / 2;
    const top = (screen.height - height) / 2;
    const features = `width=${width},height=${height},top=${top},left=${left},resizable=yes,scrollbars=yes,status=yes`;
    window.open(url, 'VisorSoportes', features);
}
</script>