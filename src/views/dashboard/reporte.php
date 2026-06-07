
<div class="card shadow-sm mb-4">

    <div class="card-header">
        <h5 class="mb-0">
            Prometido vs Recuperado
        </h5>
    </div>

    <div class="card-body">

        <div style="width:400px">
            <canvas id="graficaResultados"></canvas>
        </div>

    </div>

</div>
<div class="card shadow-sm mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            Filtros
        </h5>
    </div>

    <div class="card-body">

        <form method="GET">

            <input type="hidden"
                   name="action"
                   value="dashboard_reportes">

            <div class="row g-3">

                <div class="col-md-3">

                    <label class="form-label">
                        Período
                    </label>

                    <select
                        name="periodo"
                        class="form-select">

                        <option value="mes_actual">
                            Mes Actual
                        </option>

                        <option value="mes_pasado">
                            Mes Pasado
                        </option>

                        <option value="semana_pasada">
                            Semana Pasada
                        </option>

                        <option value="hoy">
                            Hoy
                        </option>

                        <option value="personalizado">
                            Personalizado
                        </option>

                    </select>

                </div>

                <div class="col-md-2">

                    <label class="form-label">
                        Inicio
                    </label>

                    <input
                        type="date"
                        name="inicio"
                        class="form-control">

                </div>

                <div class="col-md-2">

                    <label class="form-label">
                        Fin
                    </label>

                    <input
                        type="date"
                        name="fin"
                        class="form-control">

                </div>

                <?php if(!empty($supervisores)): ?>

                <div class="col-md-2">

                    <label class="form-label">
                        Supervisor
                    </label>

                    <select
                        name="supervisor"
                        class="form-select">

                        <option value="">
                            Todos
                        </option>

                        <?php foreach($supervisores as $s): ?>

                            <option
                                value="<?= $s['id'] ?>">

                                <?= htmlspecialchars($s['nombre']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <?php endif; ?>

                <?php if(!empty($gestores)): ?>

                <div class="col-md-2">

                    <label class="form-label">
                        Gestor
                    </label>

                    <select
                        name="gestor"
                        class="form-select">

                        <option value="">
                            Todos
                        </option>

                        <?php foreach($gestores as $g): ?>

                            <option
                                value="<?= $g['id'] ?>">

                                <?= htmlspecialchars($g['nombre']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <?php endif; ?>

                <div class="col-md-1 d-flex align-items-end">

                    <button
                        class="btn btn-primary w-100">

                        Buscar

                    </button>

                </div>

            </div>

        </form>

    </div>
</div>
<div class="row mb-4">

    <div class="col-md-2">
        <div class="card border-primary">
            <div class="card-body text-center">

                <h6>Gestiones</h6>

                <h3>
                    <?= number_format($gestiones) ?>
                </h3>

            </div>
        </div>
    </div>

    <div class="col-md-2">
        <div class="card border-success">
            <div class="card-body text-center">

                <h6>Clientes</h6>

                <h3>
                    <?= number_format($clientesGestionados) ?>
                </h3>

            </div>
        </div>
    </div>

    <div class="col-md-2">
        <div class="card border-warning">
            <div class="card-body text-center">

                <h6>Promesas</h6>

                <h3>
                    <?= number_format($promesas) ?>
                </h3>

            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-info">
            <div class="card-body text-center">

                <h6>Monto Prometido</h6>

                <h3>
                    Q<?= number_format($montoPromesas,2) ?>
                </h3>

            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-danger">
            <div class="card-body text-center">

                <h6>Recuperado</h6>

                <h3>
                    Q<?= number_format($saldoRecuperado,2) ?>
                </h3>

            </div>
        </div>
    </div>

</div>
<div class="card">

    <div class="card-header">

        <h5 class="mb-0">
            Detalle Diario
        </h5>

    </div>

    <div class="card-body">

        <div class="table-responsive">

            <table class="table table-striped">

                <thead>

                    <tr>

                        <th>Fecha</th>
                        <th>Gestiones</th>

                    </tr>

                </thead>

                <tbody>

                    <?php foreach($graficaGestiones as $fila): ?>

                        <tr>

                            <td>
                                <?= $fila['fecha'] ?>
                            </td>

                            <td>
                                <?= $fila['cantidad'] ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

const datos = <?= json_encode($graficaResultados ?? []) ?>;

console.log(datos);

const labels = datos.map(x => x.fecha);

const prometido = datos.map(x =>
    parseFloat(x.prometido || 0)
);

const recuperado = datos.map(x =>
    parseFloat(x.recuperado || 0)
);

new Chart(
    document.getElementById('graficaResultados'),
    {
        type: 'bar',

        data: {
            labels: labels,

            datasets: [
                {
                    label: 'Prometido',
                    data: prometido
                },
                {
                    label: 'Recuperado',
                    data: recuperado
                }
            ]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    }
);

</script>