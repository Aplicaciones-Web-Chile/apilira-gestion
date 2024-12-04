<?php

class dashboard
{

    const CODIGO_EXITO = 1;
    const ESTADO_EXITO = 1;
    const ESTADO_ERROR = 2;
    const ESTADO_ERROR_BD = 3;
    const ESTADO_ERROR_PARAMETROS = 4;
    const ESTADO_NO_ENCONTRADO = 5;

    public static function post($peticion)
    {
        $idUsuario = usuarios::autorizar();
        if ($peticion[0] == 'cuentas_por_pagar') {
            return self::cuentas_por_pagar();
        }
        if ($peticion[0] == 'cuentas_por_cobrar') {
            return self::cuentas_por_cobrar();
        }
        if ($peticion[0] == 'ventas_gastos_rentabilidad') {
            return self::ventas_gastos_rentabilidad();
        }
        if ($peticion[0] == 'flujo_de_caja') {
            return self::flujo_de_caja();
        }
        if ($peticion[0] == 'estado_resultado') {
            //TODO
            return self::estado_resultado();
        }
        if ($peticion[0] == 'detalles') {
            return self::Detalles();
        }
    }

    /**
     * Obtiene las cuentas por pagar
     * @return cuentas por pagar
     * @throws Exception
     */
    private static function cuentas_por_pagar()
    {
        $body = file_get_contents('php://input');
        $requ = json_decode($body);
        oci_set_call_timeout(ConexionBD::obtenerInstancia()->obtenerBDDist($requ->Distribuidor), 5000);
        /*
		$comando = "
with par as(
    SELECT add_months(trunc(sysdate,'mm'),1-:MNTH) FECH_DESD
         , trunc(sysdate) FECH_HAST
         , :KEMP kemp
     from dual
)select par.fech_desd
      , par.fech_hast
      , floor( SUM( ccp.MONT ) ) SALD
  FROM (  
    select sum(ccp.MONT)+0.0 MONT
      from ccp
     cross join par
     where ccp.kemp = par.KEMP
       and ccp.TCCP = 'C'
       and ccp.FCCP between par.fech_desd and par.fech_hast
       and ccp.rutp not in (  '77513492','77513497')
     union all
    select -sum(ccp.MONT)+0.0 MONT
      from ccp
     cross join par 
     where ccp.kemp = par.KEMP
       and ccp.TCCP = 'A'
       and ccp.FCCP between par.fech_desd and par.fech_hast
       and ccp.rutp not in (  '77513492','77513497')
  ) ccp
 cross join par
";
*/
        $comando = "
with par as(
    SELECT :fech FECH_HAST
         , :kemp kemp
     from dual
)select par.fech_hast
      , floor( SUM( ccp.MONT ) ) SALD
  FROM (
    select sum(ccp.MONT)+0.0 MONT
      from ccp
     cross join par
     where ccp.kemp = par.KEMP
       and ccp.TCCP = 'C'
       and ccp.FCCP>= par.fech_hast
       and ccp.rutp not in (  '77513492','77513497')
     union all
    select -sum(ccp.MONT)+0.0 MONT
      from ccp
     cross join par
     where ccp.kemp = par.KEMP
       and ccp.TCCP = 'A'
       and ccp.FCCP>= par.fech_hast
       and ccp.rutp not in (  '77513492','77513497')
  ) ccp
 cross join par		
		";

        try {
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBDDist($requ->Distribuidor)->prepare($comando);
            //$sentencia->bindParam(':MNTH', intval(str_replace('m','',$requ->selectedPeriod)), PDO::PARAM_INT);
            $sentencia->bindParam(':KEMP', $requ->Distribuidor, PDO::PARAM_STR);
            if (strpos($requ->selectedPeriod, 'm') > -1) {
                $requ->selectedPeriod = date('d/m/Y');
            }
            $sentencia->bindParam(':FECH', $requ->selectedPeriod, PDO::PARAM_STR);
            if ($sentencia->execute()) {
                $datos = $sentencia->fetchAll(PDO::FETCH_ASSOC);
                http_response_code(200);
                return
                    [
                        "estado"    => self::ESTADO_EXITO,
                        "datos"     =>
                        //json_encode( 
                        [
                            'title'    => 'Cuentas por Pagar',
                            'amount'    => $datos[0]['SALD'],
                            'subtitle'    => ' Al ' . $datos[0]['FECH_HAST'],
                            'bgColor'    => '#D82327'
                        ]
                        //) 
                    ];
            } else
                throw new ExcepcionApi(self::ESTADO_ERROR, "Se ha producido un error");
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    /**
     * Obtiene las cuentas por cobrar
     * @return cuentas por cobrar
     * @throws Exception
     */
    private static function cuentas_por_cobrar()
    {
        $body = file_get_contents('php://input');
        $requ = json_decode($body);
        oci_set_call_timeout(ConexionBD::obtenerInstancia()->obtenerBDDist($requ->Distribuidor), 5000);
        /*
		$comando="
with par as(
    SELECT add_months(trunc(sysdate,'mm'),1-:MNTH) FECH_DESD
         , trunc(sysdate) FECH_HAST
         , :KEMP kemp
     from dual
)
SELECT par.FECH_DESD, par.FECH_HAST,floor( SUM( ccp.MONT ) )  SALD
FROM (
    select sum(ccc.MONT)+0.0 MONT
      from ccc
     cross join par 
     where ccc.kemp = par.kemp
       and ccc.TCCC = 'C'
       and ccc.FCCC between par.FECH_DESD and par.FECH_HAST
       and NOT EXISTS( SELECT rutp FROM prv WHERE prv.kemp=ccc.kemp AND prv.rutp=ccc.rutc AND prv.sucp=ccc.succ )
     union all
    select -sum(ccc.MONT)+0.0 MONT
      from ccc
     cross join par      
     where ccc.kemp = par.kemp
       and ccc.TCCC = 'A'
       and ccc.FCCC between par.FECH_DESD and par.FECH_HAST
       and NOT EXISTS( SELECT rutp FROM prv WHERE prv.kemp=ccc.kemp AND prv.rutp=ccc.rutc AND prv.sucp=ccc.succ )
) ccp
cross join par		
";
*/
        $comando = "
with par as(
    SELECT :fech FECH_HAST
         , :kemp kemp
     from dual
)
SELECT par.FECH_HAST,floor( SUM( ccp.MONT ) )  SALD
FROM (
    select sum(ccc.MONT)+0.0 MONT
      from ccc
     cross join par
     where ccc.kemp = par.kemp
       and ccc.TCCC = 'C'
       and ccc.FCCC<=par.FECH_HAST
       and NOT EXISTS( SELECT rutp FROM prv WHERE prv.kemp=ccc.kemp AND prv.rutp=ccc.rutc AND prv.sucp=ccc.succ )
     union all
    select -sum(ccc.MONT)+0.0 MONT
      from ccc
     cross join par
     where ccc.kemp = par.kemp
       and ccc.TCCC = 'A'
       and ccc.FCCC<=par.FECH_HAST
       and NOT EXISTS( SELECT rutp FROM prv WHERE prv.kemp=ccc.kemp AND prv.rutp=ccc.rutc AND prv.sucp=ccc.succ )
) ccp
cross join par		
		";
        try {
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBDDist($requ->Distribuidor)->prepare($comando);
            //$sentencia->bindParam(':MNTH', intval(str_replace('m','',$requ->selectedPeriod)), PDO::PARAM_INT);
            $sentencia->bindParam(':KEMP', $requ->Distribuidor, PDO::PARAM_STR);
            if (strpos($requ->selectedPeriod, 'm') > -1) {
                $requ->selectedPeriod = date('d/m/Y');
            }

            $sentencia->bindParam(':FECH', $requ->selectedPeriod, PDO::PARAM_STR);
            if ($sentencia->execute()) {
                $datos = $sentencia->fetchAll(PDO::FETCH_ASSOC);
                //die( json_encode( $datos ) );
                http_response_code(200);
                return
                    [
                        "estado"    => self::ESTADO_EXITO,
                        "datos"     =>
                        //json_encode( 
                        [
                            'title'    => 'Cuentas por Cobrar',
                            'amount'    => $datos[0]['SALD'],
                            'subtitle'    => ' Al ' . $datos[0]['FECH_HAST'],
                            'bgColor'    => '#50A643'
                        ]
                        //) 
                    ];
            } else
                throw new ExcepcionApi(self::ESTADO_ERROR, "Se ha producido un error");
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }


    /**
     * Obtiene Ventas Gastos y Rentabilidad
     * @return ventas gastos y rentabilidad
     * @throws Exception
     */
    private static function ventas_gastos_rentabilidad()
    {

        $body = file_get_contents('php://input');
        $requ = json_decode($body);
        oci_set_call_timeout(ConexionBD::obtenerInstancia()->obtenerBDDist($requ->Distribuidor), 5000);

        # last_day(sysdate) last_day_of_month
        # trunc(sysdate, 'mm') first_of_month
        # trunc(sysdate, 'mm')-1 last_day_of_prev_month

        $comando = "
with par as(
    select trunc( to_date( :fech , 'DD/MM/YYYY' ) , 'mm' )   first_day_of_month
         , trunc( to_date( :fech , 'DD/MM/YYYY' ) , 'mm' )-1 last_day_of_prev_month
         , add_months( trunc( to_date( :fech , 'DD/MM/YYYY' ) , 'mm' ) , -12 ) first_day_of_12_last_months
      from dual
)
SELECT '20'||t.YEAA YEAA
    , t.mnth
    , substr( to_char( t.femi, 'Month', 'NLS_DATE_LANGUAGE=spanish' ) , 1, 3 ) month_name
    , trunc( SUM( t.COSTO_LINEA ) ) COST
    , trunc( SUM( t.TOTAL_LINEA_CON_DESCUENTO ) ) TOTA
  FROM VENTAS4 T
 cross join par
 WHERE t.FEMI between par.first_day_of_12_last_months and par.last_day_of_prev_month
 GROUP BY t.YEAA,t.MNTH,substr( to_char( t.femi, 'Month', 'NLS_DATE_LANGUAGE=spanish' ) , 1, 3 )
 order by 1,2
";
        try {
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBDDist($requ->Distribuidor)->prepare($comando);
            if ($requ->FilterYears == null) {
                $requ->FilterYears = date('Y');
            };
            if ($requ->FilterMonth == null) {
                $requ->FilterMonth = date('m');
            };
            $fech = '01/' . $requ->FilterMonth . '/' . $requ->FilterYears;
            $sentencia->bindParam(':KEMP', $requ->Distribuidor, PDO::PARAM_STR);
            $sentencia->bindParam(':FECH', $fech, PDO::PARAM_STR);
            if ($sentencia->execute()) {
                $data = $sentencia->fetchAll(PDO::FETCH_ASSOC);
                //die( json_encode($data));  
                $mnth_names = [];
                $seri[] = ["name" => "Gastos", "type" => "column", "data" => []];
                $seri[] = ["name" => "Ventas", "type" => "column", "data" => []];
                $seri[] = ["name" => "Rentabilidad", "type" => "line", "data" => []];
                foreach ($data as $rec) {
                    $mnth_names[$rec['MONTH_NAME'] . '-' . $rec['YEAA']] = 1;
                    array_push($seri[0]["data"], $rec['COST']);
                    array_push($seri[1]["data"], $rec['TOTA']);
                    array_push($seri[2]["data"], $rec['TOTA'] - $rec['COST']);
                };
                http_response_code(200);
                return
                    [
                        "estado"    => self::ESTADO_EXITO,
                        "datos"     =>
                        [
                            'categories'    => array_keys($mnth_names),
                            'series'        => $seri
                        ]
                    ];
            } else {
                throw new ExcepcionApi(self::ESTADO_ERROR, "Se ha producido un error");
            }
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    /**
     * Obtiene Flujo de caja
     * @return flujo de caja
     * @throws Exception
     */
    private static function flujo_de_caja()
    {

        $body = file_get_contents('php://input');
        $requ = json_decode($body);
        oci_set_call_timeout(ConexionBD::obtenerInstancia()->obtenerBDDist($requ->Distribuidor), 5000);
        $comando = "
select trunc(sysdate) fech_hast, 1234567890 sald from dual
		";
        try {
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBDDist($requ->Distribuidor)->prepare($comando);
            //$sentencia->bindParam(':MNTH', intval(str_replace('m','',$requ->selectedPeriod)), PDO::PARAM_INT);
            $sentencia->bindParam(':KEMP', $requ->Distribuidor, PDO::PARAM_STR);
            if (strpos($requ->selectedPeriod, 'm') > -1) {
                $requ->selectedPeriod = date('d/m/Y');
            }

            $sentencia->bindParam(':FECH', $requ->selectedPeriod, PDO::PARAM_STR);
            if ($sentencia->execute()) {
                $datos = $sentencia->fetchAll(PDO::FETCH_ASSOC);
                //die( json_encode( $datos ) );
                http_response_code(200);
                return
                    [
                        "estado"    => self::ESTADO_EXITO,
                        "datos"     =>
                        //json_encode( 
                        [
                            'title'    => 'Flujo de caja',
                            'amount'    => $datos[0]['SALD'],
                            'subtitle'    => 'Al ' . $datos[0]['FECH_HAST'],
                            'bgColor'    => '#50A643'
                        ]
                        //) 
                    ];
            } else
                throw new ExcepcionApi(self::ESTADO_ERROR, "Se ha producido un error");
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    /**
     * Obtiene 
     * @return estado resultado
     * @throws Exception
     */
    private static function estado_resultado()
    {

        $body = file_get_contents('php://input');
        $requ = json_decode($body);
        oci_set_call_timeout(ConexionBD::obtenerInstancia()->obtenerBDDist($requ->Distribuidor), 5000);
        $comando = "
select trunc(sysdate) fech_hast, 987654321 sald from dual
		";
        try {
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBDDist($requ->Distribuidor)->prepare($comando);
            //$sentencia->bindParam(':MNTH', intval(str_replace('m','',$requ->selectedPeriod)), PDO::PARAM_INT);
            $sentencia->bindParam(':KEMP', $requ->Distribuidor, PDO::PARAM_STR);
            if (strpos($requ->selectedPeriod, 'm') > -1) {
                $requ->selectedPeriod = date('d/m/Y');
            }

            $sentencia->bindParam(':FECH', $requ->selectedPeriod, PDO::PARAM_STR);
            if ($sentencia->execute()) {
                $datos = $sentencia->fetchAll(PDO::FETCH_ASSOC);
                //die( json_encode( $datos ) );
                http_response_code(200);
                return
                    [
                        "estado"    => self::ESTADO_EXITO,
                        "datos"     =>
                        //json_encode( 
                        [
                            'title'    => 'Estado resultado',
                            'amount'    => $datos[0]['SALD'],
                            'subtitle'    => 'Al ' . $datos[0]['FECH_HAST'],
                            'bgColor'    => '#50A643'
                        ]
                        //) 
                    ];
            } else
                throw new ExcepcionApi(self::ESTADO_ERROR, "Se ha producido un error");
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    /**
     * Obtiene las cuentas por cobrar en excel
     * @return cuentas por cobrar
     * @throws Exception
     */
    private static function cuentas_por_cobrar_excel()
    {

        $body = file_get_contents('php://input');
        $requ = json_decode($body);
        oci_set_call_timeout(ConexionBD::obtenerInstancia()->obtenerBDDist($requ->Distribuidor), 5000);

        $comando = "
with par as(
    SELECT :fech FECH_HAST
         , :kemp kemp
     from dual
)
SELECT par.FECH_HAST,floor( SUM( ccp.MONT ) )  SALD
FROM (
    select sum(ccc.MONT)+0.0 MONT
      from ccc
     cross join par
     where ccc.kemp = par.kemp
       and ccc.TCCC = 'C'
       and ccc.FCCC<=par.FECH_HAST
       and NOT EXISTS( SELECT rutp FROM prv WHERE prv.kemp=ccc.kemp AND prv.rutp=ccc.rutc AND prv.sucp=ccc.succ )
     union all
    select -sum(ccc.MONT)+0.0 MONT
      from ccc
     cross join par
     where ccc.kemp = par.kemp
       and ccc.TCCC = 'A'
       and ccc.FCCC<=par.FECH_HAST
       and NOT EXISTS( SELECT rutp FROM prv WHERE prv.kemp=ccc.kemp AND prv.rutp=ccc.rutc AND prv.sucp=ccc.succ )
) ccp
cross join par		
		";
        try {
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBDDist($requ->Distribuidor)->prepare($comando);
            //$sentencia->bindParam(':MNTH', intval(str_replace('m','',$requ->selectedPeriod)), PDO::PARAM_INT);
            $sentencia->bindParam(':KEMP', $requ->Distribuidor, PDO::PARAM_STR);
            if (strpos($requ->selectedPeriod, 'm') > -1) {
                $requ->selectedPeriod = date('d/m/Y');
            }

            $sentencia->bindParam(':FECH', $requ->selectedPeriod, PDO::PARAM_STR);

            header("Content-Disposition: attachment; filename=cuentas_por_cobrar.xls");
            header("Content-Type: application/vnd.ms-excel");

            if ($sentencia->execute()) {
                $datos = $sentencia->fetchAll(PDO::FETCH_ASSOC);
                die(json_encode($datos));
                http_response_code(200);
                return
                    [
                        "estado"    => self::ESTADO_EXITO,
                        "datos"     =>
                        //json_encode( 
                        [
                            'title'    => 'Cuentas por Cobrar',
                            'amount'    => $datos[0]['SALD'],
                            'subtitle'    => ' Al ' . $datos[0]['FECH_HAST'],
                            'bgColor'    => '#50A643'
                        ]
                        //) 
                    ];
            } else
                throw new ExcepcionApi(self::ESTADO_ERROR, "Se ha producido un error");
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }
    /**
     * Obtiene las cuentas por pagar
     * @return cuentas detalles
     * @throws Exception
     */
    private static function Detalles()
    {
        $body = file_get_contents('php://input'); // Leer el cuerpo de la solicitud
        $requ = json_decode($body); // Decodificar el JSON recibido

        // Validar parámetros obligatorios
        if (!isset($requ->Distribuidor) || !isset($requ->selectedPeriod)) {
            throw new ExcepcionApi(self::ESTADO_ERROR_PARAMETROS, "Faltan parámetros obligatorios.");
        }

        // Configurar la consulta SQL
        $comando = "
    with par as(
        SELECT :fech FECH_HAST,
               :kemp kemp
        FROM dual
    )
    SELECT 
        ccc.RUT AS RUT,
        ccc.SUCURSAL AS Sucursal,
        ccc.NOMBRE_RAZON_SOCIAL AS Nombre_Razon_Social,
        ccc.SALDO AS Saldo,
        ccc.CREDITO_DISPONIBLE AS Credito_Disponible
    FROM 
        CUENTAS_DETALLES ccc
    CROSS JOIN par
    WHERE 
        ccc.kemp = par.kemp
        AND ccc.FCCC <= par.FECH_HAST
    ";

        try {
            // Preparar y ejecutar la consulta
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBDDist($requ->Distribuidor)->prepare($comando);
            $sentencia->bindParam(':kemp', $requ->Distribuidor, PDO::PARAM_STR);
            $sentencia->bindParam(':fech', $requ->selectedPeriod, PDO::PARAM_STR);

            if ($sentencia->execute()) {
                $datos = $sentencia->fetchAll(PDO::FETCH_ASSOC); // Obtener los datos

                // Verificar si hay resultados
                if ($datos) {
                    http_response_code(200); // Respuesta HTTP exitosa
                    return [
                        "estado" => self::ESTADO_EXITO,
                        "datos" => $datos
                    ];
                } else {
                    throw new ExcepcionApi(self::ESTADO_NO_ENCONTRADO, "No se encontraron detalles.");
                }
            } else {
                throw new ExcepcionApi(self::ESTADO_ERROR, "Error al ejecutar la consulta.");
            }
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }
}
