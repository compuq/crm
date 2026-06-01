<!-- views/reportes/gestiones.php -->
<div class="card bg-dark border-secondary p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold">📊 Reporte de Gestión de Llamadas</h4>
        <?php if (!empty($gestiones)): ?>
            <a href="?action=reportes_gestiones_excel&<?= http_build_query($filters) ?>" 
               class="btn btn-success" target="_blank">
                📥 Descargar Excel
            </a>
        <?php endif; ?>
    </div>

    <!-- 🔍 FILTROS -->
    <form method="GET" class="row g-3 mb-4 bg-dark bg-opacity-50 p-3 rounded border border-secondary">
        <input type="hidden" name="action" value="reportes_gestiones">
        <div class="col-md-2">
            <label class="form-label small text-secondary">Desde</label>
            <input type="date" name="fecha_inicio" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($filters['fecha_inicio']) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label small text-secondary">Hasta</label>
            <input type="date" name="fecha_fin" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($filters['fecha_fin']) ?>">
        </div>

        <!-- ✅ FILTRO SUPERVISOR (Solo Admin/Supervisor General) -->
        <?php if (in_array($rol, ['admin', 'supervisor_general']) && !empty($supervisores)): ?>
            <div class="col-md-3">
                <label class="form-label small text-secondary">Supervisor</label>
                <select name="supervisor_id" class="form-select bg-dark text-white border-secondary">
                    <option value="">Todos los supervisores</option>
                    <?php foreach($supervisores as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $filters['supervisor_id'] == $s['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <!-- FILTRO GESTOR -->
        <?php if (in_array($rol, ['admin', 'supervisor', 'supervisor_general'])): ?>
            <div class="col-md-2">
                <label class="form-label small text-secondary">Gestor</label>
                <select name="usuario_id" class="form-select bg-dark text-white border-secondary">
                    <option value="">Todos</option>
                    <?php foreach($usuarios as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= $filters['usuario_id'] == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <!-- FILTRO CARTERA -->
        <?php if (in_array($rol, ['admin', 'supervisor'])): ?>
            <div class="col-md-2">
                <label class="form-label small text-secondary">Cartera</label>
                <select name="cartera_id" class="form-select bg-dark text-white border-secondary">
                    <option value="">Todas</option>
                    <?php foreach($carteras as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $filters['cartera_id'] == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="col-md-12 d-flex gap-2 mt-2">
            <button type="submit" class="btn btn-lex-primary px-4">🔍 Aplicar Filtros</button>
            <a href="?action=reportes_gestiones" class="btn btn-outline-secondary">🔄 Limpiar</a>
        </div>
    </form>

    <!-- 📋 TABLA -->
    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle mb-0">
            <thead class="table-secondary">
                <tr class="text-uppercase small">
                    <th>Fecha</th>
                    <th>Supervisor</th>
                    <th>Gestor</th>
                    <th>Cliente</th>
                    <th>Cuenta</th>
                    <th>Tipología</th>
                    <th>Estatus</th>
                    <th>💰 Monto</th>
                    <th>Comentario</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($gestiones)): ?>
                    <tr><td colspan="8" class="text-center text-secondary py-4">No hay gestiones con estos filtros.</td></tr>
                <?php else: ?>
                    <?php foreach($gestiones as $g): 
                        $badge = match($g['estatus']) {
                            'SINC' => 'bg-secondary',
                            'COMP' => 'bg-warning text-dark',
                            'PAGG' => 'bg-info text-dark',
                            'PAGO' => 'bg-success',
                            default => 'bg-secondary'
                        };
                    ?>
                    <tr>
                        <td class="small"><?= date('d/m/Y H:i', strtotime($g['fecha_gestion'])) ?></td>
                        <td class="small text-secondary"><?= htmlspecialchars($g['supervisor_nombre'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($g['gestor_nombre']) ?></td>
                        <td><?= htmlspecialchars($g['cliente_nombre']) ?></td>
                        <td class="fw-medium text-nowrap">
                            <a href="?action=clientes&q=<?= htmlspecialchars($g['cuenta']) ?>"
                            class="link-info text-decoration-none">
                                <?= htmlspecialchars($g['cuenta']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($g['tipologia_nombre'] ?? '-') ?></td>
                        <td><span class="badge <?= $badge ?>"><?= $g['estatus'] ?></span></td>
                        <td class="text-center text-warning fw-bold">

                            <?php /* if (!isset($g['monto_reporte']) || !($g['monto_reporte'])){
                                $monto_reporte=0;
                            }else{
                                $monto_reporte=(float)($g['monto_reporte']);
                            } */
                            echo $g['monto_reporte']??"--";
                            //echo "Q".number_format($monto_reporte, 2);
                             ?>

                        </td>
                        <td class="small text-secondary" style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($g['comentario']) ?>">
                            <?= htmlspecialchars($g['comentario']) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <small class="text-secondary mt-2 d-block">Mostrando últimos <?= count($gestiones) ?> registros.</small>
</div>