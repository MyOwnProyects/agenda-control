<?php

namespace App\Controllers;

use Phalcon\Mvc\Controller;
use Phalcon\Http\Request;  // Asegúrate de importar la clase Request
use Phalcon\Http\Response; // Asegúrate de importar la clase Response
use App\Library\FuncionesGlobales;

class ProfesionalesController extends BaseController
{
    protected $rutas;
    protected $url_api;
    protected $bitacora;

    public function initialize(){
        $config         = $this->getDI();
        $this->rutas    = $config->get('rutas');
        $config         = $config->get('config');
        $this->url_api  = $config['BASEAPI'];
        $this->bitacora = 'Profesionales';
    }

    public function IndexAction(){

        $route          = $this->url_api.$this->rutas['ctservicios']['show'];
        $arr_servicios  = FuncionesGlobales::RequestApi('GET',$route,$_POST);

        $this->view->arr_servicios      = $arr_servicios;
        $this->view->create = FuncionesGlobales::HasAccess("Profesionales","create");
        $this->view->update = FuncionesGlobales::HasAccess("Profesionales","update");
        $this->view->delete = FuncionesGlobales::HasAccess("Profesionales","delete");
    }

    public function createAction(){

        if ($this->request->isAjax()){
            $accion = $this->request->getPost('accion');
            $result = array();
            if ($accion == 'validateCelular'){
                //  SE BUSCA EN LA TABLA DE USUARIOS SI EXISTE UN USUARIO CON EL TELEFONO REGISTRADO
                $route  = $this->url_api.$this->rutas['ctusuarios']['show'];
                $result = FuncionesGlobales::RequestApi('GET',$route,$_POST);  
                $result = $result[0];          
            }

            $response = new Response();
            $response->setJsonContent($result);
            $response->setStatusCode(200, 'OK');
            return $response;
        }
        
        //  SE BUSCAN LOS SERVICIOS QUE PUEDE OFRECER EL USUARIO
        $route          = $this->url_api.$this->rutas['ctservicios']['show'];
        $arr_servicios  = FuncionesGlobales::RequestApi('GET',$route,$_POST);

        //  SE BUSCA LA INFORMACION DEL PERFIL DE PROFESIONAL
        $route              = $this->url_api.$this->rutas['cttipo_usuarios']['show'];
        $arr_tipo_usuario   = FuncionesGlobales::RequestApi('GET',$route,array('clave' => 'PROF'));

        $this->view->arr_servicios      = $arr_servicios;
        $this->view->arr_tipo_usuario   = $arr_tipo_usuario[0];
        
    }
}