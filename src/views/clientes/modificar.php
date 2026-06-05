<?php

$supervisores = array_filter(
    $usuarios,
    fn($u) => $u['rol'] === 'supervisor'
);

?>

<div class="container-fluid">

    <div class="card mb-3">

        <div class="card-header">
            Modificación de Clientes
        </div>

        <div class="card-body">

            <div class="row">

                <div class="col-md-5">
                    <label>Nombre</label>
                    <input
                        type="text"
                        id="nombre"
                        class="form-control">
                </div>

                <div class="col-md-5">
                    <label>Datos</label>
                    <input
                        type="text"
                        id="datos"
                        class="form-control">
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button
                        class="btn btn-primary w-100"
                        id="btnBuscar">
                        Buscar
                    </button>
                </div>

            </div>

        </div>

    </div>

    <div id="divResultados" style="display:none">

        <div class="card mb-3">

            <div class="card-header">
                Coincidencias encontradas
            </div>

            <div class="card-body">

                <table class="table table-bordered table-hover">

                    <thead>

                    <tr>
                        <th></th>
                        <th>Cuenta</th>
                        <th>DPI</th>
                        <th>Nombre</th>
                        <th>Saldo</th>
                        <th>Teléfono</th>
                    </tr>

                    </thead>

                    <tbody id="tbodyResultados"></tbody>

                </table>

                <button
                    class="btn btn-success"
                    id="btnSeleccionar"
                    disabled>
                    Cargar Cliente
                </button>

            </div>

        </div>

    </div>

    <div id="divCliente" style="display:none">

        <div class="card">

            <div class="card-header">
                Datos del Cliente
            </div>

            <div class="card-body">

                <form id="frmCliente">

                    <input type="hidden" id="id">

                    <div class="row g-3">

                        <div class="col-md-3">
                            <label>Cuenta</label>
                            <input id="cuenta" class="form-control" readonly>
                        </div>

                        <div class="col-md-3">
                            <label>Identificación</label>
                            <input id="identificacion" class="form-control" readonly>
                        </div>

                        <div class="col-md-6">
                            <label>Nombre</label>
                            <input id="cliente_nombre" class="form-control">
                        </div>

                        <div class="col-md-3">
                            <label>Saldo</label>
                            <input id="saldo" class="form-control">
                        </div>

                        <div class="col-md-3">
                            <label>Saldo Inicial</label>
                            <input id="saldo_inicial" class="form-control">
                        </div>

                        <div class="col-md-3">
                            <label>Teléfono 1</label>
                            <input id="telefono_1" class="form-control">
                        </div>

                        <div class="col-md-3">
                            <label>Teléfono 2</label>
                            <input id="telefono_2" class="form-control">
                        </div>

                        <div class="col-md-3">
                            <label>Estado</label>
                            <input id="estado" class="form-control">
                        </div>
                        <hr>

                        <h5>Datos Adicionales</h5>

                        <div
                            id="camposExtras"
                            class="row g-3">
                        </div>
                        <div class="col-md-3">
                            <label>Supervisor</label>

                            <select
                                id="id_supervisor_cadena"
                                class="form-select">

                                <option value="">Seleccione</option>

                                <?php foreach($supervisores as $s): ?>

                                    <option value="<?= $s['id'] ?>">
                                        <?= htmlspecialchars($s['nombre']) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="col-md-3">
                            <label>Gestor</label>

                            <select
                                id="id_gestor_asignado"
                                class="form-select">
                            </select>

                        </div>

                    </div>

                    <div class="alert alert-warning mt-3">
                        Si modifica el saldo actual debe verificar que el saldo inicial mantenga consistencia con la cartera.
                    </div>

                    <button
                        type="button"
                        class="btn btn-primary"
                        id="btnGuardar"
                        disabled>
                        Guardar Cambios
                    </button>
<button
    type="button"
    class="btn btn-danger"
    id="btnEliminar">
    Eliminar Cliente
</button>
                </form>

            </div>

        </div>

    </div>

</div>

<script>

const usuarios = <?= json_encode(array_values($usuarios)) ?>;

let originalData = {};

document.getElementById('btnBuscar').addEventListener('click', buscar);

async function buscar() {

    const fd = new FormData();

    fd.append('accion','buscar');
    fd.append('nombre',document.getElementById('nombre').value);
    fd.append('datos',document.getElementById('datos').value);

    const r = await fetch('', {
        method:'POST',
        body:fd
    });

    const data = await r.json();

    if (!data.ok) {
        alert(data.mensaje);
        return;
    }

    const tbody = document.getElementById('tbodyResultados');

    tbody.innerHTML = '';

    data.clientes.forEach(c => {

        tbody.innerHTML += `
            <tr>
                <td>
                    <input
                        type="radio"
                        name="cliente"
                        value="${c.id}">
                </td>
                <td>${c.cuenta ?? ''}</td>
                <td>${c.identificacion ?? ''}</td>
                <td>${c.nombre ?? ''}</td>
                <td>${c.saldo ?? ''}</td>
                <td>${c.telefono_1 ?? ''}</td>
            </tr>
        `;
    });

    document.getElementById('divResultados').style.display = 'block';

    document
        .querySelectorAll('input[name="cliente"]')
        .forEach(radio => {

            radio.addEventListener('change', () => {
                document.getElementById('btnSeleccionar').disabled = false;
            });

        });
}

document.getElementById('btnSeleccionar').addEventListener('click', cargarCliente);

async function cargarCliente() {

    const seleccionado =
        document.querySelector('input[name="cliente"]:checked');

    if (!seleccionado) {
        return;
    }

    const fd = new FormData();

    fd.append('accion','cargar');
    fd.append('id',seleccionado.value);

    const r = await fetch('', {
        method:'POST',
        body:fd
    });

    const data = await r.json();

    const c = data.cliente;
    const extrasContainer =
        document.getElementById('camposExtras');

    extrasContainer.innerHTML = '';

    let extras = {};

    try {

        if (c.data_extras) {

            extras =
                typeof c.data_extras === 'object'
                    ? c.data_extras
                    : JSON.parse(c.data_extras);

        }

    } catch(e) {

        console.error(e);

    }
    data.campos_extras.forEach(campo => {

        const valor =
            extras[campo.nombre_campo] ?? '';

        extrasContainer.innerHTML += `
            <div class="col-md-4">

                <label class="form-label">
                    ${campo.etiqueta}
                </label>

                <input
                    type="text"
                    class="form-control campo-extra"
                    data-key="${campo.nombre_campo}"
                    value="${valor}">

            </div>
        `;
    });
    originalData = {...c};

    document.getElementById('id').value = c.id ?? '';
    document.getElementById('cuenta').value = c.cuenta ?? '';
    document.getElementById('identificacion').value = c.identificacion ?? '';
    document.getElementById('cliente_nombre').value = c.nombre ?? '';
    document.getElementById('saldo').value = c.saldo ?? '';
    document.getElementById('saldo_inicial').value = c.saldo_inicial ?? '';
    document.getElementById('telefono_1').value = c.telefono_1 ?? '';
    document.getElementById('telefono_2').value = c.telefono_2 ?? '';
    document.getElementById('estado').value = c.estado ?? '';

    document.getElementById('id_supervisor_cadena').value =
        c.id_supervisor_cadena ?? '';

    actualizarGestores();

    document.getElementById('id_gestor_asignado').value =
        c.id_gestor_asignado ?? '';

    document.getElementById('divCliente').style.display = 'block';
}

document
.getElementById('id_supervisor_cadena')
.addEventListener('change', actualizarGestores);

function actualizarGestores() {

    const supervisor =
        document.getElementById('id_supervisor_cadena').value;

    const gestor =
        document.getElementById('id_gestor_asignado');

    gestor.innerHTML = '';

    usuarios
        .filter(u =>
            u.rol === 'gestor'
            && String(u.supervisor_id) === String(supervisor))
        .forEach(u => {

            gestor.innerHTML += `
                <option value="${u.id}">
                    ${u.nombre}
                </option>
            `;
        });
}

document.addEventListener('input', verificarCambios);
document.addEventListener('change', verificarCambios);

function verificarCambios() {

    if (!originalData.id) {
        return;
    }

    let cambios = false;

    const mapa = {
        cliente_nombre: 'nombre',
        saldo: 'saldo',
        saldo_inicial: 'saldo_inicial',
        telefono_1: 'telefono_1',
        telefono_2: 'telefono_2',
        estado: 'estado',
        id_supervisor_cadena: 'id_supervisor_cadena',
        id_gestor_asignado: 'id_gestor_asignado'
    };
    document
        .querySelectorAll('.campo-extra')
        .forEach(campo => {

            const key = campo.dataset.key;

            let valorOriginal = '';

            try {

                const extras =
                    typeof originalData.data_extras === 'object'
                        ? originalData.data_extras
                        : JSON.parse(
                            originalData.data_extras || '{}'
                        );

                valorOriginal =
                    extras[key] ?? '';

            } catch(e) {}

            if (
                String(campo.value)
                !==
                String(valorOriginal)
            ) {

                cambios = true;

            }

        });
    Object.entries(mapa).forEach(([html, db]) => {

        const valor =
            document.getElementById(html).value;

        if (String(valor) !== String(originalData[db] ?? '')) {
            cambios = true;
        }

    });

    document.getElementById('btnGuardar').disabled = !cambios;
}

document.getElementById('btnGuardar').addEventListener('click', guardar);

async function guardar() {

    let resumen = '';

    const campos = {
        cliente_nombre:'Nombre',
        saldo:'Saldo',
        saldo_inicial:'Saldo Inicial',
        telefono_1:'Teléfono 1',
        telefono_2:'Teléfono 2',
        estado:'Estado'
    };

    Object.keys(campos).forEach(id => {

        const actual =
            document.getElementById(id).value;

        const original =
            originalData[
                id === 'cliente_nombre'
                    ? 'nombre'
                    : id
            ];

        if (String(actual) !== String(original ?? '')) {

            resumen +=
                `${campos[id]}: ${original} → ${actual}\n`;

        }

    });

    if (!confirm(
        '¿Está seguro de modificar este cliente?\n\n'
        + resumen +
        '\nVerifique especialmente saldo y saldo inicial.'
    )) {
        return;
    }
    const dataExtras = {};

    document
        .querySelectorAll('.campo-extra')
        .forEach(campo => {

            dataExtras[campo.dataset.key] =
                campo.value;

        });
    const fd = new FormData();

    fd.append('accion','guardar');
    fd.append('id',document.getElementById('id').value);
    fd.append('nombre',document.getElementById('cliente_nombre').value);
    fd.append('saldo',document.getElementById('saldo').value);
    fd.append('saldo_inicial',document.getElementById('saldo_inicial').value);
    fd.append('telefono_1',document.getElementById('telefono_1').value);
    fd.append('telefono_2',document.getElementById('telefono_2').value);
    fd.append('estado',document.getElementById('estado').value);
    fd.append('id_supervisor_cadena',document.getElementById('id_supervisor_cadena').value);
    fd.append('id_gestor_asignado',document.getElementById('id_gestor_asignado').value);
    fd.append(
        'data_extras',
        JSON.stringify(dataExtras)
    );
    const r = await fetch('', {
        method:'POST',
        body:fd
    });

    const data = await r.json();

    alert(data.mensaje);

    if (data.ok) {
        location.reload();
    }
}

</script>
<!-- Eliminar cliente-->
 <script>
    document
    .getElementById('btnEliminar')
    .addEventListener('click', eliminarCliente);

async function eliminarCliente() {

    const nombre =
        document.getElementById('cliente_nombre').value;

    const cuenta =
        document.getElementById('cuenta').value;

    const id =
        document.getElementById('id').value;

    const confirmar = prompt(
        `Escriba ELIMINAR para confirmar\n`+
        `ATENCIÓN: Se eliminará el cliente:\n` +
        `${nombre}\n` +
        `Cuenta: ${cuenta}\n` +
        `También se eliminarán:\n` +
        `- Historial\n` +
        `- Promesas\n` +
        `- Pagos\n`

    );

    if (confirmar !== 'ELIMINAR') {
        return;
    }

    const fd = new FormData();

    fd.append('id', id);

    const r = await fetch(
        '?action=eliminar_clientes',
        {
            method: 'POST',
            body: fd
        }
    );

    const data = await r.json();

    alert(data.mensaje);

    if (data.ok) {
        location.reload();
    }
}
 </script>