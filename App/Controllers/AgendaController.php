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

            if ($accion == 'get_date'){
                $route      = $this->url_api.$this->rutas['tbapertura_agenda']['show'];
                $arr_info   = FuncionesGlobales::RequestApi('GET',$route,$_POST);

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

                $response = new Response();
                $response->setJsonContent($arr_return);
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
    }

    function get_info_by_location(){
        $arr_return = array(
            'horario_atencion'  => array()
        );
        $route              = $this->url_api.$this->rutas['tbhorarios_atencion']['get_opening_hours'];
        $horario_atencion   = FuncionesGlobales::RequestApi('GET',$route,array('id_locacion' => $_POST['id_locacion']));

        $response = new Response();

        if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400) || count($horario_atencion) == 0){
            $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
            $response->setStatusCode(404, 'Error');
            return $response;
        }

        $arr_return['horario_atencion'] = $horario_atencion;

        $arr_horas  = FuncionesGlobales::allStructureSchedule($horario_atencion);

        $arr_return['min_hora_inicio']      = $arr_horas['min_hora'];
        $arr_return['max_hora_inicio']      = $arr_horas['max_hora'];
        $arr_return['rangos_no_incluidos']  = $arr_horas['rangos_no_incluidos'];
        $tmp_json   = json_encode($arr_return['rangos_no_incluidos']);

        //  INFORMACION DE LOS SERVICIOS
        $route                      = $this->url_api.$this->rutas['ctservicios']['show'];
        $arr_return['all_services'] = FuncionesGlobales::RequestApi('GET',$route,array('id_locacion' => $_POST['id_locacion']));


        $route                              = $this->url_api.$this->rutas['ctprofesionales']['show'];
        $arr_return['all_professionals']    = FuncionesGlobales::RequestApi('GET',$route,array('id_locacion' => $_POST['id_locacion']));

        return $arr_return;
    }
    
}