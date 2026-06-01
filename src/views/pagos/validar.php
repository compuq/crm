<!-- views/pagos/validar.php -->
<div class="card bg-dark border-secondary p-4 mb-4">
    <h4 class="mb-3 fw-bold">✅ Validación de Pagos</h4>

    <p class="text-secondary small">
        Valide los pagos reportados por los gestores (PAGG) ingresando la referencia bancaria.
    </p>

    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?= $tipoMensaje ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($mensaje) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ✅ Filtros Avanzados -->
    <form method="GET"  class="row g-3 mb-4">
        <input type="hidden" name="action" value="validar_pagos">
        <div class="col-md-3">
            <label class="form-label small text-secondary">Gestor</label>
            <select name="gestor_id" class="form-select bg-dark text-white border-secondary">
                <option value="">Todos los gestores</option>
                <?php foreach($listaGestores as $g): ?>
                    <option value="<?= $g['id'] ?>" <?= ($filtroGestor == $g['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($g['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small text-secondary">Supervisor</label>
            <select name="supervisor_id" class="form-select bg-dark text-white border-secondary">
                <option value="">Todos los supervisores</option>
                <?php foreach($listaSupervisores as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= ($filtroSupervisor == $s['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small text-secondary">Desde</label>
            <input type="date" name="fecha_inicio" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($filtroFechaInicio) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label small text-secondary">Hasta</label>
            <input type="date" name="fecha_fin" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($filtroFechaFin) ?>">
        </div>
        <div class="col-md-2">

            <label class="form-label small text-secondary">Buscar</label>
            <input type="text" name="buscar" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($buscar) ?>">
        </div>
        
        <div class="col-md-2 d-flex align-items-end gap-2">

            <button type="submit" class="btn btn-lex-primary flex-grow-1">🔍 Filtrar</button>
            
            <a href="?action=validar_pagos" class="btn btn-outline-secondary" title="Limpiar filtros">🔄</a>
        </div>
    </form>
    
</div>

<!-- ✅ Tabla de Pendientes -->
<div class="card bg-dark border-secondary p-4">
    <h5 class="mb-3 fw-bold">
        <button class="btn btn-success"
                onclick="exportarTablaExcel(
                    'validacion-pagos',
                    'validacion_pagos_pendientes_validar',
                    'Detalle de pagos pendientes de validar'
                    )">
                Exportar Excel
            </button>
        📋 Pagos Pendientes de Validar                
</h5>
    
    <?php if (!empty($pendientes)): ?>
    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle mb-0" id ="validacion-pagos">
            <thead>
                <tr>
                    <th class="text-secondary text-uppercase small">Cliente</th>
                    <th class="text-secondary text-uppercase small">Identificación</th>
                    <th class="text-secondary text-uppercase small">Saldo</th>
                    <th class="text-secondary text-uppercase small">Gestor</th>
                    <th class="text-secondary text-uppercase small">Supervisor</th>
                    <th class="text-secondary text-uppercase small">Fecha</th>
                    <th class="text-secondary text-uppercase small">Monto</th>
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
                    <td><?= htmlspecialchars($p['supervisor'] ?? '-') ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($p['fecha_gestion'])) ?></td>
                    <td class="text-success fw-bold">Q<?= number_format($p['monto'], 2) ?></td>
                    <td><?= htmlspecialchars($p['comentario']) ?></td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-success" 
                                onclick="abrirModalValidar(<?= $p['pago_id'] ?>, '<?= addslashes($p['nombre']) ?>', <?= $p['monto'] ?>)">
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
        <i class="bi bi-check-circle me-2"></i>
        No hay pagos pendientes de validación con los filtros actuales.
    </div>
    <?php endif; ?>
</div>

<!-- ✅ Modal de Validación Individual -->
<div class="modal fade" id="modalValidarPago" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border-secondary">
            <form id="formValidarPago">
                <input type="hidden" id="pagoIdInput" name="pago_id">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title text-white">✅ Validar Pago</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-secondary small mb-3">
                        Cliente: <strong id="modalClienteNombre" class="text-info"></strong><br>
                        Monto: <strong class="text-success" id="modalMonto"></strong>
                    </p>
                    
                    <div class="mb-3">
                        <label class="form-label text-secondary">🏦 Referencia Bancaria *</label>
                        <input type="text" name="referencia_bancaria" id="inputReferencia" 
                               class="form-control bg-dark text-white border-secondary" 
                               placeholder="Ej: Boleta #12345, Banco XYZ, Transferencia ABC"
                               required maxlength="100">
                        <small class="text-secondary">Número de boleta, referencia de transferencia o comprobante</small>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success fw-bold">💾 Confirmar Validación</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ✅ Script para Modal y AJAX -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalEl = document.getElementById('modalValidarPago');
    if (!modalEl) return;
    const modal = new bootstrap.Modal(modalEl);
    const form = document.getElementById('formValidarPago');

    // Función global para abrir modal
    window.abrirModalValidar = function(pagoId, clienteNombre, monto) {
        document.getElementById('pagoIdInput').value = pagoId;
        document.getElementById('modalClienteNombre').textContent = clienteNombre;
        document.getElementById('modalMonto').textContent = 'Q' + parseFloat(monto).toFixed(2);
        document.getElementById('inputReferencia').value = '';
        modal.show();
    };

    // Submit del formulario vía AJAX
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const btn = form.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '⏳ Validando...';

        fetch('?action=validar_pago', {
            method: 'POST',
            body: new FormData(form)
        })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            
            if (res.success) {
                modal.hide();
                // Mostrar mensaje y recargar
                const alert = document.createElement('div');
                alert.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3';
                alert.innerHTML = res.message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                document.body.appendChild(alert);
                setTimeout(() => location.reload(), 1500);
            } else {
                alert('❌ ' + res.message);
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            alert('❌ Error de conexión con el servidor');
        });
    });
});
</script>