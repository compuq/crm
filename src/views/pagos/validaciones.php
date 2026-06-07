<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Validaciones de Supervisores</h3>

        <button
            type="button"
            class="btn btn-primary"
            id="btnNuevaValidacion">
            Nueva Validación
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>Supervisor</th>
                    <th>Monto</th>
                    <th>Autorizado por</th>
                    <th>F. Autorización</th>
                    <th>F. Vencimiento</th>
                    <th>Estado</th>
                    <th width="180">Acciones</th>
                </tr>
            </thead>

            <tbody>

            <?php foreach($lista_validaciones as $fila): ?>

                <tr>
                    <td><?= htmlspecialchars($fila['nombre_supervisor']) ?></td>
                    <td><?= number_format($fila['monto_autorizado'],2) ?></td>
                    <td><?= htmlspecialchars($fila['nombre_admin']) ?></td>
                    <td><?= htmlspecialchars($fila['fecha_autorizacion']) ?></td>
                    <td><?= htmlspecialchars($fila['fecha_vencimiento']) ?></td>
                    <td><?= htmlspecialchars($fila['estado']) ?></td>

                    <td>

                        <button
                            type="button"
                            class="btn btn-warning btn-sm btnEditar"
                            data-id-supervisor="<?= $fila['id_supervisor'] ?>"
                            data-monto="<?= $fila['monto_autorizado'] ?>"
                            data-vencimiento="<?= $fila['fecha_vencimiento'] ?>"
                            data-estado="<?= $fila['estado'] ?>"
                            data-observacion="<?= htmlspecialchars($fila['observacion']) ?>">
                            Modificar
                        </button>

                        <button
                            type="button"
                            class="btn btn-danger btn-sm btnEliminar"
                            data-id-supervisor="<?= $fila['id_supervisor'] ?>">
                            Eliminar
                        </button>

                    </td>
                </tr>

            <?php endforeach; ?>

            </tbody>
        </table>
    </div>

    <hr>

    <div id="divFormulario" style="display:none;">

        <h4 id="tituloFormulario">Nueva Validación</h4>

        <form id="frmValidacion">

            <input type="hidden" name="operacion" id="operacion" value="insertar">

            <div class="mb-3">
                <label class="form-label">Supervisor</label>

                <select
                    class="form-control"
                    name="id_supervisor"
                    id="id_supervisor"
                    required>

                    <option value="">Seleccione...</option>

                    <?php foreach($supervisores as $sup): ?>

                        <option value="<?= $sup['id'] ?>">
                            <?= htmlspecialchars($sup['nombre']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Monto autorizado</label>

                <input
                    type="number"
                    step="0.01"
                    class="form-control"
                    name="monto_autorizado"
                    id="monto_autorizado"
                    required>
            </div>

            <div class="mb-3">
                <label class="form-label">Fecha vencimiento</label>

                <input
                    type="datetime-local"
                    class="form-control"
                    name="fecha_vencimiento"
                    id="fecha_vencimiento"
                    required>
            </div>

            <div class="mb-3">
                <label class="form-label">Estado</label>

                <select
                    class="form-control"
                    name="estado"
                    id="estado">

                    <option value="ACTIVA">ACTIVA</option>
                    <option value="INACTIVA">INACTIVA</option>

                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Observación</label>

                <textarea
                    class="form-control"
                    name="observacion"
                    id="observacion"
                    rows="3"></textarea>
            </div>

            <button
                type="submit"
                class="btn btn-success">
                Guardar
            </button>

            <button
                type="button"
                class="btn btn-secondary"
                id="btnCancelar">
                Cancelar
            </button>

        </form>

    </div>

</div>
<script>

document.addEventListener('DOMContentLoaded', () => {

    const divFormulario = document.getElementById('divFormulario');
    const frmValidacion = document.getElementById('frmValidacion');
    const btnNuevaValidacion = document.getElementById('btnNuevaValidacion');
    const btnCancelar = document.getElementById('btnCancelar');

    btnNuevaValidacion.addEventListener('click', () => {

        frmValidacion.reset();

        document.getElementById('operacion').value = 'insertar';

        document.getElementById('id_supervisor').disabled = false;

        document.getElementById('tituloFormulario').innerText =
            'Nueva Validación';

        divFormulario.style.display = 'block';
    });

    btnCancelar.addEventListener('click', () => {

        divFormulario.style.display = 'none';

        frmValidacion.reset();
    });

    document.querySelectorAll('.btnEditar').forEach(btn => {

        btn.addEventListener('click', () => {

            document.getElementById('operacion').value = 'actualizar';

            document.getElementById('tituloFormulario').innerText =
                'Modificar Validación';

            document.getElementById('id_supervisor').value =
                btn.dataset.idSupervisor;

            document.getElementById('id_supervisor').disabled = true;

            document.getElementById('monto_autorizado').value =
                btn.dataset.monto;

            document.getElementById('fecha_vencimiento').value =
                convertirFecha(btn.dataset.vencimiento);

            document.getElementById('estado').value =
                btn.dataset.estado;

            document.getElementById('observacion').value =
                btn.dataset.observacion;

            divFormulario.style.display = 'block';

            window.scrollTo({
                top: divFormulario.offsetTop - 100,
                behavior: 'smooth'
            });

        });

    });

    document.querySelectorAll('.btnEliminar').forEach(btn => {

        btn.addEventListener('click', () => {

            Swal.fire({
                icon: 'warning',
                title: '¿Eliminar validación?',
                text: 'Esta acción no se puede deshacer.',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {

                if (!result.isConfirmed) {
                    return;
                }

                const formData = new FormData();

                formData.append('operacion', 'eliminar');
                formData.append(
                    'id_supervisor',
                    btn.dataset.idSupervisor
                );

                fetch('?action=validaciones', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(res => {

                    Swal.fire({
                        icon: res.status === 'ok'
                            ? 'success'
                            : 'error',
                        title: res.status === 'ok'
                            ? 'Éxito'
                            : 'Error',
                        text: res.message
                    }).then(() => {
                        location.reload();
                    });

                });

            });

        });

    });

    frmValidacion.addEventListener('submit', (e) => {

        e.preventDefault();

        const formData = new FormData(frmValidacion);

        const supervisor = document.getElementById('id_supervisor');

        if (supervisor.disabled) {
            supervisor.disabled = false;
            formData.set(
                'id_supervisor',
                supervisor.value
            );
            supervisor.disabled = true;
        }

        fetch('?action=validaciones', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(res => {

            Swal.fire({
                icon: res.status === 'ok'
                    ? 'success'
                    : 'error',
                title: res.status === 'ok'
                    ? 'Éxito'
                    : 'Error',
                text: res.message
            }).then(() => {

                if (res.status === 'ok') {
                    location.reload();
                }

            });

        })
        .catch(() => {

            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No fue posible comunicarse con el servidor.'
            });

        });

    });

});


function convertirFecha(fechaLatina)
{
    if (!fechaLatina) {
        return '';
    }

    const partes = fechaLatina.split('/');

    if (partes.length !== 3) {
        return '';
    }

    return `${partes[2]}-${partes[1]}-${partes[0]}`;
}

</script>