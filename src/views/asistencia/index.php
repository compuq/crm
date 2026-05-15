<!-- Reemplaza los bloques de ENTRADA y SALIDA en la vista -->
<div class="col-md-4">
    <div class="p-3 bg-dark border border-secondary rounded">
        <small class="text-secondary">ENTRADA</small>
        <div class="fs-4 fw-bold">
            <?= isset($registroHoy['entrada']) ? date('H:i:s', strtotime($registroHoy['entrada'])) : '--:--:--' ?>
        </div>
    </div>
</div>
<div class="col-md-4">
    <div class="p-3 bg-dark border border-secondary rounded">
        <small class="text-secondary">SALIDA</small>
        <div class="fs-4 fw-bold">
            <?= isset($registroHoy['salida']) ? date('H:i:s', strtotime($registroHoy['salida'])) : '--:--:--' ?>
        </div>
    </div>
</div>
<div class="col-md-4">
    <div class="p-3 bg-dark border border-secondary rounded">
        <small class="text-secondary">HORAS TRABAJADAS</small>
        <div class="fs-4 fw-bold text-info">
            <?= isset($registroHoy['horas_trabajadas']) ? number_format((float)$registroHoy['horas_trabajadas'], 2) : '0.00' ?> h
        </div>
    </div>
</div>