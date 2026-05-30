<!-- views/clientes/listar.php -->
<div class="card bg-dark border-secondary p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0 fw-bold">👥 Gestión de Clientes</h4>
        <form method="GET" action="index.php" class="d-flex gap-2">
            <input type="hidden" name="action" value="clientes">
            <input type="text" name="q" class="form-control bg-dark text-white border-secondary" placeholder="Buscar por nombre o identificación..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            <button type="submit" class="btn btn-lex-primary">🔍 Buscar</button>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="text-secondary text-uppercase small">Cuenta</th>
                    <th class="text-secondary text-uppercase small">Nombre</th>
                    <th class="text-secondary text-uppercase small">Identificación</th>
                    <th class="text-secondary text-uppercase small">Saldo</th>
                    <th class="text-secondary text-uppercase small">Teléfono</th>
                    <th class="text-secondary text-uppercase small">Próxima Llamada</th>
                    <?php if (!empty($configExtras)): ?>
                        <?php foreach ($configExtras as $extra): ?>
                            <th class="text-secondary text-uppercase small"><?= htmlspecialchars($extra['etiqueta']) ?></th>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <th class="text-center text-secondary text-uppercase small">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($clientes as $cliente): ?>
                <tr>
                    <td class="fw-medium"><?= htmlspecialchars($cliente['cuenta']) ?></td>
                    <td><?= htmlspecialchars($cliente['nombre']) ?></td>
                    <td><?= htmlspecialchars($cliente['identificacion']) ?></td>
                    <td class="text-warning">Q<?= number_format($cliente['saldo'], 2) ?></td>
                    <td><?= htmlspecialchars($cliente['telefono_1']) ?></td>
                    <td><?= htmlspecialchars($cliente['fecha_proxima_llamada'] ?? 'Sin fecha') ?></td>
                    <?php if (!empty($configExtras)): ?>
                        <?php foreach ($configExtras as $extra): 
                            $val = '-';
                            if (!empty($cliente['data_extras'])) {
                                $extras = is_string($cliente['data_extras']) ? json_decode($cliente['data_extras'], true) : $cliente['data_extras'];
                                if (is_array($extras) && isset($extras[$extra['nombre_campo']])) $val = $extras[$extra['nombre_campo']];
                            }
                        ?>
                        <td class="small text-secondary"><?= htmlspecialchars($val) ?></td>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <td class="text-center">
                        <button class="btn btn-sm btn-lex-primary" 
                                onclick='abrirModalGestion(<?= $cliente['id'] ?>, "<?= addslashes($cliente['nombre']) ?>", "<?= addslashes($cliente['identificacion'] ?? '') ?>", <?= $cliente['id_cartera'] ?? 'null' ?>)'>
                            📞 Gestionar
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($clientes)): ?>
                    <tr><td colspan="<?= 6 + count($configExtras) ?>" class="text-center text-secondary py-4">No se encontraron clientes.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL DE GESTIÓN                           -->
<!-- ========================================== -->
<div class="modal fade" id="modalGestion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content bg-dark border-secondary shadow-lg">
            <div class="modal-header border-secondary bg-secondary bg-opacity-10">
                <h5 class="modal-title text-white">📞 Gestionar: <span id="lblCliente" class="text-info fw-bold"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0">
                    <!-- COLUMNA IZQUIERDA: FORMULARIO -->
                    <div class="col-lg-7 p-4 border-end border-secondary">
                        <h6 class="text-uppercase text-secondary small mb-3 fw-bold">📝 Registro de Gestión</h6>
                        <form id="formGestion">
                            <input type="hidden" id="clienteId" name="cliente_id">
                            <!-- ✅ BOTÓN FICHA (DENTRO DEL FORMULARIO, SIN DATA-BS-TOGGLE) -->
                            <div class="col-md-12 d-flex align-items-end">
                                <button type="button" class="btn btn-sm btn-outline-info w-100" id="btnVerFicha">
                                    👁️ Ver Ficha (Copiar datos)
                                </button>
                            </div>
                            <!-- Botón para ver últimas gestiones -->
                            <!-- Botón corregido (SIN atributos data-bs-*) -->
                            <div class="col-12 mt-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary w-100" id="btnVerHistorial">
                                    📜 Ver Últimas 5 Gestiones
                                </button>
                            </div>                            
                            <div class="row g-3">
                                <!-- Tipología y Estatus -->
                                <div class="col-md-6">
                                    <label class="form-label small text-secondary">Tipología *</label>
                                    <select class="form-select bg-dark text-white border-secondary" id="tipologia" name="tipologia" required>
                                        <option value="">Cargando catálogo...</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-secondary">Estatus *</label>
                                    <select class="form-select bg-dark text-white border-secondary" id="estatus" name="estatus" required>
                                        <option value="SINC">Sin Compromiso</option>
                                        <option value="COMP">Compromiso de Pago</option>
                                        <option value="PAGG">Pago Reportado (Pendiente)</option>
                                    </select>
                                </div>
                                
                                <!-- Teléfono utilizado -->
                                <div class="col-md-6">
                                    <label class="form-label small text-secondary">Teléfono utilizado</label>
                                    <input type="text" class="form-control bg-dark text-white border-secondary" name="telefono_utilizado" placeholder="Ej: 5555-1234">
                                </div>

                                <!-- Campo Monto Dinámico -->
                                <div class="col-md-6 d-none" id="box-monto">
                                    <label class="form-label small text-secondary" id="lbl-monto">💰 Monto</label>
                                    <input type="number" step="0.01" name="monto_gestion" id="inputMonto" class="form-control bg-dark text-white border-secondary" placeholder="0.00">
                                    <small id="msgMonto" class="text-danger small"></small>
                                </div>

                                <!-- Campo Fecha Próxima Llamada -->
                                <div class="col-12 mt-2 d-none" id="contenedorFechaProxima">
                                    <label class="form-label text-secondary small">📅 Fecha y Hora de Próxima Llamada</label>
                                    <input type="datetime-local" name="fecha_compromiso" id="fechaProximaInput" class="form-control form-control-sm bg-dark text-white border-secondary">
                                    <small id="msgFechaProxima" class="text-secondary"></small>
                                </div>

                                <!-- Extras Dinámicos de Gestión -->
                                <div id="extras-gestion-container" class="row g-2 mb-3"></div>

                                <!-- ✅ Selección de Promesa (Solo visible si estatus = PAGG) -->
                                <!-- PROMESAS PENDIENTES -->
                                <div id="box-promesas-pendientes"
                                    class="mt-3 p-2 border border-warning rounded d-none">

                                    <label class="form-label small text-warning fw-bold">
                                        📌 ¿A qué promesa se aplica este pago?
                                    </label>

                                    <div id="lista-promesas"
                                        class="bg-dark rounded p-2"
                                            style="max-height:150px; overflow-y:auto;">

                                        <small class="text-secondary">
                                            Sin promesas cargadas
                                        </small>

                                    </div>


                                </div>
                                <!-- Comentario -->
                                <div class="col-12">
                                    <label class="form-label small text-secondary">Comentario Obligatorio *</label>
                                    <textarea class="form-control bg-dark text-white border-secondary" name="comentario" rows="4" required placeholder="Detalle de la interacción con el cliente..."></textarea>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- COLUMNA DERECHA: CONSULTA EXTERNA -->
                    <div class="col-lg-5 p-4 bg-dark bg-opacity-50">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="text-uppercase text-info small fw-bold mb-0">🔍 Consulta Externa</h6>
                            <span class="badge bg-warning text-dark">Base Pendiente</span>
                        </div>
                        <p class="text-secondary small mb-3">Busca información en bases externas.</p>
                        <form id="formConsultaExterna" onsubmit="event.preventDefault(); buscarEnBaseExterna();">
                            <div class="mb-3"><label class="form-label small text-secondary">DPI / CUI</label><input type="text" class="form-control bg-dark text-white border-secondary" id="extDpi"></div>
                            <div class="mb-3"><label class="form-label small text-secondary">Nombre Completo</label><input type="text" class="form-control bg-dark text-white border-secondary" id="extNombre"></div>
                            <button type="submit" class="btn btn-outline-info w-100 mb-2" id="btnBuscar" disabled>🔍 Buscar</button>
                        </form>
                        <div id="resultadoConsulta" class="consulta-printable" style="display:none;">
                            <div class="alert alert-dark border-secondary p-3 mb-2">
                                <div class="d-flex justify-content-between align-items-center mb-2"><strong class="text-info">📋 Resultado</strong><small class="text-secondary" id="fechaConsulta"></small></div>
                                <hr class="border-secondary my-2"><div id="contenidoConsulta" class="small"></div>
                            </div>
                            <div class="btn-group w-100">
                                <button type="button" class="btn btn-sm btn-success" onclick="imprimirConsulta()">🖨️ Imprimir</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="cerrarConsulta()">✖ Cerrar</button>
                            </div>
                        </div>
                        <div id="placeholderConsulta" class="text-center py-4">
                            <i class="bi bi-database text-secondary" style="font-size: 3rem; opacity: 0.3;"></i>
                            <p class="text-secondary small mt-2 mb-0">Sin datos consultados</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-secondary bg-secondary bg-opacity-10">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success fw-bold px-4" onclick="guardarGestion()">💾 Guardar Gestión</button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- 📋 MODAL FICHA (HERMANO, NO HIJO)          -->
<!-- ========================================== -->
<div class="modal fade" id="modalFichaCliente" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-white">📋 Ficha de Cliente</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="ficha-datos" class="row g-2"></div>
            </div>
            <div class="modal-footer border-secondary">
                <small class="text-secondary">💡 Haz click en cualquier campo para copiar. Al cerrar, el dato queda en el portapapeles.</small>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<!-- 📜 MODAL HISTORIAL DE GESTIONES -->
<div class="modal fade" id="modalHistorialGestiones" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-white">📜 Historial Reciente</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="historial-lista" class="list-group list-group-flush">
                    <small class="text-secondary">⏳ Cargando gestiones...</small>
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <small class="text-secondary">💡 Las gestiones se muestran de más reciente a más antigua.</small>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<!-- 📞 PANEL DE ALERTAS (LADO DERECHO) -->
<div id="alertas-panel" class="position-fixed top-0 end-0 p-3" style="z-index: 10000; max-width: 320px;">
    <!-- Aquí se inyectan las alertas dinámicamente -->
</div>
<!-- ESTILOS -->
<style>
@media print {
    body * { visibility: hidden; }
    #resultadoConsulta, #resultadoConsulta * { visibility: visible; }
    #resultadoConsulta { position: absolute; left: 0; top: 0; width: 100%; background: white !important; color: black !important; padding: 20px; }
    #resultadoConsulta .btn-group { display: none !important; }
    .modal, .navbar, .footer { display: none !important; }
}
.copyable { cursor: pointer; transition: all 0.2s; }
.copyable:hover { border-color: #0dcaf0 !important; background-color: rgba(13, 202, 240, 0.1) !important; }
.copy-hint { font-size: 0.7rem; opacity: 0.8; }
.copyable:hover .copy-hint { opacity: 1; }
</style>

<!-- ✅ SCRIPT UNIFICADO -->
<script>
document.addEventListener('DOMContentLoaded', function () {

    // =========================================
    // VARIABLES GLOBALES
    // =========================================
    window.clienteActual = {};

    const modalGestionEl = document.getElementById('modalGestion');
    const modalFichaEl = document.getElementById('modalFichaCliente');

    let modalGestion = null;
    let modalFicha = null;

    if (modalGestionEl) modalGestion = new bootstrap.Modal(modalGestionEl);
    if (modalFichaEl) modalFicha = new bootstrap.Modal(modalFichaEl);

    // =========================================
    // REFERENCIAS DOM
    // =========================================
    const selTipologia = document.getElementById('tipologia');
    const selEstatus = document.getElementById('estatus');
    const divFecha = document.getElementById('contenedorFechaProxima');
    const inputFecha = document.getElementById('fechaProximaInput');
    const msgFecha = document.getElementById('msgFechaProxima');
    const boxMonto = document.getElementById('box-monto');
    const inputMonto = document.getElementById('inputMonto');
    const lblMonto = document.getElementById('lbl-monto');
    const msgMonto = document.getElementById('msgMonto');
    const containerExtras = document.getElementById('extras-gestion-container');
    const btnVerFicha = document.getElementById('btnVerFicha');
    const btnVerHistorial = document.getElementById('btnVerHistorial');

    // ✅ PROMESAS
    const boxPromesas = document.getElementById('box-promesas-pendientes');
    const listaPromesas = document.getElementById('lista-promesas');
    const inputPromesa = document.getElementById('inputPromesaSeleccionada');

    // =========================================
    // CONFIGURACIÓN TIPOLOGÍAS
    // =========================================
    let configTipologias = {};

    fetch('?action=get_tipologias_config')
        .then(r => r.json())
        .then(data => {
            if (Array.isArray(data)) {
                data.forEach(t => {
                    configTipologias[t.id] = {
                        estatus_default: t.estatus_default || 'SINC',
                        requiere_proxima_fecha: t.requiere_proxima_fecha === true || t.requiere_proxima_fecha === 'true',
                        requiere_monto: t.requiere_monto === true || t.requiere_monto === 'true'
                    };
                });
            }
        });

    // =========================================
    // FUNCIONES AUXILIARES
    // =========================================
    function evaluarCampos() {
        const tipId = selTipologia ? selTipologia.value : '';
        const est = selEstatus ? selEstatus.value : '';
        const cfg = configTipologias[tipId] || {};

        if (divFecha && inputFecha && msgFecha) {
            const showDate = cfg.requiere_proxima_fecha === true;
            divFecha.classList.toggle('d-none', !showDate);
            inputFecha.required = showDate;
            msgFecha.textContent = showDate ? '⚠️ Obligatorio para esta tipología' : '';
        }

        if (boxMonto && inputMonto) {
            let showAmount = cfg.requiere_monto === true;
            let label = '💰 Monto', required = false;
            if (est === 'COMP') { showAmount = true; label = '💰 Monto Prometido'; required = true; }
            else if (est === 'PAGG') { showAmount = true; label = '💳 Monto Reportado'; required = true; }
            boxMonto.classList.toggle('d-none', !showAmount);
            lblMonto.textContent = label;
            inputMonto.required = required;
            msgMonto.textContent = required ? '⚠️ Obligatorio' : '';
        }
    }

    function cargarPromesasPendientes(clienteId) {
        if (!listaPromesas) return;
        listaPromesas.innerHTML = `<small class="text-secondary">⏳ Cargando promesas...</small>`;
        if (boxPromesas) { boxPromesas.classList.remove('d-none'); boxPromesas.style.display = 'block'; }
        if (inputPromesa) inputPromesa.value = '';

        fetch(`?action=get_promesas_pendientes&cliente_id=${clienteId}`)
            .then(res => res.ok ? res.json() : Promise.reject('Error HTTP'))
            .then(promesas => {
                listaPromesas.innerHTML = '';
                if (!Array.isArray(promesas) || promesas.length === 0) {
                    listaPromesas.innerHTML = `<small class="text-warning">⚠️ No hay promesas pendientes</small>`;
                    return;
                }
                promesas.forEach(p => {
                    let fechaTxt = 'Sin fecha';
                    try { if (p.fecha_compromiso) fechaTxt = new Date(p.fecha_compromiso.replace(' ', 'T')).toLocaleDateString('es-GT'); } catch (e) {}
                    listaPromesas.insertAdjacentHTML('beforeend', `
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="id_promesa_seleccionada" value="${p.id}" id="prom_${p.id}">
                            <label class="form-check-label text-secondary small" for="prom_${p.id}">
                                💰 <strong>Q${parseFloat(p.monto_prometido || 0).toFixed(2)}</strong> | Vence: ${fechaTxt}
                            </label>
                        </div>`);
                });
            })
            .catch(() => listaPromesas.innerHTML = `<small class="text-danger">❌ Error cargando</small>`);
    }

    function cargarUltimasGestiones(clienteId) {
        const lista = document.getElementById('historial-lista');
        if (!lista) return;
        lista.innerHTML = '<small class="text-secondary">⏳ Cargando gestiones...</small>';
        fetch(`?action=get_ultimas_gestiones&cliente_id=${clienteId}`)
            .then(res => res.ok ? res.json() : Promise.reject('Error'))
            .then(gestiones => {
                lista.innerHTML = '';
                if (!Array.isArray(gestiones) || gestiones.length === 0) {
                    lista.innerHTML = '<small class="text-warning">⚠️ No hay gestiones registradas.</small>';
                    return;
                }
                gestiones.forEach(g => {
                    const badgeClass = { 'SINC': 'bg-secondary', 'COMP': 'bg-warning text-dark', 'PAGG': 'bg-info text-dark', 'PAGO': 'bg-success' }[g.estatus] || 'bg-secondary';
                    lista.insertAdjacentHTML('beforeend', `
                        <div class="list-group-item bg-dark border-secondary mb-2 rounded">
                            <span class="badge ${badgeClass} mb-1">${g.estatus}</span>
                            <strong class="text-white d-block">${g.tipologia || 'Sin tipología'}</strong>
                            <div class="small text-secondary">🕐 ${g.fecha_gestion_fmt || ''} • 👤 ${g.gestor || '?'}</div>
                            <p class="text-secondary small mt-2 mb-0">${g.comentario ? g.comentario.substring(0, 200) + '...' : ''}</p>
                            ${g.fecha_proxima_fmt ? `<div class="mt-1"><small class="text-info">📅 Próxima: ${g.fecha_proxima_fmt}</small></div>` : ''}
                        </div>`);
                });
            })
            .catch(() => lista.innerHTML = `<small class="text-danger">⚠️ Error</small>`);
    }

    function renderizarFicha(cliente) {
        const container = document.getElementById('ficha-datos');
        if (!container) return;
        container.innerHTML = '';
        const formatMoney = (val) => { const n = parseFloat(val); return isNaN(n) ? 'Q0.00' : new Intl.NumberFormat('es-GT', { minimumFractionDigits: 2 }).format(n); };
        const addField = (label, value, type = 'text') => {
            if (!value || value === 'Nunca' || String(value).trim() === '') return '';
            const safeValue = String(value).replace(/"/g, '&quot;');
            const display = type === 'money' ? `Q${formatMoney(value)}` : value;
            return `<div class="col-md-6 mb-2">
                <label class="form-label text-secondary small mb-1">${label}</label>
                <div class="copyable p-2 rounded bg-dark border border-secondary d-flex justify-content-between align-items-center cursor-pointer" data-clipboard="${safeValue}">
                    <span class="${type === 'money' ? 'text-warning fw-bold' : 'text-white'}">${display}</span>
                    <span class="badge bg-secondary copy-hint" style="font-size:0.7rem">📋</span>
                </div>
            </div>`;
        };
        let html = '';
        html += addField('Nombre', cliente.nombre);
        html += addField('Identificación', cliente.identificacion);
        html += addField('Cuenta', cliente.cuenta);
        html += addField('Saldo Inicial', cliente.saldo_inicial, 'money');
        html += addField('Saldo Total', cliente.saldo, 'money');
        html += addField('Estado', cliente.estado);
        html += addField('Teléfono 1', cliente.telefono_1);
        html += addField('Teléfono 2', cliente.telefono_2);
        html += addField('Última Gestión', cliente.fecha_ultima_gestion ? new Date(cliente.fecha_ultima_gestion).toLocaleString('es-GT') : 'Nunca');
        try {
            let extras = typeof cliente.data_extras === 'string' ? JSON.parse(cliente.data_extras) : cliente.data_extras || {};
            if (extras && typeof extras === 'object') {
                Object.entries(extras).forEach(([key, val]) => {
                    if (val && val !== 'null' && val !== '') {
                        const label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                        html += addField(label, val);
                    }
                });
            }
        } catch (e) {}
        container.innerHTML = html;
    }

    // =========================================
    // EVENT LISTENERS
    // =========================================
    if (selTipologia) {
        selTipologia.addEventListener('change', function () {
            const cfg = configTipologias[this.value] || {};
            if (selEstatus && cfg.estatus_default) {
                selEstatus.value = cfg.estatus_default;
                selEstatus.dispatchEvent(new Event('change', { bubbles: true }));
            }
            evaluarCampos();
        });
    }

    if (selEstatus) {
        selEstatus.addEventListener('change', function () {
            const est = this.value;
            const clienteId = document.getElementById('clienteId')?.value;
            if (est === 'PAGG') {
                if (boxPromesas) { boxPromesas.classList.remove('d-none'); boxPromesas.style.display = 'block'; }
                if (clienteId) cargarPromesasPendientes(clienteId);
            } else {
                if (boxPromesas) { boxPromesas.classList.add('d-none'); boxPromesas.style.display = 'none'; }
                if (inputPromesa) inputPromesa.value = '';
            }
            evaluarCampos();
        });
    }

    if (btnVerFicha) {
        btnVerFicha.addEventListener('click', function () {
            if (modalFicha && window.clienteActual.id) { renderizarFicha(window.clienteActual); modalFicha.show(); }
        });
    }

    if (btnVerHistorial) {
        btnVerHistorial.addEventListener('click', function() {
            if (window.clienteActual?.id) {
                cargarUltimasGestiones(window.clienteActual.id);
                const modalHistorialEl = document.getElementById('modalHistorialGestiones');
                if (modalHistorialEl) {
                    const modalHistorial = bootstrap.Modal.getOrCreateInstance(modalHistorialEl);
                    modalHistorial.show();
                }
            }
        });
    }

    document.addEventListener('click', function(e) {
        const el = e.target.closest('.copyable');
        if (el && el.dataset.clipboard) {
            navigator.clipboard.writeText(el.dataset.clipboard).then(() => {
                const hint = el.querySelector('.copy-hint');
                if (hint) {
                    const original = hint.textContent;
                    hint.textContent = '✅'; hint.classList.replace('bg-secondary', 'bg-success');
                    setTimeout(() => { hint.textContent = original; hint.classList.replace('bg-success', 'bg-secondary'); }, 1500);
                }
            }).catch(() => {});
        }
    });

    // =========================================
    // FUNCIONES GLOBALES
    // =========================================
    window.abrirModalGestion = function (idCliente, nombre, dpi = '', idCartera = null) {
        window.clienteActual = { id: idCliente, nombre, identificacion: dpi, id_cartera: idCartera };
        fetch(`?action=get_cliente_detalle&id=${idCliente}`).then(r => r.json()).then(data => {
            if (data) window.clienteActual = { ...window.clienteActual, ...data };
        });

        const form = document.getElementById('formGestion');
        if (form) form.reset();
        document.getElementById('lblCliente').textContent = nombre;
        document.getElementById('clienteId').value = idCliente;
        if (divFecha) divFecha.classList.add('d-none');
        if (boxMonto) boxMonto.classList.add('d-none');
        if (boxPromesas) { boxPromesas.classList.add('d-none'); boxPromesas.style.display = 'none'; }
        if (listaPromesas) listaPromesas.innerHTML = '';
        if (containerExtras) containerExtras.innerHTML = '';

        if (selTipologia) {
            fetch(`?action=get_tipologias&cliente_id=${idCliente}`).then(r => r.json()).then(data => {
                selTipologia.innerHTML = '<option value="">Seleccione...</option>';
                if (Array.isArray(data)) data.forEach(t => {
                    selTipologia.innerHTML += `<option value="${t.id}">${t.padre_id ? '↳ ' : ''}${t.nombre}</option>`;
                });
                evaluarCampos();
            });
        }
        if (idCartera && containerExtras) {
            containerExtras.innerHTML = '<small>⏳ Cargando...</small>';
            fetch(`?action=get_extras_gestion&cartera_id=${idCartera}`).then(r => r.json()).then(extras => {
                containerExtras.innerHTML = '';
                if (Array.isArray(extras)) extras.forEach(ex => {
                    containerExtras.innerHTML += `<div class="col-md-6"><label class="form-label small text-secondary">${ex.etiqueta}</label><input type="text" name="extra_${ex.nombre_campo}" class="form-control form-control-sm bg-dark text-white border-secondary"></div>`;
                });
            });
        }
        modalGestion.show();
    };

    window.guardarGestion = function () {
        const form = document.getElementById('formGestion');
        if (!form || !form.checkValidity()) { if (form) form.reportValidity(); return; }
        const btn = document.querySelector('#modalGestion .btn-success');
        if (!btn) return;
        const original = btn.innerHTML;
        btn.disabled = true; btn.innerHTML = '⏳ Guardando...';
        fetch('?action=registrar_gestion', { method: 'POST', body: new FormData(form) })
            .then(r => r.json()).then(res => {
                btn.disabled = false; btn.innerHTML = original;
                if (res.success) { modalGestion.hide(); location.reload(); }
                else alert('❌ ' + (res.message || 'Error'));
            }).catch(() => { btn.disabled = false; btn.innerHTML = original; alert('❌ Error guardando'); });
    };

    // =========================================
    // ✅ PANEL DE ALERTAS (INSISTENTE: REAPARECE AL RECARGAR)
    // =========================================
    (function initAlertasLlamadas() {
        const panel = document.getElementById('alertas-panel');
        if (!panel) return;

        function checkAlertas() {
            fetch('?action=get_proximas_llamadas')
                .then(r => r.json())
                .then(alertas => {
                    alertas.forEach(a => {
                        // ✅ Solo verifica si ya existe en el DOM (para no duplicar visualmente)
                        if (!document.getElementById(`alerta-${a.id}`)) {
                            mostrarAlerta(a);
                        }
                    });
                })
                .catch(() => {});
        }

        function mostrarAlerta(a) {
            const el = document.createElement('div');
            el.id = `alerta-${a.id}`;
            el.className = 'alert alert-warning alert-dismissible fade show shadow-lg mb-2 bg-dark text-white border-warning';
            el.style.animation = 'slideIn 0.4s ease-out';
            el.innerHTML = `
                <div class="d-flex align-items-center">
                    <span class="badge bg-warning text-dark me-2">📞</span>
                    <div class="flex-grow-1">
                        <strong class="d-block">${a.nombre}</strong>
                        <small class="text-warning">🕐 ${a.hora} | ${a.tipologia}</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white mt-2" onclick="event.stopPropagation(); window.cerrarAlerta(${a.id})"></button>
            `;
            el.addEventListener('click', function(e) {
                if (!e.target.classList.contains('btn-close')) {
                    abrirModalGestion(a.cliente_id, a.nombre);
                    window.cerrarAlerta(a.id);
                }
            });
            panel.appendChild(el);
            const toast = new bootstrap.Toast(el, { autohide: false });
            toast.show();
        }

        window.cerrarAlerta = function(id) {
            const el = document.getElementById(`alerta-${id}`);
            if (el) {
                el.style.animation = 'slideOut 0.3s ease-in forwards';
                setTimeout(() => el.remove(), 300);
            }
        };

        if (!document.getElementById('alert-animations-style')) {
            const style = document.createElement('style');
            style.id = 'alert-animations-style';
            style.textContent = `
                @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
                @keyframes slideOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(100%); opacity: 0; } }
                #alertas-panel .alert { cursor: pointer; transition: all 0.2s; }
                #alertas-panel .alert:hover { box-shadow: 0 0 20px rgba(255, 193, 7, 0.6) !important; transform: scale(1.02); }
            `;
            document.head.appendChild(style);
        }

        setTimeout(checkAlertas, 1000);
        setInterval(checkAlertas, 30000);
    })();

}); // ✅ FIN DEL DOMContentLoaded
</script>