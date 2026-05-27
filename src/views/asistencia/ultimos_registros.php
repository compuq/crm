<?php

// CONEXIÓN
$conexion = new PDO(
    "pgsql:host=localhost;dbname=crm",
    "postgres",
    "C0N3CT4D0"
);

// CONSULTAR ÚLTIMOS 5 REGISTROS
$sql = "
    SELECT 
        id, usuario_id, tipo_movimiento, motivo, comentario, fecha_hora, 
        created_at,
        NOW() - created_at AS diferencia
    FROM asistencia_movimientos
    where id=".$this->session->getUser()['id']."
    ORDER BY id DESC
    LIMIT 5
";

$stmt = $conexion->query($sql);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!-- BOTÓN -->
<button onclick="toggleRegistros()" class="btn-registros">
    Últimos registros
</button>

<!-- POPUP -->
<div id="popupRegistros" class="popup-registros">

    <div class="popup-header">
        <span>Últimos registros</span>

        <button onclick="toggleRegistros()" class="cerrar-popup">
            X
        </button>
    </div>

    <div class="popup-body">

        <?php foreach($registros as $r): ?>

            <?php

            $fecha = new DateTime($r['created_at']);
            $ahora = new DateTime();
            $diff = $ahora->getTimestamp() - $fecha->getTimestamp();

            if ($diff < 60) {
                $hace = $diff . ' segundos';
            } elseif ($diff < 3600) {
                $hace = floor($diff / 60) . ' minutos';
            } elseif ($diff < 86400) {
                $hace = floor($diff / 3600) . ' horas';
            } else {
                $hace = floor($diff / 86400) . ' días';
            }

            ?>

            <div class="registro-item">

                <strong>
                    <?= htmlspecialchars($r['tipo_movimiento']) ?>
                </strong>

                <br>

                <small>
                    <?= $fecha->format('d/m/Y H:i:s') ?>
                </small>

                <br>

                <span class="hace">
                    Hace <?= $hace ?>
                </span>

            </div>

        <?php endforeach; ?>

    </div>
</div>

<style>

.btn-registros{
    padding:10px 15px;
    border:none;
    background:#2563eb;
    color:white;
    border-radius:8px;
    cursor:pointer;
}

.popup-registros{
    position:fixed;
    right:20px;
    bottom:20px;
    width:340px;
    background:white;
    border-radius:12px;
    box-shadow:0 5px 20px rgba(0,0,0,0.25);
    display:none;
    overflow:hidden;
    z-index:9999;
}

.popup-header{
    background:#1e293b;
    color:white;
    padding:12px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.cerrar-popup{
    border:none;
    background:red;
    color:white;
    padding:5px 10px;
    border-radius:5px;
    cursor:pointer;
}

.popup-body{
    max-height:350px;
    overflow-y:auto;
}

.registro-item{
    padding:12px;
    border-bottom:1px solid #ddd;
}

.registro-item:hover{
    background:#f3f4f6;
}

.hace{
    color:#2563eb;
    font-size:13px;
}

</style>

<script>

function toggleRegistros() {

    const popup = document.getElementById('popupRegistros');

    popup.style.display =
        popup.style.display === 'block'
        ? 'none'
        : 'block';
}

</script>
<br>