<!-- Panel de Configuración -->
<ul class="nav nav-tabs mb-4 border-secondary" id="configTabs">
    <li class="nav-item">
        <button class="nav-link active text-white" data-bs-toggle="tab" data-bs-target="#tab-carteras" type="button">📁 Carteras</button>
    </li>
    <li class="nav-item">
        <button class="nav-link text-white" data-bs-toggle="tab" data-bs-target="#tab-tipologias" type="button">🏷️ Tipologías por Cartera</button>
    </li>
</ul>

<div class="tab-content">
    <!-- PESTAÑA CARTERAS (sin cambios) -->
    <div class="tab-pane fade show active" id="tab-carteras">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 fw-bold">Gestión de Carteras</h5>
            <button class="btn btn-lex-primary btn-sm" onclick="abrirModalCartera()">+ Nueva Cartera</button>
        </div>
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre Cartera</th>
                        <th>Etiqueta Cuenta</th>
                        <th>Etiqueta Identificación</th>
                        <th class="text-center">Extras</th>
                        <th class="text-center">Extras Gestión</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbl-carteras">
                    <?php foreach($carteras as $c): ?>
                    <tr>
                        <td><?= $c['id'] ?></td>
                        <td class="fw-medium"><?= htmlspecialchars($c['nombre_cartera']) ?></td>
                        <td><?= htmlspecialchars($c['cuenta_nombre'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($c['identificacion_nombre'] ?? '-') ?></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-info" onclick='abrirModalExtras(<?= $c['id'] ?>, "<?= addslashes($c['nombre_cartera']) ?>")'>
                                ⚙️ Configurar
                            </button>
                        </td>
                        <td>
                            <a href="index.php?action=configurar_extras&id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-warning">
                            ⚙️ Configurar Extras
                            </a>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-warning" onclick='editarCartera(<?= json_encode($c) ?>)'>✏️ Editar</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($carteras)): ?>
                        <tr><td colspan="5" class="text-center text-secondary py-4">No hay carteras configuradas.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- PESTAÑA TIPOLOGÍAS (ACTUALIZADA) -->
    <!-- PESTAÑA TIPOLOGÍAS (ACTUALIZADA - SIN FORMULARIO) -->
    <div class="tab-pane fade" id="tab-tipologias">
        <div class="card bg-dark border-secondary p-4 mb-4">
            <h6 class="text-info mb-2">📤 Carga Masiva de Tipologías por Cartera</h6>
            <p class="text-secondary small mb-2">
                Formato XLSX: <code>codigo_origen, clase (T/S), codigo_padre, nombre, estatus_default, requiere_proxima_fecha, requiere_monto</code>
            </p>
            <ul class="text-secondary small ms-3 mb-2">
                <li><strong>estatus_default:</strong> SINC, COMP, PAGG o PAGO</li>
                <li><strong>requiere_proxima_fecha / requiere_monto:</strong> true o false</li>
            </ul>
            <p class="text-warning small mb-3">⚠️ <strong>Importante:</strong> Si hay algún error en el archivo, toda la operación se cancelará (rollback).</p>
            
            <form id="form-tipologias" action="javascript:void(0);" onsubmit="return false;" enctype="multipart/form-data" class="row g-2">
                <div class="col-md-3">
                    <select name="id_cartera" id="select-cartera-carga" class="form-select bg-dark text-white border-secondary" required>
                        <option value="">-- Seleccione Cartera --</option>
                        <?php foreach($carteras as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre_cartera']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <input type="file" name="csv_tipologias" class="form-control bg-dark text-white border-secondary" accept=".xlsx, .xls" required>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-success w-100 fw-bold"> Cargar CSV</button>
                </div>
            </form>
            <div id="msg-tipologias" class="mt-2"></div>
        </div>
        
        <h6 class="mb-3 fw-bold">📋 Catálogo Actual (Filtrado Dinámico)</h6>
        <!-- SELECTOR DE FILTRO (FUERA DE FORMULARIO) -->
        <div class="mb-3">
            <select id="filter-cartera" class="form-select bg-dark text-white border-secondary" style="max-width: 300px;">
                <option value="">-- Seleccione Cartera para Ver Catálogo --</option>
                <?php foreach($carteras as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre_cartera']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="table-responsive" style="max-height: 400px; overflow-y:auto;">
            <table class="table table-dark table-sm align-middle">
                <thead class="sticky-top bg-dark">
                    <tr>
                        <th width="100">Código</th>
                        <th width="60">Tipo</th>
                        <th>Nombre</th>
                        <th width="100">Es Subtipo</th>
                    </tr>
                </thead>
                <tbody id="tbl-tipologias">
                    <tr><td colspan="4" class="text-center text-secondary py-4">Seleccione una cartera arriba para cargar su catálogo</td></tr>
                </tbody>
            </table>
        </div>
    </div>
<!-- MODAL CARTERA (Actualizado con Etiquetas Visuales) -->
<div class="modal fade" id="modal-cartera" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border-secondary">
            <form id="form-cartera">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title">⚙️ Configurar Cartera</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="cartera-id">
                    
                    <!-- Datos Básicos -->
                    <div class="mb-3">
                        <label class="form-label small text-secondary">Nombre de la Cartera *</label>
                        <input type="text" name="nombre_cartera" id="cartera-nombre" class="form-control bg-dark text-white border-secondary" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small text-secondary">Etiqueta "Cuenta"</label>
                            <input type="text" name="cuenta_nombre" id="cartera-cuenta" class="form-control bg-dark text-white border-secondary" placeholder="Ej: Tarjeta, Préstamo">
                        </div>
                        <div class="col-6">
                            <label class="form-label small text-secondary">Etiqueta "ID"</label>
                            <input type="text" name="identificacion_nombre" id="cartera-ident" class="form-control bg-dark text-white border-secondary" placeholder="Ej: DPI, NIT">
                        </div>
                    </div>

                    <!-- ✅ NUEVO: Etiquetas Visuales Personalizables -->
                    <fieldset class="border border-secondary rounded p-2">
                        <legend class="text-secondary small w-auto px-2 mb-2">🏷️ Etiquetas en Portal</legend>
                        <p class="text-secondary small mb-2">Define cómo se muestran estos campos en tablas y reportes.</p>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small text-secondary">Etiqueta "Nombre"</label>
                                <input type="text" name="lbl_nombre" id="cartera-lbl-nombre" class="form-control bg-dark text-white border-secondary" placeholder="Ej: Cliente, Deudor" value="Nombre">
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-secondary">Etiqueta "Saldo"</label>
                                <input type="text" name="lbl_saldo" id="cartera-lbl-saldo" class="form-control bg-dark text-white border-secondary" placeholder="Ej: Deuda, Capital" value="Saldo">
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-secondary">Etiqueta "Teléfono"</label>
                                <input type="text" name="lbl_telefono" id="cartera-lbl-telefono" class="form-control bg-dark text-white border-secondary" placeholder="Ej: Contacto, Celular" value="Teléfono">
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-secondary">Etiqueta "Estado"</label>
                                <input type="text" name="lbl_estado" id="cartera-lbl-estado" class="form-control bg-dark text-white border-secondary" placeholder="Ej: Situación, Estatus" value="Estado">
                            </div>
                        </div>
                    </fieldset>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-lex-primary">💾 Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Modal Gestión de Campos Extra -->
<!-- Modal Gestión de Campos Extra -->
<div class="modal fade" id="modal-extras" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-white">📦 Campos Extra: <span id="lblCarteraExtra" class="text-info"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="form-extra" class="row g-2 mb-3">
                    <input type="hidden" name="id_cartera" id="extra-cartera-id">
                    <div class="col-5">
                        <input type="text" name="nombre_campo" class="form-control bg-dark text-white border-secondary" placeholder="nombre_campo" required title="Ej: direccion, email, telefono_2">
                    </div>
                    <div class="col-4">
                        <input type="text" name="etiqueta" class="form-control bg-dark text-white border-secondary" placeholder="Etiqueta visible">
                    </div>
                    <div class="col-3">
                        <button type="submit" class="btn btn-success w-100">+ Agregar</button>
                    </div>
                </form>
                <div class="table-responsive" style="max-height:200px; overflow-y:auto;">
                    <table class="table table-dark table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Campo</th>
                                <th>Etiqueta</th>
                                <th class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="tbl-extras">
                            <tr><td colspan="3" class="text-center text-secondary py-2">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- SCRIPT FINAL CONFIGURACIÓN (Blindado) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Helper para esperar Bootstrap
    function whenReady(callback) {
        if (typeof bootstrap !== 'undefined') callback();
        else setTimeout(() => whenReady(callback), 50);
    }

    whenReady(function() {
        
        // ===== MODAL CARTERA =====
        let modalCartera = null;
        const formCartera = document.getElementById('form-cartera');
        
        function initCarteraModal() {
            const el = document.getElementById('modal-cartera');
            if (el) modalCartera = new bootstrap.Modal(el);
        }
        initCarteraModal();

        window.abrirModalCartera = function(data) {
            if (!modalCartera) initCarteraModal();
            if (!formCartera || !modalCartera) return;
            
            formCartera.reset();
            document.getElementById('cartera-id').value = data ? data.id : '';
            document.getElementById('cartera-nombre').value = data ? data.nombre_cartera : '';
            document.getElementById('cartera-cuenta').value = data ? (data.cuenta_nombre || '') : '';
            document.getElementById('cartera-ident').value = data ? (data.identificacion_nombre || '') : '';
            document.getElementById('cartera-lbl-nombre').value = data ? (data.lbl_nombre || 'Nombre') : 'Nombre';
            document.getElementById('cartera-lbl-saldo').value = data ? (data.lbl_saldo || 'Saldo') : 'Saldo';
            document.getElementById('cartera-lbl-telefono').value = data ? (data.lbl_telefono || 'Teléfono') : 'Teléfono';
            document.getElementById('cartera-lbl-estado').value = data ? (data.lbl_estado || 'Estado') : 'Estado';
            
            modalCartera.show();
        };

        window.editarCartera = function(data) { window.abrirModalCartera(data); };

        if (formCartera) {
            formCartera.addEventListener('submit', function(e) {
                e.preventDefault();
                const fd = new FormData(e.target);
                fetch('?action=guardar_cartera', { method:'POST', body:fd })
                    .then(r=>r.json()).then(res => {
                        if(res.success) { modalCartera.hide(); location.reload(); }
                        else alert('❌ '+res.msg);
                    });
            });
        }

        // ===== MODAL EXTRAS =====
        let modalExtras = null;
        
        function initExtrasModal() {
            const el = document.getElementById('modal-extras');
            if (el) modalExtras = new bootstrap.Modal(el);
        }

        window.abrirModalExtras = function(cid, nombre) {
            if (!modalExtras) initExtrasModal();
            if (!modalExtras) { alert('Error inicializando modal'); return; }
            
            document.getElementById('lblCarteraExtra').textContent = nombre;
            document.getElementById('extra-cartera-id').value = cid;
            document.getElementById('form-extra').reset();
            cargarExtras(cid);
            modalExtras.show();
        };

        function cargarExtras(cid) {
            const tbody = document.getElementById('tbl-extras');
            if (!tbody) return;
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-secondary py-2">Cargando...</td></tr>';
            
            fetch(`?action=obtener_extras&cartera_id=${cid}`)
                .then(r=>r.json())
                .then(res => {
                    tbody.innerHTML = '';
                    if (res.success && res.data && res.data.length > 0) {
                        res.data.forEach(e => {
                            tbody.innerHTML += `<tr>
                                <td><code>${e.nombre_campo}</code></td>
                                <td>${e.etiqueta||'-'}</td>
                                <td class="text-center"><button class="btn btn-xs btn-outline-danger" onclick="window.eliminarExtra(${e.id},${cid})">🗑️</button></td>
                            </tr>`;
                        });
                    } else {
                        tbody.innerHTML = '<tr><td colspan="3" class="text-center text-secondary py-2">Sin campos configurados</td></tr>';
                    }
                });
        }

        window.eliminarExtra = function(id, cid) {
            if (!confirm('¿Eliminar este campo?')) return;
            const fd = new FormData(); fd.append('id', id);
            fetch('?action=eliminar_extra', {method:'POST', body:fd})
                .then(r=>r.json()).then(res => { if(res.success) cargarExtras(cid); });
        };

        const formExtra = document.getElementById('form-extra');
        if (formExtra) {
            formExtra.addEventListener('submit', function(e) {
                e.preventDefault();
                const fd = new FormData(this);
                const btn = this.querySelector('button[type="submit"]');
                btn.disabled = true; btn.textContent = '⏳';
                
                fetch('?action=guardar_extra', {method:'POST', body:fd})
                    .then(r=>r.json()).then(res => {
                        btn.disabled = false; btn.textContent = '+ Agregar';
                        if (res.success) { cargarExtras(fd.get('id_cartera')); this.reset(); }
                        else alert('❌ '+res.msg);
                    });
            });
        }

        // ===== CARGA CSV TIPOLOGÍAS =====
        const formCarga = document.getElementById('form-tipologias');
        const msgDiv = document.getElementById('msg-tipologias');
        if (formCarga && msgDiv) {
            formCarga.addEventListener('submit', async (e) => {
                e.preventDefault();
                const btn = formCarga.querySelector('button[type="submit"]');
                const fd = new FormData(formCarga);
                btn.disabled = true; btn.innerHTML = '⏳';
                msgDiv.innerHTML = '<div class="alert alert-info py-2 small">Procesando...</div>';
                
                try {
                    const res = await fetch('?action=cargar_tipologias', {method:'POST', body:fd}).then(r=>r.json());
                    btn.disabled = false; btn.innerHTML = '🚀 Cargar CSV';
                    if (res.success) {
                        msgDiv.innerHTML = `<div class="alert alert-success py-2 small">✅ ${res.msg}</div>`;
                        const f = document.getElementById('filter-cartera');
                        if (f && f.value) f.dispatchEvent(new Event('change'));
                    } else {
                        msgDiv.innerHTML = `<div class="alert alert-danger py-2 small">❌ ${res.msg}</div>`;
                    }
                } catch(err) {
                    btn.disabled = false; btn.innerHTML = '🚀 Cargar CSV';
                    msgDiv.innerHTML = `<div class="alert alert-danger py-2 small">❌ Error de red</div>`;
                }
            });
        }

        // ===== FILTRO AJAX TIPOLOGÍAS =====
        const filterSel = document.getElementById('filter-cartera');
        const tbodyTip = document.getElementById('tbl-tipologias');
        if (filterSel && tbodyTip) {
            filterSel.addEventListener('change', async function() {
                const cid = this.value;
                tbodyTip.innerHTML = '<tr><td colspan="4" class="text-center text-secondary py-4">Cargando...</td></tr>';
                if (!cid) {
                    tbodyTip.innerHTML = '<tr><td colspan="4" class="text-center text-secondary py-4">Seleccione cartera</td></tr>';
                    return;
                }
                try {
                    const res = await fetch(`?action=obtener_tipologias&cartera_id=${cid}`).then(r=>r.json());
                    if (res.success && res.data && res.data.length > 0) {
                        let html = '';
                        res.data.forEach(t => {
                            const indent = (t.clase==='S'||t.padre_id) ? '&nbsp;&nbsp;↳ ' : '';
                            html += `<tr><td><code>${t.codigo_origen||'-'}</code></td><td><span class="badge bg-secondary">${t.clase||'T'}</span></td><td>${indent}${t.nombre}</td><td class="text-center">${(t.clase==='S'||t.padre_id)?'✅Sí':'-'}</td></tr>`;
                        });
                        tbodyTip.innerHTML = html;
                    } else {
                        tbodyTip.innerHTML = '<tr><td colspan="4" class="text-center text-secondary py-4">Sin registros</td></tr>';
                    }
                } catch(err) {
                    tbodyTip.innerHTML = '<tr><td colspan="4" class="text-center text-danger py-4">Error</td></tr>';
                }
            });
        }
    });
});
</script>