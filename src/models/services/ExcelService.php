<?php
namespace LEX360\Models\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ExcelService
{
    /**
     * Lee un archivo .xlsx y devuelve un array de arrays asociativos
     */
    public function leerXlsx(string $filePath): array
    {
        if (!file_exists($filePath)) throw new \Exception("Archivo XLSX no encontrado.");
        
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        // toArray con flags para preservar celdas vacías y estructura
        $rows = $sheet->toArray(null, true, true, true);

        if (count($rows) < 2) throw new \Exception("El archivo está vacío o solo tiene encabezados.");

        // Fila 1: Encabezados (Convertimos ['A'=>'Col1', 'B'=>'Col2'] a ['Col1', 'Col2'])
        $rawHeaders = array_shift($rows); 
        $headers = array_values($rawHeaders);

        $data = [];
        foreach ($rows as $row) {
            $item = [];
            $i = 0;
            // Iteramos en orden para mapear encabezados con valores
            foreach ($row as $cellValue) {
                // Manejar fechas de Excel
                if ($cellValue instanceof \DateTimeInterface) {
                    $cellValue = $cellValue->format('Y-m-d H:i:s');
                }
                $item[$headers[$i] ?? 'columna_'.$i] = trim($cellValue ?? '');
                $i++;
            }
            // Solo agregar si la fila tiene datos
            if (count(array_filter($item)) > 0) {
                $data[] = $item;
            }
        }
        return $data;
    }

    /**
     * Exporta datos a .xlsx y fuerza la descarga
     */
    public function exportarXlsx(array $datos, string $titulo = 'Reporte', array $formatos = []): void
    {
        //  CRÍTICO: Limpiar cualquier salida previa (warnings, espacios, etc.)
        // Esto evita el error "Headers already sent" y la salida de basura "PK..."
        while (ob_get_level()) {
            ob_end_clean();
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($titulo, 0, 31));

        if (empty($datos)) {
            $sheet->setCellValue('A1', 'Sin datos para exportar');
        } else {
            $headers = array_keys($datos[0]);
            $col = 'A';
            
            // 1. Escribir Encabezados con Estilo
            foreach ($headers as $header) {
                // ✅ PHP 8.2 FIX: Usar {$col} en lugar de ${col}
                $sheet->setCellValue("{$col}1", $this->humanizeKey($header));
                
                $style = $sheet->getStyle("{$col}1");
                $style->getFont()->setBold(true);
                $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('4E73DF');
                $style->getFont()->getColor()->setRGB('FFFFFF');
                
                // Formatos
                if (isset($formatos[$header])) {
                    if (!empty($formatos[$header]['ancho'])) $sheet->getColumnDimension($col)->setWidth($formatos[$header]['ancho']);
                    
                    $lastRow = count($datos) + 1;
                    if (($formatos[$header]['formato'] ?? '') === 'date') {
                        $sheet->getStyle("{$col}2:{$col}{$lastRow}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                    } elseif (($formatos[$header]['formato'] ?? '') === 'currency') {
                        $sheet->getStyle("{$col}2:{$col}{$lastRow}")->getNumberFormat()->setFormatCode('#,##0.00');
                    } elseif (($formatos[$header]['formato'] ?? '') === 'text') {
                        // ← NUEVO: Forzar formato texto para evitar notación científica
                        $sheet->getStyle("{$col}2:{$col}{$lastRow}")->getNumberFormat()->setFormatCode('@');
                        // También poner un apóstrofe al inicio del valor ejemplo
                        if (isset($ejemplo[$header]) && is_numeric($ejemplo[$header])) {
                            $ejemplo[$header] = "'" . $ejemplo[$header]; // Apóstrofe fuerza texto en Excel
                        }
                    }
                }
                $col++;
            }

            // 2. Escribir Datos
            $rowNum = 2;
            foreach ($datos as $row) {
                $col = 'A';
                foreach ($headers as $key) {
                    $val = $row[$key] ?? '';
                    // Convertir fechas string a fecha Excel
                    if (isset($formatos[$key]['formato']) && $formatos[$key]['formato'] === 'date' && !empty($val)) {
                        $val = Date::PHPToExcel(strtotime($val));
                    }
                    // ✅ PHP 8.2 FIX
                    $sheet->setCellValue("{$col}{$rowNum}", $val);
                    $sheet->getStyle("{$col}{$rowNum}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                    $col++;
                }
                $rowNum++;
            }

            // 3. Auto-Filtro
            $lastCol = Coordinate::stringFromColumnIndex(count($headers));
            $sheet->setAutoFilter("A1:{$lastCol}" . ($rowNum - 1));
        }

        // 4. Forzar Descarga (asegurando headers limpios)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $titulo . '_' . date('Ymd_His') . '.xlsx"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function humanizeKey(string $key): string
    {
        return ucwords(str_replace(['_','-'], ' ', $key));
    }
}