<?php
namespace LEX360\Controllers;

use LEX360\Core\BaseExternaDao;

class ExternosController extends BaseExternaDao{
    public function consultarExternos(): void{
       header('Content-Type: application/json; charset=utf-8');
        $dpi     = trim($_POST['dpi'] ?? '');
        $nombres = trim($_POST['nombre'] ?? '');
        $datos   = trim($_POST['datos'] ?? '');

        $resultados = [];

        if ($dpi !== '') {
            $resultados = array_merge(
                $resultados,
                $this->findByDpi($dpi)
            );
        }

        if ($nombres !== '') {
            $resultados = array_merge(
                $resultados,
                $this->findByNombre($nombres)
            );
        }

        if ($datos !== '') {
            $resultados = array_merge(
                $resultados,
                $this->findByOtros($datos)
            );
        }

        echo json_encode($resultados, JSON_UNESCAPED_UNICODE);
    }
    public function consultarPrueba(): void{
    header('Content-Type: application/json; charset=utf-8');

    $resultados = [
        [
            'nombre'        => 'Juan Pérez',
            'dpi'           => '1234567890101',
            'telefono'      => '55555555',
            'direccion'     => '',
            'estado'        => 'Activo',
            'fecha_consulta'=> date('Y-m-d H:i:s')
        ],
        [
            'nombre'        => 'María López',
            'dpi'           => '9876543210101',
            'telefono'      => '44444444',
            'direccion'     => 'Zona 10, Ciudad de Guatemala',
            'estado'        => 'Inactivo',
            'fecha_consulta'=> date('Y-m-d H:i:s')
        ],
        [
            'nombre'        => 'Carlos Ramírez',
            'dpi'           => '4567891230101',
            'telefono'      => '33333333',
            'direccion'     => 'Mixco',
            'estado'        => 'Activo',
            'fecha_consulta'=> date('Y-m-d H:i:s')
        ]
    ];

    echo json_encode($resultados, JSON_UNESCAPED_UNICODE);
    exit;
}
}