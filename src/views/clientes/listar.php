<!-- Listado de Clientes -->
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
                    
                    <!-- ✅ CAMPOS EXTRA DINÁMICOS (Encabezados) -->
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
                    
                    <!-- ✅ CAMPOS EXTRA DINÁMICOS (Valores) -->
                    <?php if (!empty($configExtras)): ?>
                        <?php foreach ($configExtras as $extra): 
                            $val = '-';
                            if (!empty($cliente['data_extras'])) {
                                $extras = is_string($cliente['data_extras']) 
                                    ? json_decode($cliente['data_extras'], true) 
                                    : $cliente['data_extras'];
                                if (isset($extras[$extra['nombre_campo']])) {
                                    $val = $extras[$extra['nombre_campo']];
                                }
                            }
                        ?>
                        <td class="small text-secondary"><?= htmlspecialchars($val) ?></td>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <td class="text-center">
                        <button class="btn btn-sm btn-lex-primary" onclick='abrirModalGestion(<?= $cliente['id'] ?>, "<?= addslashes($cliente['nombre']) ?>", "<?= addslashes($cliente['identificacion'] ?? '') ?>")'>
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
<!-- MODAL DE GESTIÓN CON CONSULTA PARALELA     -->
<!-- ========================================== -->
<div class="modal fade" id="modalGestion" tabindex="-1" aria-hidden="true">
    <!-- Contenedor donde se inyectarán los campos extra -->
    <div id="extras-gestion-container" class="row g-2 mt-2 mb-3"></div>
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content bg-dark border-secondary shadow-lg">
            
            <!-- Header -->
            <div class="modal-header border-secondary bg-secondary bg-opacity-10">
                <h5 class="modal-title text-white">
                    📞 Gestionar: <span id="lblCliente" class="text-info fw-bold"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <!-- Body: 2 Columnas -->
            <div class="modal-body p-0">
                <div class="row g-0">
                    
                    <!-- COLUMNA IZQUIERDA: FORMULARIO DE GESTIÓN (60%) -->
                    <div class="col-lg-7 p-4 border-end border-secondary">
                        <h6 class="text-uppercase text-secondary small mb-3 fw-bold">📝 Registro de Gestión</h6>
                        <form id="formGestion">
                            <input type="hidden" id="clienteId" name="cliente_id">
                            
                            <div class="row g-3">
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

                                <div class="col-md-6">
                                    <label class="form-label small text-secondary">Teléfono utilizado</label>
                                    <input type="text" class="form-control bg-dark text-white border-secondary" id="telefonoUsado" name="telefono_usado" placeholder="Ej: 5555-1234">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-secondary">Monto Compromiso (Q)</label>
                                    <input type="number" step="0.01" class="form-control bg-dark text-white border-secondary" id="montoPromesa" name="monto_promesa" placeholder="0.00">
                                </div>

                                <!-- ✅ CAMPOS EXTRA EN MODAL (Para editar) -->
                                <?php if (!empty($configExtras)): ?>
                                    <div class="col-12">
                                        <hr class="border-secondary my-2">
                                        <h6 class="text-info small fw-bold mb-2">📦 Información Adicional</h6>
                                        <div class="row g-2">
                                            <?php foreach ($configExtras as $extra): 
                                                $valor = '';
                                                if (!empty($cliente['data_extras'])) {
                                                    $extras = is_string($cliente['data_extras']) 
                                                        ? json_decode($cliente['data_extras'], true) 
                                                        : $cliente['data_extras'];
                                                    if (isset($extras[$extra['nombre_campo']])) {
                                                        $valor = $extras[$extra['nombre_campo']];
                                                    }
                                                }
                                            ?>
                                            <div class="col-md-6">
                                                <label class="form-label small text-secondary"><?= htmlspecialchars($extra['etiqueta']) ?></label>
                                                <input type="text" 
                                                       name="extra_<?= $extra['nombre_campo'] ?>" 
                                                       class="form-control bg-dark text-white border-secondary" 
                                                       value="<?= htmlspecialchars($valor) ?>"
                                                       placeholder="<?= htmlspecialchars($extra['etiqueta']) ?>">
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="col-12">
                                    <label class="form-label small text-secondary">Comentario Obligatorio *</label>
                                    <textarea class="form-control bg-dark text-white border-secondary" id="comentario" name="comentario" rows="4" required placeholder="Detalle de la interacción con el cliente..."></textarea>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- COLUMNA DERECHA: CONSULTA PARALELA (40%) -->
                    <div class="col-lg-5 p-4 bg-dark bg-opacity-50" id="panelConsulta">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="text-uppercase text-info small fw-bold mb-0">🔍 Consulta Externa</h6>
                            <span class="badge bg-warning text-dark">Base Pendiente</span>
                        </div>
                        
                        <p class="text-secondary small mb-3">Busca información en bases externas (Vehículos, Laboral, Vacuna).</p>

                        <!-- Formulario de Búsqueda -->
                        <form id="formConsultaExterna" onsubmit="event.preventDefault(); buscarEnBaseExterna();">
                            <div class="mb-3">
                                <label class="form-label small text-secondary">DPI / CUI</label>
                                <input type="text" class="form-control bg-dark text-white border-secondary" id="extDpi" placeholder="Ingrese identificación">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small text-secondary">Nombre Completo</label>
                                <input type="text" class="form-control bg-dark text-white border-secondary" id="extNombre" placeholder="Ingrese nombre">
                            </div>
                            <button type="submit" class="btn btn-outline-info w-100 mb-2" id="btnBuscar" disabled>
                                🔍 Buscar en Bases Externas
                            </button>
                        </form>

                        <!-- Área de Resultados (Imprimible) -->
                        <div id="resultadoConsulta" class="consulta-printable" style="display:none;">
                            <div class="alert alert-dark border-secondary p-3 mb-2">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong class="text-info">📋 Resultado de Consulta</strong>
                                    <small class="text-secondary" id="fechaConsulta"></small>
                                </div>
                                <hr class="border-secondary my-2">
                                <div id="contenidoConsulta" class="small">
                                    <!-- Aquí se inyectará el contenido dinámico -->
                                </div>
                            </div>
                            
                            <!-- Botones de Acción -->
                            <div class="btn-group w-100">
                                <button type="button" class="btn btn-sm btn-success" onclick="imprimirConsulta()">
                                    🖨️ Imprimir
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="cerrarConsulta()">
                                    ✖ Cerrar
                                </button>
                            </div>
                        </div>

                        <!-- Placeholder Inicial -->
                        <div id="placeholderConsulta" class="text-center py-4">
                            <i class="bi bi-database text-secondary" style="font-size: 3rem; opacity: 0.3;"></i>
                            <p class="text-secondary small mt-2 mb-0">Sin datos consultados</p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Contenedor para campos extras dinámicos -->
            <div id="camposExtrasGestion" class="row g-2 mb-3 d-none">
                <!-- Se llenará vía JS -->
            </div>

            <script>
            // Función que se ejecuta al abrir el modal (ya la tienes, agrega esto dentro)
            window.cargarExtrasGestion = async function(idCartera) {
                try {
                    // Ajusta esta ruta a tu controlador de configuración
                    const res = await fetch(`?action=get_extras_gestion&cartera_id=${idCartera}`);
                    const extras = await res.json();
                    
                    const container = document.getElementById('camposExtrasGestion');
                    container.innerHTML = '';
                    
                    if (extras.length > 0) {
                        container.classList.remove('d-none');
                        extras.forEach(ex => {
                            const div = document.createElement('div');
                            div.className = 'col-md-6';
                            div.innerHTML = `
                                <label class="form-label text-secondary small">${ex.etiqueta}</label>
                                <input type="text" name="extra_${ex.nombre_campo}" 
                                    class="form-control form-control-sm bg-dark text-white border-secondary" 
                                    placeholder="${ex.etiqueta}">
                            `;
                            container.appendChild(div);
                        });
                    } else {
                        container.classList.add('d-none');
                    }
                } catch (e) {
                    console.warn('No se pudieron cargar extras de gestión:', e);
                }
            };

            // Llama a esta función dentro de abrirModalGestion() después de obtener id_cartera
            // ejemplo: cargarExtrasGestion(clienteActual.id_cartera);
            </script>
            <!-- Footer -->
            <div class="modal-footer border-secondary bg-secondary bg-opacity-10">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success fw-bold px-4" onclick="guardarGestion()">
                    💾 Guardar Gestión
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ESTILOS DE IMPRESIÓN -->
<style>
@media print {
    body * { visibility: hidden; }
    #resultadoConsulta, #resultadoConsulta * { visibility: visible; }
    #resultadoConsulta {
        position: absolute; left: 0; top: 0; width: 100%;
        background: white !important; color: black !important; padding: 20px;
    }
    #resultadoConsulta .btn-group { display: none !important; }
    .modal, .navbar, .footer { display: none !important; }
}
</style>

<!-- SCRIPTS -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 🔹 Esperar a que Bootstrap 5 esté disponible
    function esperarBootstrap(callback) {
        if (typeof bootstrap !== 'undefined') callback();
        else setTimeout(() => esperarBootstrap(callback), 50);
    }

    esperarBootstrap(function() {
        const modalEl = document.getElementById('modalGestion');
        if (!modalEl) return;
        
        const modalGestion = new bootstrap.Modal(modalEl);
        let clienteActual = { id: null, nombre: '', dpi: '' };

        // ✅ Funciones expuestas globalmente para onclick=""
        window.abrirModalGestion = function(id, nombre, dpi = '') {
            clienteActual = { id, nombre, dpi };
            document.getElementById('clienteId').value = id;
            document.getElementById('lblCliente').textContent = nombre;
            document.getElementById('formGestion').reset();
            document.getElementById('resultadoConsulta').style.display = 'none';
            document.getElementById('placeholderConsulta').style.display = 'block';
            
            // Cargar tipologías
            fetch(`?action=get_tipologias&cliente_id=${id}`)
                .then(r => r.json())
                .then(data => {
                    const sel = document.getElementById('tipologia');
                    sel.innerHTML = '<option value="">Seleccione...</option>';
                    data.forEach(t => {
                        const indent = t.padre_id ? '&nbsp;&nbsp;&nbsp;&nbsp;↳ ' : '';
                        sel.innerHTML += `<option value="${t.id}">${indent}${t.nombre}</option>`;
                    });
                })
                .catch(() => {
                    document.getElementById('tipologia').innerHTML = '<option value="">Error cargando</option>';
                });
            
            document.getElementById('extDpi').value = dpi;
            document.getElementById('extNombre').value = nombre;
            document.getElementById('btnBuscar').disabled = true; // Cambiar a false cuando integres la base externa
            
            modalGestion.show();
            //  Cargar campos extra dinámicos si hay cartera
            const container = document.getElementById('extras-gestion-container');
            container.innerHTML = '<div class="text-secondary small"> Cargando campos...</div>';
            
            try {
                const res = await fetch(`?action=get_extras_gestion&cartera_id=${idCartera}`);
                const extras = await res.json();
                container.innerHTML = ''; // Limpiar loader
                
                if (extras.length > 0) {
                    extras.forEach(ex => {
                        const requiredAttr = ex.obligatorio ? 'required' : '';
                        const star = ex.obligatorio ? '<span class="text-danger">*</span>' : '';
                        
                        container.innerHTML += `
                            <div class="col-md-6">
                                <label class="form-label text-secondary small">${ex.etiqueta} ${star}</label>
                                <input type="text" 
                                    name="extra_${ex.nombre_campo}" 
                                    class="form-control form-control-sm bg-dark text-white border-secondary" 
                                    placeholder="Ingrese ${ex.etiqueta.toLowerCase()}"
                                    ${requiredAttr}>
                            </div>
                        `;
                    });
                } else {
                    container.innerHTML = '<small class="text-secondary">No hay campos extra configurados para esta cartera.</small>';
                }
            } catch (e) {
                container.innerHTML = '<small class="text-danger">⚠️ Error cargando campos extra</small>';
            }
        };

        window.buscarEnBaseExterna = function() {
            const dpi = document.getElementById('extDpi').value;
            const nombre = document.getElementById('extNombre').value;
            
            if (!dpi && !nombre) {
                alert('⚠️ Ingrese al menos un criterio de búsqueda');
                return;
            }
            
            const btn = document.getElementById('btnBuscar');
            btn.disabled = true; btn.innerHTML = '⏳ Buscando...';
            
            // SIMULACIÓN (Reemplazar con fetch real cuando esté lista)
            setTimeout(() => {
                const resultado = {
                    dpi: dpi || 'N/A',
                    nombre: nombre || 'N/A',
                    vehiculos: ['Toyota Corolla 2020', 'Honda Civic 2018'],
                    laboral: { empresa: 'Empresa XYZ S.A.', puesto: 'Gerente', salario: 'Q15,000.00' },
                    vacuna: 'COVID-19: 2 dosis (2022)'
                };
                window.mostrarResultadoConsulta(resultado);
                btn.disabled = false; btn.innerHTML = '🔍 Buscar en Bases Externas';
            }, 1000);
        };

        window.mostrarResultadoConsulta = function(data) {
            document.getElementById('placeholderConsulta').style.display = 'none';
            document.getElementById('resultadoConsulta').style.display = 'block';
            document.getElementById('fechaConsulta').textContent = new Date().toLocaleString();
            
            let html = `
                <div class="mb-2"><strong>DPI:</strong> ${data.dpi}</div>
                <div class="mb-2"><strong>Nombre:</strong> ${data.nombre}</div>
                <hr class="border-secondary my-2">`;
            
            if (data.vehiculos && data.vehiculos.length > 0) {
                html += `<div class="mb-2"><strong>🚗 Vehículos:</strong><ul class="mb-0 ps-3">`;
                data.vehiculos.forEach(v => { html += `<li>${v}</li>`; });
                html += `</ul></div>`;
            }
            if (data.laboral) {
                html += `<div class="mb-2"><strong>💼 Laboral:</strong><br>&nbsp;&nbsp;• Empresa: ${data.laboral.empresa}<br>&nbsp;&nbsp;• Puesto: ${data.laboral.puesto}<br>&nbsp;&nbsp;• Salario: ${data.laboral.salario}</div>`;
            }
            if (data.vacuna) {
                html += `<div class="mb-2"><strong>💉 Vacunación:</strong> ${data.vacuna}</div>`;
            }
            document.getElementById('contenidoConsulta').innerHTML = html;
        };

        window.imprimirConsulta = function() { window.print(); };
        
        window.cerrarConsulta = function() {
            document.getElementById('resultadoConsulta').style.display = 'none';
            document.getElementById('placeholderConsulta').style.display = 'block';
        };

        window.guardarGestion = function() {
            const form = document.getElementById('formGestion');
            if (!form.checkValidity()) { form.reportValidity(); return; }

            const btn = document.querySelector('#modalGestion .btn-success');
            const originalText = btn.innerHTML;
            btn.disabled = true; btn.innerHTML = '⏳ Guardando...';

            fetch('?action=registrar_gestion', { method: 'POST', body: new FormData(form) })
                .then(r => r.json())
                .then(res => {
                    btn.disabled = false; btn.innerHTML = originalText;
                    if (res.success) {
                        modalGestion.hide();
                        location.reload();
                    } else {
                        alert('❌ ' + (res.message || res.msg));
                    }
                })
                .catch(() => {
                    btn.disabled = false; btn.innerHTML = originalText;
                    alert('❌ Error de conexión');
                });
        };
    });
});
</script>