/* ================================
   DASHBOARD LIVE STATS
   ================================= */

let carteraChart = null; // ✅ Variable global para acceder desde actualizarDashboard()

/* ================================
   INICIALIZAR GRÁFICA
   ================================= */
function inicializarGrafica() {
    const canvas = document.getElementById('carteraChart');
    if (!canvas) {
        console.warn('⚠️ Canvas #carteraChart no encontrado');
        return;
    }

    // ✅ Destruir instancia previa si existe (evita errores de "Canvas already in use")
    if (carteraChart instanceof Chart) {
        carteraChart.destroy();
    }

    // ✅ Preparar datos con fallback a 0
    const dataValues = [
        Number(DASHBOARD_DATA?.pago_confirmado ?? 0),
        Number(DASHBOARD_DATA?.pago_pendiente ?? 0),
        Number(DASHBOARD_DATA?.suma_saldo ?? 0)
    ];

    // ✅ Si todos los valores son 0, mostrar datos placeholder para que se dibuje algo
    const hasData = dataValues.some(v => v > 0);
    const chartData = hasData ? dataValues : [1, 1, 1];
    const chartColors = hasData 
        ? ['#22c55e', '#facc15', '#38bdf8'] 
        : ['#374151', '#374151', '#374151']; // Grises si no hay datos

    carteraChart = new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: ['Confirmado', 'Pendiente', 'Saldo Actual'],
            datasets: [{
                data: chartData,
                backgroundColor: chartColors,
                borderColor: '#0f172a',
                borderWidth: 2,
                hoverOffset: 8,
                spacing: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            rotation: -55,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#111827',
                    titleColor: '#fff',
                    bodyColor: '#d1d5db',
                    borderColor: '#374151',
                    borderWidth: 1,
                    padding: 12,
                    callbacks: {
                        label: function(context) {
                            const value = context.raw;
                            const total = dataValues.reduce((a, b) => a + b, 0);
                            const pct = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return ` Q${Number(value).toLocaleString('en-US', { minimumFractionDigits: 2 })} (${pct}%)`;
                        }
                    }
                }
            },
            animation: {
                animateRotate: true,
                duration: 1800
            },
            // ✅ Forzar redibujado cuando el contenedor cambie de tamaño
            onResize: (chart) => chart.update()
        }
    });

    console.log('✅ Gráfica inicializada');
}

/* ================================
   ACTUALIZAR DASHBOARD
   ================================= */
async function actualizarDashboard() {
    try {
        const response = await fetch('?action=dashboard_stats');
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        
        const data = await response.json();

        // ✅ Helper seguro para actualizar textos
        const setText = (id, value, isCurrency = true) => {
            const el = document.getElementById(id);
            if (el) {
                if (isCurrency) {
                    el.innerHTML = 'Q' + Number(value).toLocaleString('en-US', { minimumFractionDigits: 2 });
                } else {
                    el.innerHTML = value + '%';
                }
            }
        };

        // ✅ Actualizar montos
        setText('txtConfirmado', data.pago_confirmado);
        setText('txtPendiente', data.pago_pendiente);
        setText('txtSaldo', data.suma_saldo);
        setText('txtTotal', data.suma_inicial);

        // ✅ Actualizar porcentajes
        const total = Number(data.suma_inicial) || 1; // Evitar división por cero
        setText('pctConfirmado', ((data.pago_confirmado / total) * 100).toFixed(1), false);
        setText('pctPendiente', ((data.pago_pendiente / total) * 100).toFixed(1), false);
        setText('pctSaldo', ((data.suma_saldo / total) * 100).toFixed(1), false);

        // ✅ Actualizar gráfica si existe
        if (carteraChart && carteraChart instanceof Chart) {
            carteraChart.data.datasets[0].data = [
                Number(data.pago_confirmado),
                Number(data.pago_pendiente),
                Number(data.suma_saldo)
            ];
            
            // ✅ Recalcular colores si todos son cero
            const hasData = [data.pago_confirmado, data.pago_pendiente, data.suma_saldo].some(v => v > 0);
            carteraChart.data.datasets[0].backgroundColor = hasData 
                ? ['#22c55e', '#facc15', '#38bdf8'] 
                : ['#374151', '#374151', '#374151'];
                
            carteraChart.update();
        }

    } catch (error) {
        console.error('❌ Error actualizando dashboard:', error);
    }
}

/* ================================
   INICIALIZACIÓN AL CARGAR LA PÁGINA
   ================================= */
document.addEventListener('DOMContentLoaded', () => {
    // ✅ 1. Inicializar gráfica
    inicializarGrafica();
    
    // ✅ 2. Actualizar datos inmediatamente
    actualizarDashboard();
    
    // ✅ 3. Programar actualizaciones cada 15 segundos
    setInterval(actualizarDashboard, 15000);
    
    console.log('🚀 Dashboard inicializado');
});