<?php
namespace LEX360\Controllers;

use LEX360\Core\Controller;
use LEX360\Models\Services\GestionMasivaService;

class GestionCargaController extends Controller
{
    public function importarTipologias(string $filePath, int $carteraId = null): array
    {
        $this->db->beginTransaction();
        try {
            // Leer CSV/XLSX
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $rows = $spreadsheet->getActiveSheet()->toArray();
            
            if (empty($rows) || count($rows) < 2) {
                throw new \Exception("El archivo está vacío o no tiene datos.");
            }

            // Encabezados esperados
            $headers = array_map('strtolower', array_map('trim', $rows[0]));
            $required = ['clase', 'nombre'];
            $missing = array_diff($required, $headers);
            if (!empty($missing)) {
                throw new \Exception("Faltan columnas obligatorias: " . implode(', ', $missing));
            }

            $insertados = 0;
            $errores = [];
            $stmt = $this->db->prepare("
                INSERT INTO tipologias (
                    clase, padre_id, nombre, codigo_origen, id_cartera,
                    estatus_default, requiere_proxima_fecha, requiere_monto
                ) VALUES (
                    :clase, :padre_id, :nombre, :codigo_origen, :id_cartera,
                    :estatus_default, :requiere_proxima_fecha, :requiere_monto
                )
                ON CONFLICT (codigo_origen, id_cartera) 
                DO UPDATE SET 
                    nombre = EXCLUDED.nombre,
                    estatus_default = EXCLUDED.estatus_default,
                    requiere_proxima_fecha = EXCLUDED.requiere_proxima_fecha,
                    requiere_monto = EXCLUDED.requiere_monto
            ");

            foreach (array_slice($rows, 1) as $linea => $row) {
                try {
                    $data = array_combine($headers, array_map('trim', $row));
                    
                    // Validaciones básicas
                    if (!in_array($data['clase'], ['T', 'S'])) {
                        throw new \Exception("Clase inválida: debe ser 'T' o 'S'");
                    }
                    if (empty($data['nombre'])) {
                        throw new \Exception("Nombre es obligatorio");
                    }

                    // Procesar valores opcionales con defaults
                    $estatus = strtoupper($data['estatus_default'] ?? 'SINC');
                    if (!in_array($estatus, ['SINC', 'COMP', 'PAGG', 'PAGO'])) {
                        $estatus = 'SINC';
                    }

                    $reqFecha = filter_var($data['requiere_proxima_fecha'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
                    $reqMonto = filter_var($data['requiere_monto'] ?? 'false', FILTER_VALIDATE_BOOLEAN);

                    $stmt->execute([
                        'clase' => $data['clase'],
                        'padre_id' => !empty($data['padre_id']) ? (int)$data['padre_id'] : null,
                        'nombre' => substr($data['nombre'], 0, 100),
                        'codigo_origen' => !empty($data['codigo_origen']) ? substr($data['codigo_origen'], 0, 20) : null,
                        'id_cartera' => !empty($data['id_cartera']) ? (int)$data['id_cartera'] : $carteraId,
                        'estatus_default' => $estatus,
                        'requiere_proxima_fecha' => $reqFecha,
                        'requiere_monto' => $reqMonto
                    ]);

                    $insertados++;
                } catch (\Exception $e) {
                    $errores[] = "Línea " . ($linea + 2) . ": " . $e->getMessage();
                }
            }

            $this->db->commit();
            return ['success' => true, 'insertados' => $insertados, 'errores' => $errores];

        } catch (\Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'errores' => ["Error crítico: " . $e->getMessage()]];
        }
    }
    /**
     * Muestra el formulario de carga de gestiones (CSV)
     */
    public function formulario(): void
    {
        $this->session->requireAuth();
        
        // Solo administradores y supervisores generales pueden cargar históricos masivos
        if (!in_array($this->session->getUser()['role'], ['admin', 'supervisor_general'])) {
            header("Location: ?action=dashboard");
            exit;
        }

        $pageTitle = "Carga Masiva de Gestiones | LEX 360";
        
        // Buffer de salida para cargar la vista dentro del layout maestro
        ob_start();
        require_once __DIR__ . '/../views/carga/gestiones.php';
        $viewContent = ob_get_clean();
        
        // Renderizar layout completo (header + nav + footer)
        require_once __DIR__ . '/../views/frontend.php';
    }

    /**
     * Procesa el archivo CSV y guarda las gestiones
     */
    public function procesar(): void
    {
        $this->session->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['csv_gestiones'])) {
            header("Location: ?action=carga_gestiones");
            exit;
        }

        $service = new GestionMasivaService();
        $resultado = $service->importarGestiones(
            $_FILES['csv_gestiones']['tmp_name'], 
            $this->session->getUser()['id']
        );

        // Redirección con mensaje flash (simple)
        $tipo = $resultado['success'] ? 'success' : 'danger';
        $msg  = $resultado['success'] 
            ? "✅ Importadas: {$resultado['insertados']} gestiones." 
            : "❌ Error: {$resultado['error']}";
        
        // Nota: Para una implementación robusta de mensajes flash, usa $_SESSION
        // Aquí usamos parámetros GET para simplificar la demo
        header("Location: ?action=carga_gestiones&msg=" . urlencode($msg) . "&type=" . $tipo);
        exit;
    }
}