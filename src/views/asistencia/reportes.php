<?php
$user = $this->session->getUser();
?>

<div class="container-fluid py-4">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h3 class="fw-bold mb-1">📊 Reportes de Asistencia</h3>
            <div class="text-secondary small">
                Consulta de movimientos y asistencia del personal.
            </div>
        </div>

        <a href="?action=asistencia" class="btn btn-outline-light">
            ← Regresar
        </a>
    </div>

    <!-- FILTROS -->
    <div class="card bg-dark border-secondary mb-4">
        <div class="card-body">

            <form method="GET" action="index.php" class="row g-3 align-items-end">

                <input type="hidden" name="action" value="reportes_asistencia">

                <div class="col-md-3">
                    <label class="form-label small text-secondary">
                        Usuario
                    </label>

                    <select name="usuario_id"
                            class="form-select bg-dark text-white border-secondary">

                        <option value="">
                            Todos los usuarios
                        </option>

                        <?php foreach ($usuarios as $u): ?>
                            <option value="<?= $u['id'] ?>"
                                <?= (
                                    ($_GET['usuario_id'] ?? '') == $u['id']
                                ) ? 'selected' : '' ?>>

                                <?= htmlspecialchars($u['nombre']) ?>
                                (<?= htmlspecialchars($u['usuario']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small text-secondary">
                        Fecha Inicio
                    </label>

                    <input type="date"
                           name="fecha_inicio"
                           value="<?= htmlspecialchars($_GET['fecha_inicio'] ?? date('Y-m-01')) ?>"
                           class="form-control bg-dark text-white border-secondary">
                </div>

                <div class="col-md-3">
                    <label class="form-label small text-secondary">
                        Fecha Fin
                    </label>

                    <input type="date"
                           name="fecha_fin"
                           value="<?= htmlspecialchars($_GET['fecha_fin'] ?? date('Y-m-d')) ?>"
                           class="form-control bg-dark text-white border-secondary">
                </div>

                <div class="col-md-3 d-grid">
                    <button type="submit" class="btn btn-info">
                        🔍 Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- TABLA -->
    <div class="card bg-dark border-secondary">

        <div class="card-header border-secondary d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">📋 Movimientos Registrados</h5>

            <div class="small text-secondary">
                Total registros:
                <strong><?= count($movimientos) ?></strong>
            </div>
        </div>

        <div class="card-body p-0">

            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">

                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Usuario</th>
                            <th>Movimiento</th>
                            <th>Motivo</th>
                            <th>Comentario</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (!empty($movimientos)): ?>

                            <?php foreach ($movimientos as $m): ?>

                                <tr>

                                    <td>
                                        <?= date('d/m/Y', strtotime($m['fecha_hora'])) ?>
                                    </td>

                                    <td>
                                        <?= date('H:i:s', strtotime($m['fecha_hora'])) ?>
                                    </td>

                                    <td>
                                        <div class="fw-medium">
                                            <?= htmlspecialchars($m['nombre']) ?>
                                        </div>

                                        <div class="small text-secondary">
                                            <?= htmlspecialchars($m['usuario']) ?>
                                        </div>
                                    </td>

                                    <td>
                                        <?php if ($m['tipo_movimiento'] === 'entrada'): ?>
                                            <span class="badge bg-success">
                                                ✅ Entrada
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">
                                                🚪 Salida
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php
                                        $badge = 'bg-secondary';

                                        switch ($m['motivo']) {
                                            case 'laboral':
                                                $badge = 'bg-primary';
                                                break;

                                            case 'almuerzo':
                                                $badge = 'bg-warning text-dark';
                                                break;

                                            case 'refaccion':
                                                $badge = 'bg-info text-dark';
                                                break;

                                            case 'permiso':
                                                $badge = 'bg-danger';
                                                break;

                                            case 'otro':
                                                $badge = 'bg-dark border';
                                                break;
                                        }
                                        ?>

                                        <span class="badge <?= $badge ?>">
                                            <?= ucfirst($m['motivo']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= !empty($m['comentario'])
                                            ? nl2br(htmlspecialchars($m['comentario']))
                                            : '<span class="text-secondary">-</span>' ?>
                                    </td>
                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="6"
                                    class="text-center text-secondary py-5">

                                    No se encontraron movimientos.
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
