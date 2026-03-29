<?php 

namespace App\Library;

use Phalcon\Di\Di; // Ajuste para Phalcon >= 4.x
use Phalcon\Di\DiInterface; // Interfaz para compatibilidad
use Mpdf\Mpdf;
use Exception;

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
    public static function RequestApi($method, $route, $params = null, $headers = null) {
        try {
            $method = self::UpperString($method);
            
            // Obtener el contenedor DI global
            $di = Di::getDefault();
            
            // Verificar si el servicio de sesión está disponible
            if (!$di instanceof DiInterface || !$di->has('session')) {
                return ['error' => 'Sesión no disponible', 'status_code' => 500];
            }
            
            // Obtener el servicio de sesión
            $session = $di->get('session');
            
            // ============================================
            // AGREGAR TOKEN JWT AUTOMÁTICAMENTE
            // ============================================
            if (!is_array($headers)) {
                $headers = [];
            }
            
            // Verificar si es una ruta que NO requiere token (login, refresh)
            $publicRoutes = ['/autenticacion/login', '/autenticacion/refresh'];
            $isPublicRoute = false;
            
            foreach ($publicRoutes as $publicRoute) {
                if (strpos($route, $publicRoute) !== false) {
                    $isPublicRoute = true;
                    break;
                }
            }
            
            // Agregar Authorization header si NO es ruta pública
            if (!$isPublicRoute && $session->has('access_token')) {
                $headers[] = 'Authorization: Bearer ' . $session->get('access_token');
            }
            
            // Agregar usuario_solicitud (tu lógica actual)
            if ($params === null) {
                $params = [];
            }
            $params['usuario_solicitud'] = $session->get('clave');
            
            // ============================================
            // CONFIGURAR cURL
            // ============================================
            $ch = curl_init();
            
            // RUTA
            curl_setopt($ch, CURLOPT_URL, $route);
            
            // METODO DE ENVIO
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            
            if (!empty($params)) {
                if ($method == 'GET') {
                    curl_setopt($ch, CURLOPT_URL, $route . '?' . http_build_query($params, 'flags_'));
                } else {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
                    $headers[] = 'Content-Type: application/json';
                }
            }
            
            if (!empty($headers)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }
            
            // DEVUELVE LA SOLICITUD COMO STRING
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            // Ejecutar la solicitud
            $response = curl_exec($ch);
            
            // STATUS CODE DE RESPUESTA
            $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            curl_close($ch);
            
            // ============================================
            // MANEJAR 401 - TOKEN EXPIRADO - REFRESH AUTOMÁTICO
            // ============================================
            if ($httpStatus === 401 && !$isPublicRoute && $session->has('refresh_token')) {
                // Intentar renovar el token
                $refreshed = self::refreshToken($session);
                
                if ($refreshed) {
                    // Reintentar la petición original con el nuevo token
                    return self::RequestApi($method, $route, $params);
                } else {
                    // Refresh falló, destruir sesión y redirigir a login
                    $session->destroy();
                    
                    return [
                        'error' => 'Sesión expirada. Por favor, inicie sesión nuevamente.',
                        'status' => 401,
                        'redirect' => '/'
                    ];
                }
            }
            
            // ============================================
            // MANEJAR OTROS ERRORES
            // ============================================
            if (curl_errno($ch) || $httpStatus >= 400) {
                $msg_error = $response;
                try {
                    $response = json_decode($response, true);
                } catch (\Exception $ex) {
                    $response = $msg_error;
                }
                return ['error' => $response, 'status_code' => $httpStatus];
            }
            
            // Devolver la respuesta como JSON
            return json_decode($response, true);
            
        } catch (\Exception $e) {
            return ['error' => $e->getMessage(), 'status_code' => 500];
        }
    }

    /**
     * Renovar access token usando refresh token
     */
    private static function refreshToken($session)
    {
        try {
            $refreshToken = $session->get('refresh_token');
            
            if (!$refreshToken) {
                return false;
            }
            
            // Obtener URL de la API desde config o variable global
            $di = Di::getDefault();
            $config = $di->get('config'); // Asume que tienes config en DI
            $apiUrl = $config['BASEAPI']; // Ajustar según tu config
            
            $refreshRoute = $apiUrl . '/autenticacion/refresh';
            
            // Hacer petición de refresh
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $refreshRoute);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'refresh_token' => $refreshToken
            ]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $response = curl_exec($ch);
            $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpStatus === 200) {
                $data = json_decode($response, true);
                
                if (isset($data['access_token'])) {
                    // Actualizar access token en sesión
                    $session->set('access_token', $data['access_token']);
                    $session->set('token_created_at', time());
                    return true;
                }
            }

            return false;
            
        } catch (\Exception $e) {
            return false;
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

        $arr_permisos   = $session->has('permisos') ? $session->get('permisos') : array();

        foreach($arr_permisos as $permiso){
            if ($permiso['controlador'] == $controller && $permiso['accion'] == $action){
                $flag_return    = true;
                break;
            }
        }

        return $flag_return;

    }

    /**
     * FUNCION QUE CONSTRUYE LA DATA DEL CURL A GUARDAR 
     * 
     * @param   string  $controlador
     * @param   string  $accion
     * @param   string  $mensaje
     * @param   array   $data  
     * 
     * @return boolean 
     */
    public static function saveBitacora($controlador,$accion,$mensaje,$data){
        try{
            //  VARIABLES OBLIGATORIAS
            if (empty($controlador)){
                return false;
            }

            if (empty($accion)){
                return false;
            }

            if (empty($mensaje)){
                return false;
            }

            //  IP DE LA SOLICITUD
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];

            //  CONSTRUCCION DE LA RUTA
            $di = Di::getDefault(); // Se usa en lugar de $this->getDI()

            // OBTENER CONFIGURACIONES
            $rutas      = $di->get('rutas');
            $config     = $di->get('config');
            $url_api    = $config['BASEAPI'];

            //  INFORMACION DEL NAVEGADOR OBTENIDA DE SESION
            $session    = $di->get('session');
            $bfp        = $session->get('bfp');
            $navegador  = $session->get('navegador');


            //  CONSTRUCCION DEL ARRAY
            $arr_params = array(
                'controlador'   => $controlador,
                'accion'        => $accion,
                'mensaje'       => $mensaje,
                'data'          => $data,
                'ip_cliente'    => $ipAddress,
                'bfp'           => $bfp,
                'navegador'     => $navegador
            );

            $captura    = self::RequestApi('POST',$url_api.$rutas['tbbitacora_movimientos']['create'],$arr_params);
            
        }catch (\Exception $e){
            $aqui   = 1;
        }

        return true;
        
    }

    /**
     * FUNCIÓN PARA OBTENER LA HORA MÍNIMA Y MÁXIMA A PINTAR CON PRECISIÓN DE MINUTOS
     *
     * @param   array   $horario_atencion
     * @param   int     $intervalo_citas
     *
     * @return array
     */
    public static function allStructureSchedule($horario_atencion, $intervalo_citas = 60) {
        $min_hora_inicio = '23:59';
        $max_hora_termino = '00:00';
        $dias_disponibled = [];

        foreach ($horario_atencion as $horario) {
            $hora_inicio = strtotime($horario['hora_inicio']);
            $hora_termino = strtotime($horario['hora_termino']);

            if ($hora_inicio < strtotime($min_hora_inicio)) {
                $min_hora_inicio = $horario['hora_inicio'];
            }
            if ($hora_termino > strtotime($max_hora_termino)) {
                $max_hora_termino = $horario['hora_termino'];
            }

            foreach ($horario['dias'] as $dia) {
                if (!in_array($dia['dia'], $dias_disponibled)) {
                    $dias_disponibled[] = $dia['dia'];
                }
            }
        }

        $minutos_restantes = explode(':', $max_hora_termino)[1];
        $min_hora = explode(':', $min_hora_inicio)[0];

        if ($minutos_restantes != '00') {
            $max_hora = explode(':', $max_hora_termino)[0];
        } else {
            $max_hora = explode(':', $max_hora_termino)[0] - 1;
        }

        // Minutos totales desde medianoche
        $inicio_parts = explode(':', $min_hora_inicio);
        $termino_parts = explode(':', $max_hora_termino);

        $tmp_min_hora = intval($inicio_parts[0]) * 60 + intval($inicio_parts[1]);
        $tmp_max_hora = intval($termino_parts[0]) * 60 + intval($termino_parts[1]);

        $rangos_no_incluidos = SELF::timeRangeNotIncluded($horario_atencion, $tmp_min_hora, $tmp_max_hora);

        return [
            'min_hora'              => $min_hora,
            'max_hora'              => $max_hora,
            'min_hora_inicio'       => $min_hora_inicio,
            'max_hora_termino'      => $max_hora_termino,
            'rangos_no_incluidos'   => $rangos_no_incluidos
        ];
    }

    /** 
     * FUNCION PARA CALCULAR LAS HORAS NO INCLUIDAS EN EL HORARIO DE ATENCION
     * 
     * @param   Object $horario_atencion
     * 
     * @return array
    */
    public static function timeRangeNotIncluded($horario_atencion, $minutos_inicio, $minutos_termino) {
        $dias_semana = [
            1 => "Lunes",
            2 => "Martes",
            3 => "Miércoles",
            4 => "Jueves",
            5 => "Viernes",
            6 => "Sábado",
            7 => "Domingo"
        ];

        $rangos_no_incluidos = [];

        foreach ($dias_semana as $numero_dia => $nombre_dia) {
            $rangos_ocupados = [];

            foreach ($horario_atencion as $horario) {
                foreach ($horario['dias'] as $dia) {
                    if ($dia['dia'] == $numero_dia) {
                        // Convertimos a minutos totales desde medianoche
                        $inicio = explode(':', $horario['hora_inicio']);
                        $fin = explode(':', $horario['hora_termino']);
                        $rangos_ocupados[] = [
                            'start' => intval($inicio[0]) * 60 + intval($inicio[1]),
                            'end' => intval($fin[0]) * 60 + intval($fin[1]),
                        ];
                    }
                }
            }

            // Ordenar los rangos por hora de inicio
            usort($rangos_ocupados, fn($a, $b) => $a['start'] - $b['start']);

            $actual = $minutos_inicio;

            foreach ($rangos_ocupados as $rango) {
                if ($actual < $rango['start']) {
                    $rangos_no_incluidos[] = [
                        'start' => sprintf('%02d:%02d', intdiv($actual, 60), $actual % 60),
                        'end'   => sprintf('%02d:%02d', intdiv($rango['start'], 60), $rango['start'] % 60),
                        'day'   => $nombre_dia
                    ];
                }
                // Avanzamos al final del rango cubierto si es mayor
                $actual = max($actual, $rango['end']);
            }

            // Verificar si hay un hueco al final del día
            if ($actual < $minutos_termino) {
                $rangos_no_incluidos[] = [
                    'start' => sprintf('%02d:%02d', intdiv($actual, 60), $actual % 60),
                    'end'   => sprintf('%02d:%02d', intdiv($minutos_termino, 60), $minutos_termino % 60),
                    'day'   => $nombre_dia
                ];
            }
        }

        return $rangos_no_incluidos;
    }


    public static function obtenerRangosNoDisponiblesPorDia($horariosLocacion, $horariosProfesional, $hora_cierre) {
        $diasSemana = [
            1 => "Lunes", 2 => "Martes", 3 => "Miércoles", 4 => "Jueves",
            5 => "Viernes", 6 => "Sábado", 7 => "Domingo"
        ];

        $rangosNoDisponibles    = [];
        $arr_flag_vacio         = array();

        foreach ($diasSemana as $numeroDia => $nombreDia) {
            $horasLocacion = [];
            foreach ($horariosLocacion as $horario) {
                foreach ($horario['dias'] as $dia) {
                    if ($dia['dia'] == $numeroDia) {
                        $horasLocacion[] = [
                            "start" => strtotime($horario['hora_inicio']),
                            "end" => strtotime($horario['hora_termino'])
                        ];
                        $arr_flag_vacio[]   = [
                            "start" => $horario['hora_inicio'],
                            "end"   => $horario['hora_termino'],
                            "day"   => $nombreDia
                        ];
                    }
                }
            }

            $horasProfesional = [];
            foreach ($horariosProfesional as $horario) {
                foreach ($horario['dias'] as $dia) {
                    if ($dia['dia'] == $numeroDia) {
                        $horasProfesional[] = [
                            "start" => strtotime($horario['hora_inicio']),
                            "end" => strtotime($horario['hora_termino'])
                        ];
                    }
                }
            }

            foreach ($horasLocacion as $rangoLocacion) {
                $horaActual = $rangoLocacion['start'];
                while ($horaActual < $rangoLocacion['end']) {
                    $esCubierta = false;
                    $limiteRango = $rangoLocacion['end'];

                    foreach ($horasProfesional as $rangoProfesional) {
                        if ($horaActual >= $rangoProfesional['start'] && $horaActual < $rangoProfesional['end']) {
                            $esCubierta = true;
                            break;
                        }
                        if ($rangoProfesional['start'] > $horaActual && $rangoProfesional['start'] < $limiteRango) {
                            $limiteRango = $rangoProfesional['start'];
                        }
                    }

                    if (!$esCubierta) {
                        $start = date('H:i', $horaActual);
                        $horaActual = $limiteRango; // Detenerse en el inicio del rango del profesional
                        $end = ($hora_cierre == date('H:i', $horaActual)) ? date('H:i', $horaActual - 1) : date('H:i', $horaActual);
                        $rangosNoDisponibles[] = ["start" => $start, "end" => $end, "day" => $nombreDia];
                    } else {
                        $horaActual += 300; // Avanzar en intervalos de 5 minutos
                    }
                }
            }
        }

        $horariosAgrupados = [];
        $horariosPorDia = [];

        foreach ($rangosNoDisponibles as $horario) {
            $horariosPorDia[$horario['day']][] = $horario;
        }

        foreach ($horariosPorDia as $dia => $horariosDelDia) {
            usort($horariosDelDia, fn($a, $b) => strtotime($a['start']) - strtotime($b['start']));
            $horarioActual = $horariosDelDia[0];

            for ($i = 1; $i < count($horariosDelDia); $i++) {
                $siguienteHorario = $horariosDelDia[$i];
                if (strtotime($horarioActual['end']) + 1 >= strtotime($siguienteHorario['start'])) {
                    $horarioActual['end'] = $siguienteHorario['end'];
                } else {
                    $horariosAgrupados[] = $horarioActual;
                    $horarioActual = $siguienteHorario;
                }
            }

            $horariosAgrupados[] = $horarioActual;
        }

        //  SI ES VACIO RETORNARA EL HORARIO DE LA LOCACION COMO HORARIO NO DISPONIBLE
        if (count($horariosAgrupados) == 0){
            $horariosAgrupados[]    = $arr_flag_vacio;
        }

        return $horariosAgrupados;
    }

    /*  
        FUNCION QUE RECIBE DE PARAMETRO TODAS LAS CITAS DEL PACIENTE Y EL HORARIO DE 
        ATENCION DE LA LOCACION, RETORNA LAS CITAS QUE ENCAJAN CON EL HORARIO

        @param  $citas_paciente             {Obj}
        @param  $horario_atencion_locacion  {Obj}

        @return {Obj}
    */
    public static function AppoitmentByLocation($citas_paciente, $horario_atencion_locacion) {
        $cita_locacion = array();

        // Mapeo de días en español a números (1 = Lunes, ..., 7 = Domingo)
        $dias_semana = array(
            'Lunes' => 1,
            'Martes' => 2,
            'Miércoles' => 3,
            'Jueves' => 4,
            'Viernes' => 5,
            'Sábado' => 6,
            'Domingo' => 7
        );

        // Extrae días válidos de atención
        $dias_validos = array_map(function($d) {
            return $d['dia'];
        }, $horario_atencion_locacion['dias']);

        foreach ($citas_paciente as $cita) {
            $dia_cita = is_numeric($cita['day']) ? $cita['day'] : $dias_semana[$cita['day']];
            $hora_inicio = $horario_atencion_locacion['hora_inicio'];
            $hora_termino = $horario_atencion_locacion['hora_termino'];

            // Verifica si el día está en el horario permitido
            if (in_array($dia_cita, $dias_validos)) {
                if ($cita['start'] >= $hora_inicio && $cita['end'] <= $hora_termino) {
                    $cita_locacion[] = $cita;
                }
            }
        }

        return $cita_locacion;
    }

    // Crear función helper
    public static function cacheToArray($data)
    {
        $aqui = 1;
        if (is_null($data)) {
            return null;
        }
        
        // Si es array, recorrer cada elemento recursivamente
        if (is_array($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                $result[$key] = SELF::cacheToArray($value); // Llamada recursiva
            }
            return $result;
        }
        
        // Si es objeto
        if (is_object($data)) {
            // Si es stdClass, convertir a array
            if ($data instanceof \stdClass) {
                $array = (array) $data;
                // Recorrer recursivamente cada propiedad
                $result = [];
                foreach ($array as $key => $value) {
                    $result[$key] = SELF::cacheToArray($value); // Llamada recursiva
                }
                return $result;
            }
            
            // Si es modelo o resultset de Phalcon
            if (method_exists($data, 'toArray')) {
                $array = $data->toArray();
                // Recorrer recursivamente por si hay objetos anidados
                return SELF::cacheToArray($array);
            }
            
            // Para otros tipos de objeto, convertir a array y procesar recursivamente
            $array = (array) $data;
            $result = [];
            foreach ($array as $key => $value) {
                $result[$key] = SELF::cacheToArray($value); // Llamada recursiva
            }
            return $result;
        }
        
        // Si es un tipo primitivo (string, int, float, bool)
        return $data;
    }

    /*  FUNCION PARA BUSCAR EL ARCHIVO CACHE
        
        @PARAM  $cacheKey   (String)    Nombre del archivo cache
        RETURN CLASS/NULL
    */
    public static function searchCache($cacheKey){
        try{

            $di = Di::getDefault();
            $cache  = $di->get('cache');
            return $cache->get($cacheKey);

        }catch(\Exception $e){
            return null;
        }
    }

    /*  FUNCION PARA GUARDAR LA INFORMACION EN EL ARCHIVO CACHE
        
        @PARAM  $cacheKey   (String)    Nombre del archivo cache
        @PARAM  $data       (Array)     Array con la informacion a guardar
        RETURN TRUE
    */
    public static function saveCache($cacheKey,$data){

        try{

            $di     = Di::getDefault();
            $cache  = $di->get('cache');
            $cache->set($cacheKey, $data);
            return true;

        }catch(\Exception $e){
            return null;
        }
    }

    /*  FUNCION PARA BORRAR LA INFORMACION DE UN ARCHIVO CACHE
        
        @PARAM  $cacheKey   (String)    Nombre del archivo cache
        RETURN TRUE
    */
    public static function deleteCache($cacheKey){
        try{
            $di     = Di::getDefault();
            $cache  = $di->get('cache');
            $cache->delete($cacheKey);
            return true;
        }catch(\Exception $e){
            return null;
        }
    }

    /*  FUNCION PARA BORRAR LA INFORMACION DE UN ARCHIVO CACHE QUE
        CONCUERDE CON LA VARIABLE PATTERN
        
        @PARAM  $cacheKey   (String)    Nombre del archivo cache
        RETURN TRUE
    */
    public static function deleteCacheByPattern(string $pattern): bool
    {
        try {
            $storageDir = BASE_PATH . '/storage/cache/';

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($storageDir, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $filename = $file->getFilename();

                    if (strpos($filename, $pattern) !== false) {
                        @unlink($file->getPathname());
                    }
                }
            }

            return true;
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    /*
        FUNCION PARA RETORNAR EL PATH DE LA CARPETA, ESTO DEPENDIENDO DEL TIPO DE ARCHIVO

        @param  $type_file  ($string)   tipo de archivo
        return  (String);
    */ 
    public static function get_path_file($type_file){
        $path_principal = "../storage/files";
        $path_save      = '';

        switch($type_file){
            case 'res_lab':
                $path_save = '/resultados_de_laboratorio/';
                break;
            case 'inf_meds':
                $path_save = '/informes_medicos/';
                break;
            case 'ima_diag':
                $path_save = '/imagenes_diagnosticos/';
                break;
            case 'consen':
                $path_save = '/consentimientos_informados/';
                break;
            case 'rec_firs':
                $path_save = '/recetas_medicas_firmadas/';
                break;
            case 'not_med':
                $path_save = '/notas_medicas/';
                break;
            case 'jus':
                $path_save = '/justificantes/';
                break;
            case 'rep_seg':
                $path_save = '/reportes_de_seguimiento/';
                break;
            case 'perfil':
                $path_save = '/perfil/';
                break;
            case 'recetas':
                $path_save = '/tmp/';
                break;
            case 'reportes':
                $path_save = '/tmp/';
                break;
            case 'tickets':
                $path_save = '/tmp/';
                break;
            case 'otros':
                $path_save = '/otros_documentos/';
                break;
            default:
                $path_save = '/otros_documentos/';
                break;
        }
            

        return $path_principal.$path_save;
    }

    /**
     * FUNCION QUE RETORNA LA URL A DESCARGAR DEL ARCHIVO
     * 
     * @param   $clave_tipo_archivo String
     * @param   $nombre_archivo String
     * 
     * @return   String
     */
    public static function get_url_download($clave_tipo_archivo,$nombre_archivo){
        return '/Menu/download?tipo_archivo='.$clave_tipo_archivo.'&nombre_archivo='.$nombre_archivo;
    }

    /*  
        FUNCION QUE CONVIERTE, POR EJEMPLO 25M EN BYTES, SIRVE PARA VALIDAR EN IF

        @param  $val (String)   $val    
        return (Numeric)
    */
    public static function returnBytes($val) {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        $num = (int)$val;

        switch($last) {
            case 'g': $num *= 1024;
            case 'm': $num *= 1024;
            case 'k': $num *= 1024;
        }

        return $num;
    }

    /** 
     * FUNCION PARA SANITIZAR NOMBRE DE ARCHIVOS
     * 
     * @param   $nombre_original    String
     * @return  String
    */
    public static function clear_filename($nombre_original){
        return preg_replace('/[^a-zA-Z0-9._\- ]/', '', $nombre_original);
    }

        public static function formatearTelefono($telefono) {
        $telefonoLimpio = preg_replace('/\D/', '', $telefono);
        
        if (strlen($telefonoLimpio) !== 10) {
            return '';
        }
        
        return sprintf("(%s) %s-%s", 
            substr($telefonoLimpio, 0, 3),
            substr($telefonoLimpio, 3, 3),
            substr($telefonoLimpio, 6, 4)
        );
    }

    public static function formatearFecha($fecha,$formato_retorno = null) {
        if(empty($fecha)) return '';
        $formato_retorno    = $formato_retorno != null ? $formato_retorno : "d/m/Y";
        return date($formato_retorno, strtotime($fecha));
    }

    public static function formatoMonetario($numero, int $decimales = 2): string{
        // Convertimos a número por seguridad
        $numero = (float)$numero;

        // Formato: miles con coma, decimales con punto
        return number_format($numero, $decimales, '.', ',');
    }

    public static function create_pdf_prescription($id_receta){
        try{

            $di = Di::getDefault(); // Se usa en lugar de $this->getDI()

            // OBTENER CONFIGURACIONES
            $rutas      = $di->get('rutas');
            $config     = $di->get('config');
            $url_api    = $config['BASEAPI'];

            //  SE BUSCA LA INFORMACION DE LA RECETA A IMPRIMIR
            $result = SELF::RequestApi('GET',$url_api.$rutas['ctpacientes']['show_receta'],array(
                'id_receta' => $id_receta
            ));

            if ($result == null || !is_array($result) || count($result) == 0){
                throw new Exception("Error al obtener la información de la receta");
            }

            $receta = $result[0];

            // HTML de ejemplo (puede venir de una vista Volt)
            $html = '
    <!DOCTYPE html>
    <html lang="es">
    <head>
    <meta charset="UTF-8">
    <title>Receta Médica</title>
    <style>
        body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        background: #fff;
        color: #000;
        font-size: 12px;
        line-height: 1.3;
        }

        .receta {
        width: 100%;
        margin: 0;
        padding: 5mm 10mm 15mm 10mm;
        }

        .contenido-principal {
        padding: 0 3mm;
        }

        .logo {
        font-size: 20px;
        font-weight: bold;
        color: #6a1b9a;
        margin-bottom: 8px;
        }

        .doctor-info {
        text-align: right;
        }

        .doctor-info h2 {
        margin: 0 0 3px 0;
        font-size: 16px;
        color: #6a1b9a;
        }

        .doctor-info p {
        margin: 1px 0;
        font-size: 11px;
        }

        .section {
        margin: 8px 0;
        }

        .datos-table {
        width: 100%;
        border-collapse: collapse;
        margin: 6px 0;
        }

        .datos-table td {
        padding: 3px 6px;
        vertical-align: bottom;
        }

        .campo-linea {
        display: inline-block;
        border-bottom: 1px solid;
        width: 120px;
        vertical-align: bottom;
        padding-right: 100px;
        white-space: nowrap;
        overflow: hidden;
        min-height: 14px;
        }

        .indicaciones {
        margin: 8px 0;
        }

        .indicaciones h3 {
        margin: 8px 0 6px 0;
        font-size: 13px;
        }

        .tratamiento {
        height: 66mm;
        padding: 5px 5px 5px 15px;
        overflow: auto;
        margin-left: 8px;
        }

        .tratamiento p {
        margin: 4px 0;
        font-size: 12px;
        }

        .footer-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
        }

        .footer-table td {
        padding: 4px 6px;
        vertical-align: top;
        width: 33%;
        }

        .footer-center {
        text-align: center;
        }

        .footer-right {
        text-align: right;
        font-weight: bold;
        }

        .contact-text p {
        margin: 0;
        font-size: 11px;
        line-height: 1.2;
        }

        .espacio-superior {
        height: 6mm;
        width: 100%;
        }

        .icono {
        font-weight: bold;
        margin-right: 3px;
        }

        .tratamiento p {
            margin: 0;
            padding: 0;
        }

        .tratamiento p + p {
            margin-top: 0;
        }
    </style>
    </head>
    <body>
    <div class="receta">
        <!-- Espacio reducido en la parte superior -->
        <div class="espacio-superior"></div>
        
        <div class="contenido-principal">
        <!-- Header -->
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
            <td width="50%" align="left" valign="top">
                <div class="logo">'.$receta['nombre_locacion'].'</div>
            </td>
            <td width="50%" align="right" valign="top">
                <div class="doctor-info">
                <h2>Dr. '.$receta['nombre_profesional'].'</h2>
                <p>Ginecología y Obstetricia</p>
                <p>Céd. Prof. '.$receta['cedula_profesional'].' | Esp. 1234567</p>
                <p>Reg. S.S.A. 111/22</p>
                </div>
            </td>
            </tr>
        </table>

        <!-- Línea divisoria superior -->
        <div style="height: 3px; background-color: #6a1b9a; width: 100%; margin: 12px 0;"></div>

        <!-- Datos del Paciente -->
        <div class="section">
            <table class="datos-table">
            <tr>
                <td width="75%">
                <strong>Nombre:</strong> 
                <span class="campo-linea">'.$receta['nombre_completo'].'</span>
                </td>
                <td width="25%">
                <strong>Fecha:</strong> 
                <span class="campo-linea">'.$receta['fecha_ultima_edicion'].'</span>
                </td>
            </tr>
            </table>

            <table class="datos-table">
            <tr>
                <td width="30%">
                <strong>Temp.:</strong> 
                <span class="campo-linea">'.$receta['temperatura'].'</span> <strong>(°C)</strong>
                </td>
                <td width="35%">
                <strong>Fre. cardiaca:</strong> 
                <span class="campo-linea">'.$receta['frecuencia_cardiaca'].'</span> <strong>(lpm)</strong>
                </td>
                <td width="35%">
                <strong>Pre. Arterial:</strong> 
                <span class="campo-linea">'.$receta['presion_arterial'].'</span> <strong>(mmHg)</strong>
                </td>
            </tr>
            </table>

            <table class="datos-table">
            <tr>
                <td width="25%">
                <strong>Frec. Resp.:</strong> 
                <span class="campo-linea">'.$receta['frecuencia_respiratoria'].'</span> <strong>(rpm)</strong>
                </td>
                <td width="25%">
                <strong>Satur. O₂:</strong> 
                <span class="campo-linea">'.$receta['saturacion_oxigeno'].'</span> <strong>(%)</strong>
                </td>
                <td width="25%">
                <strong>Peso:</strong> 
                <span class="campo-linea">'.$receta['peso'].'</span> <strong>(kg)</strong>
                </td>
                <td width="25%">
                <strong>Talla:</strong> 
                <span class="campo-linea">'.$receta['altura'].'</span> <strong>(cm)</strong>
                </td>
            </tr>
            </table>
        </div>

        <!-- Indicaciones -->
        <div class="section indicaciones">
            <h3>Fx:</h3>
            <div class="tratamiento">
            '.$receta['tratamiento'].'
            <!-- Aquí se insertará el HTML con la receta redactada por el doctor -->
            </div>
        </div>

        <!-- Línea divisoria inferior -->
        <div style="height: 3px; background-color: #6a1b9a; width: 100%; margin: 15px 0 12px 0;"></div>
        </div>

        <!-- Footer -->
        <table class="footer-table">
        <tr>
            <td class="footer-left">
            <div class="contact-text">
                <p><span class="icono">■</span> '.$receta['direccion'].'</p>
                
            </div>
            </td>
            <td class="footer-center">
            <div class="contact-text">
                <p><span class="icono">►</span> '.SELF::formatearTelefono($receta['telefono']).'</p>
                <p style="padding-left: 12px;">'.SELF::formatearTelefono($receta['celular']).'</p>
                <p><span class="icono">@</span> '.$receta['correo_electronico'].'</p>
            </div>
            </td>
            <td class="footer-right">
            <p>Dr. '.$receta['nombre_profesional'].'</p>
            </td>
        </tr>
        </table>
    </div>
    </body>
    </html>
                ';

    $mpdf = new \Mpdf\Mpdf([
    'format' => 'A4',
    'orientation' => 'P',
    'margin_top' => 0,
    'margin_bottom' => 0,
    'margin_left' => 0,
    'margin_right' => 0,
    'tempDir' => MPDF_TEMP_DIR
    ]);



            // HTML generado a partir de la base de datos
            $fecha  = str_replace('/','_',$receta['fecha_ultima_edicion']);

            $mpdf->WriteHTML($html);

            // Guardar temporalmente en storage/tmp
            //$nombre_archivo = 'receta_' .$id_agenda_cita. '_'.$fecha.'.pdf';
            $nombre_archivo = 'receta_'.$fecha.'.pdf';
            //$archivoTemp = MPDF_TEMP_DIR . '/receta_' .$id_agenda_cita. '_'.$fecha.'.pdf';
            $archivoTemp = MPDF_TEMP_DIR .'/'.$nombre_archivo;
            $mpdf->Output($archivoTemp, \Mpdf\Output\Destination::FILE);

            return array(
                'url_receta'    => SELF::get_url_download('recetas',$nombre_archivo),
                'msg_error'     => ''
            );

        } catch(\Exception $e){
            return array(
                'url_receta'    => '',
                'msg_error'     => $e->getMessage()
            );
        }
    }

    //  FUNCION PARA CREAR PDF DEL TICKET POR MEDIO DE UN FOLIO
    public static function create_pdf_ticket($folio){
        try{

            //  IMAGENES BASE64
            $base_64_icono_paciente = "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";
            $base_64_icono_check    = "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";

            $mpdf = new \Mpdf\Mpdf([
                'format'        => [105, 180], // [ancho, alto] en mm
                'margin_left'   => 6,
                'margin_right'  => 6,
                'margin_top'    => 6,
                'margin_bottom' => 6,
                'default_font'  => 'dejavusans',
                'tempDir' => MPDF_TEMP_DIR
            ]);

            // Datos del ticket (reemplaza con tus variables dinámicas)
            //$folio        = 'F-10294';
            $fecha        = '09/03/2026 10:15 AM';
            $paciente     = 'Acosta Carrillo Iker';
            $servicios    = 'PSI - Consulta x2';
            $subtotal     = '$600.00';
            $descuento    = '-$50.00';
            $total        = '$550.00';
            $monto_rec    = '$700.00';
            $excedente    = '$150.00';
            $resolucion   = 'Abonado a Saldo a Favor';

            $html = <<<HTML
            <style>
                * {
                    font-family: DejaVu Sans, sans-serif;
                    box-sizing: border-box;
                }

                body {
                    font-size: 9pt;
                    color: #1a1a2e;
                    margin: 0;
                    padding: 0;
                }

                /* ── Encabezado ── */
                .header {
                    text-align: center;
                    margin-bottom: 10px;
                }

                .header .icon {
                    font-size: 18pt;
                    color: #5c6bc0;
                }

                .header h1 {
                    font-size: 13pt;
                    font-weight: bold;
                    margin: 4px 0 2px;
                    color: #1a1a2e;
                }

                .header p {
                    font-size: 8pt;
                    color: #666;
                    margin: 0;
                }

                /* ── Caja folio/fecha ── */
                .folio-box {
                    background-color: #f4f5fb;
                    border-radius: 6px;
                    padding: 7px 10px;
                    margin-bottom: 10px;
                }

                .folio-box table {
                    width: 100%;
                }

                .folio-box .label {
                    font-size: 7pt;
                    color: #888;
                    text-transform: uppercase;
                    letter-spacing: 0.4px;
                }

                .folio-box .value {
                    font-size: 9pt;
                    font-weight: bold;
                    color: #1a1a2e;
                }

                /* ── Separador punteado ── */
                .separator {
                    border: none;
                    height: 1px;
                    background-image: linear-gradient(
                        to right,
                        #cfd3f7 50%,
                        rgba(255,255,255,0) 0%
                    );
                    background-size: 6px 1px;
                    background-repeat: repeat-x;
                    margin: 10px 0;
                }

                /* ── Paciente ── */
                .section-label {
                    font-size: 7pt;
                    color: #888;
                    text-transform: uppercase;
                    letter-spacing: 0.4px;
                    margin-bottom: 2px;
                }

                .paciente-name {
                    font-size: 10pt;
                    font-weight: bold;
                    color: #1a1a2e;
                }

                /* ── Servicios ── */
                .dot {
                    color: #5c6bc0;
                    font-size: 10pt;
                    margin-right: 4px;
                }

                /* ── Métodos de pago ── */
                .metodo-row td {
                    font-size: 9pt;
                    padding: 2px 0;
                    color: #333;
                }

                /* ── Totales ── */
                .totals-table {
                    width: 100%;
                    margin-top: 4px;
                }

                .totals-table td {
                    font-size: 9pt;
                    padding: 2px 0;
                    color: #333;
                }

                .totals-table .descuento {
                    color: #e53935;
                }

                .totals-table .total-row td {
                    font-size: 11pt;
                    font-weight: bold;
                    color: #1a1a2e;
                    padding-top: 4px;
                }

                .totals-table .total-row .amount {
                    color: #5c6bc0;
                }

                /* ── Excedente ── */
                .excedente-box {
                    border: 1.5px solid #c7d2fe;
                    border-radius: 6px;
                    padding: 7px 10px;
                    margin-top: 8px;
                    background-color: #ffffff;
                }

                .excedente-box table {
                    width: 100%;
                }

                .excedente-header {
                    font-size: 9pt;
                    color: #4f46e5;
                    font-weight: bold;
                }

                .excedente-amount {
                    font-size: 10pt;
                    font-weight: bold;
                    color: #4f46e5;
                    text-align: right;
                }

                .resolucion-box {
                    border: 1.2px solid #c7d2fe;
                    border-radius: 4px;
                    padding: 5px 8px;
                    margin-top: 5px;
                    background-color: #eef2ff;
                }

                .resolucion-label {
                    font-size: 7pt;
                    color: #6366f1;
                    text-transform: uppercase;
                    letter-spacing: 0.4px;
                    margin-bottom: 2px;
                }

                .resolucion-value {
                    font-size: 8.5pt;
                    font-weight: bold;
                    color: #1a1a2e;
                }

                .icon-box {
                    background-color: #eef2ff;
                    border-radius: 8px;
                    width: 28px;
                    height: 28px;
                    text-align: center;
                    vertical-align: middle;
                    border: 1px solid #e1e5ff;
                }

                .icon-box img {
                    margin-top: 6px;
                }

                .paciente-container td {
                    vertical-align: middle;
                }

                .section-label {
                    font-size: 7pt;
                    color: #9aa0b5;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    margin-bottom: 2px;
                }
            </style>

            <!-- ENCABEZADO -->
            <div class="header">
                <div class="icon">&#9989;</div>
                <h1>¡Ya casi terminas!</h1>
                <p>Verifica los datos antes de procesar el cobro.</p>
            </div>

            <!-- FOLIO Y FECHA -->
            <div class="folio-box">
                <table>
                    <tr>
                        <td>
                            <div class="label">Folio</div>
                            <div class="value">{$folio}</div>
                        </td>
                        <td align="right">
                            <div class="label">Fecha y Hora</div>
                            <div class="value">{$fecha}</div>
                        </td>
                    </tr>
                </table>
            </div>

            <hr class="separator">

            <!-- PACIENTE -->
            <table class="paciente-container" style="width:100%; margin-bottom:4px;">
                <tr>
                    <td style="width:30px;">
                        <div class="icon-box">
                            <img src="data:image/png;base64,{$base_64_icono_paciente}" 
                                width="14" 
                                height="14"/>
                        </div>
                    </td>
                    <td style="padding-left:8px;">
                        <div class="section-label">Paciente</div>
                        <div class="paciente-name">{$paciente}</div>
                    </td>
                </tr>
            </table>

            <hr class="separator">

            <!-- SERVICIOS -->
            <div class="section-label">Servicios Liquidados</div>
            <table style="width:100%; margin-top:3px;">
                <tr>
                    <td>
                        <span class="dot">&#9679;</span> {$servicios}
                    </td>
                    <td align="right" style="font-weight:bold;">{$subtotal}</td>
                </tr>
            </table>

            <hr class="separator">

            <!-- MÉTODOS DE PAGO -->
            <div class="section-label">Método de Pago</div>
            <table style="width:100%; margin-top:4px;">
                {$metodos_pago}
            </table>

            <hr class="separator">

            <!-- TOTALES -->
            <table class="totals-table">
                <tr>
                    <td>Subtotal ({$num_citas} citas)</td>
                    <td align="right">{$subtotal}</td>
                </tr>
                <tr>
                    <td>Saldo a favor aplicado</td>
                    <td align="right" class="descuento">{$saldo_favor}</td>
                </tr>
                <tr class="total-row">
                    <td>Total a Pagar</td>
                    <td align="right" class="amount">{$total}</td>
                </tr>
                <tr>
                    <td>Monto Recibido</td>
                    <td align="right">{$monto_rec}</td>
                </tr>
            </table>

            <!-- EXCEDENTE -->
            <div class="excedente-box">
                <table>
                    <tr>
                        <td class="excedente-header">&#9673; Excedente Detectado</td>
                        <td class="excedente-amount">{$excedente}</td>
                    </tr>
                </table>
                <div class="resolucion-box">
                    <div class="resolucion-label">Resolución Elegida</div>
                    <div class="resolucion-value">{$resolucion}</div>
                </div>
            </div>

            HTML;

             // HTML generado a partir de la base de datos
            $fecha  = str_replace('/','_','2026/03/28');
            $folio  = str_replace('-','_',$folio);

            $mpdf->WriteHTML($html);

            // Guardar temporalmente en storage/tmp
            //$nombre_archivo = 'receta_' .$id_agenda_cita. '_'.$fecha.'.pdf';
            $nombre_archivo = 'ticket_'.$folio.'_'.$fecha.'.pdf';
            //$archivoTemp = MPDF_TEMP_DIR . '/receta_' .$id_agenda_cita. '_'.$fecha.'.pdf';
            $archivoTemp = MPDF_TEMP_DIR .'/'.$nombre_archivo;
            $mpdf->Output($archivoTemp, \Mpdf\Output\Destination::FILE);

            return array(
                'url_ticket'    => SELF::get_url_download('tickets',$nombre_archivo),
                'msg_error'     => '',
                'success'       => true
            );

        } catch(\Exception $e){
            return array(
                'url_receta'    => '',
                'msg_error'     => $e->getMessage()
            );
        }
    }

}