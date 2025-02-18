<?php

namespace App\Controllers;

use Phalcon\Mvc\Controller;
use Phalcon\Http\Request;  // Asegúrate de importar la clase Request
use Phalcon\Http\Response; // Asegúrate de importar la clase Response
use App\Library\FuncionesGlobales;

class PacientesController extends BaseController
{
    protected $rutas;
    protected $url_api;
    protected $bitacora;

    public function initialize(){
        $config         = $this->getDI();
        $this->rutas    = $config->get('rutas');
        $config         = $config->get('config');
        $this->url_api  = $config['BASEAPI'];
        $this->bitacora = 'Pacientes';
    }

    public function IndexAction(){

        if ($this->request->isAjax()){
            $accion = $this->request->getPost('accion', 'string');
            $result = array();
            if($accion == 'get_rows'){

                $arr_return = array(
                    "draw"              => $this->request->getPost('draw'),
                    "recordsTotal"      => 0,
                    "recordsFiltered"   => 10,
                    "data"              => array()
                );
        
                // SE REALIZA LA BUSQUEDA DEL COUNT

                $route          = $this->url_api.$this->rutas['ctpacientes']['count'];
                $num_registros  = FuncionesGlobales::RequestApi('GET',$route,$_POST);
        
                if ($num_registros == 0){
                    $result = array(
                        "draw"              => $this->request->getPost('draw'),
                        "recordsTotal"      => count($result),
                        "recordsFiltered"   => 10,
                        "data"              => $result
                    );
                }

                $route  = $this->url_api.$this->rutas['ctpacientes']['show'];
                $result = FuncionesGlobales::RequestApi('GET',$route,$_POST);
        
                $result = array(
                    "draw"              => $this->request->getPost('draw'),
                    "recordsTotal"      => $num_registros,
                    "recordsFiltered"   => $num_registros,
                    "data"              => $result
                );
        
            }

            $response = new Response();
            $response->setJsonContent($result);
            $response->setStatusCode(200, 'OK');
            return $response;
        }

        $route          = $this->url_api.$this->rutas['ctservicios']['show'];
        $arr_servicios  = FuncionesGlobales::RequestApi('GET',$route,$_POST);

        $this->view->arr_servicios      = $arr_servicios;

        //SE BUSCAS LAS LOCACIONES EXISTENTES
        $route          = $this->url_api.$this->rutas['ctlocaciones']['show'];
        $arr_locaciones = FuncionesGlobales::RequestApi('GET',$route,$_POST);
        //unset($arr_locaciones[1]);
        $this->view->arr_locaciones = $arr_locaciones;

        $this->view->create = FuncionesGlobales::HasAccess("Pacientes","create");
        $this->view->update = FuncionesGlobales::HasAccess("Pacientes","update");
        $this->view->delete = FuncionesGlobales::HasAccess("Pacientes","delete");
        $this->view->preview    = FuncionesGlobales::HasAccess("Pacientes","preview");
    }

    public function createAction(){
        if ($this->request->isAjax()){
            $accion = $this->request->getPost('accion');
            $result = array();

            if ($accion == 'create_express'){
                $_POST  = $_POST['obj_info'];
                $route  = $this->url_api.$this->rutas['ctpacientes']['create'];
                $result = FuncionesGlobales::RequestApi('POST',$route,$_POST); 
                
                $response = new Response();

                if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                    $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                    $response->setStatusCode(404, 'Error');
                    return $response;
                }

                $nombre     = $_POST['primer_apellido'].' '.$_POST['segundo_apellido'].' '.$_POST['nombre'];
                FuncionesGlobales::saveBitacora($this->bitacora,'CREAR','Se creo el paciente para registro rapido: '.$nombre.' con numero de telefono: '.$_POST['celular'].' en la locación: '.$_POST['label_locacion'] ,$_POST);

                $response->setJsonContent('Captura exitosa');
                $response->setStatusCode(200, 'OK');
                return $response;
            }
        }
    }

}