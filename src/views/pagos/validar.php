<!-- Validación Masiva de Pagos -->
<div class="card bg-dark border-secondary p-4 mb-4">
    <h4 class="mb-3 fw-bold">🏦 Validación Masiva de Pagos</h4>
    <p class="text-secondary small mb-3">
        Cargue el reporte CSV del banco para confirmar los pagos reportados por los gestores (PAGG) y actualizar saldos.
    </p>
    <p class="text-info small mb-4">
        <strong>Formato requerido:</strong> Columnas <code>identificacion</code>, <code>monto</code>
    </p>

    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?= $tipoMensaje ?> alert-dismissible fade show" role="alert">
            <?= $mensaje ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form action="?action=validar_pagos" method="POST" enctype="multipart/form-data" class="row g-3">
        <div class="col-md-8">
            <input type="file" name="csv_banco" class="form-control bg-dark text-white border-secondary" accept=".xlsx, .xls" required>
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-success w-100 fw-bold">
                🚀 Validar y Descontar Saldos
            </button>
        </div>
    </form>
</div>

<!-- Pendientes de Validación -->
<div class="card bg-dark border-secondary p-4">
    <h5 class="mb-3 fw-bold">📋 Pendientes de Validación (Gestores)</h5>
    
    <?php if (!empty($pendientes)): ?>
    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="text-secondary text-uppercase small">Cliente</th>
                    <th class="text-secondary text-uppercase small">Identificación</th>
                    <th class="text-secondary text-uppercase small">Saldo Actual</th>
                    <th class="text-secondary text-uppercase small">Gestor</th>
                    <th class="text-secondary text-uppercase small">Fecha Gestión</th>
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
                    <td><?= date('d/m/Y H:i', strtotime($p['fecha_gestion'])) ?></td>
                    <td class="text-secondary small"><?= htmlspecialchars(substr($p['comentario'], 0, 50)) ?>...</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-success" onclick="alert('Validación individual: ID <?= $p['id'] ?>')">
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
        <i class="bi bi-info-circle me-2"></i>
        No hay pagos pendientes de validación en este momento.
    </div>
    <?php endif; ?>
</div>