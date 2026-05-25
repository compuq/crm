<!-- Pestañas de Navegación -->
<ul class="nav nav-tabs mb-4 border-secondary" id="backupTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link active text-white" data-bs-toggle="tab" data-bs-target="#migrar" type="button">📦 Migrar a Histórico</button>
    </li>
    <li class="nav-item">
        <button class="nav-link text-white" data-bs-toggle="tab" data-bs-target="#consulta" type="button">🔍 Consulta Histórica</button>
    </li>
</ul>

<div class="tab-content">
    <!-- PESTAÑA 1: MIGRAR (Sin cambios) -->
    <div class="tab-pane fade show active" id="migrar">
        <!-- ... tu código existente de migrar ... -->
        <div class="card bg-dark border-secondary p-3">
            <p class="text-secondary small mb-2">Seleccione los clientes que desea migrar a la base histórica.</p>
            <div class="d-flex gap-2 mb-3">
                <button class="btn btn-sm btn-outline-info" onclick="seleccionarTodos(true)">✅ Seleccionar Todos</button>
                <button class="btn btn-sm btn-outline-secondary" onclick="seleccionarTodos(false)">❌ Deseleccionar</button>
            </div>
            <!-- 🔍 FORMULARIO DE FILTRO COMPLETO -->
<form method="GET" action="?action=backup" class="row g-3 mb-4 p-3 bg-dark bg-opacity-50 rounded border border-secondary">
    <input type="hidden" name="action" value="backup">
    
    <!-- Filtro por Estado -->
    <div class="col-md-3">
        <label class="form-label small text-secondary">📊 Estado del Cliente</label>
        <select name="estado" class="form-select bg-dark text-white border-secondary">
            <option value="">Todos los estados</option>
            <option value="pagado" <?= ($_GET['estado'] ?? '') === 'pagado' ? 'selected' : '' ?>>✅ Pagado</option>
            <option value="historico" <?= ($_GET['estado'] ?? '') === 'historico' ? 'selected' : '' ?>>📦 Histórico/Inactivo</option>
        </select>
    </div>
    
    <!-- Filtro por Búsqueda -->
    <div class="col-md-3">
        <label class="form-label small text-secondary">🔍 Buscar (Nombre o ID)</label>
        <input type="text" name="busqueda" class="form-control bg-dark text-white border-secondary" 
               placeholder="Otros datos..." 
               value="<?= htmlspecialchars($_GET['busqueda'] ?? '') ?>">
    </div>
    
    <!-- Filtro por Fecha -->
    <div class="col-md-2">
        <label class="form-label small text-secondary">📅 Última Gestión Desde</label>
        <input type="date" name="fecha_inicio" class="form-control bg-dark text-white border-secondary" 
               value="<?= htmlspecialchars($_GET['fecha_inicio'] ?? '') ?>">
    </div>
    <div class="col-md-2">
        <label class="form-label small text-secondary">Hasta</label>
        <input type="date" name="fecha_fin" class="form-control bg-dark text-white border-secondary" 
               value="<?= htmlspecialchars($_GET['fecha_fin'] ?? '') ?>">
    </div>
    
    <!-- Botones -->
    <div class="col-md-2 d-flex align-items-end gap-2">
        <button type="submit" class="btn btn-lex-primary flex-grow-1">🔍 Filtrar</button>
        <a href="?action=backup" class="btn btn-outline-secondary">🔄 Limpiar</a>
    </div>
</form>
            <!-- ... Tabla de migración ... -->
            <!-- (Mantén tu tabla de migración aquí, es la misma que tenías) -->
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead class="sticky-top bg-dark">
                        <tr>
                            <th width="40"><input type="checkbox" id="checkAll" onchange="seleccionarTodos(this.checked)"></th>
                            <th>Nombre</th>
                            <th>Cuenta</th>
                            <th>Identificación</th>
                            <th>Saldo</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody id="tablaClientes">
                        <?php foreach($clientesElegibles as $c): ?>
                        <tr>
                            <td><input type="checkbox" class="cliente-check" value="<?= $c['id'] ?>"></td>
                            <td><?= htmlspecialchars($c['nombre']) ?></td>
                            <td><?= htmlspecialchars($c['cuenta']) ?></td>
                            <td><?= htmlspecialchars($c['identificacion']) ?></td>
                            <td class="text-warning">Q<?= number_format($c['saldo'], 2) ?></td>
                            <td><span class="badge bg-secondary"><?= strtoupper($c['estado']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($clientesElegibles)): ?>
                            <tr><td colspan="5" class="text-center text-secondary py-4">No hay clientes elegibles para migrar.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <button id="btnMigrar" class="btn btn-warning mt-3 w-100 fw-bold" onclick="ejecutarMigracion()">
                🚀 Migrar Seleccionados a Histórico
            </button>
        </div>
    </div>

    <!-- PESTAÑA 2: CONSULTA HISTÓRICA (ACTUALIZADA) -->
    <div class="tab-pane fade" id="consulta">
        <div class="card bg-dark border-secondary p-3 mb-3">
            <form id="formHistorico" class="row g-3 align-items-end" onsubmit="buscarHistorico(event)">
                <div class="col-md-2">
                    <label class="text-secondary small">Desde</label>
                    <input type="date" name="fecha_inicio" class="form-control bg-dark text-white border-secondary" value="<?= date('Y-m-01') ?>">
                </div>
                <div class="col-md-2">
                    <label class="text-secondary small">Hasta</label>
                    <input type="date" name="fecha_fin" class="form-control bg-dark text-white border-secondary" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-3">
                    <label class="text-secondary small">Identificación</label>
                    <input type="text" name="identificacion" class="form-control bg-dark text-white border-secondary" placeholder="DPI / Identificación">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-lex-primary flex-grow-1">🔍 Buscar</button>
                    <a href="?action=exportar_historico&fecha_inicio=<?= date('Y-m-01') ?>&fecha_fin=<?= date('Y-m-d') ?>" class="btn btn-success" target="_blank" title="Exportar Excel">📥 Excel</a>
                </div>
            </form>
        </div>

        <!-- Tabla de Resultados Históricos -->
        <div class="table-responsive">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="text-white">Resultados</h6>
                <button id="btnRestaurar" class="btn btn-outline-warning btn-sm d-none" onclick="restaurarSeleccionados()">
                    ♻️ Restaurar Seleccionados
                </button>
            </div>
            <table class="table table-dark table-hover align-middle mb-0" id="tablaHistorica">
                <thead>
                    <tr>
                        <th width="40"><input type="checkbox" id="checkHistAll" onchange="toggleCheckHist(this.checked)"></th>
                        <th>Nombre</th>
                        <th>Cuenta</th>
                        <th>Identificación</th>
                        <th>Saldo</th>
                        <th>Fecha Migración</th>
                        <th>Operación</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody id="cuerpoHistorico">
                    <tr><td colspan="7" class="text-center text-secondary py-4">Realice una búsqueda para ver registros.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ✅ MODAL: HISTORIAL DEL CLIENTE -->
<div class="modal fade" id="modalHistorialCliente" tabindex="-1" aria-hidden="false">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-white">📜 Historial de: <span id="lblClienteHist" class="text-info"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-dark table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipología</th>
                                <th>Gestor</th>
                                <th>Estatus</th>
                                <th>Comentario</th>
                            </tr>
                        </thead>
                        <tbody id="listaHistorialModal">
                            <tr><td colspan="5" class="text-center py-3">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SCRIPTS -->
<script>
// 1. Seleccionar todos en pestaña Migrar
function seleccionarTodos(checked) {
    document.querySelectorAll('.cliente-check').forEach(cb => cb.checked = checked);
    document.getElementById('checkAll').checked = checked;
}

// 2. Seleccionar todos en pestaña Histórica
function toggleCheckHist(checked) {
    document.querySelectorAll('.hist-check').forEach(cb => cb.checked = checked);
    actualizarBtnRestaurar();
}

// 3. Buscar Histórico
function buscarHistorico(e) {
    e.preventDefault();
    const form = e.target;
    const params = new URLSearchParams(new FormData(form)).toString();
    const tbody = document.getElementById('cuerpoHistorico');
    const btnRestaurar = document.getElementById('btnRestaurar');
    
    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-secondary py-4">Cargando...</td></tr>';
    btnRestaurar.classList.add('d-none');

    fetch(`?action=consultar_historico&${params}`)
        .then(r => r.json())
        .then(data => {
            tbody.innerHTML = '';
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-secondary py-4">No se encontraron registros.</td></tr>';
                return;
            }
            data.forEach(row => {
                tbody.innerHTML += `
                    <tr>
                        <td><input type="checkbox" class="hist-check" value="${row.id_original}" onchange="actualizarBtnRestaurar()"></td>
                        <td>${row.nombre || '-'}</td>
                        <td>${row.cuenta || '-'}</td>
                        <td>${row.identificacion || '-'}</td>
                        <td class="text-warning">Q${parseFloat(row.saldo || 0).toFixed(2)}</td>
                        <td>${row.fecha_migracion ? new Date(row.fecha_migracion).toLocaleString() : '-'}</td>
                        <td><span class="badge bg-info">${row.tipo_operacion}</span></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary" onclick="verHistorial(${row.id_original}, '${(row.nombre || '').replace(/'/g, "\\'")}')">
                                👁️ Ver Historial
                            </button>
                        </td>
                    </tr>`;
            });
        });
}

// 4. Ver Historial en Modal
function verHistorial(idCliente, nombre) {
    document.getElementById('lblClienteHist').textContent = nombre;
    const modal = new bootstrap.Modal(document.getElementById('modalHistorialCliente'));
    modal.show();
    
    const tbody = document.getElementById('listaHistorialModal');
    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-secondary py-4">Cargando...</td></tr>';

    fetch(`?action=ver_historial_cliente&id=${idCliente}`)
        .then(r => r.json())
        .then(historial => {
            tbody.innerHTML = '';
            if (!historial || historial.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-secondary py-4">Sin historial encontrado.</td></tr>';
                return;
            }
            historial.forEach(h => {
                const badgeClass = { 'SINC': 'bg-secondary', 'COMP': 'bg-warning text-dark', 'PAGG': 'bg-info text-dark', 'PAGO': 'bg-success' }[h.estatus] || 'bg-secondary';
                tbody.innerHTML += `
                    <tr>
                        <td class="small">${h.fecha_gestion ? new Date(h.fecha_gestion).toLocaleString() : '-'}</td>
                        <td>${h.tipologia || '-'}</td>
                        <td>${h.gestor || '-'}</td>
                        <td><span class="badge ${badgeClass}">${h.estatus}</span></td>
                        <td class="small">${h.comentario || ''}</td>
                    </tr>`;
            });
        });
}

// 5. Mostrar/Ocultar botón Restaurar
function actualizarBtnRestaurar() {
    const checks = document.querySelectorAll('.hist-check:checked');
    const btn = document.getElementById('btnRestaurar');
    if (checks.length > 0) btn.classList.remove('d-none');
    else btn.classList.add('d-none');
}

// 6. Restaurar Seleccionados
function restaurarSeleccionados() {
    const checks = document.querySelectorAll('.hist-check:checked');
    const ids = Array.from(checks).map(cb => cb.value);
    
    if (ids.length === 0) return;
    if (!confirm(`¿Está seguro de restaurar ${ids.length} clientes al sistema activo? Esta acción los eliminará del histórico.`)) return;

    const formData = new FormData();
    formData.append('ids', JSON.stringify(ids));

    fetch('?action=restaurar_historico', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                alert(`✅ ${res.migrados} clientes restaurados exitosamente.`);
                // Recargar la búsqueda actual
                document.getElementById('formHistorico').dispatchEvent(new Event('submit'));
            } else {
                alert('❌ Error: ' + (res.error || 'No se pudo restaurar.'));
            }
        })
        .catch(err => alert('❌ Error de conexión'));
}

// 7. Migrar (Tu código existente)
function ejecutarMigracion() {
    const seleccionados = Array.from(document.querySelectorAll('.cliente-check:checked')).map(cb => cb.value);
    if (seleccionados.length === 0) return alert('⚠️ Seleccione al menos un cliente.');
    if (!confirm(`¿Está seguro de migrar ${seleccionados.length} clientes a histórico?`)) return;

    const btn = document.getElementById('btnMigrar');
    btn.disabled = true; btn.innerHTML = '⏳ Migrando...';
    const formData = new FormData();
    formData.append('ids', JSON.stringify(seleccionados));

    fetch('?action=migrar_historico', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false; btn.innerHTML = '🚀 Migrar Seleccionados a Histórico';
            if (res.success) {
                alert('✅ Migración exitosa.'); location.reload();
            } else {
                alert('❌ Error: ' + (res.error || res.msg));
            }
        });
}
function ejecutarMigracion() {
    const seleccionados = Array.from(document.querySelectorAll('.cliente-check:checked')).map(cb => cb.value);
    
    console.log('🔍 IDs seleccionados:', seleccionados); // ← DEBUG
    
    if (seleccionados.length === 0) return alert('⚠️ Seleccione al menos un cliente.');
    
    const formData = new FormData();
    formData.append('ids', JSON.stringify(seleccionados));
    
    // Debug: ver contenido del FormData
    for (let [key, value] of formData.entries()) {
        console.log(`📦 FormData - ${key}:`, value);
    }
    
    fetch('?action=migrar_historico', { 
        method: 'POST', 
        body: formData 
    })
    .then(r => {
        console.log('📡 Response status:', r.status);
        return r.json();
    })
    .then(res => {
        console.log('✅ Response:', res);
        // ... resto del código
    })
    .catch(err => {
        console.error('❌ Error de conexión:', err);
        alert('❌ Error de conexión');
    });
}
</script>