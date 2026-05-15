<!-- Reportes OLD-->
<div class="card bg-dark border-secondary p-4 mb-4">
    <h4 class="mb-3">📊 Generación de Reportes</h4>
    <p class="text-secondary small mb-4">Seleccione los filtros para generar y descargar el reporte en formato Excel compatible. La exportación es nativa (sin librerías externas).</p>

    <form method="GET" class="row g-3">
        <div class="col-md-3">
            <label class="form-label text-secondary small">Tipo de Reporte</label>
            <select name="tipo" class="form-select bg-dark text-white border-secondary">
                <option value="gestiones">📞 Gestiones / Llamadas</option>
                <option value="promesas">📅 Promesas de Pago</option>
                <option value="pagos">💰 Pagos Validados</option>
                <option value="asistencia">🕒 Asistencia</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label text-secondary small">Fecha Inicio</label>
            <input type="date" name="inicio" class="form-control bg-dark text-white border-secondary" value="<?= date('Y-m-01') ?>" required>
        </div>
        <div class="col-md-2">
            <label class="form-label text-secondary small">Fecha Fin</label>
            <input type="date" name="fin" class="form-control bg-dark text-white border-secondary" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="col-md-2">
            <label class="form-label text-secondary small">Cartera (Opcional)</label>
            <input type="text" name="cartera" class="form-control bg-dark text-white border-secondary" placeholder="Nombre o ID">
        </div>
        <input type="hidden" name="action" value="descargar_reporte_gestiones">
        <div class="col-md-3 d-flex align-items-end">
            <button type="submit" class="btn btn-lex-primary w-100 fw-bold">📥 Descargar Excel</button>
        </div>
    </form>
</div>

<div class="alert alert-info border-secondary bg-dark text-light">
    <strong>ℹ️ Nota:</strong> Los reportes se filtran automáticamente según su rol. 
    <span class="d-block mt-1">• <strong>Gestores:</strong> Solo ven sus gestiones/asistencia.<br>
    • <strong>Supervisores:</strong> Ven a su equipo completo.<br>
    • <strong>Sup. General / Admin:</strong> Visión global sin restricciones.</span>
</div>

<!-- Reportes -->
<!-- views/reportes/index.php -->
<div class="alert alert-info border-secondary bg-dark text-light mt-3">
    <strong>ℹ️ Nota:</strong> Los reportes se filtran por rol automáticamente. 
    Si el navegador bloquea la descarga, permite ventanas emergentes para este sitio.
</div>
<div class="alert alert-info border-secondary bg-dark text-light">
    <strong>ℹ️ Nota:</strong> Los reportes se filtran automáticamente según su rol. 
    <span class="d-block mt-1">• <strong>Gestores:</strong> Solo ven sus gestiones/asistencia.<br>
    • <strong>Supervisores:</strong> Ven a su equipo completo.<br>
    • <strong>Sup. General / Admin:</strong> Visión global sin restricciones.</span>
</div>
