<!-- Carga Masiva de Gestiones -->
<div class="card bg-dark border-secondary p-4 mb-4">
    <h4 class="mb-3">📥 Carga Masiva de Historial de Gestiones</h4>
    <p class="text-secondary small mb-3">
        Sube un CSV con el historial de llamadas. Encabezados requeridos: 
        <code>identificacion, tipologia, telefono, comentario, fecha_gestion</code>.
    </p>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-<?= $_GET['type'] ?? 'info' ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_GET['msg']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form action="?action=procesar_gestiones" method="POST" enctype="multipart/form-data" class="row g-3">
        <div class="col-md-8">
            <input type="file" name="csv_gestiones" class="form-control bg-dark text-white border-secondary" accept=".xlsx, .xls" required>
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-lex-primary w-100 fw-bold">🚀 Procesar Historial</button>
        </div>
    </form>
</div>

<div class="table-responsive">
    <table class="table table-dark table-sm border-secondary">
        <thead>
            <tr>
                <th class="text-secondary">ENCABEZADO CSV</th>
                <th class="text-secondary">DESCRIPCIÓN</th>
                <th class="text-secondary">EJEMPLO</th>
            </tr>
        </thead>
        <tbody>
            <tr><td><code>identificacion</code></td><td>DPI del cliente</td><td>1234567890101</td></tr>
            <tr><td><code>tipologia</code></td><td>Nombre exacto en catálogo</td><td>Se niega a pagar</td></tr>
            <tr><td><code>telefono</code></td><td>Número contactado</td><td>5555-1234</td></tr>
            <tr><td><code>comentario</code></td><td>Detalle de la gestión</td><td>Cliente reporta problemas.</td></tr>
            <tr><td><code>fecha_gestion</code></td><td>Fecha y hora (Y-m-d H:i:s)</td><td>2024-05-20 14:30:00</td></tr>
        </tbody>
    </table>
</div>