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
                    "recordsFiltered"   => 0,
                    "data"              => array()
                );
        
                // SE REALIZA LA BUSQUEDA DEL COUNT

                $route          = $this->url_api.$this->rutas['ctpacientes']['count'];
                $num_registros  = FuncionesGlobales::RequestApi('GET',$route,$_POST);
        
                if ($num_registros == 0){
                    $result = array(
                        "draw"              => $this->request->getPost('draw'),
                        "recordsTotal"      => count($result),
                        "recordsFiltered"   => 0,
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

            if ($accion == 'get_services'){
                $result = array(
                    'all_services'  => array(),
                    'info_paciente' => array(),
                    'info_agenda'   => array()
                );

                $route                  = $this->url_api.$this->rutas['ctservicios']['show'];
                $result['all_services'] = FuncionesGlobales::RequestApi('GET',$route,array('id_locacion' => $_POST['id_locacion_registro']));

                //  SE BUSCAN LOS REGISTROS DEL PACIENTE
                $route                      = $this->url_api.$this->rutas['ctpacientes']['get_program_date'];
                $result['info_paciente']    = FuncionesGlobales::RequestApi('GET',$route,array(
                    'id_paciente'   => $_POST['id_paciente'],
                    'id_locacion'   => $_POST['id_locacion']
                ));

                //  SE BUSCA LA INFORMACION DE LA AGENDA
                $route                  = $this->url_api.$this->rutas['tbapertura_agenda']['show'];
                $result['info_agenda']  = FuncionesGlobales::RequestApi('GET',$route,$_POST);

            }

            if ($accion == 'get_profesionales'){
                $route  = $this->url_api.$this->rutas['ctprofesionales']['show'];
                $result = FuncionesGlobales::RequestApi('GET',$route,$_POST);
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
        $route                  = $this->url_api.$this->rutas['ctlocaciones']['show'];
        $_POST['onlyallowed']   = 1;
        $arr_locaciones = FuncionesGlobales::RequestApi('GET',$route,$_POST);
        $this->view->arr_locaciones = $arr_locaciones;

        //  SE BUSCA VARIABLE DEL SISTEMA dias_programacion_citas
        $route                      = $this->url_api.$this->rutas['ctvariables_sistema']['show'];
        $dias_programacion_citas    = FuncionesGlobales::RequestApi('GET',$route,array(
            'clave' => 'dias_programacion_citas'
        ));

        if (!is_array($dias_programacion_citas)){
            $dias_programacion_citas    = 31;
        } else {
            $dias_programacion_citas    = $dias_programacion_citas[0]['valor'];
        }

        $this->view->dias_programacion_citas    = $dias_programacion_citas;

        $this->view->create = FuncionesGlobales::HasAccess("Pacientes","create");
        $this->view->update = FuncionesGlobales::HasAccess("Pacientes","update");
        $this->view->delete = FuncionesGlobales::HasAccess("Pacientes","delete");
        $this->view->preview    = FuncionesGlobales::HasAccess("Pacientes","preview");
        $this->view->schedule_appointments  = FuncionesGlobales::HasAccess("Pacientes","scheduleappointments");
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

    public function updateAction(){
        if ($this->request->isAjax()){
            $accion = $this->request->getPost('accion');
            $result = array();

            if($accion == 'save_program_date'){
                $route  = $this->url_api.$this->rutas['ctpacientes']['save_program_date'];
                $result = FuncionesGlobales::RequestApi('POST',$route,array(
                    'id_paciente'   => $_POST['id_paciente'],
                    'id_locacion'   => $_POST['id_locacion'],
                    'obj_info'      => $_POST['obj_info'],
                    'generar_citas' => $_POST['generar_citas'],
                    'fecha_inicio'  => $_POST['fecha_inicio'],
                    'fecha_termino' => $_POST['fecha_termino'],
                )); 
                
                $response = new Response();

                if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                    $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                    $response->setStatusCode(404, 'Error');
                    return $response;
                }

                $nombre     = $_POST['nombre_completo'];
                $servicios  = count($_POST['obj_info']);
                $msg_generar_citas  = '';

                if ($_POST['generar_citas']){
                    $msg_generar_citas  = ', y se programaron citas desde el ' . $_POST['fecha_inicio'].' Al '.$_POST['fecha_termino'];
                }

                FuncionesGlobales::saveBitacora($this->bitacora,'CITA PROGRAMADA','Se creo/modifico el registro de citas programadas para el paciente: '.$nombre.' el cual tendrá '.$servicios.' servicios programados'.$msg_generar_citas,$_POST);

                $response->setJsonContent('Captura exitosa');
                $response->setStatusCode(200, 'OK');
                return $response;
            }
        }
    }

    public function scheduleappointmentsAction(){

        if ($this->request->isAjax()){
            $accion = $this->request->getPost('accion');

            if ($accion == 'get_info_locacion'){
                $arr_return = array(
                    'horario_atencion'  => array()
                );
                $route              = $this->url_api.$this->rutas['tbhorarios_atencion']['get_opening_hours'];
                $horario_atencion   = FuncionesGlobales::RequestApi('GET',$route,array('id_locacion' => $_POST['id_locacion']));

                $response = new Response();

                if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400) || count($horario_atencion) == 0){
                    $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                    $response->setStatusCode(404, 'Error');
                    return $response;
                }

                $arr_return['horario_atencion'] = $horario_atencion;

                $arr_horas  = FuncionesGlobales::allStructureSchedule($horario_atencion);

                $arr_return['min_hora_inicio']      = $arr_horas['min_hora'];
                $arr_return['max_hora_inicio']      = $arr_horas['max_hora'];
                $arr_return['rangos_no_incluidos']  = $arr_horas['rangos_no_incluidos'];
                $tmp_json   = json_encode($arr_return['rangos_no_incluidos']);

                //  INFORMACION DE LOS SERVICIOS
                $route                      = $this->url_api.$this->rutas['ctservicios']['show'];
                $arr_return['all_services'] = FuncionesGlobales::RequestApi('GET',$route,array('id_locacion' => $info_paciente['id_locacion_registro']));

                $response->setJsonContent($arr_return);
                $response->setStatusCode(200, 'OK');
                return $response;
            }

            if ($accion == 'get_profesionales'){
                $route  = $this->url_api.$this->rutas['ctprofesionales']['show'];
                $result = FuncionesGlobales::RequestApi('GET',$route,$_POST);

                $response = new Response();
                $response->setJsonContent($result);
                $response->setStatusCode(200, 'OK');
                return $response;
            }

            if ($accion == 'get_horario_profesional'){
                $route              = $this->url_api.$this->rutas['tbhorarios_atencion']['get_opening_hours'];
                $horario_atencion_locacion  = FuncionesGlobales::RequestApi('GET',$route,array(
                    'id_locacion'           => $_POST['id_locacion']
                ));

                $horario_atencion_profesional   = FuncionesGlobales::RequestApi('GET',$route,array(
                    'id_locacion'           => $_POST['id_locacion'],
                    'id_profesional'        => $_POST['id_profesional'],
                ));

                $hora_cierre    = (INT) $_POST['max_hora_inicio'] + 1;
                $hora_cierre    = $hora_cierre.':00';

                $result = FuncionesGlobales::obtenerRangosNoDisponiblesPorDia($horario_atencion_locacion,$horario_atencion_profesional,$hora_cierre);

                $aqui = json_encode($result);

                $response = new Response();
                $response->setJsonContent($result);
                $response->setStatusCode(200, 'OK');
                return $response;
            }
        }

        $id_paciente    = $_GET['id'];

        if (empty($id_paciente) || !is_numeric($id_paciente)){
            $response   = $this->getDI()->get('response');
            // Redirigir a login/index si es una solicitud normal
            $response->redirect('Menu/route404');
            $response->send();
            exit;
        }

        //  SE BUSCA LA LOCACION POR DEFECTO DEL PACIENTE
        $route          = $this->url_api.$this->rutas['ctpacientes']['show'];
        $info_paciente  = FuncionesGlobales::RequestApi('GET',$route,array('id' => $id_paciente));

        if (count($info_paciente) == 0){
            $response   = $this->getDI()->get('response');
            // Redirigir a login/index si es una solicitud normal
            $response->redirect('Menu/route404');
            $response->send();
            exit;
        }

        $info_paciente  = $info_paciente[0];

        //  SE BUSCA LA INFORMACION DE LA LOCACION, ASI COMO SU HORARIO
        $route              = $this->url_api.$this->rutas['tbhorarios_atencion']['get_opening_hours'];
        $horario_atencion   = FuncionesGlobales::RequestApi('GET',$route,array('id_locacion' => $info_paciente['id_locacion_registro']));
        $this->view->horario_atencion   = $horario_atencion;

        $arr_horas  = FuncionesGlobales::allStructureSchedule($horario_atencion);

        $this->view->min_hora_inicio    = $arr_horas['min_hora'];
        $this->view->max_hora_inicio    = $arr_horas['max_hora'];
        $this->view->rangos_no_incluidos = json_encode($arr_horas['rangos_no_incluidos']);


        // HORARIO DE ATENCION PLANTEL
        $route                  = $this->url_api.$this->rutas['ctlocaciones']['show'];
        $_POST['onlyallowed']   = 1;
        $arr_locaciones = FuncionesGlobales::RequestApi('GET',$route,array());
        $this->view->arr_locaciones = $arr_locaciones;
        $aqui   = 1;

        $result = array(
            'all_services'  => array(),
            'info_paciente' => array(),
            'info_agenda'   => array()
        );

        //$route                  = $this->url_api.$this->rutas['ctservicios']['show'];
        //$result['all_services'] = FuncionesGlobales::RequestApi('GET',$route,array('id_locacion' => $info_paciente['id_locacion_registro']));

        //  SE BUSCAN LOS REGISTROS DEL PACIENTE
        $route                      = $this->url_api.$this->rutas['ctpacientes']['get_program_date'];
        $result['info_paciente']    = FuncionesGlobales::RequestApi('GET',$route,array(
            'id_paciente'   => $id_paciente,
            'id_locacion'   => $info_paciente['id_locacion_registro']
        ));

        //  SE BUSCA LA INFORMACION DE LA AGENDA
        $route                  = $this->url_api.$this->rutas['tbapertura_agenda']['show'];
        $result['info_agenda']  = FuncionesGlobales::RequestApi('GET',$route,$_POST);

        $aqui = 1;

    } 

}