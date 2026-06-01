<!-- Auditoría del Sistema -->
<div class="card bg-dark border-secondary p-4 mb-4">
    <h4 class="mb-3 fw-bold">🔍 Trazabilidad y Auditoría</h4>
            <div class="col-md-3">
            <button class="btn btn-success"
                    onclick="exportarTablaExcel(
                        'trazabilidad',
                        'detalle-trazabilidad',
                        'Detalle Trazabilidad y Auditoría'
                    )">
                Exportar Excel
            </button>
        </div>

    <p class="text-secondary small mb-3">Consulta el historial de cambios críticos, logins y modificaciones de datos.</p>

    <form method="GET" action="index.php" class="row g-2 mb-3">
        <input type="hidden" name="action" value="auditoria">
        <div class="col-md-2">
            <input type="date" name="fecha" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($_GET['fecha'] ?? date('Y-m-d')) ?>">
        </div>
        <div class="col-md-3">
            <input type="text" name="usuario" class="form-control bg-dark text-white border-secondary" placeholder="Buscar usuario..." value="<?= htmlspecialchars($_GET['usuario'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <select name="accion" class="form-select bg-dark text-white border-secondary">
                <option value="">Todas las acciones</option>
                <option value="login" <?= ($_GET['accion'] ?? '') === 'login' ? 'selected' : '' ?>>Login</option>
                <option value="carga_csv" <?= ($_GET['accion'] ?? '') === 'carga_csv' ? 'selected' : '' ?>>Carga CSV</option>
                <option value="cambio_saldo" <?= ($_GET['accion'] ?? '') === 'cambio_saldo' ? 'selected' : '' ?>>Cambio Saldo</option>
                <option value="traslado_cliente" <?= ($_GET['accion'] ?? '') === 'traslado_cliente' ? 'selected' : '' ?>>Traslado Cliente</option>
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-lex-primary w-100">🔍 Filtrar</button>
        </div>

    </form>

    <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
        <table class="table table-dark table-hover align-middle mb-0 text-nowrap" id="trazabilidad">
            <thead class="sticky-top bg-dark">
                <tr>
                    <th class="text-secondary small">Fecha/Hora</th>
                    <th class="text-secondary small">Usuario</th>
                    <th class="text-secondary small">Acción</th>
                    <th class="text-secondary small">Tabla Afectada</th>
                    <th class="text-secondary small">ID Registro</th>
                    <th class="text-center text-secondary small">Detalles</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($logs as $log): ?>
                <tr>
                    <td class="small"><?= date('d/m/Y H:i:s', strtotime($log['fecha'])) ?></td>
                    <td class="fw-medium"><?= htmlspecialchars($log['usuario_nombre'] ?? 'Sistema') ?></td>
                    <td><span class="badge bg-info text-dark"><?= strtoupper($log['accion']) ?></span></td>
                    <td><code class="small"><?= htmlspecialchars($log['tabla_afectada']) ?></code></td>
                    <td><?= $log['registro_id'] ?></td>
                    <td class="text-center">
                        <?php if ($log['datos_anteriores'] || $log['datos_nuevos']): ?>
                            <button class="btn btn-sm btn-outline-warning" 
                                    onclick='verDiff(<?= json_encode($log['datos_anteriores']) ?>, <?= json_encode($log['datos_nuevos']) ?>)'>
                                📄 Ver Diff
                            </button>
                        <?php else: ?>
                            <span class="text-secondary small">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($logs)): ?>
                    <tr><td colspan="6" class="text-center text-secondary py-4">No se encontraron registros para los filtros seleccionados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Visualizador de Diff JSON -->
<div class="modal fade" id="modalDiff" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-white"> Comparación de Cambios</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <h6 class="text-danger small fw-bold">ANTES</h6>
                        <pre id="diffOld" class="bg-dark border border-danger p-2 rounded small" style="color:#ff6b6b; overflow-x:auto;"></pre>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-success small fw-bold">DESPUÉS</h6>
                        <pre id="diffNew" class="bg-dark border border-success p-2 rounded small" style="color:#51cf66; overflow-x:auto;"></pre>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
function verDiff(oldData, newData) {
    document.getElementById('diffOld').textContent = oldData ? JSON.stringify(JSON.parse(oldData), null, 2) : 'Sin datos previos';
    document.getElementById('diffNew').textContent = newData ? JSON.stringify(JSON.parse(newData), null, 2) : 'Sin datos nuevos';
    new bootstrap.Modal(document.getElementById('modalDiff')).show();
}
</script>