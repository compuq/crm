<div class="container-fluid">

    <h3 class="mb-4">
        🗑️ Eliminación de Gestiones
    </h3>

    <div id="mensaje"></div>

    <form method="GET">

        <input type="hidden" name="action" value="borrado_gestiones">

        <div class="row mb-3">

            <div class="col-md-3">
                <label>Usuario</label>

                <select name="idUsuario" class="form-control">
                    <option value="">Todos</option>

                    <?php foreach($usuarios as $usuario): ?>

                        <option
                            value="<?= $usuario['id'] ?>"
                            <?= ($_GET['idUsuario'] ?? '') == $usuario['id'] ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($usuario['usuario']) ?>
                        </option>

                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label>Fecha inicio</label>

                <input
                    type="datetime-local"
                    name="fechaInicio"
                    class="form-control"
                    value="<?= htmlspecialchars($_GET['fechaInicio'] ?? '') ?>">
            </div>

            <div class="col-md-3">
                <label>Fecha fin</label>

                <input
                    type="datetime-local"
                    name="fechaFin"
                    class="form-control"
                    value="<?= htmlspecialchars($_GET['fechaFin'] ?? '') ?>">
            </div>

            <div class="col-md-3 d-flex align-items-end">
                <button class="btn btn-primary w-100">
                    Buscar
                </button>
            </div>

        </div>

    </form>

    <div class="mb-3">

        <button
            id="btnEliminar"
            class="btn btn-danger">
            Eliminar Seleccionadas
        </button>

    </div>

    <table class="table table-dark table-striped">

        <thead>
            <tr>
                <th width="50">
                    <input type="checkbox" id="checkAll">
                </th>

                <th>ID</th>
                <th>Usuario</th>
                <th>Fecha</th>
                <th>Tipología</th>
                <th>Promesas</th>
                <th>Pagos</th>
                <th>Comentario</th>
            </tr>
        </thead>

        <tbody>

        <?php foreach($gestiones as $g): ?>

            <tr>

                <td>
                    <input
                        type="checkbox"
                        class="gestion-check"
                        value="<?= $g['id'] ?>">
                </td>

                <td><?= $g['id'] ?></td>

                <td><?= htmlspecialchars($g['usuario']) ?></td>

                <td><?= htmlspecialchars($g['fecha_gestion']) ?></td>

                <td><?= htmlspecialchars($g['tipologia']) ?></td>

                <td><?= $g['total_promesas'] ?></td>

                <td><?= $g['total_pagos'] ?></td>

                <td><?= htmlspecialchars($g['comentario']) ?></td>

            </tr>

        <?php endforeach; ?>

        </tbody>

    </table>

</div>

<script>

document.getElementById('checkAll').addEventListener('change', function(){

    document.querySelectorAll('.gestion-check').forEach(chk => {
        chk.checked = this.checked;
    });

});

document.getElementById('btnEliminar').addEventListener('click', async () => {

    const ids = [...document.querySelectorAll('.gestion-check:checked')]
        .map(x => x.value);

    if(ids.length === 0){
        alert('Seleccione al menos una gestión');
        return;
    }

    if(!confirm(`¿Eliminar ${ids.length} gestiones?`)){
        return;
    }

    const formData = new FormData();

    ids.forEach(id => {
        formData.append('ids[]', id);
    });

    try{

        const response = await fetch(
            '?action=borrar_gestiones',
            {
                method: 'POST',
                body: formData
            }
        );

        const data = await response.json();

        document.getElementById('mensaje').innerHTML = `
            <div class="alert alert-${data.success ? 'success' : 'danger'}">
                ${data.message}
            </div>
        `;

        if(data.success){

            ids.forEach(id => {

                const checkbox = document.querySelector(
                    `.gestion-check[value="${id}"]`
                );

                if(checkbox){
                    checkbox.closest('tr').remove();
                }

            });

        }

    }catch(error){

        document.getElementById('mensaje').innerHTML = `
            <div class="alert alert-danger">
                Error al procesar la solicitud.
            </div>
        `;

    }

});

</script>