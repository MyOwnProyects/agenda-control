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
                $_POST['fromCatalogProfessional']   = 1;
                $result = FuncionesGlobales::RequestApi('GET',$route,$_POST);  
                $result = $result[0];  
                
                $response = new Response();
                $response->setJsonContent($result);
                $response->setStatusCode(200, 'OK');
                return $response;
            }

            if ($accion == 'create_record'){
                $route  = $this->url_api.$this->rutas['ctprofesionales']['create'];
                $result = FuncionesGlobales::RequestApi('POST',$route,$_POST); 
                
                $response = new Response();

                if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                    $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                    $response->setStatusCode(404, 'Error');
                    return $response;
                }

                $nombre     = $_POST['primer_apellido'].' '.$_POST['segundo_apellido'].' '.$_POST['nombre'];
                $locaciones = isset($_POST['lista_locaciones']) ? count($_POST['lista_locaciones']) : 0;
                FuncionesGlobales::saveBitacora($this->bitacora,'CREAR','Se creo el registro para el profesional: '.$nombre.' con numero de telefono: '.$_POST['celular'].' siendo tipo usuario: '.$_POST['label_tipo_usuario'].' con '.$locaciones.' locaciones',$_POST);

                $response->setJsonContent('Captura exitosa');
                $response->setStatusCode(200, 'OK');
                return $response;
            }

            
        }
        
        //  SE BUSCAN LOS SERVICIOS QUE PUEDE OFRECER EL USUARIO
        $route          = $this->url_api.$this->rutas['ctlocaciones']['show'];
        $arr_locaciones= FuncionesGlobales::RequestApi('GET',$route,array('get_servicios' => 1));

        //  SE BUSCA LA INFORMACION DEL PERFIL DE PROFESIONAL
        $route              = $this->url_api.$this->rutas['cttipo_usuarios']['show'];
        $arr_tipo_usuario   = FuncionesGlobales::RequestApi('GET',$route,array('clave' => 'PROF'));

        $this->view->arr_locaciones     = $arr_locaciones;
        $this->view->arr_tipo_usuario   = $arr_tipo_usuario[0];
        
    }
}