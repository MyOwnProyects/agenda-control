<?php 

namespace App\Library;

use Phalcon\Di\Di; // Ajuste para Phalcon >= 4.x
use Phalcon\Di\DiInterface; // Interfaz para compatibilidad

class FuncionesGlobales{

    /** 
     * FUNCION PARA HACER MAYUSCULAS UN STRING CON UTF-8
     * 
     * @param   string $string PARAMETRO A MODIFICAR
     * @return  string 
    */
    public static function UpperString($string){
        if (is_string($string)){
            $string = mb_strtoupper($string,'UTF-8');
        }
        return $string;
    }

    /**
     * FUNCION PARA CREAR UNA PETICION A LA API
     * 
     * ESTA FUNCION CREA LA ESTRUCTURA PARA ENVIAR UNA SOLICITUD A LA API YA SE
     * -    GET
     * -    POST
     * -    PUT
     * -    DELETE
     * 
     * @param   string      $method     TIPO DE SOLICITUD GET POST PUT DELETE
     * @param   string      $route      RUTA COMPLETA DE LA API
     * @param   string|null $params     PARAMETROS DE LA SOLICITUD
     * @param   string|null $headers    HEADERS DE LA SOLICITUD
     * 
     * @return  mixed               RETORNA LA RESPUESTA DE LA API
     */
    public static function RequestApi($method,$route,$params = null,$headers = null){
        try{

            $method = self::UpperString($method);

            // Configurar cURL
            $ch = curl_init();

            // RUTA
            curl_setopt($ch, CURLOPT_URL, $route);

            // METODO DE ENVIO
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            
            if (!empty($params)){
                if ($method == 'GET'){
                    curl_setopt($ch,CURLOPT_URL,$route . '?'. http_build_query($params,'flags_'));
                } else {
                    curl_setopt($ch,CURLOPT_POSTFIELDS, json_encode($params));
                }
            }

            if (!empty($headers)){
                curl_setopt($ch,CURLOPT_HTTPHEADER, $headers);
            }

            //  DEVUELVE LA SOLICITUD COMO STRING EN LUGAR DE PINTARLA
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // Ejecutar la solicitud
            $response = curl_exec($ch);

            // Manejar errores
            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                return ['error' => $response,'status_code' => 400];
            }

            curl_close($ch);

            // Devolver la respuesta como JSON
            return json_decode($response, true);

        }catch(\Exception $e){
            return $e->getMessage();
        }
    }

    /**
     * FUNCION QUE RETORNA SI EL USUARIO TIENE ACCESO A UN PERMISO EN ESPECIFICO
     * 
     * @param   string  $controller
     * @param   string  $action
     * @return  boolean
     */
    public static function HasAccess($controller,$action){
        
        $flag_return    = false;
        // Obtén el contenedor DI global
        $di = Di::getDefault();

        // Verifica si el servicio de sesión está disponible
        if (!$di instanceof DiInterface || !$di->has('session')) {
            return $flag_return;
        }

        // Obtén el servicio de sesión
        $session = $di->get('session');

        $arr_permisos   = $session->get('permisos');

        foreach($arr_permisos as $permiso){
            if ($permiso['controlador'] == $controller && $permiso['accion'] == $action){
                $flag_return    = true;
                break;
            }
        }

        return $flag_return;

    }
}