<?php

namespace App\Controllers;

use Phalcon\Mvc\Controller;
use Phalcon\Http\Request;  // Asegúrate de importar la clase Request
use Phalcon\Http\Response; // Asegúrate de importar la clase Response
use App\Library\FuncionesGlobales;

class BloqueoagendaController extends BaseController
{
    protected $rutas;
    protected $url_api;
    protected $bitacora;

    public function initialize(){
        $config         = $this->getDI();
        $this->rutas    = $config->get('rutas');
        $config         = $config->get('config');
        $this->url_api  = $config['BASEAPI'];
        $this->bitacora = 'Bloqueoagenda';
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

                $route          = $this->url_api.$this->rutas['tbfechas_bloqueo_agenda']['count'];
                $num_registros  = FuncionesGlobales::RequestApi('GET',$route,$_POST);
        
                if (!is_numeric($num_registros) || $num_registros == 0){
                    $result = array(
                        "draw"              => $this->request->getPost('draw'),
                        "recordsTotal"      => count($result),
                        "recordsFiltered"   => 0,
                        "data"              => $result
                    );
                } else {
                    $route  = $this->url_api.$this->rutas['tbfechas_bloqueo_agenda']['show'];
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
                $route      = $this->url_api.$this->rutas['tbfechas_bloqueo_agenda']['show'];
                $arr_return = FuncionesGlobales::RequestApi('GET',$route,array("id" => $_POST['id']));

                $result = $arr_return;
            }

            if ($accion == 'verificar_citas'){
                // SE REALIZA LA BUSQUEDA DEL COUNT
                $arr_return = array();

                //  SE BUSCAN LOS PERMISOS ASIGNADOS AL USUARIO
                $route      = $this->url_api.$this->rutas['tbagenda_citas']['count'];
                $arr_return = FuncionesGlobales::RequestApi('GET',$route,$_POST['obj_info']);

                $result = $arr_return;
            }

            $response = new Response();
            $response->setJsonContent($result);
            $response->setStatusCode(200, 'OK');
            return $response;
        }

        $this->view->create = FuncionesGlobales::HasAccess("Bloqueoagenda","create");
        $this->view->update = FuncionesGlobales::HasAccess("Bloqueoagenda","update");
        $this->view->delete = FuncionesGlobales::HasAccess("Bloqueoagenda","delete");

        // INFORMACION DE LOS PROFESIONALES
        $route              = $this->url_api.$this->rutas['ctprofesionales']['show'];
        $all_professionals  = FuncionesGlobales::RequestApi('GET',$route,array());

        $this->view->all_professionals  = $all_professionals;

        //  LOCACIONES
        $route                  = $this->url_api.$this->rutas['ctlocaciones']['show'];
        $_POST['onlyallowed']   = 1;
        $arr_locaciones = FuncionesGlobales::RequestApi('GET',$route,$_POST);
        $this->view->arr_locaciones = $arr_locaciones;

        //  MOTIVOS PARA BLOQUEAR LA AGENDA POR PERSONAL
        $route                  = $this->url_api.$this->rutas['ctmotivos_cancelacion_cita']['show'];
        $motivos_bloqueo_agenda = FuncionesGlobales::RequestApi('GET',$route,array('bloqueo_agenda' => 1));

        $this->view->motivos_bloqueo_agenda  = $motivos_bloqueo_agenda;
    }

    public function createAction(){
        if ($this->request->isAjax()){
            $accion = $this->request->getPost('accion');
            $num_citas_afectadas    = $_POST['num_citas_afectadas'];
            $_POST                  = $_POST['obj_info'];
            $_POST['accion']        = 'create';

            $route  = $this->url_api.$this->rutas['tbfechas_bloqueo_agenda']['save'];
            $result = FuncionesGlobales::RequestApi('POST',$route,$_POST); 
            
            $response = new Response();

            if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                $response->setStatusCode(404, 'Error');
                return $response;
            }

            if ($accion == 'create_dia_inhabil'){
                FuncionesGlobales::saveBitacora($this->bitacora,'CREAR','Se creo el registro de día inhabil para la fecha: '.$_POST['fecha_inicio'].' con la descripción:'.$_POST['label_bloqueo'].' afectando: '.$num_citas_afectadas.' cita(s) activa(s)' ,$_POST);
            }

            if ($accion == 'create_dia_inhabil_profesional'){
                FuncionesGlobales::saveBitacora($this->bitacora,'CREAR','Se creo el registro por motivo: '.$_POST['label_bloqueo'].' para el profesional: '.$_POST['nombre_profesional'].' en el rango de fecha: '.$_POST['fecha_inicio'].' - '.$_POST['fecha_termino'].' afectando: '.$num_citas_afectadas.' cita(s) activa(s)' ,$_POST);
            }

            $response->setJsonContent('Captura exitosa');
            $response->setStatusCode(200, 'OK');
            return $response;

        }
    }

    public function updateAction(){
        if ($this->request->isAjax()){
            $accion             = $this->request->getPost('accion');
            $num_citas_afectadas    = $_POST['num_citas_afectadas'];
            $data_old               = $_POST['data_old'];
            $_POST                  = $_POST['obj_info'];
            $_POST['accion']        = 'update';

            $route      = $this->url_api.$this->rutas['tbfechas_bloqueo_agenda']['save'];
            $result     = FuncionesGlobales::RequestApi('POST',$route,$_POST); 
            
            $response = new Response();

            if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                $response->setStatusCode(404, 'Error');
                return $response;
            }

            if ($accion == 'update_dia_inhabil'){
                FuncionesGlobales::saveBitacora($this->bitacora,'EDITAR','Se edito el registro de día inhabil de la fecha: '.$data_old["fecha_inicio"].' a '.$_POST['fecha_inicio'].' de la descripción: '.$data_old["label_bloqueo"].' a '.$_POST['label_bloqueo'].' afectando: '.$num_citas_afectadas.' cita(s) activa(s)' ,$_POST);
            }

            if ($accion == 'update_dia_inhabil_profesional'){
                FuncionesGlobales::saveBitacora($this->bitacora,'EDITAR','Se edito el registro de '.$data_old["label_bloqueo"].' a '.$_POST['label_bloqueo'].' del profesional: '.$_POST["nombre_profesional"].' de las fechas: '.$data_old["fecha_inicio"].' - '.$data_old["fecha_termino"].' a '.$_POST['fecha_inicio'].' - '.$_POST['fecha_termino'].' afectando: '.$num_citas_afectadas.' cita(s) activa(s)' ,$_POST);
            }

            $response->setJsonContent('Edición exitosa');
            $response->setStatusCode(200, 'OK');
            return $response;

        }
    }

    public function deleteAction(){
        if ($this->request->isAjax()){
            $accion = $this->request->getPost('accion');
            if ($accion == 'delete_dia_inhabil'){
                $data_old   = $_POST['data_old'];
                //$_POST      = $_POST['obj_info'];
                $route      = $this->url_api.$this->rutas['tbfechas_bloqueo_agenda']['delete'];
                $result     = FuncionesGlobales::RequestApi('POST',$route,$_POST); 
                
                $response = new Response();

                if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                    $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                    $response->setStatusCode(404, 'Error');
                    return $response;
                }
                FuncionesGlobales::saveBitacora($this->bitacora,'BORRAR','Se elimino el registro de día inhabil de la fecha: '.$data_old["fecha_inicio"].' con la descripción: '.$data_old["label_bloqueo"] ,$_POST);

                $response->setJsonContent('Borrado exitosa');
                $response->setStatusCode(200, 'OK');
                return $response;
            }

        }
    }
}