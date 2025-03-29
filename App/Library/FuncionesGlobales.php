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

            // Obtén el contenedor DI global
            $di = Di::getDefault();

            // Verifica si el servicio de sesión está disponible
            if (!$di instanceof DiInterface || !$di->has('session')) {
                return $flag_return;
            }

            // Obtén el servicio de sesión
            $session = $di->get('session');

            $params['usuario_solicitud']    = $session->get('clave');

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
                    $headers[] = 'Content-Type: application/json';
                }
            }

            if (!empty($headers)){
                curl_setopt($ch,CURLOPT_HTTPHEADER, $headers);
            }

            //  DEVUELVE LA SOLICITUD COMO STRING EN LUGAR DE PINTARLA
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // Ejecutar la solicitud
            $response = curl_exec($ch);

            // STATUS CODE DE RESPUESTA
            $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            // Manejar errores
            if (curl_errno($ch) || $httpStatus >= 400) {
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
            // OBTENER EL CONTENEDOR DE DEPENDENCIAS
            // OBTENER EL CONTENEDOR DE DEPENDENCIAS
            $di = Di::getDefault(); // Se usa en lugar de $this->getDI()

            // OBTENER CONFIGURACIONES
            $rutas = $di->get('rutas');
            $config = $di->get('config');
            $url_api = $config['BASEAPI'];


            //  CONSTRUCCION DEL ARRAY
            // $method,$route,$params = null,$headers = null

            $arr_params = array(
                'controlador'   => $controlador,
                'accion'        => $accion,
                'mensaje'       => $mensaje,
                'data'          => $data,
                'ip_cliente'    => $ipAddress
            );

            $captura    = self::RequestApi('POST',$url_api.$rutas['tbbitacora_movimientos']['create'],$arr_params);
            
        }catch (\Exception $e){
            $aqui   = 1;
        }

        return true;
        
    }

    /**
     * FUNCION PARA OBTENER LA HORA MINIMA Y MAXIMA A PINTAR
     * 
     * @param   Object  $horario_atencion
     * 
     * @return array
     */
    public static function allStructureSchedule($horario_atencion){
        //  SE RECORRE EL ARRAY PARA OBTENER LA HORA DE INICIO Y TERMINO
        $min_hora_inicio    = '23:59'; // Inicializa con la mayor hora posible
        $max_hora_termino   = '00:00'; // Inicializa con la menor hora posible
        $dias_disponibled   = array();

        foreach ($horario_atencion as $key => $horario) {
            $hora_inicio = strtotime($horario['hora_inicio']); // Convierte a timestamp
            $hora_termino = strtotime($horario['hora_termino']); // Convierte a timestamp

            // Comparar y establecer la hora de inicio mínima
            if ($hora_inicio < strtotime($min_hora_inicio)) {
                $min_hora_inicio = $horario['hora_inicio'];
            }

            // Comparar y establecer la hora de término máxima
            if ($hora_termino > strtotime($max_hora_termino)) {
                $max_hora_termino = $horario['hora_termino'];
            }

            foreach ($horario['dias'] as $dia) {
                // Verifica si el día ya está en el array $dias_disponibled
                if (!in_array($dia['dia'], $dias_disponibled)) { 
                    array_push($dias_disponibled, $dia['dia']); // Añade el día al array si no existe
                }
            }
        }

        $min_hora = explode(':', $min_hora_inicio)[0];
        $max_hora = explode(':', $max_hora_termino)[0] - 1;

        $tmp_min_hora   = intval(explode(':', $min_hora_inicio)[0]); // Convierte a número entero
        $tmp_max_hora   = intval(explode(':', $max_hora_termino)[0]); // Convierte a número entero

        $rangos_no_incluidos    = SELF::timeRangeNotIncluded($horario_atencion,$tmp_min_hora,$tmp_max_hora);

        return array(
            'min_hora'  => $min_hora,
            'max_hora'  => $max_hora,
            'min_hora_inicio'   => $min_hora_inicio,
            'max_hora_termino'  => $max_hora_termino,
            'rangos_no_incluidos'   => $rangos_no_incluidos
        );
    }
    /** 
     * FUNCION PARA CALCULAR LAS HORAS NO INCLUIDAS EN EL HORARIO DE ATENCION
     * 
     * @param   Object $horario_atencion
     * 
     * @return array
    */
    public static function timeRangeNotIncluded($horario_atencion,$min_hora,$max_hora){
        // Inicializar los días de la semana
        $dias_semana = [
            1 => "Lunes",
            2 => "Martes",
            3 => "Miércoles",
            4 => "Jueves",
            5 => "Viernes",
            6 => "Sábado",
            7 => "Domingo"
        ];

        // Crear un array que registre los rangos de tiempo no incluidos
        $rangos_no_incluidos = [];

        // Iterar por los días de la semana
        foreach ($dias_semana as $numero_dia => $nombre_dia) {
            $horas_cubiertas = []; // Guardará los rangos cubiertos por los horarios

            // Revisar los horarios para el día actual
            foreach ($horario_atencion as $horario) {
                foreach ($horario['dias'] as $dia) {
                    if ($dia['dia'] == $numero_dia) {
                        $horas_cubiertas[] = [
                            "start" => strtotime($horario['hora_inicio']),
                            "end" => strtotime($horario['hora_termino']),
                        ];
                    }
                }
            }

            // Ordenar los rangos por hora de inicio
            usort($horas_cubiertas, function ($a, $b) {
                return $a['start'] - $b['start'];
            });

            // Establecer la hora mínima y máxima para el día
            $hora_actual = strtotime(sprintf("%02d:00", $min_hora)); // Inicia en $min_hora
            $hora_final = strtotime(sprintf("%02d:00", $max_hora)); // Termina en $max_hora

            foreach ($horas_cubiertas as $rango) {
                if ($hora_actual < $rango['start']) {
                    // Ajustar el "end" si es igual a hora_final
                    $end_time = min($rango['start'], $hora_final);
                    if ($end_time == $hora_final) {
                        $end_time -= 1; // Restar 1 segundo si end == hora_termino
                    }
                    // Hay un intervalo no cubierto antes del rango actual y dentro del rango permitido
                    $rangos_no_incluidos[] = [
                        "start" => date('H:i', $hora_actual),
                        "end" => date('H:i', $end_time),
                        "day" => $nombre_dia
                    ];
                }
                // Actualizar la hora actual al final del rango cubierto
                $hora_actual = max($hora_actual, $rango['end']);
            }

            // Verificar si hay un intervalo no cubierto después del último rango dentro del rango permitido
            if ($hora_actual < $hora_final) {
                $end_time = $hora_final;
                if ($end_time == $hora_final) {
                    $end_time -= 1; // Restar 1 segundo si end == hora_termino
                }
                $rangos_no_incluidos[] = [
                    "start" => date('H:i', $hora_actual),
                    "end" => date('H:i', $end_time),
                    "day" => $nombre_dia
                ];
            }
        }

        return $rangos_no_incluidos;
    }

    public static function obtenerRangosNoDisponiblesPorDia($horariosLocacion, $horariosProfesional,$hora_cierre) {
        // Inicializar los días de la semana
        $diasSemana = [
            1 => "Lunes",
            2 => "Martes",
            3 => "Miércoles",
            4 => "Jueves",
            5 => "Viernes",
            6 => "Sábado",
            7 => "Domingo"
        ];
    
        // Resultado donde se almacenarán los rangos no disponibles
        $rangosNoDisponibles = [];
    
        // Iterar por los días de la semana
        foreach ($diasSemana as $numeroDia => $nombreDia) {
            // Obtener los rangos de horas para la locación en este día
            $horasLocacion = [];
            foreach ($horariosLocacion as $horario) {
                foreach ($horario['dias'] as $dia) {
                    if ($dia['dia'] == $numeroDia) {
                        $horasLocacion[] = [
                            "start" => strtotime($horario['hora_inicio']),
                            "end" => strtotime($horario['hora_termino'])
                        ];
                    }
                }
            }
    
            // Obtener los rangos de horas para el profesional en este día
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
    
            // Calcular los rangos no disponibles
            foreach ($horasLocacion as $rangoLocacion) {
                $horaActual = $rangoLocacion['start'];
                while ($horaActual < $rangoLocacion['end']) {
                    $esCubierta = false;
    
                    foreach ($horasProfesional as $rangoProfesional) {
                        if ($horaActual >= $rangoProfesional['start'] && $horaActual < $rangoProfesional['end']) {
                            $esCubierta = true;
                            break;
                        }
                    }
    
                    // Si la hora actual no está cubierta, calcular el rango faltante
                    if (!$esCubierta) {
                        $start = date('H:i', $horaActual);
                        $horaActual += 3600; // Avanzar en intervalos de 1 hora
                        $tmp_end    = date('H:i', $horaActual);
                        $end = $hora_cierre == $tmp_end ? date('H:i', $horaActual - 1) : date('H:i', $horaActual); // Restar 1 segundo al final del rango
                        $rangosNoDisponibles[] = [
                            "start" => $start,
                            "end" => $end,
                            "day" => $nombreDia
                        ];
                    } else {
                        $horaActual += 3600; // Avanzar 1 hora si está cubierta
                    }
                }
            }
        }

        // Decodificar el JSON si los horarios están en formato JSON
        $horarios = $rangosNoDisponibles;

        // Resultado donde se almacenarán los horarios agrupados
        $horariosAgrupados = [];

        // Agrupar por día
        $horariosPorDia = [];
        foreach ($horarios as $horario) {
            $horariosPorDia[$horario['day']][] = $horario;
        }

        // Procesar cada día
        foreach ($horariosPorDia as $dia => $horariosDelDia) {
            // Ordenar los horarios del día por la hora de inicio
            usort($horariosDelDia, function ($a, $b) {
                return strtotime($a['start']) - strtotime($b['start']);
            });

            // Combinar horarios consecutivos
            $horarioActual = $horariosDelDia[0];
            for ($i = 1; $i < count($horariosDelDia); $i++) {
                $siguienteHorario = $horariosDelDia[$i];

                // Si el fin del horario actual coincide o es consecutivo con el inicio del siguiente, los combinamos
                if (strtotime($horarioActual['end']) + 1 >= strtotime($siguienteHorario['start'])) {
                    $horarioActual['end'] = $siguienteHorario['end']; // Extender el final del rango
                } else {
                    // Si no son consecutivos, guardamos el horario actual y comenzamos un nuevo rango
                    $horariosAgrupados[] = $horarioActual;
                    $horarioActual = $siguienteHorario;
                }
            }

            // Agregar el último rango procesado
            $horariosAgrupados[] = $horarioActual;
        }
    
        return $horariosAgrupados;
    }
}