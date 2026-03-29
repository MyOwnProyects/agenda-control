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
        
        if (!is_numeric($numero) || empty($numero)) return $numero;
        
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

            $di = Di::getDefault(); // Se usa en lugar de $this->getDI()

            // OBTENER CONFIGURACIONES
            $rutas      = $di->get('rutas');
            $config     = $di->get('config');
            $url_api    = $config['BASEAPI'];

            //  SE BUSCA LA INFORMACION DE LA RECETA A IMPRIMIR
            $result = SELF::RequestApi('GET',$url_api.$rutas['caja']['tickets_show'],array(
                'folio' => $folio
            ));

            if ($result == null || !is_array($result) || count($result) == 0){
                throw new Exception("Error al obtener la información de la receta");
            }

            $result     = $result[0];
            $detalle    = json_decode($result['detalle'],true);

            //  IMAGENES BASE64
            $base_64_icono_paciente     = "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";
            $base_64_icono_check        = "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";
            $base_64_icono_excedente    = "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";
            $logo_cliente = 'data:image/png;base64,' . base64_encode(
                file_get_contents(IMAGES_DIR . '/imagen_cliente.png')
            );

            $mpdf = new \Mpdf\Mpdf([
                'format'        => [85, 180],
                'margin_left'   => 6,
                'margin_right'  => 6,
                'margin_top'    => 6,
                'margin_bottom' => 6,
                'default_font'  => 'dejavusans',
                'tempDir'       => MPDF_TEMP_DIR
            ]);
 

            // ─────────────────────────────────────────────
            // VARIABLES DINÁMICAS  (reemplaza con las tuyas)
            // ─────────────────────────────────────────────
            $fecha       = $result['label_fecha'];
            $paciente    = $result['nombre_completo'];
            $num_citas   = $detalle['num_citas'];
            $subtotal    = '$'.SELF::formatoMonetario($detalle['subtotal_calculado']);
            $saldo_favor = '$'.SELF::formatoMonetario($detalle['saldo_favor']);
            $total       = '$'.SELF::formatoMonetario($detalle['total_pagar']);
            $monto_rec   = '$'.SELF::formatoMonetario($detalle['monto_recibido']);
            $excedente   = '$'.SELF::formatoMonetario($detalle['excedente']);
            $resolucion  = '';
            
            // Servicios: array de ['nombre' => '...', 'precio' => '...']
            $servicios = $detalle['servicios'];
            
            // Métodos de pago: array de ['metodo' => '...', 'monto' => '...']
            $metodos_pago = array();

            if (!empty($detalle['pago_efectivo']) && $detalle['pago_efectivo'] > 0){
                $metodos_pago[] = array(
                    'metodo'    => 'EFECTIVO',
                    'monto'     => SELF::formatoMonetario($detalle['pago_efectivo']),
                );
            }

            if (!empty($detalle['pago_transferencia']) && $detalle['pago_transferencia'] > 0){
                $metodos_pago[] = array(
                    'metodo'    => 'TRANSFERENCIA',
                    'monto'     => SELF::formatoMonetario($detalle['pago_transferencia']),
                );
            }
            
            // ─────────────────────────────────────────────
            // Íconos en base64 inline (SVG pequeños convertidos)
            // Puedes reemplazar $icon_check e $icon_paciente con tus propios base64
            // ─────────────────────────────────────────────
            
            // Ícono check (checkbox con paloma) — SVG inline como data URI
            $icon_check = 'data:image/png;base64,'.$base_64_icono_check;
            
            // Ícono paciente — silueta simple
            $icon_paciente = 'data:image/png;base64,'.$base_64_icono_paciente;
            
            // Ícono excedente — caja/clipboard
            $icon_excedente = 'data:image/png;base64,' . $base_64_icono_excedente;
            
            // ─────────────────────────────────────────────
            // Construir filas dinámicas
            // ─────────────────────────────────────────────
            $servicios_html = '';
            foreach ($servicios as $s) {
                $servicios_html .= '
                    <tr>
                        <td style="padding:3px 0; vertical-align:middle;">
                            <span style="color:#5c6bc0; font-size:9pt; margin-right:5px;">&#9679;</span>
                            <span style="font-size:9pt; color:#1a1a2e;">' . htmlspecialchars($s['servicio']) . '</span>
                            <span style="font-size:7pt; color:#9aa0b5;"> X' . $s['num_servicios'] . '</span>
                        </td>
                        <td align="right" style="padding:3px 0; font-size:9pt; color:#1a1a2e;">$' . SELF::formatoMonetario($s['total']) . '</td>
                    </tr>';
            }
            
            $pagos_html = '';
            foreach ($metodos_pago as $p) {
                $pagos_html .= '
                <tr>
                    <td style="padding:3px 0; font-size:9pt; color:#333;">' . htmlspecialchars($p['metodo']) . '</td>
                    <td align="right" style="padding:3px 0; font-size:9pt; color:#1a1a2e;">$' . htmlspecialchars($p['monto']) . '</td>
                </tr>';
            }

            $excedente_html = "";

            if (is_numeric($detalle['excedente']) && $detalle['excedente'] > 0){
                if ($detalle['accion_excedente'] == 'saldo_favor'){
                    $resolucion = 'Saldo a favor';
                } else {
                    $resolucion = 'Devolver cambio';
                }

                $excedente_html = '
                    <div class="excedente-box">
                        <table>
                            <tr>
                                <td class="excedente-header">
                                    <img src="{'.$icon_excedente.'}" width="26" height="14" style="vertical-align:middle; margin-right:5px;" />
                                    <span style="vertical-align:middle;">Excedente</span>
                                </td>
                                <td class="excedente-amount">'.$excedente.'</td>
                            </tr>
                        </table>
                        <div class="resolucion-box">
                            <div class="resolucion-label">Resolución elegida</div>
                            <div class="resolucion-value">'.$resolucion.'</div>
                        </div>
                    </div>
                ';
            }
            
            // ─────────────────────────────────────────────
            // HTML del ticket
            // ─────────────────────────────────────────────
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
                    padding: 12px;
                    background: #ffffff;
                }
            
                /* ── Encabezado ── */
                .header {
                    text-align: center;
                    margin-bottom: 12px;
                    padding-top: 4px;
                }
                .header-icon-wrap {
                    display: inline-block;
                    width: 42px;
                    height: 42px;
                    border-radius: 50%;
                    text-align: center;
                    vertical-align: middle;
                    margin-bottom: 6px;
                }
                .header h1 {
                    font-size: 12pt;
                    font-weight: bold;
                    margin: 0 0 2px;
                    color: #1a1a2e;
                }
            
                /* ── Folio/fecha ── */
                .folio-box {
                    background-color: #f4f5fb;
                    border-radius: 6px;
                    padding: 7px 10px;
                    margin-bottom: 12px;
                }
                .folio-box table { width: 100%; }
                .folio-label {
                    font-size: 7pt;
                    color: #9aa0b5;
                    text-transform: uppercase;
                    letter-spacing: 0.4px;
                    margin-bottom: 2px;
                }
                .folio-value {
                    font-size: 8pt;
                    font-weight: bold;
                    color: #1a1a2e;
                }
            
                /* ── Separador ── */
                .separator {
                    border: none;
                    border-top: 1px dashed #c7cff5;
                    margin: 10px 0;
                }
            
                /* ── Sección labels ── */
                .section-label {
                    font-size: 7pt;
                    color: #9aa0b5;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    margin-bottom: 4px;
                }
            
                /* ── Paciente ── */
                .paciente-wrap { width: 100%; margin-bottom: 2px; }
                .icon-box {
                    
                    border-radius: 6px;
                    width: 28px;
                    height: 28px;
                    text-align: center;
                    vertical-align: middle;
                    
                }
                .paciente-name {
                    font-size: 8pt;
                    font-weight: bold;
                    color: #1a1a2e;
                }
            
                /* ── Totales ── */
                .totals-table { width: 100%; margin-top: 2px; }
                .totals-table td { font-size: 9pt; padding: 3px 0; color: #333; }
                .saldo-favor-amt { color: #e53935; }
                .total-row td {
                    font-size: 11pt;
                    font-weight: bold;
                    color: #1a1a2e;
                    padding-top: 5px;
                    border-top: 1px solid #eee;
                }
                .total-row .amount { color: #5c6bc0; }
            
                /* ── Excedente ── */
                .excedente-box {
                    border: 1.5px solid #c7d2fe;
                    border-radius: 6px;
                    padding: 8px 10px;
                    margin-top: 10px;
                    background-color: #f4f5fb; /* asegúrate que sea blanco */
                }
                .excedente-box table { width: 100%; }
                .excedente-header {
                    font-size: 9pt;
                    color: #4f46e5;
                    font-weight: bold;
                    vertical-align: middle;
                }
                .excedente-amount {
                    font-size: 10pt;
                    font-weight: bold;
                    color: #4f46e5;
                    text-align: right;
                    vertical-align: middle;
                }
                .resolucion-box {
                    border: 1px solid #c7d2fe;
                    border-radius: 4px;
                    padding: 5px 8px;
                    margin-top: 6px;
                    background-color: #ffffff; /* cambia de #eef2ff a blanco */
                }
                .resolucion-label {
                    font-size: 7pt;
                    color: #6366f1;
                    text-transform: uppercase;
                    letter-spacing: 0.4px;
                    margin-bottom: 2px;
                }
                .resolucion-value {
                    font-size: 9pt;
                    font-weight: bold;
                    color: #1a1a2e;
                }
            </style>
            
            <!-- ═══ ENCABEZADO ═══ -->
            <div class="header">

                <!-- Logo cliente arriba a la izquierda -->
                <table style="width:100%; margin-bottom:6px;">
                    <tr>
                        <td style="width:60px; vertical-align:middle;">
                            <img src="{$logo_cliente}" width="60" height="50" style="object-fit:contain;" />
                        </td>
                        <td align="center" style="vertical-align:middle;">
                            <div class="header-icon-wrap">
                                <img src="{$icon_check}" width="22" height="22" style="margin-top:10px;" />
                            </div>
                        </td>
                        <td style="width:70px;"></td>
                    </tr>
                </table>

                <h1>&#161;Pago realizado!</h1>
            </div>
            
            <!-- ═══ FOLIO / FECHA ═══ -->
            <div class="folio-box">
                <table>
                    <tr>
                        <td>
                            <div class="folio-label">Folio</div>
                            <div class="folio-value">{$folio}</div>
                        </td>
                        <td align="right">
                            <div class="folio-label">Fecha y Hora</div>
                            <div class="folio-value">{$fecha}</div>
                        </td>
                    </tr>
                </table>
            </div>
            
            <hr class="separator">
            
            <!-- ═══ PACIENTE ═══ -->
            <table class="paciente-wrap">
                <tr>
                    <td style="width:30px;">
                        <div class="icon-box">
                            <table style="width:100%; height:28px;">
                                <tr>
                                    <td align="center" style="vertical-align:middle;">
                                        <img src="{$icon_paciente}" width="14" height="14" />
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </td>
                    <td style="padding-left:8px; vertical-align:middle;">
                        <table style="width:100%; border-collapse:collapse;">
                            <tr>
                                <td style="font-size:7pt; color:#9aa0b5; text-transform:uppercase; letter-spacing:0.5px; padding-bottom:4px;">Paciente</td>
                            </tr>
                            <tr>
                                <td style="font-size:8pt; font-weight:bold; color:#1a1a2e;">{$paciente}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            
            <hr class="separator">
            
            <!-- ═══ SERVICIOS ═══ -->
            <div class="section-label">Servicios</div>
            <table style="width:100%; margin-top:2px;">
                {$servicios_html}
            </table>
            
            <hr class="separator">
            
            <!-- ═══ MÉTODO DE PAGO ═══ -->
            <div class="section-label">Método de pago</div>
            <table style="width:100%; margin-top:4px;">
                {$pagos_html}
            </table>
            
            <hr class="separator">
            
            <!-- ═══ TOTALES ═══ -->
            <table class="totals-table">
                <tr>
                    <td>Subtotal ({$num_citas} citas)</td>
                    <td align="right">{$subtotal}</td>
                </tr>
                <tr>
                    <td>Saldo a favor aplicado</td>
                    <td align="right" class="saldo-favor-amt">{$saldo_favor}</td>
                </tr>
                <tr class="total-row">
                    <td><strong>Total a Pagar</strong></td>
                    <td align="right" class="amount"><strong>{$total}</strong></td>
                </tr>
                <tr>
                    <td style="padding-top:4px;">Monto Recibido</td>
                    <td align="right" style="padding-top:4px;">{$monto_rec}</td>
                </tr>
            </table>
            
            <!-- ═══ EXCEDENTE ═══ -->
            {$excedente_html}
            
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