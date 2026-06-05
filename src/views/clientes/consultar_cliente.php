<div class="card mb-3">
    <div class="card-body">

        <div class="row">

            <div class="col-md-10">
                <input
                    type="text"
                    id="txtBuscarCliente"
                    class="form-control"
                    placeholder="Buscar por nombre, cuenta o identificación">
            </div>

            <div class="col-md-2">
                <button
                    class="btn btn-primary w-100"
                    onclick="buscarClientes()">
                    Buscar
                </button>
            </div>

        </div>

    </div>
</div>
<button
    class="btn btn-primary btn-sm"
    onclick="verCliente(<?= $cliente['id'] ?>)">
    Ver
</button>   
<div id="resultadoClientes"></div>
<div
    class="modal fade"
    id="modalCliente"
    tabindex="-1">

    <div class="modal-dialog modal-xl">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">
                    Cliente
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal">
                </button>

            </div>

            <div class="modal-body">

                <div id="contenidoCliente"></div>

            </div>

        </div>

    </div>

</div>
<script>
function buscarClientes(pagina = 1)
{
    const busqueda =
        document.getElementById('txtBuscarCliente').value;

    fetch(
        '?action=buscar_cliente',
        {
            method: 'POST',
            headers: {
                'Content-Type':
                    'application/x-www-form-urlencoded'
            },
            body:
                'busqueda=' +
                encodeURIComponent(busqueda) +
                '&pagina=' +
                pagina
        }
    )
    .then(r => r.text())
    .then(html => {

        document.getElementById(
            'resultadoClientes'
        ).innerHTML = html;

    });
}
function verCliente(idCliente)
{
    fetch(
        '?action=detalle_cliente',
        {
            method: 'POST',
            headers: {
                'Content-Type':
                    'application/x-www-form-urlencoded'
            },
            body:
                'id=' + idCliente
        }
    )
    .then(r => r.text())
    .then(html => {

        document.getElementById(
            'contenidoCliente'
        ).innerHTML = html;

        new bootstrap.Modal(
            document.getElementById(
                'modalCliente'
            )
        ).show();

    });
}    
</script>