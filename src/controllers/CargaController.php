<?php
namespace LEX360\Controllers;

use LEX360\Core\Controller;
use LEX360\Models\Services\CsvService;

class CargaController extends Controller
{
    public function formulario(): void
    {
        $this->session->requireAuth();
        // Solo supervisores y admins pueden cargar
        if (!in_array($this->session->getUser()['role'], ['supervisor', 'supervisor_general', 'admin'])) {
            header("Location: ?action=dashboard");
            exit;
        }

        $user = $this->session->getUser();
        // Obtener carteras permitidas para este usuario
        $carteras = $this->carteraDao->findAllActive(); 

        $pageTitle = "Carga de Clientes | LEX 360";
        $viewContent = '';
        
        ob_start();
        require_once __DIR__ . '/../views/carga/clientes.php';
        $viewContent = ob_get_clean();
        
        require_once __DIR__ . '/../views/frontend.php';
    }


    // Método auxiliar de redirección (mantener si ya existe)
    /* private function redirigirConMensaje(string $msg, string $tipo): void
    {
        $_SESSION['flash_message'] = $msg;
        $_SESSION['flash_type'] = $tipo;
        header("Location: ?action=carga_clientes");
        exit;
    } */

    // Método auxiliar de redirección (mantener este si ya existe)
    /* private function redirigirConMensaje(string $msg, string $tipo): void
    {
        $_SESSION['flash_message'] = $msg;
        $_SESSION['flash_type'] = $tipo;
        header("Location: ?action=carga_clientes");
        exit;
    } */
        /**
     * Genera y descarga una plantilla CSV con los campos de la cartera seleccionada
     */
        public function descargarPlantillaGestiones(): void
    {
        $this->session->requireAuth();
        $excel = new \LEX360\Models\Services\ExcelService();
        
        $headers = [
            'Identificación' => 'identificacion',
            'Fecha Gestión' => 'fecha_gestion',
            'Tipología' => 'tipologia',
            'Comentario' => 'comentario',
            'Gestor' => 'usuario_gestor'
        ];

        $ejemplo = [
            'identificacion' => '1234567890',
            'fecha_gestion' => date('Y-m-d H:i:s'), // Excel formateará esto como fecha
            'tipologia' => 'Contacto Exitoso', // Nombre de la tipología
            'comentario' => 'Cliente confirma deuda.',
            'usuario_gestor' => 'juan.perez'
        ];

        $excel->exportarXlsx([$ejemplo], 'Plantilla_Gestiones', ['fecha_gestion' => ['formato' => 'date']]);
    }
        public function importarGestiones(): void
    {
        $this->session->requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        $file = $_FILES['archivo_gestiones'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            // Mensaje error
            return;
        }

        try {
            $excel = new \LEX360\Models\Services\ExcelService();
            $rows = $excel->leerXlsx($file['tmp_name']);
            
            $this->db->beginTransaction();
            $insertados = 0;
            $errores = [];

            foreach ($rows as $linea => $row) {
                try {
                    $idCliente = null;
                    // 1. Buscar Cliente por Identificación
                    $stmt = $this->db->prepare("SELECT id FROM clientes WHERE identificacion = :id LIMIT 1");
                    $stmt->execute(['id' => $row['identificacion'] ?? '']);
                    $cli = $stmt->fetch();
                    if (!$cli) {
                        $errores[] = "Fila " . ($linea + 2) . ": Cliente no encontrado.";
                        continue;
                    }
                    $idCliente = $cli['id'];

                    // 2. Buscar Tipología por Nombre
                    $idTipologia = null;
                    if (!empty($row['tipologia'])) {
                        $stmt = $this->db->prepare("SELECT id FROM tipologias WHERE nombre ILIKE :nombre LIMIT 1");
                        $stmt->execute(['nombre' => $row['tipologia']]);
                        $tip = $stmt->fetch();
                        if ($tip) $idTipologia = $tip['id'];
                    }

                    // 3. Buscar Usuario/Gestor (si viene en archivo)
                    $idUsuario = $this->session->getUser()['id']; // Por defecto el uploader
                    if (!empty($row['usuario_gestor'])) {
                        $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE usuario = :u AND activo=true");
                        $stmt->execute(['u' => $row['usuario_gestor']]);
                        $usr = $stmt->fetch();
                        if ($usr) $idUsuario = $usr['id'];
                    }

                    // 4. Insertar Historial
                    $stmt = $this->db->prepare("
                        INSERT INTO historial (id_cliente, id_usuario, id_tipologia, comentario, fecha_gestion) 
                        VALUES (:id_cli, :id_usr, :id_tip, :com, :fecha)
                    ");
                    $stmt->execute([
                        'id_cli' => $idCliente,
                        'id_usr' => $idUsuario,
                        'id_tip' => $idTipologia,
                        'com'    => $row['comentario'] ?? '',
                        'fecha'  => !empty($row['fecha_gestion']) ? $row['fecha_gestion'] : date('Y-m-d H:i:s')
                    ]);
                    $insertados++;
                } catch (\Exception $e) {
                    $errores[] = "Fila " . ($linea + 2) . ": " . $e->getMessage();
                }
            }
            
            $this->db->commit();
            // Redirigir con éxito
        } catch (\Exception $e) {
            $this->db->rollBack();
            // Redirigir con error crítico
        }
    }

public function descargarPlantilla(): void
{
    while (ob_get_level()) ob_end_clean();
    $this->session->requireAuth();
    
    $carteraId = (int)($_GET['cartera_id'] ?? 0);
    if (!$carteraId) { header("Location: index.php?action=carga_clientes"); exit; }

    $stmt = $this->db->prepare("SELECT * FROM carteras WHERE id = :id");
    $stmt->execute(['id' => $carteraId]);
    $config = $stmt->fetch();

    $stmt = $this->db->prepare("SELECT nombre_campo, etiqueta FROM extras_cartera WHERE id_cartera = :cid AND activo = true ORDER BY orden");
    $stmt->execute(['cid' => $carteraId]);
    $extras = $stmt->fetchAll();

    // === CONSTRUIR 3 FILAS ===
    
    // Fila 1: Nombres técnicos (LO QUE LEE EL SISTEMA)
    $filaTecnicos = [
        'cuenta', 'nombre', 'identificacion', 'saldo', 'telefono_1', 
        'telefono_2', 'email', 'direccion', 'gestor_usuario'
    ];
    foreach ($extras as $e) {
        $filaTecnicos[] = $e['nombre_campo']; // Solo nombre técnico, sin espacios
    }

    // Fila 2: Etiquetas visuales (GUÍA PARA EL USUARIO)
    $filaEtiquetas = [
        $config['cuenta_nombre'] ?? 'Cuenta',
        $config['lbl_nombre'] ?? 'Nombre',
        $config['identificacion_nombre'] ?? 'Identificación',
        $config['lbl_saldo'] ?? 'Saldo',
        $config['lbl_telefono'] ?? 'Teléfono',
        'Teléfono 2', 'Email', 'Dirección',
        $config['lbl_gestor'] ?? 'Gestor Asignado'
    ];
    foreach ($extras as $e) {
        $filaEtiquetas[] = $e['etiqueta'] ?? $e['nombre_campo'];
    }

    // Fila 3: Ejemplo de valores (FORMATO ESPERADO)
    $filaEjemplo = [
        'VISA-001234', 'Juan Pérez García', '1234567890101', '2500.00', '5555-1234',
        '', 'juan@ejemplo.com', '6a calle 4-44 zona 5 Mixco', 'maria.gestora'
    ];
    foreach ($extras as $e) {
        $filaEjemplo[] = match($e['nombre_campo']) {
            'tasa_interes' => '4.5',
            'fecha_nacimiento' => '1990-05-15',
            default => 'Ejemplo'
        };
    }

    // === GENERAR XLSX CON PhpSpreadsheet ===
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Fila 1: Técnicos (ocultos visualmente pero presentes para el sistema)
    $sheet->fromArray($filaTecnicos, null, 'A1');
    $sheet->getStyle('A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($filaTecnicos)) . '1')
          ->getFont()->setItalic(true)->getColor()->setRGB('888888');
    
    // Fila 2: Etiquetas (destacadas para el usuario)
    $sheet->fromArray($filaEtiquetas, null, 'A2');
    $sheet->getStyle('A2:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($filaEtiquetas)) . '2')
          ->getFont()->setBold(true)->getColor()->setRGB('4E73DF');
    
    // Fila 3: Ejemplo
    $sheet->fromArray($filaEjemplo, null, 'A3');
    
    // Auto-ajustar columnas y congelar paneles
    foreach (range('A', \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($filaTecnicos))) as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    $sheet->freezePane('A4'); // Congelar las 3 filas de guía

    // Forzar descarga
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="Plantilla_' . preg_replace('/[^a-zA-Z0-9]/', '_', $config['nombre_cartera']) .  '_' . date('dmYHis') .'.xlsx"');
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
    // Método auxiliar de redirección con soporte HTML
    private function redirigirConMensaje(string $msg, string $tipo): void
    {
        $_SESSION['flash_message'] = $msg;
        $_SESSION['flash_type'] = $tipo;
        header("Location: index.php?action=carga_clientes");
        exit;
    }
    // Método auxiliar de redirección
        public function importar(): void
    {
        $this->session->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?action=carga_clientes");
            exit;
        }

        $user = $this->session->getUser();
        $carteraId = $_POST['id_cartera'] ?? null;
        
        // ⚠️ IMPORTANTE: Debe coincidir con el name del input en la vista: name="archivo_csv"
        $file = $_FILES['archivo_csv'] ?? null;

        // Validaciones básicas
        if (!$carteraId || !$file || $file['error'] !== UPLOAD_ERR_OK) {
            $this->redirigirConMensaje('Error: Archivo inválido o falta seleccionar cartera.', 'danger');
            return;
        }

        // Validar extensión
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls'])) {
            $this->redirigirConMensaje('Error: Solo se permiten archivos Excel (.xlsx)', 'danger');
            return;
        }

        try {
            $service = new \LEX360\Models\Services\CsvService();
            
            // Pasar usuario completo para validar jerarquía de gestores
            $resultado = $service->importarClientes(
                $file['tmp_name'], 
                (int)$carteraId, 
                $user['id'],
                $user
            );

            if ($resultado['success']) {
                // Registrar en Auditoría (opcional si tienes LogService)
                if (class_exists('\LEX360\Models\Services\LogService')) {
                    \LEX360\Models\Services\LogService::registrar(
                        $this->db, $user['id'], 'carga_csv', 'clientes', null, null,
                        ['registros_importados' => $resultado['insertados'], 'cartera_id' => $carteraId]
                    );
                }

                $msg = "✅ Carga exitosa: {$resultado['insertados']} registros importados.";
                if (!empty($resultado['errores'])) {
                    $msg .= "<br>⚠️ Se encontraron {$resultado['total_errores']} errores:";
                    $msg .= "<ul class='mb-0 mt-1 small'>";
                    foreach ($resultado['errores'] as $err) {
                        $msg .= "<li>" . htmlspecialchars($err) . "</li>";
                    }
                    $msg .= "</ul>";
                }
                $this->redirigirConMensaje($msg, 'success');
            } else {
                $listaErrores = implode('<br>• ', array_map('htmlspecialchars', $resultado['errores']));
                $msg = "❌ Error crítico en carga:<br>• {$listaErrores}";
                $this->redirigirConMensaje($msg, 'danger');
            }
        } catch (\Exception $e) {
            error_log("[LEX360] Error en importar: " . $e->getMessage());
            $this->redirigirConMensaje("❌ Error del sistema: " . htmlspecialchars($e->getMessage()), 'danger');
        }
    }

}
