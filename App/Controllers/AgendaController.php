<?php

namespace App\Controllers;

use Phalcon\Mvc\Controller;
use Phalcon\Http\Request;  // Asegúrate de importar la clase Request
use Phalcon\Http\Response; // Asegúrate de importar la clase Response
use App\Library\FuncionesGlobales;


class AgendaController extends BaseController
{
    protected $rutas;
    protected $url_api;

    public function initialize(){
        $config         = $this->getDI();
        $this->rutas    = $config->get('rutas');
        $config         = $config->get('config');
        $this->url_api  = $config['BASEAPI'];
    }

    public function IndexAction(){

        if ($this->request->isAjax()){
            $accion = $_POST['accion'];

            if ($accion == 'fill_combo'){
                $route      = $this->url_api.$this->rutas['ctpacientes']['fill_combo'];
                $arr_info   = FuncionesGlobales::RequestApi('GET',$route,$_POST);

                $response = new Response();
                $response->setJsonContent($arr_info);
                $response->setStatusCode(200, 'OK');
                return $response;
            }

            if ($accion == 'get_date'){
                $route      = $this->url_api.$this->rutas['tbapertura_agenda']['show'];
                $arr_info           = FuncionesGlobales::RequestApi('GET',$route,$_POST);

                $response = new Response();
                $response->setJsonContent($arr_info);
                $response->setStatusCode(200, 'OK');
                return $response;
            }

            if ($accion == 'save_agenda_opening'){
                $obj_info   = $_POST['obj_info'];
                $route      = $this->url_api.$this->rutas['tbapertura_agenda']['save'];
                $result     = FuncionesGlobales::RequestApi('POST',$route,$obj_info);

                $response = new Response();

                if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                    $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                    $response->setStatusCode(404, 'Error');
                    return $response;
                }

                FuncionesGlobales::saveBitacora($this->bitacora,'CREAR','Se realizó la apertura de agenda para la locaci&oacuite;n: '.$obj_info['nombre'].' con rango de fechas del : '.$obj_info['fecha_inicio'].' al '.$obj_info['fecha_limite'],$obj_info);

                $response->setJsonContent('Apertura de agenda exitosa!');
                $response->setStatusCode(200, 'OK');
                return $response;
            }

            if ($accion == 'get_info_locacion'){
                $arr_return = $this->get_info_by_location();

                if (!is_array($arr_return)){
                    $response = new Response();
                    $response->setJsonContent($arr_return);
                    $response->setStatusCode(404, 'Error');
                    return $response;
                }

                //  SE RECORREN TODOS LOS PROFESIONALES PARA 
                //  BUSCAR SU HORARIO DE ATENCION
                $route  = $this->url_api.$this->rutas['tbhorarios_atencion']['get_opening_hours'];
                foreach($arr_return['all_professionals'] as $index => $profesional){
                    $horario_atencion_profesional   = FuncionesGlobales::RequestApi('GET',$route,array(
                        'id_locacion'           => $_POST['id_locacion'],
                        'id_profesional'        => $profesional['id'],
                    ));

                    $hora_cierre    = (INT) $arr_return['max_hora_inicio'] + 1;
                    $hora_cierre    = $hora_cierre.':00';

                    $result = array(
                        'rango_no_disponible'   => array(),
                        'citas_programadas'     => array()
                    );

                    $arr_return['all_professionals'][$index]['rango_no_disponible']  = FuncionesGlobales::obtenerRangosNoDisponiblesPorDia($arr_return['horario_atencion'],$horario_atencion_profesional,$hora_cierre);
                }


                $response = new Response();
                $response->setJsonContent($arr_return);
                $response->setStatusCode(200, 'OK');
                return $response;
            }

            if ($accion == 'cancelar_cita'){
                $route      = $this->url_api.$this->rutas['tbagenda_citas']['cancelar_cita'];
                $result     = FuncionesGlobales::RequestApi('DELETE',$route,$_POST);

                $response = new Response();

                if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                    $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                    $response->setStatusCode(404, 'Error');
                    return $response;
                }

                FuncionesGlobales::saveBitacora($this->bitacora,'DELETE','Se realizó la cancelación de la cita con identificar: '.$_POST['id_agenda_cita'],$obj_info);

                $response->setJsonContent('Cancelacion exitosa!');
                $response->setStatusCode(200, 'OK');
                return $response;
            }

            if ($accion == 'modificar_asistencia'){
                $route      = $this->url_api.$this->rutas['tbagenda_citas']['modificar_asistencia'];
                $result     = FuncionesGlobales::RequestApi('PUT',$route,$_POST);

                $response = new Response();

                if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                    $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                    $response->setStatusCode(404, 'Error');
                    return $response;
                }

                FuncionesGlobales::saveBitacora($this->bitacora,'DELETE','Se realizó la cancelación de la cita con identificar: '.$_POST['id_agenda_cita'],$obj_info);

                $response->setJsonContent('Cancelacion exitosa!');
                $response->setStatusCode(200, 'OK');
                return $response;
            }

            if ($accion == 'get_horario_profesional'){
                $route              = $this->url_api.$this->rutas['tbhorarios_atencion']['get_opening_hours'];
                $horario_atencion_locacion  = FuncionesGlobales::RequestApi('GET',$route,array(
                    'id_locacion'           => $_POST['id_locacion']
                ));

                $horario_atencion_profesional   = FuncionesGlobales::RequestApi('GET',$route,array(
                    'id_locacion'           => $_POST['id_locacion'],
                    'id_profesional'        => $_POST['id_profesional'],
                ));

                $response = new Response();
                if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400) || count($horario_atencion_profesional) == 0){
                    $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                    $response->setStatusCode(404, 'Error');
                    return $response;
                }

                $hora_cierre    = (INT) $_POST['max_hora_inicio'] + 1;
                $hora_cierre    = $hora_cierre.':00';

                $result = array(
                    'rango_no_disponible'   => array(),
                    'citas_programadas'     => array()
                );

                $result['rango_no_disponible']  = FuncionesGlobales::obtenerRangosNoDisponiblesPorDia($horario_atencion_locacion,$horario_atencion_profesional,$hora_cierre);

                //  SE BUSCAN LAS CITAS PROGRAMADAS DEL PROFESIONAL
                $result['citas_paciente']   = $this->get_citas_programadas(array(
                    'id_profesional'    => $_POST['id_profesional'],
                    'activa'            => 1,
                    'rango_fechas'      => array(
                        'fecha_inicio'  => $_POST['fecha_programar'],
                        'fecha_termino' => $_POST['fecha_programar'],
                    )
                ));

                //  SE BUSCAN LOS SERVICIOS QUE DA EL PROFESIONAL EN EL LOCAL INDICADO
                $route      = $this->url_api.$this->rutas['ctprofesionales']['show'];
                $servicios  = FuncionesGlobales::RequestApi('GET',$route,array(
                    'id_locacion'   => $_POST['id_locacion'],
                    'id'            => $_POST['id_profesional'],
                    'get_servicios' => true
                ));

                $result['servicios']    = $servicios[0]['locacion_servicios'];

                $response = new Response();
                $response->setJsonContent($result);
                $response->setStatusCode(200, 'OK');
                return $response;
            }

            if ($accion == 'save_appoinment'){
                $route      = $this->url_api.$this->rutas['tbagenda_citas']['create'];
                $result     = FuncionesGlobales::RequestApi('POST',$route,$_POST['obj_info']);

                $response = new Response();

                if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                    $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                    $response->setStatusCode(404, 'Error');
                    return $response;
                }
                $nombre_paciente    = $_POST['primer_apellido'].' '. $_POST['segundo_apellido'].' '.$_POST['nombre'];
                FuncionesGlobales::saveBitacora($this->bitacora,'CREATE','Se programo la cita para el paciente: '.$nombre_paciente,$_PST['obj_info']);

                $response->setJsonContent('Cancelacion exitosa!');
                $response->setStatusCode(200, 'OK');
                return $response;
            }
        }     

        $route                  = $this->url_api.$this->rutas['ctlocaciones']['show'];
        $_POST['onlyallowed']   = 1;
        $arr_locaciones = FuncionesGlobales::RequestApi('GET',$route,$_POST);
        $this->view->arr_locaciones = $arr_locaciones;

        $this->view->apertura_agenda    = FuncionesGlobales::HasAccess("Agenda","agenda_opening");

        $route                      = $this->url_api.$this->rutas['ctvariables_sistema']['show'];
        $dias_programacion_citas    = FuncionesGlobales::RequestApi('GET',$route,array(
            'clave' => 'dias_programacion_citas'
        ));

        if (!is_array($dias_programacion_citas)){
            $dias_programacion_citas    = 31;
        } else {
            $dias_programacion_citas    = $dias_programacion_citas[0]['valor'];
        }

        $this->view->dias_programacion_citas    = $dias_programacion_citas;

        //  MOTIVOS PARA CANCELAR UNA CITA
        $route                      = $this->url_api.$this->rutas['ctmotivos_cancelacion_cita']['show'];
        $motivos_cancelacion_cita   = FuncionesGlobales::RequestApi('GET',$route);

        $this->view->motivos_cancelacion_cita   = $motivos_cancelacion_cita;
    }

    function get_info_by_location(){
        $arr_return = array(
            'horario_atencion'  => array()
        );
        $route              = $this->url_api.$this->rutas['tbhorarios_atencion']['get_opening_hours'];
        $horario_atencion   = FuncionesGlobales::RequestApi('GET',$route,array('id_locacion' => $_POST['id_locacion']));

        $response = new Response();

        if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400) || count($horario_atencion) == 0){
            return 'No existe un horario de atenci&oacute;n registrado a la locaci&oacute;n';
        }

        $arr_return['horario_atencion'] = $horario_atencion;

        $arr_horas  = FuncionesGlobales::allStructureSchedule($horario_atencion);

        $arr_return['min_hora_inicio']      = $arr_horas['min_hora'];
        $arr_return['max_hora_inicio']      = $arr_horas['max_hora'];
        $arr_return['rangos_no_incluidos']  = $arr_horas['rangos_no_incluidos'];

        //  SE BUSCA LA ULTIMA FECHA DISPONIBLE ANTES DEL CIERRE DE AGENDA
        $route      = $this->url_api.$this->rutas['tbapertura_agenda']['show'];
        $arr_return['cierre_agenda']    = FuncionesGlobales::RequestApi('GET',$route,$_POST);

        //  INFORMACION DE LOS SERVICIOS
        $route                      = $this->url_api.$this->rutas['ctservicios']['show'];
        $arr_return['all_services'] = FuncionesGlobales::RequestApi('GET',$route,array('id_locacion' => $_POST['id_locacion']));

        // INFORMACION DE LOS PROFESIONALES
        $route                              = $this->url_api.$this->rutas['ctprofesionales']['show'];
        $arr_return['all_professionals']    = FuncionesGlobales::RequestApi('GET',$route,array('id_locacion' => $_POST['id_locacion']));

        //  SE BUSCAN LAS CITAS AGENDADAS EN EL RANGO DE FECHAS
        $route                          = $this->url_api.$this->rutas['tbagenda_citas']['show'];
        $_POST['activa']                = 1;
        $arr_return['citas_agendadas']  = FuncionesGlobales::RequestApi('GET',$route,$_POST);

        //  SI VIENEN VACIOS ESTOS ESPACIOS, SE UNIFICAN LAS HORAS PARA QUE SEA UN SOLO DIV CORRIDO
        if (empty($_POST['id_profesional']) && empty($_POST['id_paciente'])) {
            $arr_return['citas_unificadas'] =   $this->unificar_citas_agendadas($arr_return['citas_agendadas']);
        }
        
        return $arr_return;
    }

    function get_citas_programadas($params){
        $route                      = $this->url_api.$this->rutas['tbagenda_citas']['show'];
        $result['info_paciente']    = FuncionesGlobales::RequestApi('GET',$route,$params);

        $citas_paciente = array();
        $dias_semana    = ["Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado","Domingo"];
        foreach($result['info_paciente'] as $id_cita_programada_servicio => $info_citas){
            foreach($info_citas['horarios'] as $horario){
                $citas_paciente[]       = array(
                    'start' => $horario['hora_inicio'],
                    'end'   => $horario['hora_termino'],
                    'day'   => $dias_semana[$horario['dia'] - 1],
                    'servicio'      => $info_citas['servicio'],
                    'profesional'   => $info_citas['profesional'],
                    'duracion'      => $info_citas['duracion'],
                    'id_cita_programada_servicio'           => $id_cita_programada_servicio,
                    'id_cita_programada_servicio_horario'   => $horario['id_cita_programada_servicio_horario'],
                    'nombre_locacion'                       => $info_citas['nombre_locacion']
                );
            }
        }

        return $citas_paciente;
    }

    function unificar_citas_agendadas($citas){
        // Array original de citas
        // $citas = [
        //     ["day" => 1, "fecha_cita" => "2025-05-12", "start" => "09:35", "end" => "10:20"],
        //     ["day" => 1, "fecha_cita" => "2025-05-12", "start" => "10:20", "end" => "10:55"],
        //     ["day" => 1, "fecha_cita" => "2025-05-12", "start" => "11:30", "end" => "12:50"], // Nueva cita separada
        //     ["day" => 3, "fecha_cita" => "2025-05-14", "start" => "09:45", "end" => "10:20"],
        //     ["day" => 3, "fecha_cita" => "2025-05-14", "start" => "11:00", "end" => "11:45"],
        //     ["day" => 5, "fecha_cita" => "2025-05-16", "start" => "10:00", "end" => "10:35"]
        // ];

        // Agrupar citas por día
        $citasAgrupadas = [];

        foreach ($citas as $cita) {
            $day = $cita["day"];
            $fecha = $cita["fecha_cita"];
            $start = $cita["start"];
            $end = $cita["end"];

            if (!isset($citasAgrupadas[$day])) {
                // Primera cita del día
                $citasAgrupadas[$day][] = ["start" => $start, "end" => $end, "fecha_cita" => $fecha, "day" => $day,"nombre_completo" => 'Horario ocupado'];
            } else {
                // Obtener el último grupo de citas
                $lastGroup = &$citasAgrupadas[$day][count($citasAgrupadas[$day]) - 1];

                // Si la cita inicia justo después del último rango, se une al grupo
                if ($start === $lastGroup["end"]) {
                    $lastGroup["end"] = $end;
                } else {
                    // Si la cita no está conectada al rango anterior, se crea un nuevo grupo
                    $citasAgrupadas[$day][] = ["start" => $start, "end" => $end, "fecha_cita" => $fecha, "day" => $day,"nombre_completo" => 'Horario ocupado'];
                }
            }
        }

        // Convertir el resultado a un array numerado
        $citasUnificadas = [];
        foreach ($citasAgrupadas as $grupos) {
            foreach ($grupos as $grupo) {
                $citasUnificadas[] = $grupo;
            }
        }

        // Imprimir resultado
        return $citasUnificadas;

    }
    
}