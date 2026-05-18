<?php
function convertirABase64($url) {
    // Obtener el contenido de la URL
    $data = @file_get_contents($url); // El símbolo @ suprime los errores si la URL no existe o no es accesible

    if ($data !== false) {
        // Obtener el tipo MIME de la imagen (ej. image/png)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_buffer($finfo, $data);
        finfo_close($finfo);

        // Convertir la imagen a base64
        $base64 = 'data:' . $mime_type . ';base64,' . base64_encode($data);
        return $base64; // Devolver la cadena en Base64
    } else {
        return false; // Devolver false si no pudo obtener la imagen
    }
}

function LogoTabla($table){
    if (str_contains(strtolower($table),"vac")) 
        $logo="logos/covid.png";
    elseif(strtolower(($table))=="claro2025")
        $logo="logos/claro2025.png";
    elseif(str_contains(strtolower($table),"mov") || $table =="gtpospago" || $table =="claro") 
        $logo="logos/claro.png";
    elseif(str_contains(strtolower($table),"tigo")) 
        $logo="logos/tigo.png";
    elseif(str_contains(strtolower($table),"gallo")) 
        $logo="logos/gallo.jpeg";
    elseif(str_contains(strtolower($table),"renap")) 
        $logo="logos/renap.jpeg";
    elseif(str_contains(strtolower($table),"claro")) 
        $logo="logos/claro.png";
    elseif(str_contains(strtolower($table),"licencia")) 
        $logo="logos/maycom.jpeg";
    elseif(str_contains(strtolower($table),"igss")) 
        $logo="logos/igss.jpg";
    elseif(str_contains(strtolower($table),"mineduc")) 
        $logo="logos/mineduc.png";
    elseif(str_contains(strtolower($table),"visanet")) 
        $logo="logos/neonet.jpeg";
    elseif(str_contains(strtolower($table),"votantes") || $table =="sede" || str_contains(strtolower($table),"padron")) 
        $logo="logos/tse.png";
    elseif($table=="oj") 
        $logo="logos/oj.jpg";
    elseif($table=="correos") 
        $logo="logos/correo.png";
    elseif(str_contains(strtolower($table),"tenencia")) 
        $logo="logos/digecam.jpeg";
    elseif(str_contains(strtolower($table),"irtra")) 
        $logo="logos/irtra.jpg";
    elseif(str_contains(strtolower($table),"segeplan")) 
        $logo="logos/segeplan.png";
    elseif(str_contains(strtolower($table),"pnc") || $table=="detenidos" 
        || $table== "antecedentesn" || $table=="ante_policiacos") 
        $logo="logos/pnc.jpeg";
    elseif(str_contains(strtolower($table),"pasaporte")) 
        $logo="logos/pasaporte.jpg";
    elseif(str_contains(strtolower($table),"sat") || str_contains(strtolower($table),"nit") || str_contains(strtolower($table),"vehi")
            || $table == "representantes2021" || $table=="formato_bases") 
    $logo="logos/sat.jpg";
    else $logo="person.ico";
    $icono =convertirABase64($logo);
    $imagen_logo='<img src="'.$icono.'" width="35" height="35" >';
    return $imagen_logo;
}

$mysqli = new mysqli("localhost", "root", "", "a2");

if ($mysqli->connect_error) {
    die("Error conexión: " . $mysqli->connect_error);
}

require 'array_tablas.php';
include_once "decode.php";

$search = $_GET["nombre"] ?? '';
$search = trim($search);

$por_pagina = 10;
$pagina = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($pagina - 1) * $por_pagina;

$limite_global = 500;
$total_registros = 0;

$excepciones = [
    'search_vector','firma','leftfinger','foto',
    'foto1','foto2','foto3','foto4','foto5','foto6',
    'rightfinger','leftfinger','currentpicture','idl','id'
];

function formatearValor($valor) {
    if ($valor === null || $valor === '') return null;

    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $valor)) {
        return date('d-m-Y', strtotime($valor));
    }

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
        return date('d-m-Y', strtotime($valor));
    }

    if (is_numeric($valor) && strpos($valor, '.') !== false) {
        return number_format($valor, 2);
    }

    return htmlspecialchars($valor);
}

function buildSearch($search) {
    $words = preg_split('/\s+/', $search);
    $words = array_filter($words);
    return '+' . implode('* +', $words) . '*';
}

$resultados = [];
$alerta = '';
$cantidad = "";

if ($search !== '') {

    $searchFT = buildSearch($search);
    $searchFT = $mysqli->real_escape_string($searchFT);

    foreach ($array_base as $table) {

        if ($total_registros >= $limite_global) break;

        $tabla = $table['origen'];
        if ($tabla=='juicios'){
            continue;
        }
        $nombre_completo = $table['nombre_completo'] ?? 'NULL';

        $es_expresion = preg_match('/\|\||concat|coalesce|trim/i', $nombre_completo);

        if ($nombre_completo !== 'NULL' && !$es_expresion) {
            $match = "MATCH(`$nombre_completo`) AGAINST('$searchFT' IN BOOLEAN MODE)";
        } else {

            $cols = [];

            foreach (['nombre1','nombre2','nombre3','apellido1','apellido2','apellido3'] as $col) {
                if (isset($table[$col]) && $table[$col] !== 'NULL') {
                    $cols[] = "`".$table[$col]."`";
                }
            }

            if (empty($cols)) continue;

            $match = "MATCH(".implode(',', $cols).") AGAINST('$searchFT' IN BOOLEAN MODE)";
        }

        $filtro_largo = "";
        if (strtolower($tabla) == 'juicios'){
            $filtro_largo = " CHAR_LENGTH(demandados) < 50 AND ";
        }

        $sql = "SELECT * 
                FROM `$tabla` 
                WHERE $filtro_largo$match 
                LIMIT 200";

        $res = $mysqli->query($sql);

        if ($res) {
            while ($row = $res->fetch_assoc()) {

                $total_registros++;

                if ($total_registros <= $limite_global) {
                    $resultados[] = [
                        'tabla' => $tabla,
                        'config' => $table,
                        'data' => $row
                    ];
                }

                if ($total_registros >= $limite_global) break 2;
            }
        }
    }

    // Mensajes
    if ($total_registros >= $limite_global) {
        $alerta = "⚠️ Límite alcanzado ($limite_global registros). Filtra más tu búsqueda.";
    } elseif ($total_registros > 0) {
        $cantidad = "Se encontraron $total_registros registros";
    } else {
        $cantidad = "Sin coincidencias en sistema...";
    }
}

$total_paginas = max(1, ceil($total_registros / $por_pagina));
$resultados = array_slice($resultados, $offset, $por_pagina);

?>

<div class="container">




<!--  se deshabilita por cambio de formato en formulario
<form method="GET" class="mb-4 d-flex gap-2">
    <input type="text" name="nombre" class="form-control" minlength="8" maxlength="40" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar..." required>
    <button class="btn btn-primary">Buscar</button>
</form>
 -->


<?php 

$token=$_GET['token'];
if ($search && $token!=$_SESSION["token"]) die("Algo salió mal token incorrecto, favor de intentar de nuevo, presione inicio...");
$nombre=$_GET["nombre"];

// Asignar el token a la sesión
$token = bin2hex(random_bytes(32)); // Genera un token de 64 caracteres (32 bytes)

// Asignar el token a la sesión
$_SESSION["token"] = $token;
echo '
<div class="centered">
<form method="get" action="">
        <div class="form-group">
            <label for="dpi"><i class="fas fa-id-card icon-label"></i>Ingrese nombre:</label>
            <input type="text" name="nombre" id="nombre" class="form-control" placeholder="Ingrese nombre" 
                value = "'.$search.'"required>
            <input type="hidden" name="token" value="'.$token.'" >
        </div>
        <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-search"></i> Consultar</button>
    </form>
</div>

';

if ($alerta): ?>
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <?= $alerta ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if ($cantidad): ?>
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <?= $cantidad ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php foreach ($resultados as $item): 
    $tabla = $item['tabla'];
    $config = $item['config'];
    $data = $item['data'];
?>

<div class="card mb-3">
    <div class="card-header bg-dark text-white">
        ORIGEN: <?= LogoTabla($tabla) ?>
    </div>
    <div class="card-body">

    <?php
    // 🔹 PRIORIDAD: array_base
    foreach ($config as $key => $col) {

        if ($key == 'origen' || $col == 'NULL') continue;

        if (!isset($data[$col])) continue;

        $valor = formatearValor($data[$col]);

        if (!trim($valor)) continue;

        if (in_array(strtolower($key),['dpi','cui','identificacion'])){
            $valor_despliegue="<a href=\"?dpi=" . urlencode(encript($valor)) . "\">$valor</a>";
            echo "<div><strong>".strtoupper($key).":</strong> $valor_despliegue</div>";
        } else{
            echo "<div><strong>".strtoupper($key).":</strong> $valor</div>";
        }

    }

    // 🔹 RESTO
    foreach ($data as $col => $valor) {

        if (in_array(strtolower($col), $excepciones)) continue;

        if (in_array($col, $config)) continue;

        if (!trim($valor)) continue;

        $valor = formatearValor($valor);

        if (!$valor) continue;
        if (in_array(strtolower($col),['dpi','cui','identificacion'])){
            $valor_despliegue="<a href=\"?dpi=" . urlencode(encript($valor)) . "\">$valor</a>";
            echo "<div><strong>".strtoupper($col).":</strong> $valor_despliegue</div>";
        } else{
            echo "<div><strong>".strtoupper($col).":</strong> $valor</div>";
        }
    }
    ?>

    </div>
</div>

<?php endforeach; 

if ($search&&$total_registros):?>

<!-- PAGINACIÓN -->
<div class="d-flex flex-wrap gap-2">

<?php if ($pagina > 1): ?>
<a class="btn btn-secondary btn-sm" href="?nombre=<?= urlencode($search) ?>&token=<?=$token?>&page=1"><<</a>
<a class="btn btn-secondary btn-sm" href="?nombre=<?= urlencode($search) ?>&token=<?=$token?>&page=<?= $pagina-1 ?>"><</a>
<?php endif; ?>

<?php
$rangos = [];

for ($i=1;$i<=3 && $i<=$total_paginas;$i++) $rangos[]=$i;
for ($i=$total_paginas-2;$i<=$total_paginas;$i++) if($i>0)$rangos[]=$i;
for ($i=$pagina-1;$i<=$pagina+1;$i++) if($i>0 && $i<=$total_paginas)$rangos[]=$i;

$rangos = array_unique($rangos);
sort($rangos);

foreach ($rangos as $p):
?>
<a class="btn btn-sm <?= $p==$pagina?'btn-primary':'btn-outline-primary' ?>"
   href="?nombre=<?= urlencode($search) ?>&token=<?=$token?>&page=<?= $p ?>">
   <?= $p ?>
</a>
<?php endforeach; ?>

<?php if ($pagina < $total_paginas): ?>
<a class="btn btn-secondary btn-sm" href="?nombre=<?= urlencode($search) ?>&token=<?=$token?>&page=<?= $pagina+1 ?>">></a>
<a class="btn btn-secondary btn-sm" href="?nombre=<?= urlencode($search) ?>&token=<?=$token?>&page=<?= $total_paginas ?>">>></a>
<?php endif; ?>

</div>

<!-- IR A PÁGINA -->
<form method="GET" class="mt-3 d-flex gap-2">
    <input type="hidden" name="nombre" value="<?= htmlspecialchars($search) ?>">
    <input type="number" name="page" class="form-control" min="1" max="<?= $total_paginas ?>" placeholder="Ir a página">
    <button class="btn btn-outline-dark">Ir</button>
    <input type="hidden" name="token" value="<?=$token?>" >
</form>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php endif; ?>
