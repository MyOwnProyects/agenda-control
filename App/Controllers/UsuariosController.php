<?php

namespace App\Controllers;

use Phalcon\Mvc\Controller;
use Phalcon\Http\Request;  // Asegúrate de importar la clase Request
use Phalcon\Http\Response; // Asegúrate de importar la clase Response
use App\Library\FuncionesGlobales;

class UsuariosController extends BaseController
{
    protected $rutas;
    protected $url_api;
    protected $bitacora;

    public function initialize(){
        $config         = $this->getDI();
        $this->rutas    = $config->get('rutas');
        $config         = $config->get('config');
        $this->url_api  = $config['BASEAPI'];
        $this->bitacora = 'Usuarios';
    }

    public function IndexAction(){

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
                $route  = $this->url_api.$this->rutas['ctusuarios']['show'];
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

            if ($accion == 'get_permisos_tipo_usuario'){

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

        $route              = $this->url_api.$this->rutas['cttipo_usuarios']['show'];
        $arr_tipo_usuarios  = FuncionesGlobales::RequestApi('GET',$route,$_POST);

        $this->view->arr_tipo_usuarios  = $arr_tipo_usuarios; 
        $this->view->create = FuncionesGlobales::HasAccess("Usuarios","create");
        $this->view->update = FuncionesGlobales::HasAccess("Usuarios","update");
        $this->view->delete = FuncionesGlobales::HasAccess("Usuarios","delete");
    }

    public function createAction(){
        if ($this->request->isAjax()){
            $aqui   = 1;

            $route  = $this->url_api.$this->rutas['ctusuarios']['create'];
            $result = FuncionesGlobales::RequestApi('POST',$route,$_POST);
            $response = new Response();

            if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                $response->setStatusCode(404, 'Error');
                return $response;
            }

            $nombre = $_POST['primer_apellido'].' '.$_POST['segundo_apellido'].' '.$_POST['nombre'];
            FuncionesGlobales::saveBitacora($this->bitacora,'CREAR','Se creo el usuario: '.$nombre.' con numero de telefono: '.$_POST['celular'].' siendo tipo usuario: '.$_POST['label_tipo_usuario'].' con'.count($_POST['lista_permisos']).' permisos',$_POST);

            $response->setJsonContent('Captura exitosa');
            $response->setStatusCode(200, 'OK');
            return $response;
        }
    }

    public function updateAction(){
        if ($this->request->isAjax()){

            $accion = $this->request->getPost('accion');

            if ($this->request->hasPost('accion') && $this->request->getPost('accion') == 'change_status'){
                $route  = $this->url_api.$this->rutas['ctusuarios']['change_status'];
                $result = FuncionesGlobales::RequestApi('PUT',$route,$_POST);
                $response = new Response();
    
                if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                    $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                    $response->setStatusCode(404, 'Error');
                    return $response;
                }

                FuncionesGlobales::saveBitacora($this->bitacora,'EDITAR','Se mando modificar el estatus del usuario: '.$_POST['clave'].' de '.$_POST['last_estatus'].' a '.$_POST['estatus']  ,$_POST);
            } else {
                $route  = $this->url_api.$this->rutas['ctusuarios']['update'];
                $result = FuncionesGlobales::RequestApi('PUT',$route,$_POST);
                $response = new Response();

                if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                    $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                    $response->setStatusCode(404, 'Error');
                    return $response;
                }

                FuncionesGlobales::saveBitacora($this->bitacora,'EDITAR','Se mando editar el tipo usuario: Clave antigua :'.$_POST['clave_old'].' por '.$_POST['clave'].' nombre antiguo: '.$_POST['nombre_old'].' permisos de '.count($_POST['permisos_old']).' a '.count($_POST['lista_permisos']),$_POST);
            }


            $response->setJsonContent('Captura exitosa');
            $response->setStatusCode(200, 'OK');
            return $response;
        }
    }
}