<?php

namespace App\Controllers;

use Phalcon\Mvc\Controller;
use Phalcon\Http\Request;  // Asegúrate de importar la clase Request
use Phalcon\Http\Response; // Asegúrate de importar la clase Response
use App\Library\FuncionesGlobales;

class LocacionesController extends BaseController
{
    protected $rutas;
    protected $url_api;
    protected $bitacora;

    public function initialize(){
        $config         = $this->getDI();
        $this->rutas    = $config->get('rutas');
        $config         = $config->get('config');
        $this->url_api  = $config['BASEAPI'];
        $this->bitacora = 'Locaciones';
    }

    public function IndexAction(){

        if ($this->request->isAjax()){
            $accion = $this->request->getPost('accion');

            $result = array();
            if($accion == 'get_rows'){
                $arr_return = array(
                    "draw"              => $this->request->getPost('draw'),
                    "recordsTotal"      => 0,
                    "recordsFiltered"   => 10,
                    "data"              => array()
                );
        
                // SE REALIZA LA BUSQUEDA DEL COUNT

                $route          = $this->url_api.$this->rutas['ctlocaciones']['count'];
                $num_registros  = FuncionesGlobales::RequestApi('GET',$route,$_POST);
        
                if ($num_registros == 0){
                    $result = array(
                        "draw"              => $this->request->getPost('draw'),
                        "recordsTotal"      => count($result),
                        "recordsFiltered"   => 10,
                        "data"              => $result
                    );
                }

                $route  = $this->url_api.$this->rutas['ctlocaciones']['show'];
                $result = FuncionesGlobales::RequestApi('GET',$route,$_POST);
        
                $result = array(
                    "draw"              => $this->request->getPost('draw'),
                    "recordsTotal"      => $num_registros,
                    "recordsFiltered"   => $num_registros,
                    "data"              => $result
                );
        
            }

            if ($accion == 'get_info_edit'){
                // SE REALIZA LA BUSQUEDA DEL COUNT
                $arr_return = array();

                //  SE BUSCAN LOS PERMISOS ASIGNADOS AL USUARIO
                $route      = $this->url_api.$this->rutas['ctlocaciones']['show'];
                $arr_return = FuncionesGlobales::RequestApi('GET',$route,array(
                    "id" => $_POST['id'],
                    "get_servicios" => 1
                ));

                $result = $arr_return;
            }

            $response = new Response();
            $response->setJsonContent($result);
            $response->setStatusCode(200, 'OK');
            return $response;
        }

        $route          = $this->url_api.$this->rutas['ctservicios']['show'];
        $arr_servicios  = FuncionesGlobales::RequestApi('GET',$route,$_POST);

        $this->view->arr_servicios  = $arr_servicios;
        $this->view->create         = FuncionesGlobales::HasAccess("Locaciones","create");
        $this->view->update         = FuncionesGlobales::HasAccess("Locaciones","update");
        $this->view->delete         = false;//FuncionesGlobales::HasAccess("Locaciones","delete");
    }

    public function createAction(){
        if ($this->request->isAjax()){
            $aqui   = 1;

            $route  = $this->url_api.$this->rutas['ctlocaciones']['create'];
            $result = FuncionesGlobales::RequestApi('POST',$route,$_POST);
            $response = new Response();

            if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                $response->setStatusCode(404, 'Error');
                return $response;
            }

            $lista_servicios    = isset($_POST['lista_servicios']) ? count($_POST['lista_servicios']) : array();
            FuncionesGlobales::saveBitacora($this->bitacora,'CREAR','Se creo la locacion: '.$_POST['clave'].' - '.$_POST['nombre'].' con '.$lista_servicios.' servicios',$_POST);

            $response->setJsonContent('Captura exitosa');
            $response->setStatusCode(200, 'OK');
            return $response;
        }
    }

    public function deleteAction(){
        if ($this->request->isAjax()){
            $route  = $this->url_api.$this->rutas['ctlocaciones']['delete'];
            $result = FuncionesGlobales::RequestApi('DELETE',$route,$_POST);
            $response = new Response();

            if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                $response->setStatusCode(404, 'Error');
                return $response;
            }

            FuncionesGlobales::saveBitacora($this->bitacora,'BORRAR','Se elimino la locacion '.$_POST['clave'].' - '.$_POST['nombre'],$_POST);

            $response->setJsonContent('Captura exitosa');
            $response->setStatusCode(200, 'OK');
            return $response;
        }
    }

    public function updateAction(){
        if ($this->request->isAjax()){

            $accion = $_POST['accion'] ?? null;

            //  RUTA PARA GUARDAR EL HORARIO DE ATENCION
            if ($accion == 'save_opening_hours'){
                $route  = $this->url_api.$this->rutas['ctlocaciones']['save_opening_hours'];
                $result = FuncionesGlobales::RequestApi('POST',$route,$_POST);
                $response = new Response();
    
                if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                    $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                    $response->setStatusCode(404, 'Error');
                    return $response;
                }

                FuncionesGlobales::saveBitacora($this->bitacora,'EDITAR','Se CREO/MODIFICO el horario de atención de la locacion: Clave :'.$_POST['clave'].' - '.$_POST['nombre'],$_POST['obj_info']);

                $response->setJsonContent('Captura exitosa');
                $response->setStatusCode(200, 'OK');
                return $response;
            }

            if ($accion == 'get_opening_hours'){
                $route  = $this->url_api.$this->rutas['tbhorarios_atencion']['get_opening_hours'];
                $result = FuncionesGlobales::RequestApi('GET',$route,$_POST);
                $response = new Response();
    
                if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                    $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                    $response->setStatusCode(404, 'Error');
                    return $response;
                }

                $response->setJsonContent($result);
                $response->setStatusCode(200, 'OK');
                return $response;
            }

            $route  = $this->url_api.$this->rutas['ctlocaciones']['update'];
            $result = FuncionesGlobales::RequestApi('PUT',$route,$_POST);
            $response = new Response();

            if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                $response->setStatusCode(404, 'Error');
                return $response;
            }
            
            $servicios_old      = isset($_POST['servicios_old']) ? count($_POST['servicios_old']) : 0;
            $lista_servicios    = isset($_POST['lista_servicios']) ? count($_POST['lista_servicios']) : 0;
            FuncionesGlobales::saveBitacora($this->bitacora,'EDITAR','Se edito la locacion: Clave :'.$_POST['clave_old'].' por '.$_POST['clave'].' con nombre: '.$_POST['nombre_old'].' anteriormente tenia '.$servicios_old.' servicios, ahora cuenta con '.$lista_servicios.' servicios',$_POST);

            $response->setJsonContent('Captura exitosa');
            $response->setStatusCode(200, 'OK');
            return $response;
        }
    }
}