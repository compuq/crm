<?php
namespace LEX360\Models\Services;

class ReporteService
{
    /**
     * Genera y descarga un archivo XLS nativo usando headers HTTP + tabla HTML
     * Excel interpreta automáticamente el HTML como hoja de cálculo
     */
    public function exportarExcelNativo(array $datos, string $titulo = 'Reporte'): void
    {
        // 1. Headers para forzar descarga como Excel
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=\"LEX360_{$titulo}_" . date('YmdHis') . ".xls\"");
        header("Pragma: no-cache");
        header("Expires: 0");

        // 2. Estructura HTML que Excel parsea como hoja de cálculo
        echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
        echo '<head>';
        echo '<meta http-equiv="Content-Type" content="application/vnd.ms-excel; charset=UTF-8">';
        echo '<style>
            @page { margin: 1cm; }
            body { font-family: Calibri, Arial, sans-serif; font-size: 10pt; }
            table { border-collapse: collapse; width: 100%; }
            th { background-color: #003366; color: #ffffff; font-weight: bold; padding: 6px 8px; border: 1px solid #cccccc; text-align: left; }
            td { padding: 5px 8px; border: 1px solid #cccccc; }
            tr:nth-child(even) { background-color: #f2f2f2; }
            .num { text-align: right; }
            .fecha { text-align: center; }
        </style>';
        echo '</head><body>';
        
        echo "<h2 style='color:#003366; margin-bottom:10px;'>{$titulo}</h2>";
        echo "<p style='color:#666; font-size:9pt;'>Generado: " . date('d/m/Y H:i:s') . " | LEX 360 CRM</p>";
        echo '<table>';

        // 3. Encabezados dinámicos
        if (!empty($datos)) {
            echo '<tr>';
            foreach (array_keys($datos[0]) as $col) {
                $label = ucwords(str_replace('_', ' ', $col));
                echo "<th>{$label}</th>";
            }
            echo '</tr>';

            // 4. Filas de datos
            foreach ($datos as $row) {
                echo '<tr>';
                foreach ($row as $cell) {
                    $val = is_null($cell) ? '' : $cell;
                    $class = is_numeric($val) ? 'num' : '';
                    echo "<td class='{$class}'>" . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . "</td>";
                }
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="10" style="text-align:center; padding:20px; color:#999;">Sin datos para el período seleccionado</td></tr>';
        }

        echo '</table></body></html>';
        exit; // Finaliza ejecución para evitar salida extra
    }
}