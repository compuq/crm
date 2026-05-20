<?php
namespace LEX360\Core;

class Router
{
    private array $routes = [
        // Auth
        'login'             => 'AuthController@loginView',
        'do_login'          => 'AuthController@doLogin',
        'logout'            => 'AuthController@logout',
        
        // Dashboard
        'dashboard'         => 'DashboardController@index',
        
        // Clientes & Gestión
        'clientes'          => 'ClienteController@listar',
        'registrar_gestion' => 'GestionController@registrarGestion',
        'get_tipologias'    => 'GestionController@getTipologias',
        'get_tipologias_config' => 'GestionController@getTipologiasConfig',
        
        
        // Cargas
        'carga_clientes'    => 'CargaController@formulario',
        'importar_clientes' => 'CargaController@importar',
        'carga_gestiones'   => 'GestionCargaController@formulario',
        'procesar_gestiones'=> 'GestionCargaController@procesar',
        
        // Pagos
        'validar_pagos'     => 'PagoController@validar',
        'validar_pago' => 'PagoController@validarPago',
        
        // Asistencia
        'asistencia'        => 'AsistenciaController@index',
        'registrar_asistencia' => 'AsistenciaController@registrar',
        
        // Reportes
        'reportes'          => 'ReporteController@index',
        'descargar_reporte' => 'ReporteController@generar',
        'descargar_reporte_gestiones' => 'ReporteController@generarGestiones',
        
        // Usuarios & Config
        'usuarios'          => 'UsuarioController@index',
        'guardar_usuario'   => 'UsuarioController@guardar',
        'toggle_usuario'    => 'UsuarioController@toggleActivo',
        'configuracion'     => 'ConfiguracionController@index',
        'guardar_cartera'   => 'ConfiguracionController@guardarCartera',
        'cargar_tipologias' => 'ConfiguracionController@cargarTipologias',
        'obtener_tipologias' => 'ConfiguracionController@obtenerTipologias',
        //Claves
        'cambiar_clave' => 'AuthController@cambiarClave',
        //Auditoría
        'auditoria' => 'LogController@index',
        //Proesas
        'mis_promesas' => 'PromesaController@index',
        //Configuración extras de cartera
        'obtener_extras'   => 'ConfiguracionController@obtenerExtras',
        'guardar_extra'    => 'ConfiguracionController@guardarExtra',
        'eliminar_extra'   => 'ConfiguracionController@eliminarExtra',
        //Plantilla csv para carga
        'plantilla_clientes' => 'CargaController@descargarPlantilla',
        // Rutas de Cargas y Plantillas
        'descargar_plantilla'        => 'CargaController@descargarPlantilla',
        //'importar_clientes'          => 'CargaController@importar', // O 'importar' según tu formulario

        // Rutas de Gestiones (si ya creaste los métodos)
        'descargar_plantilla_gestiones' => 'GestionController@descargarPlantillaGestiones',
        'importar_gestiones'          => 'GestionController@importarGestiones',
        'get_extras_gestion' => 'ConfigController@getExtrasGestion',
        'get_ultimas_gestiones' => 'GestionController@getUltimasGestiones',
        //'guardar_extra' => 'ConfigController@guardarExtra',
        'guardar_campo_extra' => 'ConfigController@guardarCampoExtra',
        'configurar_extras'    => 'ConfigController@configurarExtras',
        //'guardar_campo_extra'  => 'ConfigController@guardarCampoExtra',
        'toggle_extra'         => 'ConfigController@toggleExtra',
        //Detalle de clientes en vista
        'get_cliente_detalle' => 'ClienteController@getDetalle',
        'get_promesas_pendientes' => 'GestionController@getPromesasPendientes',
        'get_proximas_llamadas' => 'GestionController@getProximasLlamadas',
        //Reportes
        // Reportes
        'reportes_gestiones' => 'ReporteController@verGestiones',
        'reportes_pagos'     => 'ReporteController@verPagos',
        'reportes_promesas'  => 'ReporteController@verPromesas',
    ];

    public function dispatch(): void
    {
        $action = $_GET['action'] ?? 'login';
        $action = preg_replace('/[^a-zA-Z0-9_]/', '', $action);

        if (isset($this->routes[$action])) {
            [$controllerName, $method] = explode('@', $this->routes[$action]);
            $controllerClass = "LEX360\\Controllers\\{$controllerName}";
            
            if (class_exists($controllerClass) && method_exists($controllerClass, $method)) {
                $controller = new $controllerClass();
                $controller->$method();
            } else {
                http_response_code(404);
                echo "🔴 Error 404: Controlador '$controllerName' o método '$method' no encontrado. Verifica la estructura de archivos.";
            }
        } else {
            http_response_code(404);
            echo "🔴 Error 404: Ruta '$action' no válida.";
        }
    }
}