<?php
namespace LEX360\Core;

use LEX360\Models\Dao\Db\DatabaseExterna;
use PDO;

abstract class BaseExternaDao implements DaoExternaInterface
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = DatabaseExterna::getInstance();
    }

    public function findByDpi(String $dpi): array{
        $params= ['dpi'=>$dpi];
        $sql= "
            (SELECT 
                'Laboral 2026' as origen,
                fecha::text AS fecha_pago,
                dpi,
                salario::text,
                nit,
                razon_social,
                nombre_comercial,
                direccion AS direccion_trabajo,
                correo1,
                correo2,
                contacto,
                telefono_empresa,
                telefono_2 AS telefono_empresa_2,
                nombre_completo,
                NULL AS afiliacion,
                NULL AS no_patron,
                NULL AS nacimiento,
                NULL AS telefono_vac,
                NULL AS placa,
                NULL AS marca,
                NULL AS color,
                NULL AS modelo,
                NULL AS chasis,
                NULL AS ccubicos,
                NULL AS motor,
                NULL AS transaccion,
                NULL AS falza
            FROM public.laboral2026
            WHERE dpi = :dpi
            LIMIT 100)
            UNION ALL

            (SELECT 
                'Laboral 2025' as origen,
                cuota::text AS fecha_pago,
                dpi,
                salario::text,
                NULL AS nit,
                razon_social,
                nombre_comercial,
                NULL AS direccion_trabajo,
                NULL AS correo1,
                NULL AS correo2,
                NULL AS contacto,
                NULL AS telefono_empresa,
                NULL AS telefono_empresa_2,
                nombre AS nombre_completo,
                afiliacion,
                no_patron,
                NULL AS nacimiento,
                NULL AS telefono_vac,
                NULL AS placa,
                NULL AS marca,
                NULL AS color,
                NULL AS modelo,
                NULL AS chasis,
                NULL AS ccubicos,
                NULL AS motor,
                NULL AS transaccion,
                NULL AS falza
            FROM public.laboral2025
            WHERE dpi = :dpi
            LIMIT 100)
            UNION ALL

            (SELECT 
                'Vacunas' as origen,
                NULL AS fecha_pago,
                cui as dpi,
                NULL AS salario,
                nit,
                NULL AS razon_social,
                NULL AS nombre_comercial,
                direccion1 AS direccion_trabajo,
                NULL AS correo1,
                NULL AS correo2,
                NULL AS contacto,
                NULL AS telefono_empresa,
                NULL AS telefono_empresa_2,
                nombre_completo,
                NULL AS afiliacion,
                NULL AS no_patron,
                fecha_nacimiento AS nacimiento,
                telefono AS telefono_vac,
                NULL AS placa,
                NULL AS marca,
                NULL AS color,
                NULL AS modelo,
                NULL AS chasis,
                NULL AS ccubicos,
                NULL AS motor,
                NULL AS transaccion,
                NULL AS falza
            FROM public.vac7m
            WHERE cui = :dpi
            LIMIT 100)
            UNION ALL

            (SELECT 
                'Vehículos' as origen,
                NULL AS fecha_pago,
                dpi,
                NULL AS salario,
                nit,
                NULL AS razon_social,
                NULL AS nombre_comercial,
                NULL AS direccion_trabajo,
                NULL AS correo1,
                NULL AS correo2,
                NULL AS contacto,
                NULL AS telefono_empresa,
                NULL AS telefono_empresa_2,
                nombre AS nombre_completo,
                NULL AS afiliacion,
                NULL AS no_patron,
                NULL AS nacimiento,
                NULL AS telefono_vac,
                placa,
                marca,
                color,
                modelo,
                chasis,
                ccubicos,
                motor,
                transaccion,
                falza
            FROM public.vehiculos2024
            WHERE dpi = :dpi
            LIMIT 100)
        ;";

        $stmt=$this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
    public function findByNombre(String $nombre): array{
        $params= ['nombre'=>$nombre];
        $sql= "
SELECT *
FROM (

    (
        SELECT
            'Laboral 2026'::text AS origen,
            fecha::text AS fecha_pago,
            dpi::text AS dpi,
            salario::text AS salario,
            nit::text AS nit,
            razon_social::text,
            nombre_comercial::text,
            direccion::text AS direccion_trabajo,
            correo1::text,
            correo2::text,
            contacto::text,
            telefono_empresa::text,
            telefono_2::text AS telefono_empresa_2,
            nombre_completo::text,
            NULL::text AS afiliacion,
            NULL::text AS no_patron,
            NULL::text AS nacimiento,
            NULL::text AS telefono_vac,
            NULL::text AS placa,
            NULL::text AS marca,
            NULL::text AS color,
            NULL::text AS modelo,
            NULL::text AS chasis,
            NULL::text AS ccubicos,
            NULL::text AS motor,
            NULL::text AS transaccion,
            NULL::text AS falza
        FROM public.laboral2026
        WHERE to_tsvector('spanish', lower(coalesce(nombre_completo,'')))
              @@ plainto_tsquery('spanish', lower(:nombre))
        LIMIT 100
    )

    UNION ALL

    (
        SELECT
            'Laboral 2025'::text AS origen,
            cuota::text AS fecha_pago,
            dpi::text AS dpi,
            salario::text AS salario,
            NULL::text AS nit,
            razon_social::text,
            nombre_comercial::text,
            NULL::text AS direccion_trabajo,
            NULL::text AS correo1,
            NULL::text AS correo2,
            NULL::text AS contacto,
            NULL::text AS telefono_empresa,
            NULL::text AS telefono_empresa_2,
            nombre::text AS nombre_completo,
            afiliacion::text,
            no_patron::text,
            NULL::text AS nacimiento,
            NULL::text AS telefono_vac,
            NULL::text AS placa,
            NULL::text AS marca,
            NULL::text AS color,
            NULL::text AS modelo,
            NULL::text AS chasis,
            NULL::text AS ccubicos,
            NULL::text AS motor,
            NULL::text AS transaccion,
            NULL::text AS falza
        FROM public.laboral2025
        WHERE to_tsvector('spanish', lower(coalesce(nombre,'')))
              @@ plainto_tsquery('spanish', lower(:nombre))
        LIMIT 100
    )

    UNION ALL

    (
        SELECT
            'Vacunas'::text AS origen,
            NULL::text AS fecha_pago,
            cui::text AS dpi,
            NULL::text AS salario,
            nit::text AS nit,
            NULL::text AS razon_social,
            NULL::text AS nombre_comercial,
            direccion1::text AS direccion_trabajo,
            NULL::text AS correo1,
            NULL::text AS correo2,
            NULL::text AS contacto,
            NULL::text AS telefono_empresa,
            NULL::text AS telefono_empresa_2,
            nombre_completo::text,
            NULL::text AS afiliacion,
            NULL::text AS no_patron,
            fecha_nacimiento::text AS nacimiento,
            telefono::text AS telefono_vac,
            NULL::text AS placa,
            NULL::text AS marca,
            NULL::text AS color,
            NULL::text AS modelo,
            NULL::text AS chasis,
            NULL::text AS ccubicos,
            NULL::text AS motor,
            NULL::text AS transaccion,
            NULL::text AS falza
        FROM public.vac7m
        WHERE to_tsvector('spanish', lower(coalesce(nombre_completo,'')))
              @@ plainto_tsquery('spanish', lower(:nombre))
        LIMIT 100
    )

    UNION ALL

    (
        SELECT
            'Vehículos'::text AS origen,
            NULL::text AS fecha_pago,
            dpi::text AS dpi,
            NULL::text AS salario,
            nit::text AS nit,
            NULL::text AS razon_social,
            NULL::text AS nombre_comercial,
            NULL::text AS direccion_trabajo,
            NULL::text AS correo1,
            NULL::text AS correo2,
            NULL::text AS contacto,
            NULL::text AS telefono_empresa,
            NULL::text AS telefono_empresa_2,
            nombre::text AS nombre_completo,
            NULL::text AS afiliacion,
            NULL::text AS no_patron,
            NULL::text AS nacimiento,
            NULL::text AS telefono_vac,
            placa::text,
            marca::text,
            color::text,
            modelo::text,
            chasis::text,
            ccubicos::text,
            motor::text,
            transaccion::text,
            falza::text
        FROM public.vehiculos2024
        WHERE to_tsvector('spanish', lower(coalesce(nombre,'')))
              @@ plainto_tsquery('spanish', lower(:nombre))
        LIMIT 100
    )

) t;        ";

        $stmt=$this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
   public function findByOtros(String $datos): array{
        $params= ['datos'=>$datos];
        $sql= "
        SELECT *
            FROM (

                (SELECT 
                    'Laboral 2026' as origen,
                    fecha::text AS fecha_pago,
                    dpi,
                    salario::text,
                    nit,
                    razon_social,
                    nombre_comercial,
                    direccion AS direccion_trabajo,
                    correo1,
                    correo2,
                    contacto,
                    telefono_empresa,
                    telefono_2 AS telefono_empresa_2,
                    nombre_completo,
                    NULL AS afiliacion,
                    NULL AS no_patron,
                    NULL AS nacimiento,
                    NULL AS telefono_vac,
                    NULL AS placa,
                    NULL AS marca,
                    NULL AS color,
                    NULL AS modelo,
                    NULL AS chasis,
                    NULL AS ccubicos,
                    NULL AS motor,
                    NULL AS transaccion,
                    NULL AS falza
                FROM public.laboral2026
                WHERE search_vector @@ websearch_to_tsquery('spanish', :datos)
                LIMIT 100)
                UNION ALL

                (SELECT 
                    'Laboral 2025' as origen,
                    cuota::text AS fecha_pago,
                    dpi,
                    salario::text,
                    NULL AS nit,
                    razon_social,
                    nombre_comercial,
                    NULL AS direccion_trabajo,
                    NULL AS correo1,
                    NULL AS correo2,
                    NULL AS contacto,
                    NULL AS telefono_empresa,
                    NULL AS telefono_empresa_2,
                    nombre AS nombre_completo,
                    afiliacion,
                    no_patron,
                    NULL AS nacimiento,
                    NULL AS telefono_vac,
                    NULL AS placa,
                    NULL AS marca,
                    NULL AS color,
                    NULL AS modelo,
                    NULL AS chasis,
                    NULL AS ccubicos,
                    NULL AS motor,
                    NULL AS transaccion,
                    NULL AS falza
                FROM public.laboral2025
                WHERE search_vector @@ websearch_to_tsquery('spanish', :datos)
                LIMIT 100)
                UNION ALL

                (SELECT 
                    'Vacunas' as origen,
                    NULL::text AS fecha_pago,
                    cui as dpi,
                    NULL AS salario,
                    nit,
                    NULL AS razon_social,
                    NULL AS nombre_comercial,
                    direccion1 AS direccion_trabajo,
                    NULL AS correo1,
                    NULL AS correo2,
                    NULL AS contacto,
                    NULL AS telefono_empresa,
                    NULL AS telefono_empresa_2,
                    nombre_completo,
                    NULL AS afiliacion,
                    NULL AS no_patron,
                    fecha_nacimiento AS nacimiento,
                    telefono AS telefono_vac,
                    NULL AS placa,
                    NULL AS marca,
                    NULL AS color,
                    NULL AS modelo,
                    NULL AS chasis,
                    NULL AS ccubicos,
                    NULL AS motor,
                    NULL AS transaccion,
                    NULL AS falza
                FROM public.vac7m
                WHERE search_vector @@ websearch_to_tsquery('spanish', :datos)
                LIMIT 100)
                UNION ALL

                (SELECT 
                    'Vehículos' as origen,
                    NULL::text AS fecha_pago,
                    dpi,
                    NULL AS salario,
                    nit,
                    NULL AS razon_social,
                    NULL AS nombre_comercial,
                    NULL AS direccion_trabajo,
                    NULL AS correo1,
                    NULL AS correo2,
                    NULL AS contacto,
                    NULL AS telefono_empresa,
                    NULL AS telefono_empresa_2,
                    nombre AS nombre_completo,
                    NULL AS afiliacion,
                    NULL AS no_patron,
                    NULL AS nacimiento,
                    NULL AS telefono_vac,
                    placa,
                    marca,
                    color,
                    modelo,
                    chasis,
                    ccubicos,
                    motor,
                    transaccion,
                    falza
                FROM public.vehiculos2024
                WHERE search_vector @@ websearch_to_tsquery('spanish', :datos)
                LIMIT 100)
            ) t;
        ";

        $stmt=$this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }    
}