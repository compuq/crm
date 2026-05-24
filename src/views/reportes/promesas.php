<div class="card bg-dark border-secondary p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold">💰 Reporte de Promesas y Seguimiento</h4>
        <?php if(!empty($promesas)): ?>
            <a href="?action=exportar_promesas_excel&<?= http_build_query($filters) ?>" class="btn btn-success" target="_blank">📥 Descargar Excel</a>
        <?php endif; ?>
    </div>

        <form method="GET" class="row g-3 mb-4 bg-dark p-3 rounded border border-secondary">
        <input type="hidden" name="action" value="<?= $_GET['action'] ?>">
        
        <div class="col-md-2"><label class="text-secondary small">Desde</label><input type="date" name="fecha_inicio" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($filters['fecha_inicio']) ?>"></div>
        <div class="col-md-2"><label class="text-secondary small">Hasta</label><input type="date" name="fecha_fin" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($filters['fecha_fin']) ?>"></div>

        <!-- Supervisor (Solo Admin/Supervisor General) -->
        <?php if (in_array($rol, ['admin', 'supervisor_general']) && !empty($supervisores)): ?>
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
        <?php if (in_array($rol, ['admin', 'supervisor', 'supervisor_general']) && !empty($usuarios)): ?>
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

    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle mb-0">
            <thead class="table-secondary text-uppercase small">
                <tr>
                    <th>Fecha Gestión</th>
                    <th>Fecha Promesa</th>
                    <th>Estado</th>
                    <th>Cliente</th>
                    <th>Cuenta</th>
                    <th>Monto</th>
                    <th>Gestor</th>
                    <th class="text-center">📞 Seg.</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($promesas as $p): 
                    $badge = $p['estatus']=='pendiente'?'bg-warning text-dark':($p['estatus']=='cumplida'?'bg-success':'bg-danger');
                    $llamadas = (int)$p['llamadas_seguimiento'];
                    $alertClass = ($p['estatus']=='pendiente' && $llamadas < 2) ? 'text-danger fw-bold' : 'text-secondary';
                ?>
                <tr>
                    <td><?= date('d/m/Y H:i:s', strtotime($p['fecha_registro'])) ?></td>
                    <td><?= date('d/m/Y H:i:s', strtotime($p['fecha_compromiso'])) ?></td>
                    <td><span class="badge <?= $badge ?>"><?= strtoupper($p['estatus']) ?></span></td>
                    <td><?= htmlspecialchars($p['nombre']) ?></td>
                    <td class="fw-medium"><?= htmlspecialchars($p['cuenta']) ?></td>
                    <td class="text-warning">Q<?= number_format($p['monto_prometido'],2) ?></td>
                    <td><?= htmlspecialchars($p['gestor'] ?? '-') ?></td>
                    <td class="text-center <?= $alertClass ?>"><?= $llamadas ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php include __DIR__ . '/_paginacion.php'; ?>
</div>