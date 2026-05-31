<?php

namespace App\Controllers;

use Phalcon\Mvc\Controller;
use Phalcon\Http\Request;  // Asegúrate de importar la clase Request
use Phalcon\Http\Response; // Asegúrate de importar la clase Response
use App\Library\FuncionesGlobales;

class BecasController extends BaseController
{
    protected $rutas;
    protected $url_api;
    protected $bitacora;

    public function initialize(){
        $config         = $this->getDI();
        $this->rutas    = $config->get('rutas');
        $config         = $config->get('config');
        $this->url_api  = $config['BASEAPI'];
        $this->bitacora = 'Becas';
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

                $route          = $this->url_api.$this->rutas['becas']['count'];
                $num_registros  = FuncionesGlobales::RequestApi('GET',$route,$_POST);
        
                if (!is_numeric($num_registros) || $num_registros == 0){
                    $result = array(
                        "draw"              => $this->request->getPost('draw'),
                        "recordsTotal"      => count($result),
                        "recordsFiltered"   => 0,
                        "data"              => $result
                    );
                } else {
                    $route  = $this->url_api.$this->rutas['becas']['show'];
                    $result = FuncionesGlobales::RequestApi('GET',$route,$_POST);
            
                    $result = array(
                        "draw"              => $this->request->getPost('draw'),
                        "recordsTotal"      => $num_registros,
                        "recordsFiltered"   => $num_registros,
                        "data"              => $result
                    );
                }
        
            }

            if ($accion == 'get_info_edit'){
                // SE REALIZA LA BUSQUEDA DEL COUNT
                $arr_return = array();

                //  SE BUSCAN LOS PERMISOS ASIGNADOS AL USUARIO
                $route      = $this->url_api.$this->rutas['becas']['show'];
                $arr_return = FuncionesGlobales::RequestApi('GET',$route,array("id" => $_POST['id']));

                $result = $arr_return;
            }

            $response = new Response();
            $response->setJsonContent($result);
            $response->setStatusCode(200, 'OK');
            return $response;
        }

        $this->view->create = FuncionesGlobales::HasAccess("Becas","create");
        $this->view->update = FuncionesGlobales::HasAccess("Becas","update");
        $this->view->delete = FuncionesGlobales::HasAccess("Becas","delete");
    }

    public function createAction(){
        if ($this->request->isAjax()){
            $aqui   = 1;

            $route  = $this->url_api.$this->rutas['becas']['save'];
            $result = FuncionesGlobales::RequestApi('POST',$route,$_POST);
            $response = new Response();

            if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                $response->setStatusCode(404, 'Error');
                return $response;
            }

            $msg_tipo_beca  = $_POST['tipo_beca'] == 1 ? 'Importe' : 'Porcentaje';

            FuncionesGlobales::saveBitacora($this->bitacora,'CREAR','Se creo el registro de beca: '.$_POST['clave'].' - '.$_POST['nombre'].' de tipo: '.$msg_tipo_beca,$_POST);

            $response->setJsonContent('Captura exitosa');
            $response->setStatusCode(200, 'OK');
            return $response;
        }
    }

    public function deleteAction(){
        if ($this->request->isAjax()){
            $route  = $this->url_api.$this->rutas['becas']['delete'];
            $result = FuncionesGlobales::RequestApi('DELETE',$route,$_POST);
            $response = new Response();

            if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                $response->setStatusCode(404, 'Error');
                return $response;
            }

            FuncionesGlobales::saveBitacora($this->bitacora,'BORRAR','Se desactivo la beca '.$_POST['data_bitacora']['clave'].' - '.$_POST['data_bitacora']['nombre'],$_POST['data_bitacora']);

            $response->setJsonContent('Captura exitosa');
            $response->setStatusCode(200, 'OK');
            return $response;
        }
    }

    public function updateAction(){
        if ($this->request->isAjax()){

            if (!empty($this->request->getPost('accion')) && $this->request->getPost('accion') == 'change_status'){
                $route  = $this->url_api.$this->rutas['becas']['change_status'];
                $result = FuncionesGlobales::RequestApi('PUT',$route,$_POST);
                $response = new Response();
    
                if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                    $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                    $response->setStatusCode(404, 'Error');
                    return $response;
                }
                $estatus_actual = $_POST['status_actual'] == 1 ? 'ACTIVO' : 'INACTIVO';
                $nuevo_estatus  = $_POST['nuevo_status'] == 1 ? 'ACTIVO' : 'INACTIVO';
                FuncionesGlobales::saveBitacora($this->bitacora,'EDITAR','Se mando modificar el estatus de la beca: '.$_POST['beca'].' de '.$estatus_actual.' a '.$nuevo_estatus  ,$_POST);
                
                $response->setJsonContent('Edición exitosa');
                $response->setStatusCode(200, 'OK');
                return $response;
            } 

            $route  = $this->url_api.$this->rutas['becas']['save'];
            $result = FuncionesGlobales::RequestApi('POST',$route,$_POST);
            $response = new Response();

            if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                $response->setStatusCode(404, 'Error');
                return $response;
            }
            
            $label_tipo_beca        = $_POST['tipo_beca'] == 1 ? 'IMPORTE' : 'PORCENTAJE';
            $label_tipo_beca_old    = $_POST['tipo_beca_old'] == 1 ? 'IMPORTE' : 'PORCENTAJE';
            FuncionesGlobales::saveBitacora($this->bitacora,'EDITAR','Se edito la beca con Clave :'.$_POST['clave_old'].' por '.$_POST['clave'].' con nombre: '.$_POST['nombre_old'].' por '.$_POST['nombre'].' de tipo: '.$label_tipo_beca_old.' a '.$label_tipo_beca ,$_POST);

            $response->setJsonContent('Captura exitosa');
            $response->setStatusCode(200, 'OK');
            return $response;
        }
    }
}