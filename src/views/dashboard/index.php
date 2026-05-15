<!-- Dashboard Content -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0 fw-bold">📊 Panel de Control</h2>
    <span class="badge bg-primary fs-6">👋 Hola, <?= htmlspecialchars($nombreUser) ?></span>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card bg-dark border-secondary p-4 h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-secondary text-uppercase mb-1">📞 Para Llamar Hoy</h6>
                    <h2 class="mb-0 fw-bold text-info"><?= number_format($statsHoy) ?></h2>
                </div>
                <div class="fs-1 text-info opacity-75">📞</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-dark border-secondary p-4 h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-secondary text-uppercase mb-1">👥 Cartera Asignada</h6>
                    <h2 class="mb-0 fw-bold text-warning"><?= number_format($statsTotal) ?></h2>
                </div>
                <div class="fs-1 text-warning opacity-75">👥</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-dark border-secondary p-4 h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-secondary text-uppercase mb-1">💰 Promesas Hoy</h6>
                    <h2 class="mb-0 fw-bold text-success"><?= number_format($statsProm) ?></h2>
                </div>
                <div class="fs-1 text-success opacity-75">💰</div>
            </div>
        </div>
    </div>
</div>

<div class="card bg-dark border-secondary p-4">
    <h5 class="mb-3">⚡ Accesos Rápidos</h5>
    <div class="d-flex flex-wrap gap-2">
        <a href="?action=clientes" class="btn btn-lex-primary">👥 Gestionar Clientes</a>
        <a href="?action=asistencia" class="btn btn-outline-light">🕒 Registrar Asistencia</a>
        <?php if (in_array($this->session->getUser()['role'], ['supervisor', 'supervisor_general', 'admin'])): ?>
            <a href="?action=carga_clientes" class="btn btn-outline-info">📥 Carga Masiva</a>
            <a href="?action=reportes" class="btn btn-outline-warning">📊 Reportes</a>
        <?php endif; ?>
    </div>
</div>