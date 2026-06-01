<?php
// Asegúrate de que las variables estén disponibles desde el controlador
//print_r($SaldoCarteras);
?>


<!-- RESUMEN EJECUTIVO CON PESTAÑAS -->
<div class="card bg-dark border-secondary shadow-lg mb-4 overflow-hidden">

    <!-- NAVEGACIÓN DE PESTAÑAS -->
    <ul class="nav nav-tabs bg-transparent border-bottom border-secondary" id="mainTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active text-light" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard-pane" type="button" role="tab">💼 Dashboard</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link text-secondary" id="carteras-tab" data-bs-toggle="tab" data-bs-target="#carteras-pane" type="button" role="tab">📁 Carteras</button>
        </li>
    </ul>

    <!-- CONTENIDO DE PESTAÑAS -->
    <div class="tab-content" id="mainTabsContent">
        
        <!-- 🟦 PESTAÑA: DASHBOARD -->
        <div class="tab-pane fade show active" id="dashboard-pane" role="tabpanel">
            <!-- HEADER -->
            <div class="p-3 border-bottom border-secondary">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h5 class="fw-bold text-light mb-1">📊 Resumen Ejecutivo</h5>
                        <small class="text-secondary">Vista general de tu cartera</small>
                    </div>
                    <div class="text-end mt-2 mt-md-0">
                        <small class="text-secondary d-block">Cartera Asignada (100%)</small>
                        <h4 id="txtTotal" class="text-warning fw-bold mb-0">
                            Q<?= number_format($suma_inicial, 2) ?>
                        </h4>
                    </div>
                </div>
            </div>

            <!-- BODY -->
            <div class="p-3">
                <div class="row align-items-center">
                    <!-- GRAFICA -->
                    <div class="col-xl-5 col-lg-5 col-md-12 text-center mb-4 mb-lg-0">
                        <div class="chart-container position-relative" style="height: 200px;">
                            <canvas id="carteraChart"></canvas>
                        </div>
                    </div>

                    <!-- STATS -->
                    <div class="col-xl-7 col-lg-7 col-md-12">
                        <div class="row g-3">
                            <!-- CONFIRMADO -->
                            <div class="col-md-12">
                                <div class="mini-card border-success">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="stat-title text-success">● Pago Confirmado</div>
                                            <small class="text-secondary">Aplicados correctamente</small>
                                        </div>
                                        <div class="text-end">
                                            <div id="txtConfirmado" class="stat-amount text-success">
                                                Q<?= number_format($pago_confirmado, 2) ?>
                                            </div>
                                            <small id="pctConfirmado" class="badge-percent">
                                                <?= $suma_inicial > 0 ? number_format(($pago_confirmado / $suma_inicial) * 100, 1) : 0 ?>%
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- PENDIENTE -->
                            <div class="col-md-12">
                                <div class="mini-card border-warning">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="stat-title text-warning">● Pago Pendiente</div>
                                            <small class="text-secondary">Pendientes de confirmación</small>
                                        </div>
                                        <div class="text-end">
                                            <div id="txtPendiente" class="stat-amount text-warning">
                                                Q<?= number_format($pago_pendiente, 2) ?>
                                            </div>
                                            <small id="pctPendiente" class="badge-percent">
                                                <?= $suma_inicial > 0 ? number_format(($pago_pendiente / $suma_inicial) * 100, 1) : 0 ?>%
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- SALDO -->
                            <div class="col-md-12">
                                <div class="mini-card border-info">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="stat-title text-info">● Saldo Actual</div>
                                            <small class="text-secondary">Saldo restante de cartera</small>
                                        </div>
                                        <div class="text-end">
                                            <div id="txtSaldo" class="stat-amount text-info">
                                                Q<?= number_format($suma_saldo, 2) ?>
                                            </div>
                                            <small id="pctSaldo" class="badge-percent">
                                                <?= $suma_inicial > 0 ? number_format(($suma_saldo / $suma_inicial) * 100, 1) : 0 ?>%
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 🟨 PESTAÑA: CARTERAS (Placeholder) -->
        <div class="tab-pane fade" id="carteras-pane" role="tabpanel">
            <div class="p-5 text-center">
                <!-- 🟨 PESTAÑA: CARTERAS -->
                
                    
                    <!-- HEADER DE LA SECCIÓN -->
                    <div class="p-3 border-bottom border-secondary">
                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                            <div>
                                <h5 class="fw-bold text-light mb-1">📊 Rendimiento por Cartera</h5>
                                <small class="text-secondary">Comparativa: Total asignado vs. Logro recuperado</small>
                            </div>
                            <div class="mt-2 mt-md-0">
                                <span class="badge bg-secondary text-light px-3 py-2">
                                    <i class="bi bi-bar-chart-fill me-1"></i> <?= count($SaldoCarteras ?? []) ?> carteras activas
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- CONTENEDOR DE LA GRÁFICA -->
                    <div class="p-3">
                        <div class="chart-container position-relative" style="height: 420px;">
                            <canvas id="chartCarteras"></canvas>
                        </div>
                    </div>

                    <!-- LEYENDA PERSONALIZADA -->
                    <div class="px-3 pb-3 d-flex justify-content-center gap-4 flex-wrap">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge" style="background: linear-gradient(180deg, #0d6efd 0%, #084298 100%); width: 26px; height: 14px; border-radius: 4px;"></span>
                            <small class="text-light">Total Asignado</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge" style="background: linear-gradient(180deg, #198754 0%, #0f5132 100%); width: 26px; height: 14px; border-radius: 4px;"></span>
                            <small class="text-light">Logro (Recuperado)</small>
                        </div>
                    </div>

                    <!-- SCRIPT CHART.JS -->
                    <script>
                    document.addEventListener('DOMContentLoaded', () => {

                        let chartInicializado = false;

                        const tabBtn = document.querySelector('[data-bs-target="#carteras-pane"]');

                        tabBtn?.addEventListener('shown.bs.tab', () => {

                            if (chartInicializado) return;
                            chartInicializado = true;

                            const canvas = document.getElementById('chartCarteras');
                            if (!canvas) return;

                            const ctx = canvas.getContext('2d');

                            const datos = <?= json_encode($SaldoCarteras ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

                            console.log(datos);

                            if (!datos || datos.length === 0) return;

                            const labels   = datos.map(d => d.cartera);
                            const asignado = datos.map(d => Number(d.inicial));
                            const logro    = datos.map(d => Number(d.logro));

                            new Chart(ctx, {
                                type: 'bar',
                                data: {
                                    labels,
                                    datasets: [
                                        {
                                            label: 'Total Asignado',
                                            data: asignado,
                                            backgroundColor: '#0d6efd'
                                        },
                                        {
                                            label: 'Logro',
                                            data: logro,
                                            backgroundColor: '#198754'
                                        }
                                    ]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false
                                }
                            });

                        });

                    });
                    </script>

            </div>
        </div>

    </div>
</div>

<!-- ACCESOS RÁPIDOS -->
<div class="card bg-dark border-secondary p-4">
    <h5 class="mb-3">⚡ Accesos Rápidos</h5>
    <div class="d-flex flex-wrap gap-2">
        <a href="?action=clientes" class="btn btn-lex-primary">👥 Gestionar Clientes</a>
        <a href="?action=asistencia" class="btn btn-outline-light">🕒 Registrar Asistencia</a>
        <?php if (in_array($this->session->getUser()['role'] ?? '', ['supervisor', 'supervisor_general', 'admin'])): ?>
            <a href="?action=carga_clientes" class="btn btn-outline-info">📥 Carga Masiva</a>
            <a href="?action=reportes_gestiones" class="btn btn-outline-warning">📊 Reportes</a>
        <?php endif; ?>
    </div>
</div>

<!-- STATS ADICIONALES -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card bg-dark border-secondary p-4 h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-secondary text-uppercase mb-1">
                        📞 Para Llamar Hoy <b style="color:cyan;"><?= number_format($statsHoy) ?></b>
                    </h6>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-dark border-secondary p-4 h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-secondary text-uppercase mb-1">
                        👥 Cartera Asignada <b style="color:cyan;"><?= number_format($statsTotal) ?></b>
                    </h6>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-dark border-secondary p-4 h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-secondary text-uppercase mb-1">
                        💰 Promesas Hoy <b style="color:cyan;"><?= number_format($statsProm) ?></b>
                    </h6>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ✅ CARGA DE CHART.JS (ANTES DE INICIALIZAR) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- ✅ DATA PARA EL DASHBOARD (PHP → JS) -->
<script>
const DASHBOARD_DATA = {
    pago_confirmado: <?= json_encode($pago_confirmado ?? 0) ?>,
    pago_pendiente: <?= json_encode($pago_pendiente ?? 0) ?>,
    suma_saldo: <?= json_encode($suma_saldo ?? 0) ?>,
    suma_inicial: <?= json_encode($suma_inicial ?? 0) ?>
};
</script>

<!-- ✅ SCRIPT EXTERNO -->
<script src="public/js/dashboard.js"></script>

<!-- ✅ ESTILOS CRÍTICOS PARA LA GRÁFICA -->
<style>
.chart-container {
    position: relative !important;
    height: 200px !important; /* Altura fija para maintainAspectRatio: false */
    width: 100%;
    max-width: 280px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
}

#carteraChart {
    max-width: 100%;
    max-height: 100%;
}

.mini-card {
    padding: 10px 12px !important;
    border-radius: 10px;
    min-height: 35px;
    display: flex;
    align-items: center;
    transition: all .2s ease;
    border-left: 4px solid transparent;
}
.mini-card.border-success { border-left-color: #22c55e; }
.mini-card.border-warning { border-left-color: #facc15; }
.mini-card.border-info { border-left-color: #38bdf8; }
.mini-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,.25);
}

.stat-title { font-size: .82rem; font-weight: 700; margin-bottom: 1px; }
.stat-amount { font-size: .92rem !important; font-weight: 800; }
.badge-percent {
    font-size: .64rem;
    padding: 3px 7px;
    background: rgba(255,255,255,0.1);
    border-radius: 4px;
}

/* Responsive */
@media(max-width: 768px) {
    .chart-container {
        height: 160px !important;
        max-width: 200px;
    }
}
</style>