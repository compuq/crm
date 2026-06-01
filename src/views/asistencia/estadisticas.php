<?php
$user = $this->session->getUser();
?>

<div class="container-fluid py-4">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

        <div>
            <h2 class="fw-bold mb-1">
                📈 Estadísticas de Asistencia
            </h2>

            <div class="text-secondary small">
                Análisis de horas trabajadas, promedios y productividad.
            </div>
        </div>

        <div class="d-flex gap-2 flex-wrap">

            <a href="?action=reportes_asistencia"
               class="btn btn-outline-light">
                📋 Reportes
            </a>

            <button class="btn btn-success"
                    onclick="exportarTablaExcel(
                        'resumen-productividad-asistencia',
                        'detalle_productividad_asistencia',
                        'Detalle Productividad por Asistencia'
                    )">
                Exportar Excel
            </button>


        </div>
    </div>

    <!-- FILTROS RÁPIDOS -->
    <div class="card bg-dark border-secondary mb-4">

        <div class="card-body">

            <div class="d-flex flex-wrap gap-2">

                <a href="?action=estadisticas_asistencia&periodo=hoy"
                   class="btn btn-sm btn-outline-info">
                    Hoy
                </a>

                <a href="?action=estadisticas_asistencia&periodo=ayer"
                   class="btn btn-sm btn-outline-info">
                    Ayer
                </a>

                <a href="?action=estadisticas_asistencia&periodo=7dias"
                   class="btn btn-sm btn-outline-info">
                    Últimos 7 días
                </a>

                <a href="?action=estadisticas_asistencia&periodo=30dias"
                   class="btn btn-sm btn-outline-info">
                    Últimos 30 días
                </a>

                <a href="?action=estadisticas_asistencia&periodo=mes_actual"
                   class="btn btn-sm btn-outline-warning">
                    Mes Actual
                </a>

                <a href="?action=estadisticas_asistencia&periodo=mes_pasado"
                   class="btn btn-sm btn-outline-warning">
                    Mes Pasado
                </a>

            </div>
        </div>
    </div>

    <!-- FILTROS -->
    <div class="card bg-dark border-secondary mb-4">

        <div class="card-body">

            <form method="GET"
                  action="index.php"
                  class="row g-3 align-items-end">

                <input type="hidden"
                       name="action"
                       value="estadisticas_asistencia">

                <div class="col-md-3">

                    <label class="form-label small text-secondary">
                        Fecha Inicio
                    </label>

                    <input type="date"
                           name="fecha_inicio"
                           value="<?= htmlspecialchars($fechaInicio) ?>"
                           class="form-control bg-dark text-white border-secondary">
                </div>

                <div class="col-md-3">

                    <label class="form-label small text-secondary">
                        Fecha Fin
                    </label>

                    <input type="date"
                           name="fecha_fin"
                           value="<?= htmlspecialchars($fechaFin) ?>"
                           class="form-control bg-dark text-white border-secondary">
                </div>

                <?php if (
                    in_array(
                        $user['role'],
                        ['admin', 'supervisor_general']
                    )
                ): ?>

                    <div class="col-md-3">

                        <label class="form-label small text-secondary">
                            Supervisor
                        </label>

                        <select name="supervisor_id"
                                class="form-select bg-dark text-white border-secondary">

                            <option value="">
                                Todos
                            </option>

                            <?php foreach ($supervisores as $s): ?>

                                <option value="<?= $s['id'] ?>"
                                    <?= (
                                        $supervisorId == $s['id']
                                    ) ? 'selected' : '' ?>>

                                    <?= htmlspecialchars($s['nombre']) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>
                    </div>

                <?php endif; ?>

                <div class="col-md-3">

                    <label class="form-label small text-secondary">
                        Usuario
                    </label>

                    <select name="usuario_id"
                            class="form-select bg-dark text-white border-secondary">

                        <option value="">
                            Todos
                        </option>

                        <?php foreach ($usuarios as $u): ?>

                            <option value="<?= $u['id'] ?>"
                                <?= (
                                    $usuarioId == $u['id']
                                ) ? 'selected' : '' ?>>

                                <?= htmlspecialchars($u['nombre']) ?>

                                <?php if (!empty($u['supervisor_nombre'])): ?>
                                    - <?= htmlspecialchars($u['supervisor_nombre']) ?>
                                <?php endif; ?>

                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="col-md-12 d-flex gap-2">

                    <button type="submit"
                            class="btn btn-info">

                        🔍 Filtrar
                    </button>

                    <a href="?action=estadisticas_asistencia"
                       class="btn btn-outline-secondary">

                        Limpiar
                    </a>

                </div>

            </form>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row g-3 mb-4">

        <div class="col-md-3">

            <div class="card bg-dark border-secondary h-100">

                <div class="card-body">

                    <div class="text-secondary small mb-2">
                        Promedio General
                    </div>

                    <div class="fs-2 fw-bold text-info">
                        <?= number_format($promedioGeneral, 2) ?>h
                    </div>

                </div>
            </div>
        </div>

        <div class="col-md-3">

            <div class="card bg-dark border-secondary h-100">

                <div class="card-body">

                    <div class="text-secondary small mb-2">
                        Usuarios Analizados
                    </div>

                    <div class="fs-2 fw-bold text-success">
                        <?= count($estadisticas) ?>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-md-3">

            <div class="card bg-dark border-secondary h-100">

                <div class="card-body">

                    <div class="text-secondary small mb-2">
                        Rango
                    </div>

                    <div class="fw-bold text-warning">
                        <?= htmlspecialchars($fechaInicio) ?>
                        →
                        <?= htmlspecialchars($fechaFin) ?>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-md-3">

            <div class="card bg-dark border-secondary h-100">

                <div class="card-body">

                    <div class="text-secondary small mb-2">
                        Horas Totales
                    </div>

                    <div class="fs-2 fw-bold text-primary">

                        <?= number_format(
                            array_sum(
                                array_column(
                                    $estadisticas,
                                    'total_horas'
                                )
                            ),
                            2
                        ) ?>h

                    </div>

                </div>
            </div>
        </div>

    </div>

    <!-- TABLA -->
    <div class="card bg-dark border-secondary">

        <div class="card-header border-secondary d-flex justify-content-between align-items-center flex-wrap gap-2">

            <h5 class="mb-0">
                📋 Resumen de Productividad
            </h5>

            <div class="small text-secondary">

                <?= count($estadisticas) ?>
                registros

            </div>

        </div>

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-dark table-hover align-middle mb-0" id="resumen-productividad-asistencia">

                    <thead>

                        <tr>

                            <th>Usuario</th>

                            <th>Días</th>

                            <th>Horas Trabajadas</th>

                            <th>Horas Fuera</th>

                            <th>Promedio</th>

                            <th>Productividad</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php if (!empty($estadisticas)): ?>

                            <?php foreach ($estadisticas as $e): ?>

                                <?php

                                $prom = $e['promedio'];

                                $badge = 'bg-danger';
                                $label = 'Bajo';

                                if ($prom >= 8) {

                                    $badge = 'bg-success';
                                    $label = 'Excelente';

                                } elseif ($prom >= 6) {

                                    $badge = 'bg-warning text-dark';
                                    $label = 'Aceptable';
                                }

                                ?>

                                <tr>

                                    <td>

                                        <div class="fw-medium">

                                            <?= htmlspecialchars($e['usuario']['nombre']) ?>

                                        </div>

                                        <div class="small text-secondary">

                                            <?= htmlspecialchars($e['usuario']['usuario']) ?>

                                        </div>

                                    </td>

                                    <td>

                                        <span class="badge bg-secondary">

                                            <?= $e['dias'] ?>

                                        </span>

                                    </td>

                                    <td>

                                        <span class="badge bg-success fs-6">

                                            <?= number_format(
                                                $e['total_horas'],
                                                2
                                            ) ?>h

                                        </span>

                                    </td>

                                    <td>

                                        <span class="badge bg-danger fs-6">

                                            <?= number_format(
                                                $e['horas_fuera'],
                                                2
                                            ) ?>h

                                        </span>

                                    </td>

                                    <td>

                                        <span class="badge bg-info text-dark fs-6">

                                            <?= number_format(
                                                $e['promedio'],
                                                2
                                            ) ?>h

                                        </span>

                                    </td>

                                    <td>

                                        <span class="badge <?= $badge ?>">

                                            <?= $label ?>

                                        </span>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="6"
                                    class="text-center text-secondary py-5">

                                    No hay datos disponibles.

                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>