<?php

$supervisores = array_filter(
    $usuarios,
    fn($u) => $u['rol'] === 'supervisor'
);

?>

<div class="container-fluid">

    <div class="card mb-3">

        <div class="card-header">
            Consulta de clientes
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
                        <th>Identificación</th>
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

                </form>

            </div>

        </div>

    </div>

</div>
<div id="loadingClientes" class="text-center py-3 d-none">
    <div class="spinner-border text-info" role="status"></div>
    <div class="mt-2 text-info">
        ⏳ Cargando clientes...
    </div>
</div>
<div id="detalleCliente" style="display:none; margin-top:20px;">
    <div id="contenidoCliente"></div>
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

document.getElementById('btnSeleccionar').addEventListener('click', function () {

    const divResultados = document.getElementById('divResultados');

    if (divResultados) {
        divResultados.style.display = 'none';
    }
    const loading = document.getElementById('loadingClientes');

    // Mostrar cargando
    loading?.classList.remove('d-none');

    // 1. Obtener radio seleccionado
    const seleccionado = document.querySelector('#tbodyResultados input[type="radio"]:checked');

    if (!seleccionado) {
        alert('Selecciona un cliente');
        return;
    }

    const id = seleccionado.value;

    // 2. Enviar por POST
    fetch('?action=consultar_cliente', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
            id: id
        })
    })
    .then(response => response.text())
    .then(data => {

        // 3. Mostrar resultado en div
        const contenedor = document.getElementById('contenidoCliente');
        contenedor.innerHTML = data;

        // 4. Mostrar div oculto
        document.getElementById('detalleCliente').style.display = 'block';

        // opcional: scroll hacia el resultado
        document.getElementById('detalleCliente')
            .scrollIntoView({ behavior: 'smooth' });
        loading?.classList.add('d-none');


    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al consultar cliente');
    });

});

const datos = document.getElementById('datosGrafico');

if (datos) {

    const saldoInicial = parseFloat(datos.dataset.saldo);
    const recuperado   = parseFloat(datos.dataset.recuperado);
    const pendiente    = saldoInicial - recuperado;

    const canvas = document.getElementById('graficoRecuperacion');

    if (canvas) {

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: [
                    'Saldo Inicial',
                    'Recuperado',
                    'Pendiente'
                ],
                datasets: [{
                    label: 'Monto',
                    data: [
                        saldoInicial,
                        recuperado,
                        pendiente
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

    }
}
</script>

