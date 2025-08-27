<?php

namespace App\Controllers;

use Phalcon\Mvc\Controller;
use Phalcon\Http\Request;  // Asegúrate de importar la clase Request
use Phalcon\Http\Response; // Asegúrate de importar la clase Response
use App\Library\FuncionesGlobales;

class BitacoraController extends BaseController
{
    protected $rutas;
    protected $url_api;
    protected $bitacora;

    public function initialize(){
        $config         = $this->getDI();
        $this->rutas    = $config->get('rutas');
        $config         = $config->get('config');
        $this->url_api  = $config['BASEAPI'];
        $this->bitacora = 'Bitacora';
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

                $route          = $this->url_api.$this->rutas['tbbitacora']['count'];
                $num_registros  = FuncionesGlobales::RequestApi('GET',$route,$_POST);
        
                if ($num_registros == 0){
                    $result = array(
                        "draw"              => $this->request->getPost('draw'),
                        "recordsTotal"      => count($result),
                        "recordsFiltered"   => 10,
                        "data"              => $result
                    );
                }

                $route  = $this->url_api.$this->rutas['tbbitacora']['show'];
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
                $route      = $this->url_api.$this->rutas['tbbitacora']['show'];
                $arr_return = FuncionesGlobales::RequestApi('GET',$route,array("id" => $_POST['id']));

                $result = $arr_return;
            }

            $response = new Response();
            $response->setJsonContent($result);
            $response->setStatusCode(200, 'OK');
            return $response;
        }

        $this->view->create = FuncionesGlobales::HasAccess("Servicios","create");
        $this->view->update = FuncionesGlobales::HasAccess("Servicios","update");
        $this->view->delete = FuncionesGlobales::HasAccess("Servicios","delete");
    }
}