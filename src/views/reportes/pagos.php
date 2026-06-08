<!-- views/reportes/pagos.php -->
<!-- style para los documentos de soporte  -->
 <style>
    .doc-slider {
        display: flex;
        overflow-x: auto;
        gap: 1rem;
        padding: 0.5rem 0.5rem 1.5rem 0.5rem; /* Padding abajo para la sombra del scroll */
        scroll-snap-type: x mandatory; /* Efecto de imán al deslizar */
        -webkit-overflow-scrolling: touch; /* Scroll suave en iOS */
    }
    /* Ocultar barra de scroll fea pero mantener funcionalidad */
    .doc-slider::-webkit-scrollbar {
        height: 8px;
    }
    .doc-slider::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    .doc-card {
        flex: 0 0 180px; /* Ancho fijo, no se encoge */
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
        object-fit: cover; /* Recorta la imagen para que llene el cuadro */
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
        /* Truncar texto si es muy largo */
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
<div class="card bg-dark border-secondary p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold">📊 Reporte de Pagos</h4>
        <?php if(!empty($pagos)): ?>
            <a href="?action=exportar_pagos_excel&<?= http_build_query($filters) ?>" class="btn btn-success" target="_blank">📥 Descargar Excel</a>
        <?php endif; ?>
    </div>

    <!-- 🔍 FILTROS -->
    <form method="GET" class="row g-3 mb-4 bg-dark p-3 rounded border border-secondary">
        <input type="hidden" name="action" value="<?= $_GET['action'] ?>">
        
        <div class="col-md-2"><label class="text-secondary small">Desde</label><input type="date" name="fecha_inicio" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($filters['fecha_inicio']) ?>"></div>
        <div class="col-md-2"><label class="text-secondary small">Hasta</label><input type="date" name="fecha_fin" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($filters['fecha_fin']) ?>"></div>

        <!-- Supervisor (Solo Admin/Supervisor General) -->
        <?php if (in_array($rol, ['admin', 'supervisor_general','gestor']) && !empty($supervisores)): ?>
        <div class="col-md-2">
            <label class="text-secondary small">Supervisor</label>
            <select name="supervisor_id" class="form-select bg-dark text-white border-secondary">
                <option value="">Todos</option>
                <?php foreach($supervisores as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $filters['supervisor_id'] == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <!-- Gestor (Admin/Supervisor/Supervisor General) -->
        <?php if (in_array($rol, ['admin', 'supervisor', 'supervisor_general','gestor']) && !empty($usuarios)): ?>
        <div class="col-md-2">
            <label class="text-secondary small">Gestor</label>
            <select name="usuario_id" class="form-select bg-dark text-white border-secondary">
                <option value="">Todos</option>
                <?php foreach($usuarios as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= $filters['usuario_id'] == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <!-- Cartera (Admin/Supervisor) -->
        <?php if (in_array($rol, ['admin', 'supervisor']) && !empty($carteras)): ?>
        <div class="col-md-2">
            <label class="text-secondary small">Cartera</label>
            <select name="cartera_id" class="form-select bg-dark text-white border-secondary">
                <option value="">Todas</option>
                <?php foreach($carteras as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $filters['cartera_id'] == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <!-- Estado (Específico por reporte) -->
        <?php if ($_GET['action'] === 'reportes_pagos'): ?>
            <div class="col-md-2">
                <label class="text-secondary small">Estado</label>
                <select name="estatus_pago" class="form-select bg-dark text-white border-secondary">
                    <option value="ambos" <?= $filters['estatus_pago']=='ambos'?'selected':'' ?>>Ambos</option>
                    <option value="PAGG" <?= $filters['estatus_pago']=='PAGG'?'selected':'' ?>>Pendientes</option>
                    <option value="PAGO" <?= $filters['estatus_pago']=='PAGO'?'selected':'' ?>>Confirmados</option>
                </select>
            </div>
        <?php elseif ($_GET['action'] === 'reportes_promesas'): ?>
            <div class="col-md-2">
                <label class="text-secondary small">Estado</label>
                <select name="estatus_promesa" class="form-select bg-dark text-white border-secondary">
                    <option value="pendiente" <?= $filters['estatus_promesa']=='pendiente'?'selected':'' ?>>Pendientes</option>
                    <option value="cumplida" <?= $filters['estatus_promesa']=='cumplida'?'selected':'' ?>>Cumplidas</option>
                    <option value="ambas" <?= $filters['estatus_promesa']=='ambas'?'selected':'' ?>>Ambas</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="text-secondary small">Llamadas Seg.</label>
                <select name="llamadas" class="form-select bg-dark text-white border-secondary">
                    <option value="todas">Todas</option>
                    <?php for($i=0; $i<=5; $i++): ?><option value="<?= $i ?>" <?= $filters['llamadas']==$i?'selected':'' ?>><?= $i ?></option><?php endfor; ?>
                    <option value="5+" <?= $filters['llamadas']=='5+'?'selected':'' ?>>5 o más</option>
                </select>
            </div>
        <?php endif; ?>

        <div class="col-md-12 d-flex align-items-end gap-2 mt-2">
            <button type="submit" class="btn btn-lex-primary">🔍 Filtrar</button>
            <a href="?action=<?= $_GET['action'] ?>" class="btn btn-outline-secondary">🔄</a>
        </div>
    </form>
    <!-- 📋 TABLA DE RESULTADOS -->
    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle mb-0">
            <thead class="table-secondary text-uppercase small">
                <tr>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Cuenta</th>
                    <th>Gestor</th>
                    <th>Estado</th>
                    <th class="text-end">💰 Monto</th>
                    <th>Comentario</th>
                    <th>Comentario Validación</th>
                    <th>Soportes</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($pagos)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-4">
                            No hay pagos con estos filtros.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach($pagos as $p): 
                        $badge = match($p['estatus']) {
                            'PAGG' => 'bg-warning text-dark',
                            'PAGO' => 'bg-success',
                            default => 'bg-secondary'
                        };
                    ?>
                    <tr>
                        <td class="small"><?= date('d/m/Y H:i', strtotime($p['fecha_gestion'])) ?></td>
                        <td><?= htmlspecialchars($p['nombre']) ?></td>
                        <td class="fw-medium text-nowrap">
                            <a href="?action=clientes&q=<?= htmlspecialchars($p['cuenta']) ?>"
                            class="link-info text-decoration-none">
                                <?= htmlspecialchars($p['cuenta']) ?>
                            </a>
                        </td>

                        <td><?= htmlspecialchars($p['gestor']) ?></td>
                        <td><span class="badge <?= $badge ?>"><?= $p['estatus'] ?></span></td>
                        <td class="text-end text-warning fw-bold">Q<?= number_format($p['monto'], 2) ?></td>
                        <td class="small text-secondary" style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($p['comentario']) ?>">
                            <?= htmlspecialchars($p['comentario']) ?>
                        </td>
                        <td><?php if ($p['referencia_bancaria'])echo htmlspecialchars($p['referencia_bancaria']); else echo "SIN COMENTARIO"; ?></td>
                        <td class="text-center align-middle">
                            <button 
                                type="button" 
                                class="btn btn-sm btn-info text-white d-inline-flex align-items-center gap-1" 
                                onclick='gestionarSoportes(
                                    <?= (int)$p['id_pago'] ?>, 
                                    <?= json_encode($p['nombre'] ?? 'N/A') ?>, 
                                    <?= json_encode(number_format($p['monto'], 2)) ?>
                                )' 
                                title="Ver y adjuntar documentos de soporte"
                                <?php 
                                if ($p['estatus']=="PAGO") echo "disabled";
                                ?>
                                >
                                <i class="bi bi-folder2-open"></i> 
                                <span>Soportes</span>

                            </button>
                        </td>                
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- 📄 PAGINACIÓN (al final, después de la tabla) -->
    <?php 
        $dataVar = 'pagos'; // Variable que contiene los datos
        include __DIR__ . '/_paginacion.php'; 
    ?>
</div>

<div class="modal fade" id="modalSoportes" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered"> <!-- modal-lg para más ancho -->
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
                        <div class="fw-semibold" id="modalCliente">-</div>
                    </div>
                    <div class="text-end">
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Monto</small>
                        <div class="fw-semibold text-success fs-5" id="modalMonto">-</div>
                    </div>
                </div>

                <!-- SECCIÓN 1: CARRUSEL DE DOCUMENTOS EXISTENTES -->
                <h6 class="border-bottom pb-2 mb-3"><i class="bi bi-images me-1"></i> Documentos Adjuntos</h6>
                
                <div id="contenedorDocumentos" class="doc-slider">
                    <!-- Aquí se inyectan las tarjetas vía JS -->
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

<script>
function gestionarSoportes(id_pago, cliente, monto) {
    document.getElementById('modalCliente').textContent = cliente;
    document.getElementById('modalMonto').textContent = monto;
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
                </div>`;
            });
            contenedor.innerHTML = html;        })
        .catch(err => {
            console.error('Error al cargar documentos:', err);
            contenedor.innerHTML = '<div class="text-center w-100 py-4 text-danger">Error al cargar documentos.</div>';
        });
}

// ✅ FUNCIÓN AUXILIAR: Escapar caracteres HTML para inyección segura
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
    // Calcular posición para centrar la ventana en la pantalla
    const left = (screen.width - width) / 2;
    const top = (screen.height - height) / 2;
    
    // Características de la ventana: sin barras de herramientas innecesarias, pero con scroll y redimensionable
    const features = `width=${width},height=${height},top=${top},left=${left},resizable=yes,scrollbars=yes,status=yes`;
    
    window.open(url, 'VisorSoportes', features);
}
</script>