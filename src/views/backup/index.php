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
    <!-- PESTAÑA 1: MIGRAR -->
    <div class="tab-pane fade show active" id="migrar">
        <div class="card bg-dark border-secondary p-3">
            <p class="text-secondary small mb-2">Seleccione los clientes que desea migrar a la base histórica. Esta acción mueve los registros a <code>clientes_bk</code> y elimina la copia operativa.</p>
            
            <div class="d-flex gap-2 mb-3">
                <button class="btn btn-sm btn-outline-info" onclick="seleccionarTodos(true)">✅ Seleccionar Todos</button>
                <button class="btn btn-sm btn-outline-secondary" onclick="seleccionarTodos(false)">❌ Deseleccionar</button>
            </div>

            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead class="sticky-top bg-dark">
                        <tr>
                            <th width="40"><input type="checkbox" id="checkAll" onchange="seleccionarTodos(this.checked)"></th>
                            <th>Nombre</th>
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

    <!-- PESTAÑA 2: CONSULTA HISTÓRICA -->
    <div class="tab-pane fade" id="consulta">
        <form id="formHistorico" class="row g-3 mb-4" onsubmit="buscarHistorico(event)">
            <div class="col-md-3">
                <input type="date" name="fecha_inicio" class="form-control bg-dark text-white border-secondary" required>
            </div>
            <div class="col-md-3">
                <input type="date" name="fecha_fin" class="form-control bg-dark text-white border-secondary" required>
            </div>
            <div class="col-md-3">
                <input type="text" name="identificacion" class="form-control bg-dark text-white border-secondary" placeholder="DPI / Identificación">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-lex-primary w-100">🔍 Buscar</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-dark table-hover" id="tablaHistorico">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Identificación</th>
                        <th>Saldo al Migrar</th>
                        <th>Fecha Migración</th>
                        <th>Tipo Operación</th>
                        <th>Estado Lote</th>
                    </tr>
                </thead>
                <tbody id="cuerpoHistorico">
                    <tr><td colspan="6" class="text-center text-secondary py-4">Realice una búsqueda para ver registros.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- SCRIPTS ESPECÍFICOS DEL MÓDULO -->
<script>
function seleccionarTodos(checked) {
    document.querySelectorAll('.cliente-check').forEach(cb => cb.checked = checked);
    document.getElementById('checkAll').checked = checked;
}

function ejecutarMigracion() {
    const seleccionados = Array.from(document.querySelectorAll('.cliente-check:checked')).map(cb => cb.value);
    if (seleccionados.length === 0) return alert('⚠️ Seleccione al menos un cliente.');
    
    if (!confirm(`¿Está seguro de migrar ${seleccionados.length} clientes a histórico? Esta acción no se puede deshacer desde el módulo operativo.`)) return;

    const btn = document.getElementById('btnMigrar');
    btn.disabled = true;
    btn.innerHTML = '⏳ Migrando...';

    const formData = new FormData();
    formData.append('ids', JSON.stringify(seleccionados));

    fetch('?action=migrar_historico', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = '🚀 Migrar Seleccionados a Histórico';
            if (res.success) {
                alert('✅ Migración exitosa. ' + res.migrados + ' registros movidos.');
                location.reload();
            } else {
                alert('❌ Error: ' + (res.error || res.msg));
            }
        });
}

function buscarHistorico(e) {
    e.preventDefault();
    const form = e.target;
    const params = new URLSearchParams(new FormData(form)).toString();
    const tbody = document.getElementById('cuerpoHistorico');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-secondary py-4">Cargando...</td></tr>';

    fetch(`?action=consultar_historico&${params}`)
        .then(r => r.json())
        .then(data => {
            tbody.innerHTML = '';
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-secondary py-4">No se encontraron registros.</td></tr>';
                return;
            }
            data.forEach(row => {
                tbody.innerHTML += `
                    <tr>
                        <td>${row.nombre || '-'}</td>
                        <td>${row.identificacion || '-'}</td>
                        <td class="text-warning">Q${parseFloat(row.saldo || 0).toFixed(2)}</td>
                        <td>${row.fecha_migracion ? new Date(row.fecha_migracion).toLocaleString() : '-'}</td>
                        <td><span class="badge bg-info">${row.tipo_operacion}</span></td>
                        <td><span class="badge ${row.estado_lote === 'completado' ? 'bg-success' : 'bg-warning'}">${row.estado_lote}</span></td>
                    </tr>`;
            });
        });
}
</script>