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

                $route          = $this->url_api.$this->rutas['ctprofesionales']['count'];
                $num_registros  = FuncionesGlobales::RequestApi('GET',$route,$_POST);
        
                if ($num_registros == 0){
                    $result = array(
                        "draw"              => $this->request->getPost('draw'),
                        "recordsTotal"      => count($result),
                        "recordsFiltered"   => 10,
                        "data"              => $result
                    );
                }

                $route  = $this->url_api.$this->rutas['ctprofesionales']['show'];
                $_POST['location_allower']  = 1;
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
        $this->view->create = FuncionesGlobales::HasAccess("Profesionales","create");
        $this->view->update = FuncionesGlobales::HasAccess("Profesionales","update");
        $this->view->delete = FuncionesGlobales::HasAccess("Profesionales","delete");
        $this->view->preview    = FuncionesGlobales::HasAccess("Profesionales","preview");
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

    public function deleteAction(){
        if ($this->request->isAjax()){
            $route  = $this->url_api.$this->rutas['ctprofesionales']['delete'];
            $result = FuncionesGlobales::RequestApi('DELETE',$route,$_POST);
            $response = new Response();

            if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                $response->setStatusCode(404, 'Error');
                return $response;
            }

            FuncionesGlobales::saveBitacora($this->bitacora,'BORRAR','Se elimino el profesional '.$_POST['clave'],$_POST);

            $response->setJsonContent('Captura exitosa');
            $response->setStatusCode(200, 'OK');
            return $response;
        }
    }

    public function updateAction($id = null){
        if ($this->request->isAjax()){

            $accion = $this->request->getPost('accion');

            if (!empty($accion) && $this->request->getPost('accion') == 'change_status'){
                $route  = $this->url_api.$this->rutas['ctprofesionales']['change_status'];
                $result = FuncionesGlobales::RequestApi('PUT',$route,$_POST);
                $response = new Response();
    
                if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                    $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                    $response->setStatusCode(404, 'Error');
                    return $response;
                }

                FuncionesGlobales::saveBitacora($this->bitacora,'EDITAR','Se mando modificar el estatus del usuario: '.$_POST['clave'].' de '.$_POST['last_estatus'].' a '.$_POST['estatus']  ,$_POST);
            } 

            if (!empty($accion) && $accion == 'save_edit'){

                $route                  = $this->url_api.$this->rutas['ctprofesionales']['show'];
                $arr_info_profesional   = FuncionesGlobales::RequestApi('GET',$route,array(
                    'id' => $id,
                    'get_locaciones'    => 1,
                    'location_allower'  => 1,
                ));

                $route  = $this->url_api.$this->rutas['ctprofesionales']['update'];
                $result = FuncionesGlobales::RequestApi('PUT',$route,$_POST);
                $response = new Response();

                if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                    $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                    $response->setStatusCode(404, 'Error');
                    return $response;
                }
                $info   = $arr_info_profesional[0];
                $arr_save   = array(
                    'data_old'  => $info,
                    'new_data'  => $_POST
                );
                FuncionesGlobales::saveBitacora($this->bitacora,'EDITAR','Se edito el profesional: '.$_POST['current_clave'],$arr_save);
            }

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
                $arr_return = array(
                    'locacion'      => array(),
                    'profesional'   => array()
                );
                $route  = $this->url_api.$this->rutas['tbhorarios_atencion']['get_opening_hours'];
                $result = FuncionesGlobales::RequestApi('GET',$route,array('id_locacion' => $_POST['id_locacion']));
                $response = new Response();
    
                if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400) || count($result) == 0){
                    $response->setJsonContent(isset($result['error']) ? $result['error'] : 'Locaci&oacute;n sin horario de atenci&oacute;n asignado');
                    $response->setStatusCode(404, 'Error');
                    return $response;
                }

                $arr_return['locacion'] = $result;

                $route  = $this->url_api.$this->rutas['tbhorarios_atencion']['get_opening_hours'];
                $result = FuncionesGlobales::RequestApi('GET',$route,$_POST);
                $response = new Response();
    
                if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                    $response->setJsonContent(isset($result['error']) ? $result['error'] : 'Locaci&oacute;n sin horario de atenci&oacute;n asignado');
                    $response->setStatusCode(404, 'Error');
                    return $response;
                }

                $arr_return['profesional']  = $result;

                $response->setJsonContent($arr_return);
                $response->setStatusCode(200, 'OK');
                return $response;
            }

            if ($accion == 'get_locaciones'){
                $route                  = $this->url_api.$this->rutas['ctprofesionales']['show'];
                $arr_info_profesional   = FuncionesGlobales::RequestApi('GET',$route,array(
                    'id'                => $_POST['id'],
                    'location_allower'  => 1,
                    'only_locations'    => 1
                ));

                $response = new Response();
                $response->setJsonContent($arr_info_profesional[0]['locaciones']);
                $response->setStatusCode(200, 'OK');
                return $response;
            }


            $response->setJsonContent('Captura exitosa');
            $response->setStatusCode(200, 'OK');
            return $response;
        }

        if (!is_numeric($id)){
            $response   = $this->getDI()->get('response');
            // Redirigir a login/index si es una solicitud normal
            $response->redirect('Menu/route404');
            $response->send();
            exit;
        }

        //  SE BUSCAN LOS SERVICIOS QUE PUEDE OFRECER EL USUARIO
        $route          = $this->url_api.$this->rutas['ctlocaciones']['show'];
        $arr_locaciones= FuncionesGlobales::RequestApi('GET',$route,array('get_servicios' => 1,'onlyallowed' => 1));

        //  SE BUSCA LA INFORMACION DEL PERFIL DE PROFESIONAL
        $route              = $this->url_api.$this->rutas['cttipo_usuarios']['show'];
        $arr_tipo_usuario   = FuncionesGlobales::RequestApi('GET',$route,array('clave' => 'PROF'));

        $this->view->arr_locaciones     = $arr_locaciones;
        $this->view->arr_tipo_usuario   = $arr_tipo_usuario[0];

        //  SE BUSCA LA INFORMACION DEL PROFESIONAL
        $route                  = $this->url_api.$this->rutas['ctprofesionales']['show'];
        $arr_info_profesional   = FuncionesGlobales::RequestApi('GET',$route,array(
            'id' => $id,
            'get_locaciones'    => 1,
            'location_allower'  => 1
        ));

        if (!is_array($arr_info_profesional) || count($arr_info_profesional) == 0){
            $response   = $this->getDI()->get('response');
            // Redirigir a login/index si es una solicitud normal
            $response->redirect('Menu/route404');
            $response->send();
            exit;
        }

        $this->view->id = $id;
        $this->view->arr_info_profesional       = $arr_info_profesional[0];

    }

    public function previewAction($id = null){
        if (!is_numeric($id)){
            $response   = $this->getDI()->get('response');
            // Redirigir a login/index si es una solicitud normal
            $response->redirect('Menu/route404');
            $response->send();
            exit;
        }

        //  SE BUSCAN LOS SERVICIOS QUE PUEDE OFRECER EL USUARIO
        $route          = $this->url_api.$this->rutas['ctlocaciones']['show'];
        $arr_locaciones= FuncionesGlobales::RequestApi('GET',$route,array('get_servicios' => 1));

        //  SE BUSCA LA INFORMACION DEL PERFIL DE PROFESIONAL
        $route              = $this->url_api.$this->rutas['cttipo_usuarios']['show'];
        $arr_tipo_usuario   = FuncionesGlobales::RequestApi('GET',$route,array('clave' => 'PROF'));

        $this->view->arr_locaciones     = $arr_locaciones;
        $this->view->arr_tipo_usuario   = $arr_tipo_usuario[0];

        //  SE BUSCA LA INFORMACION DEL PROFESIONAL
        $route                  = $this->url_api.$this->rutas['ctprofesionales']['show'];
        $arr_info_profesional   = FuncionesGlobales::RequestApi('GET',$route,array(
            'id' => $id,
            'get_locaciones'    => 1,
            'location_allower'  => 1
        ));

        if (!is_array($arr_info_profesional) || count($arr_info_profesional) == 0){
            $response   = $this->getDI()->get('response');
            // Redirigir a login/index si es una solicitud normal
            $response->redirect('Menu/route404');
            $response->send();
            exit;
        }

        $this->view->id = $id;
        $this->view->arr_info_profesional       = $arr_info_profesional[0];

    }
}