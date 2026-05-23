<!-- views/reportes/pagos.php -->
<div class="card bg-dark border-secondary p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold">📊 Reporte de Pagos</h4>
        <?php if(!empty($pagos)): ?>
            <a href="?action=exportar_pagos_excel&<?= http_build_query($filters) ?>" class="btn btn-success" target="_blank">📥 Descargar Excel</a>
        <?php endif; ?>
    </div>

    <!-- 🔍 FILTROS -->
    <form method="GET" class="row g-3 mb-4 bg-dark p-3 rounded border border-secondary">
        <input type="hidden" name="action" value="reportes_pagos">
        <div class="col-md-3">
            <label class="text-secondary small">Desde</label>
            <input type="date" name="fecha_inicio" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($filters['fecha_inicio']) ?>">
        </div>
        <div class="col-md-3">
            <label class="text-secondary small">Hasta</label>
            <input type="date" name="fecha_fin" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($filters['fecha_fin']) ?>">
        </div>
        <div class="col-md-3">
            <label class="text-secondary small">Estado</label>
            <select name="estatus_pago" class="form-select bg-dark text-white border-secondary">
                <option value="ambos" <?= $filters['estatus_pago']=='ambos'?'selected':'' ?>>Ambos (PAGG y PAGO)</option>
                <option value="PAGG" <?= $filters['estatus_pago']=='PAGG'?'selected':'' ?>>Pendientes (PAGG)</option>
                <option value="PAGO" <?= $filters['estatus_pago']=='PAGO'?'selected':'' ?>>Confirmados (PAGO)</option>
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-end gap-2">
            <button type="submit" class="btn btn-lex-primary flex-grow-1">🔍 Filtrar</button>
            <a href="?action=reportes_pagos" class="btn btn-outline-secondary">🔄</a>
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
                        <td class="fw-medium"><?= htmlspecialchars($p['cuenta']) ?></td>
                        <td><?= htmlspecialchars($p['gestor']) ?></td>
                        <td><span class="badge <?= $badge ?>"><?= $p['estatus'] ?></span></td>
                        <td class="text-end text-warning fw-bold">Q<?= number_format($p['monto'], 2) ?></td>
                        <td class="small text-secondary" style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($p['comentario']) ?>">
                            <?= htmlspecialchars($p['comentario']) ?>
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