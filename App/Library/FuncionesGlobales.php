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
                $msg_error  = $response;
                try{
                    $response   = json_decode($response);
                } catch(\Exception $ex){
                    $response   = $msg_error;
                }
                
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


}