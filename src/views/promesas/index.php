<!-- Mis Promesas -->
<div class="card bg-dark border-secondary p-4 mb-4">
    <h4 class="mb-3 fw-bold"> Mis Promesas de Pago</h4>
    <p class="text-secondary small mb-3">Listado de compromisos registrados por ti. El sistema actualizará el estado cuando el Admin valide el pago.</p>

    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="text-secondary small text-uppercase">Cliente</th>
                    <th class="text-secondary small text-uppercase">Identificación</th>
                    <th class="text-secondary small text-uppercase">Monto Prometido</th>
                    <th class="text-secondary small text-uppercase">Fecha Compromiso</th>
                    <th class="text-center text-secondary small text-uppercase">Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($promesas as $p): ?>
                <tr>
                    <td class="fw-medium"><?= htmlspecialchars($p['cliente_nombre']) ?></td>
                    <td><?= htmlspecialchars($p['identificacion']) ?></td>
                    <td class="text-warning fw-bold">Q<?= number_format($p['monto_prometido'], 2) ?></td>
                    <td><?= date('d/m/Y', strtotime($p['fecha_compromiso'])) ?></td>
                    <td class="text-center">
                        <?php if ($p['estatus'] === 'cumplida'): ?>
                            <span class="badge bg-success">✅ Cumplida</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">⏳ Pendiente</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($promesas)): ?>
                    <tr><td colspan="5" class="text-center text-secondary py-4">No tienes promesas registradas.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>