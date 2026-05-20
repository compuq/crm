<form method="GET" action="?action=reporte_gestiones" class="row g-3 mb-4 bg-dark p-3 rounded border border-secondary">
    <!-- Fecha -->
    <div class="col-md-3">
        <label class="form-label small text-secondary">Desde</label>
        <input type="date" name="fecha_inicio" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($_GET['fecha_inicio'] ?? '') ?>">
    </div>
    <div class="col-md-3">
        <label class="form-label small text-secondary">Hasta</label>
        <input type="date" name="fecha_fin" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($_GET['fecha_fin'] ?? '') ?>">
    </div>

    <!-- Filtros por Rol -->
    <?php if (in_array($rol, ['admin','supervisor_general'])): ?>
        <div class="col-md-3">
            <label class="form-label small text-secondary">Supervisor</label>
            <select name="supervisor_id" class="form-select bg-dark text-white border-secondary">
                <option value="">Todos</option>
                <?php foreach($supervisores as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= ($_GET['supervisor_id'] ?? '') == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>

    <?php if ($rol !== 'gestor'): ?>
        <div class="col-md-3">
            <label class="form-label small text-secondary">Gestor</label>
            <select name="usuario_id" class="form-select bg-dark text-white border-secondary">
                <option value="">Todos</option>
                <?php foreach($usuarios as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= ($_GET['usuario_id'] ?? '') == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>

    <?php if (in_array($rol, ['admin','supervisor'])): ?>
        <div class="col-md-3">
            <label class="form-label small text-secondary">Cartera</label>
            <select name="cartera_id" class="form-select bg-dark text-white border-secondary">
                <option value="">Todas</option>
                <?php foreach($carteras as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($_GET['cartera_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>

    <div class="col-md-2 d-flex align-items-end gap-2">
        <button type="submit" class="btn btn-lex-primary flex-grow-1">🔍 Filtrar</button>
        <a href="?action=reporte_gestiones" class="btn btn-outline-secondary" title="Limpiar">🔄</a>
    </div>
</form>