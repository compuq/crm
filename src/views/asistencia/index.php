<?php

//require_once 'ultimos_registros.php';

$user = $this->session->getUser();
$role = $user['role'];
?>
<div id="horaActual" style="
    padding:10px;
    background:#1e293b;
    color:white;
    border-radius:8px;
    font-size:18px;
    display:inline-block;
">
    Cargando hora...
</div>

<script>
function actualizarHora() {
    const ahora = new Date();

    const opciones = {
        timeZone: 'America/Guatemala',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
    };

    const horaFormateada = ahora.toLocaleString('es-GT', opciones);

    document.getElementById('horaActual').innerHTML =
        'Hora del registro: ' + horaFormateada;
}

actualizarHora();

setInterval(actualizarHora, 1000);
</script>
<div class="container-fluid py-4">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1">🕒 Control de Asistencia</h3>
            <div class="text-secondary small">
                <?= htmlspecialchars($user['name']) ?>
            </div>
        </div>

        <?php if (in_array($role, ['admin', 'supervisor_general', 'supervisor'])): ?>
            <a href="?action=reportes_asistencia" class="btn btn-outline-info">
                📄 Reportes
            </a>
        <?php endif; ?>
        <?php if (in_array($role, ['admin', 'supervisor_general'])): ?>
            <a href="?action=estadisticas_asistencia" class="btn btn-outline-info">
                📊 Estadisticas
            </a>
        <?php endif; ?>
    </div>

    <!-- ESTADO ACTUAL -->
    <div class="card bg-dark border-secondary mb-4">
        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                <div>
                    <div class="text-secondary small mb-1">Estado actual</div>

                    <?php if (empty($ultimoMovimiento)): ?>
                        <span class="badge bg-secondary fs-6">
                            Sin registros hoy
                        </span>
                    <?php else: ?>

                        <?php if ($ultimoMovimiento['tipo_movimiento'] === 'entrada'): ?>
                            <span class="badge bg-success fs-6">
                                ✅ Dentro
                            </span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark fs-6">
                                🚪 Fuera
                            </span>
                        <?php endif; ?>

                        <div class="small text-secondary mt-2">
                            Último movimiento:
                            <strong>
                                <?= ucfirst($ultimoMovimiento['tipo_movimiento']) ?>
                            </strong>
                            -
                            <?= ucfirst($ultimoMovimiento['motivo']) ?>
                            -
                            <?= date('d/m/Y H:i', strtotime($ultimoMovimiento['fecha_hora'])) ?>
                        </div>

                    <?php endif; ?>
                </div>

                <!-- BOTONES DINÁMICOS -->
                <div class="d-flex flex-wrap gap-2">

                    <?php if (empty($ultimoMovimiento)): ?>

                        <!-- PRIMER INGRESO -->
                        <button class="btn btn-success"
                                onclick="abrirMovimiento('entrada', 'laboral')">
                            ✅ Entrada Laboral
                        </button>

                    <?php else: ?>

                        <?php
                        $tipo = $ultimoMovimiento['tipo_movimiento'];
                        $motivo = $ultimoMovimiento['motivo'];
                        ?>

                        <?php if ($tipo === 'entrada'): ?>

                            <!-- SALIDAS DISPONIBLES -->
                            <button class="btn btn-danger"
                                    onclick="abrirMovimiento('salida', 'laboral')">
                                🚪 Salida Final
                            </button>

                            <button class="btn btn-warning"
                                    onclick="abrirMovimiento('salida', 'almuerzo')">
                                🍽 Salida Almuerzo
                            </button>

                            <button class="btn btn-info"
                                    onclick="abrirMovimiento('salida', 'refaccion')">
                                ☕ Salida Refacción
                            </button>

                            <button class="btn btn-primary"
                                    onclick="abrirMovimiento('salida', 'permiso')">
                                📝 Salida Permiso
                            </button>

                            <button class="btn btn-secondary"
                                    onclick="abrirMovimiento('salida', 'otro')">
                                ⚠️ Salida Otro
                            </button>

                        <?php elseif ($tipo === 'salida' && $motivo !== 'laboral'): ?>

                            <!-- REGRESO OBLIGATORIO -->
                            <button class="btn btn-success"
                                    onclick="abrirMovimiento('entrada', '<?= $motivo ?>')">
                                ✅ Regresar de <?= ucfirst($motivo) ?>
                            </button>

                        <?php else: ?>

                            <div class="alert alert-secondary mb-0 py-2 px-3">
                                Jornada finalizada.
                            </div>

                        <?php endif; ?>

                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- HISTORIAL DEL DÍA -->
    <div class="card bg-dark border-secondary">

        <div class="card-header border-secondary d-flex justify-content-between align-items-center">
            <h5 class="mb-0">📋 Movimientos de Hoy</h5>
        </div>

        <div class="card-body p-0">

            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Hora</th>
                            <th>Movimiento</th>
                            <th>Motivo</th>
                            <th>Comentario</th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php if (!empty($movimientosHoy)): ?>

                            <?php foreach ($movimientosHoy as $m): ?>
                                <tr>
                                    <td>
                                        <?= date('H:i:s', strtotime($m['fecha_hora'])) ?>
                                    </td>

                                    <td>
                                        <?php if ($m['tipo_movimiento'] === 'entrada'): ?>
                                            <span class="badge bg-success">
                                                ✅ Entrada
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">
                                                🚪 Salida
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <span class="badge bg-secondary">
                                            <?= ucfirst($m['motivo']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= !empty($m['comentario'])
                                            ? htmlspecialchars($m['comentario'])
                                            : '<span class="text-secondary">-</span>' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="4" class="text-center text-secondary py-4">
                                    No hay movimientos registrados hoy.
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL MOVIMIENTO -->
<div class="modal fade" id="modal-movimiento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border-secondary">

            <form id="form-movimiento">

                <div class="modal-header border-secondary">
                    <h5 class="modal-title">🕒 Registrar Movimiento</h5>
                    <button type="button"
                            class="btn-close btn-close-white"
                            data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden" name="tipo_movimiento" id="tipo_movimiento">
                    <input type="hidden" name="motivo" id="motivo">

                    <div class="mb-3">
                        <label class="form-label small text-secondary">
                            Tipo de Movimiento
                        </label>

                        <input type="text"
                               id="texto_movimiento"
                               class="form-control bg-dark text-white border-secondary"
                               readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-secondary">
                            Comentario
                            <span id="comentario-label"></span>
                        </label>

                        <textarea name="comentario"
                                  id="comentario"
                                  rows="4"
                                  class="form-control bg-dark text-white border-secondary"
                                  placeholder="Agregar observación..."></textarea>
                    </div>

                </div>

                <div class="modal-footer border-secondary">
                    <button type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">
                        Cancelar
                    </button>

                    <button type="submit" class="btn btn-success">
                        💾 Registrar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {

    const modalMovimiento = new bootstrap.Modal(
        document.getElementById('modal-movimiento')
    );

    const form = document.getElementById('form-movimiento');
    const comentario = document.getElementById('comentario');
    const comentarioLabel = document.getElementById('comentario-label');

    window.abrirMovimiento = function(tipo, motivo) {

        document.getElementById('tipo_movimiento').value = tipo;
        document.getElementById('motivo').value = motivo;

        document.getElementById('texto_movimiento').value =
            tipo.toUpperCase() + ' - ' + motivo.toUpperCase();

        comentario.value = '';
        comentario.required = false;
        comentarioLabel.innerHTML = '(opcional)';

        if (motivo === 'permiso' || motivo === 'otro') {
            comentario.required = true;
            comentarioLabel.innerHTML = '<span class="text-danger">* requerido</span>';
        }

        modalMovimiento.show();
    };

    form.addEventListener('submit', function(e) {

        e.preventDefault();

        const fd = new FormData(form);

        console.log('📤 Enviando formulario...');

        fetch('?action=registrar_movimiento', {
            method: 'POST',
            body: fd
        })
        .then(async r => {

            console.log('📥 Status:', r.status);

            const text = await r.text();

            console.log('📥 Respuesta RAW:');
            console.log(text);

            return JSON.parse(text);
        })
        .then(res => {

            console.log('✅ JSON parseado:', res);

            if (res.success) {
                location.reload();
            } else {
                alert('❌ ' + res.msg);
            }
        })
        .catch(err => {

            console.error('❌ ERROR COMPLETO:');
            console.error(err);

            alert('❌ Error de conexión'+err);
        });

    });
});
</script>
