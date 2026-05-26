<!-- views/backup/migrar.php -->
<div class="card bg-dark border-secondary p-4 mb-4">
    <h4 class="mb-3 fw-bold">🔄 Migración de Clientes</h4>
    
    <!-- 🔍 FILTROS -->
    <form method="GET" class="row g-3 mb-4 bg-dark p-3 rounded border border-secondary">
        <input type="hidden" value="migrar_clientes" name="action">
        <div class="col-md-3">
            <label class="text-secondary small">📁 Cartera</label>
            <select name="cartera_id" class="form-select bg-dark text-white border-secondary">
                <option value="">Todas las carteras</option>
                <?php foreach($carteras as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $filters['cartera_id'] == $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['nombre_cartera']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="text-secondary small">👤 Gestor Actual</label>
            <select name="usuario_id" class="form-select bg-dark text-white border-secondary">
                <option value="">Todos</option>
                <?php foreach($usuarios as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= $filters['usuario_id'] == $u['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="text-secondary small">👔 Supervisor Actual</label>
            <select name="supervisor_id" class="form-select bg-dark text-white border-secondary">
                <option value="">Todos</option>
                <?php foreach($supervisores as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $filters['supervisor_id'] == $s['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="text-secondary small">🔍 Buscar (Cuenta, Nombre, ID)</label>
            <input type="text" name="search" class="form-control bg-dark text-white border-secondary" 
                   value="<?= htmlspecialchars($filters['search']) ?>" placeholder="Términos separados por espacio">
        </div>
        <div class="col-md-2 d-flex align-items-end gap-2">
            <button type="submit" class="btn btn-lex-primary flex-grow-1">🔍 Filtrar</button>
            <a href="?action=migrar_clientes" class="btn btn-outline-secondary">🔄</a>
        </div>
    </form>

    <!-- 📋 TABLA DE CLIENTES -->
    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
        <table class="table table-dark table-hover align-middle mb-0">
            <thead class="sticky-top bg-dark">
                <tr>
                    <th width="40">
                        <input type="checkbox" id="checkAll" onchange="toggleTodos(this.checked)">
                    </th>
                    <th>Cuenta</th>
                    <th>Nombre</th>
                    <th>Identificación</th>
                    <th>Saldo</th>
                    <th>Gestor Actual</th>
                    <th>Supervisor Actual</th>
                    <th>Cartera</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($clientes)): ?>
                    <tr><td colspan="8" class="text-center text-secondary py-4">No hay clientes con estos filtros</td></tr>
                <?php else: ?>
                    <?php foreach($clientes as $c): ?>
                    <tr>
                        <td>
                            <input type="checkbox" class="cliente-check" value="<?= $c['id'] ?>" 
                                   data-gestor="<?= $c['id_gestor_asignado'] ?? '' ?>" 
                                   data-supervisor="<?= $c['id_supervisor_cadena'] ?? '' ?>">
                        </td>
                        <td class="fw-medium"><?= htmlspecialchars($c['cuenta']) ?></td>
                        <td><?= htmlspecialchars($c['nombre']) ?></td>
                        <td><?= htmlspecialchars($c['identificacion']) ?></td>
                        <td class="text-warning">Q<?= number_format($c['saldo'], 2) ?></td>
                        <td><small class="text-secondary"><?= htmlspecialchars($c['gestor_nombre'] ?? '-') ?></small></td>
                        <td><small class="text-secondary"><?= htmlspecialchars($c['supervisor_nombre'] ?? '-') ?></small></td>
                        <td><span class="badge bg-info"><?= htmlspecialchars($c['nombre_cartera'] ?? '-') ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- 🎯 PANEL DE ACCIONES -->
<div class="card bg-dark border-secondary p-4">
    <h6 class="mb-3 fw-bold">⚡ Acciones para Seleccionados (<span id="countSelected">0</span>)</h6>
    
    <div class="row g-3">
        <!-- 🔄 TRASLADO A USUARIO -->
        <div class="col-md-6">
            <div class="border border-warning rounded p-3">
                <h6 class="text-warning mb-2">🔄 Traslado entre Gestores</h6>
                <p class="text-secondary small mb-2">Mueve clientes a otro gestor/supervisor</p>
                
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="text-secondary small">Nuevo Gestor</label>
                        <select id="nuevoGestor" class="form-select form-select-sm bg-dark text-white border-secondary">
                            <option value="">Seleccionar...</option>
                            <?php foreach($usuarios as $u): ?>
                                <option value="<?= $u['id'] ?>" data-supervisor="<?= $u['supervisor_id'] ?? '' ?>">
                                    <?= htmlspecialchars($u['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="text-secondary small">Nuevo Supervisor</label>
                        <select id="nuevoSupervisor" class="form-select form-select-sm bg-dark text-white border-secondary">
                            <option value="">Seleccionar...</option>
                            <?php foreach($supervisores as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button class="btn btn-warning btn-sm w-100" onclick="ejecutarTraslado()">
                    🔄 Traslado a Gestor
                </button>
            </div>
        </div>

        <!-- 📦 ENVÍO A BACKUP -->
        <div class="col-md-6">
            <div class="border border-danger rounded p-3">
                <h6 class="text-danger mb-2">📦 Enviar a Histórico</h6>
                <p class="text-secondary small mb-2">Mueve clientes a la base de backup (elimina de operativo)</p>
                
                <div class="alert alert-dark border-secondary p-2 mb-2">
                    <small class="text-warning">⚠️ Esta acción es irreversible desde el módulo operativo</small>
                </div>
                <button class="btn btn-danger btn-sm w-100" onclick="ejecutarBackup()">
                    📦 Enviar a Backup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ✅ SCRIPTS -->
<script>
// Contador de seleccionados
function actualizarContador() {
    const count = document.querySelectorAll('.cliente-check:checked').length;
    document.getElementById('countSelected').textContent = count;
}
document.addEventListener('change', actualizarContador);

// Seleccionar todos
function toggleTodos(checked) {
    document.querySelectorAll('.cliente-check').forEach(cb => cb.checked = checked);
    actualizarContador();
}

// 🔄 Trasladar a usuario
function ejecutarTraslado() {
    const seleccionados = Array.from(document.querySelectorAll('.cliente-check:checked')).map(cb => cb.value);
    if (seleccionados.length === 0) return alert('⚠️ Selecciona al menos un cliente');
    
    const nuevoGestor = document.getElementById('nuevoGestor').value;
    const nuevoSupervisor = document.getElementById('nuevoSupervisor').value;
    
    if (!nuevoGestor || !nuevoSupervisor) return alert('⚠️ Selecciona nuevo gestor y supervisor');
    
    if (!confirm(`¿Trasladar ${seleccionados.length} clientes al gestor #${nuevoGestor}?`)) return;
    
    const btn = event.target;
    btn.disabled = true; btn.innerHTML = '⏳ Trasladando...';
    
    const formData = new FormData();
    formData.append('ids', JSON.stringify(seleccionados));
    formData.append('nuevo_gestor_id', nuevoGestor);
    formData.append('nuevo_supervisor_id', nuevoSupervisor);
    
    fetch('?action=trasladar_clientes', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false; btn.innerHTML = '🔄 Traslado a Gestor';
            if (res.success) {
                alert(`✅ ${res.migrados} clientes trasladados`);
                location.reload();
            } else {
                alert('❌ Error: ' + res.msg);
            }
        });
}

// 📦 Enviar a backup
function ejecutarBackup() {
    const seleccionados = Array.from(document.querySelectorAll('.cliente-check:checked'))
        .map(cb => cb.value);

    if (seleccionados.length === 0)
        return alert('⚠️ Selecciona al menos un cliente');

    if (!confirm(`¿Enviar ${seleccionados.length} clientes a backup histórico?`))
        return;

    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '⏳ Enviando...';

    const formData = new FormData();
    formData.append('ids', JSON.stringify(seleccionados));

    // 👇 Ver contenido antes de enviar
    console.log('Contenido de FormData:');

    for (const [key, value] of formData.entries()) {
        console.log(key, value);
    }

    fetch('?action=migrar_historico', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerHTML = '📦 Enviar a Backup';

        if (res.success) {
            alert(`✅ ${res.migrados} clientes enviados a backup`);
            location.reload();
        } else {
            alert('❌ Error: ' + (res.msg || res.error));
        }
    });
}
</script>