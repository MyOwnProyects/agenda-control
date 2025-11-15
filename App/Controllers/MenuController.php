<?php

namespace App\Controllers;

use Phalcon\Mvc\Controller;
use Phalcon\Http\Request;  // Asegúrate de importar la clase Request
use Phalcon\Http\Response; // Asegúrate de importar la clase Response
use App\Library\FuncionesGlobales;


class MenuController extends BaseController
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
            $route          = $this->url_api.$this->rutas['dashboard_menu']['show'];
            $data_dashboard = FuncionesGlobales::RequestApi('GET',$route);

            $response = new Response();

            if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                $response->setStatusCode(404, 'Error');
                return $response;
            }

            $response->setJsonContent(array(
                'citas' => $data_dashboard['citas'],
                'fecha_actual_label'    => $data_dashboard['fecha_actual_label']
            ));
            $response->setStatusCode(200, 'OK');
            return $response;
        }

        // $route                  = $this->url_api.$this->rutas['ctlocaciones']['show'];
        // $_POST['onlyallowed']   = 1;
        // $arr_locaciones = FuncionesGlobales::RequestApi('GET',$route,$_POST);
        // $this->view->arr_locaciones = $arr_locaciones;

        // $route      = $this->url_api.$this->rutas['ctprofesionales']['show'];
        // $servicios  = FuncionesGlobales::RequestApi('GET',$route,array(
        //     'id_locacion'   => $_POST['id_locacion'],
        //     'id'            => $_POST['id_profesional'],
        //     'get_servicios' => true
        // ));

        $route          = $this->url_api.$this->rutas['dashboard_menu']['show'];
        $data_dashboard = FuncionesGlobales::RequestApi('GET',$route);

        $this->view->json_data_citas        = json_encode($data_dashboard['citas']);
        $this->view->fecha_actual           = $data_dashboard['fecha_actual'];
        $this->view->hora_bd                = $data_dashboard['hora_bd'];
        $this->view->fecha_actual_label     = $data_dashboard['fecha_actual_label'];
        $this->view->fecha_inicio_semana    = $data_dashboard['fecha_inicio_semana'];
        $this->view->fecha_termino_semana   = $data_dashboard['fecha_termino_semana'];
        $this->view->dia_semana             = $data_dashboard['dia_semana'];
        $this->view->nombre_usuario         = $this->session->get('nombre');
        $this->view->pacientes              = FuncionesGlobales::HasAccess("Pacientes","index");
        $this->view->expediente_digital     = FuncionesGlobales::HasAccess("Pacientes","digitalRecord");
        $this->view->expediente_clinico     = FuncionesGlobales::HasAccess("Pacientes","clinicalData");
        $this->view->agenda                 = FuncionesGlobales::HasAccess("Agenda","index");
    }

    public function route404Action(){
        
    }
    
}