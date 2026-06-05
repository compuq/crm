<div id="detalleCliente">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">
            Detalle Cliente
        </h4>

    </div>

    <hr>

    <div class="row">

        <div class="col-md-6">

            <table class="table table-sm table-bordered">

                <tr>
                    <th>Cartera</th>
                    <td><?= htmlspecialchars($cliente['nombre_cartera'] ?? '') ?></td>
                </tr>

                <tr>
                    <th>Gestor</th>
                    <td><?= htmlspecialchars($cliente['gestor_nombre'] ?? '') ?></td>
                </tr>

                <tr>
                    <th>Supervisor</th>
                    <td><?= htmlspecialchars($cliente['supervisor_nombre'] ?? '') ?></td>
                </tr>

                <tr>
                    <th>Cuenta</th>
                    <td><?= htmlspecialchars($cliente['cuenta'] ?? '') ?></td>
                </tr>

                <tr>
                    <th>Identificación</th>
                    <td><?= htmlspecialchars($cliente['identificacion'] ?? '') ?></td>
                </tr>

                <tr>
                    <th>Cliente</th>
                    <td><?= htmlspecialchars($cliente['nombre'] ?? '') ?></td>
                </tr>

            </table>

        </div>

        <div class="col-md-6">

            <table class="table table-sm table-bordered">

                <tr>
                    <th>Saldo Inicial</th>
                    <td>
                        Q <?= number_format((float)($cliente['saldo_inicial'] ?? 0), 2) ?>
                    </td>
                </tr>

                <tr>
                    <th>Saldo Actual</th>
                    <td>
                        Q <?= number_format((float)($cliente['saldo'] ?? 0), 2) ?>
                    </td>
                </tr>

                <tr>
                    <th>Teléfono 1</th>
                    <td><?= htmlspecialchars($cliente['telefono_1'] ?? '') ?></td>
                </tr>

                <tr>
                    <th>Teléfono 2</th>
                    <td><?= htmlspecialchars($cliente['telefono_2'] ?? '') ?></td>
                </tr>

                <tr>
                    <th>Última Actualización</th>
                    <td>
                        <?= !empty($cliente['ultima_actualizacion'])
                            ? date('d/m/Y H:i', strtotime($cliente['ultima_actualizacion']))
                            : '' ?>
                    </td>
                </tr>

                <tr>
                    <th>Estado</th>
                    <td><?= htmlspecialchars($cliente['estado'] ?? '') ?></td>
                </tr>

            </table>

        </div>

    </div>

    <hr>

    <h5>Indicadores</h5>

    <div class="row">

        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <strong>Recuperación</strong>
                    <h4><?= number_format($porcentajeRecuperacion, 2) ?>%</h4>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <strong>Pagos Confirmados</strong>
                    <h4>
                        Q <?= number_format((float)($resumenPagos['monto_confirmado'] ?? 0), 2) ?>
                    </h4>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <strong>Pagos Pendientes</strong>
                    <h4>
                        Q <?= number_format((float)($resumenPagos['monto_pendiente'] ?? 0), 2) ?>
                    </h4>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <strong>Total Pagos</strong>
                    <h4>
                        <?= (int)($resumenPagos['total_pagos'] ?? 0) ?>
                    </h4>
                </div>
            </div>
        </div>

    </div>

    <br>

    <div class="row">

        <div class="col-md-6">

            <div class="card">
                <div class="card-header">
                    Promesas
                </div>

                <div class="card-body">

                    <p>
                        <strong>Total:</strong>
                        <?= (int)($resumenPromesas['total_promesas'] ?? 0) ?>
                    </p>

                    <p>
                        <strong>Monto Cumplido:</strong>
                        Q <?= number_format((float)($resumenPromesas['monto_cumplido'] ?? 0), 2) ?>
                    </p>

                    <p>
                        <strong>Monto Pendiente:</strong>
                        Q <?= number_format((float)($resumenPromesas['monto_pendiente'] ?? 0), 2) ?>
                    </p>

                </div>

            </div>

        </div>

        <div class="col-md-6">

            <div class="card">
                <div class="card-header">
                    Última Gestión
                </div>

                <div class="card-body">

                    <?php if ($ultimaGestion): ?>

                        <p>
                            <strong>Fecha:</strong>
                            <?= date('d/m/Y H:i', strtotime($ultimaGestion['fecha_gestion'])) ?>
                        </p>

                        <p>
                            <strong>Tipología:</strong>
                            <?= htmlspecialchars($ultimaGestion['tipologia_nombre'] ?? '') ?>
                        </p>

                        <p>
                            <strong>Estatus:</strong>
                            <?= htmlspecialchars($ultimaGestion['estatus'] ?? '') ?>
                        </p>

                        <p>
                            <strong>Comentario:</strong><br>
                            <?= nl2br(htmlspecialchars($ultimaGestion['comentario'] ?? '')) ?>
                        </p>

                    <?php else: ?>

                        <div class="alert alert-warning">
                            Sin gestiones registradas.
                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

    <?php if (!empty($extrasMostrar)): ?>

        <hr>

        <h5>Datos Adicionales</h5>

        <table class="table table-bordered table-striped">

            <thead>
                <tr>
                    <th>Campo</th>
                    <th>Valor</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($extrasMostrar as $extra): ?>

                    <tr>
                        <td>
                            <?= htmlspecialchars($extra['etiqueta']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($extra['valor']) ?>
                        </td>
                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    <?php endif; ?>

</div>
<div>
        <button
            type="button"
            class="btn btn-success"
            onclick="imprimirDiv(
                'detalleCliente'
            )">
            Imprimir
        </button>

</div>

<!--
<button class="btn btn-primary">Gestiones</button>
<button class="btn btn-success">Pagos</button>
<button class="btn btn-warning">Promesas</button>
-->
