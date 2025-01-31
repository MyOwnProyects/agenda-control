<?php

namespace App\Controllers;

use Phalcon\Mvc\Controller;
use Phalcon\Http\Request;  // Asegúrate de importar la clase Request
use Phalcon\Http\Response; // Asegúrate de importar la clase Response
use App\Library\FuncionesGlobales;


class TipousuariosController extends BaseController
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
        $aqui = 1;
        if ($this->request->isAjax()){
            $accion = $this->request->getPost('accion', 'string');
            $result = array();
            if($accion == 'get_rows'){
                $aqui   = 1;

                $arr_return = array(
                    "draw"              => $this->request->getPost('draw'),
                    "recordsTotal"      => 0,
                    "recordsFiltered"   => 10,
                    "data"              => array()
                );
        
                // SE REALIZA LA BUSQUEDA DEL COUNT
                $route  = $this->url_api.$this->rutas['cttipo_usuarios']['show'];
                $result = FuncionesGlobales::RequestApi('GET',$route,$_POST);
        
                if (count($result) == 0){
                    $result = array(
                        "draw"              => $this->request->getPost('draw'),
                        "recordsTotal"      => count($result),
                        "recordsFiltered"   => 10,
                        "data"              => $result
                    );
                }
        
                $result = array(
                    "draw"              => $this->request->getPost('draw'),
                    "recordsTotal"      => count($result),
                    "recordsFiltered"   => 10,
                    "data"              => $result
                );
        
            }

            if ($accion == 'get_permisos'){
                // SE REALIZA LA BUSQUEDA DEL COUNT
                $_POST['fromcatalog']   = 1;
                $route  = $this->url_api.$this->rutas['ctpermisos']['show'];
                $result = FuncionesGlobales::RequestApi('GET',$route,$_POST);
            }

            if ($accion == 'get_info_edit'){
                // SE REALIZA LA BUSQUEDA DEL COUNT
                $arr_return = array(
                    'permisos'  => array(),
                    'info'      => array()
                );

                $_POST['get_permisos']  = 1;

                $route  = $this->url_api.$this->rutas['ctpermisos']['show'];
                $arr_return['permisos'] = FuncionesGlobales::RequestApi('GET',$route,array(
                    'fromcatalog'   => 1
                ));

                $route              = $this->url_api.$this->rutas['cttipo_usuarios']['show'];
                $arr_return['info'] = FuncionesGlobales::RequestApi('GET',$route,$_POST);

                $arr_return['info'] = $arr_return['info'][0];

                $result = $arr_return;
            }

            $response = new Response();
            $response->setJsonContent($result);
            $response->setStatusCode(200, 'OK');
            return $response;
        }

        $this->view->create = FuncionesGlobales::HasAccess("Tipousuarios","create");
        $this->view->update = FuncionesGlobales::HasAccess("Tipousuarios","update");
        $this->view->delete = FuncionesGlobales::HasAccess("Tipousuarios","delete");
    }

    public function createAction(){
        if ($this->request->isAjax()){
            $aqui   = 1;

            $route  = $this->url_api.$this->rutas['cttipo_usuarios']['create'];
            $result = FuncionesGlobales::RequestApi('POST',$route,$_POST);
            $response = new Response();

            if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                $response->setStatusCode(404, 'Error');
                return $response;
            }

            $response->setJsonContent('Captura exitosa');
            $response->setStatusCode(200, 'OK');
            return $response;
        }
    }

    public function deleteAction(){
        if ($this->request->isAjax()){
            $route  = $this->url_api.$this->rutas['cttipo_usuarios']['change_estatus'];
            $result = FuncionesGlobales::RequestApi('DELETE',$route,$_POST);
            $response = new Response();

            if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                $response->setStatusCode(404, 'Error');
                return $response;
            }

            $response->setJsonContent('Captura exitosa');
            $response->setStatusCode(200, 'OK');
            return $response;
        }
    }
    
}