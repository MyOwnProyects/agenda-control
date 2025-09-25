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
        
                if (!is_numeric($num_registros) || $num_registros == 0){
                    $result = array(
                        "draw"              => $this->request->getPost('draw'),
                        "recordsTotal"      => count($result),
                        "recordsFiltered"   => 0,
                        "data"              => $result
                    );
                } else {
                    $route  = $this->url_api.$this->rutas['ctpacientes']['show'];
                    $result = FuncionesGlobales::RequestApi('GET',$route,$_POST);
            
                    $result = array(
                        "draw"              => $this->request->getPost('draw'),
                        "recordsTotal"      => $num_registros,
                        "recordsFiltered"   => $num_registros,
                        "data"              => $result
                    );
                }
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

        //  GET LISTA DE TRANSTORNOS
        $route              = $this->url_api.$this->rutas['cttranstornos_neurodesarrollo']['show'];
        $arr_transtornos    = FuncionesGlobales::RequestApi('GET',$route,$_POST);
        $this->view->arr_transtornos    = $arr_transtornos;

        //  GET LISTA DE SERVICIOS
        $route          = $this->url_api.$this->rutas['ctservicios']['show'];
        $arr_servicios  = FuncionesGlobales::RequestApi('GET',$route,$_POST);
        $this->view->arr_servicios      = $arr_servicios;

        //SE BUSCAS LAS LOCACIONES EXISTENTES
        $route                  = $this->url_api.$this->rutas['ctlocaciones']['show'];
        $_POST['onlyallowed']   = 1;
        $arr_locaciones = FuncionesGlobales::RequestApi('GET',$route,$_POST);
        $this->view->arr_locaciones = $arr_locaciones;

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
                    'obj_info'      => $_POST['obj_info']
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

                if (isset ($_POST['generar_citas']) && $_POST['generar_citas']){
                    $msg_generar_citas  = ', y se programaron citas desde el ' . $_POST['fecha_inicio'].' Al '.$_POST['fecha_termino'];
                }

                FuncionesGlobales::saveBitacora($this->bitacora,'CITA PROGRAMADA','Se creo/modifico el registro de citas programadas para el paciente: '.$nombre.' el cual tendrá '.$servicios.' servicios programados'.$msg_generar_citas,$_POST);

                $response->setJsonContent('Captura exitosa');
                $response->setStatusCode(200, 'OK');
                return $response;
            }

            if (!empty($accion) && $this->request->getPost('accion') == 'change_status'){
                $route  = $this->url_api.$this->rutas['ctpacientes']['change_status'];
                $result = FuncionesGlobales::RequestApi('PUT',$route,$_POST);
                $response = new Response();

                if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                    $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                    $response->setStatusCode(404, 'Error');
                    return $response;
                }

                $last_estatus   = $_POST['estatus'] == 1 ? 'ACTIVO' : 'INACTIVO';
                $new_estatus    = $_POST['estatus'] == 1 ? 'INACTIVO' : 'ACTIVO';

                FuncionesGlobales::saveBitacora($this->bitacora,'EDITAR','Se mando modificar el estatus del paciente: '.$_POST['nombre_completo'].' de '.$last_estatus.' a '.$new_estatus.' el cual contaba con '.$_POST['num_servicios'].' servicios(s) asignado(s)'  ,$_POST);

                $response->setJsonContent('Edición exitosa');
                $response->setStatusCode(200, 'OK');
                return $response;
            } 

            if (!empty($accion) && $this->request->getPost('accion') == 'save_express'){
                $route  = $this->url_api.$this->rutas['ctpacientes']['save_express'];
                $result = FuncionesGlobales::RequestApi('PUT',$route,$_POST['obj_info']);
                $response = new Response();
    
                if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                    $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                    $response->setStatusCode(404, 'Error');
                    return $response;
                }

                FuncionesGlobales::saveBitacora($this->bitacora,'EDITAR','Se mando modificar la información del paciente: '.$_POST['data_old']['clave'],$_POST);

                $response->setJsonContent('Edición exitosa');
                $response->setStatusCode(200, 'OK');
                return $response;
            }

            if (!empty($accion) && $accion == 'save_diagnoses'){
                $route  = $this->url_api.$this->rutas['ctpacientes']['save_diagnoses'];
                $result = FuncionesGlobales::RequestApi('POST',$route,$_POST);
                $response = new Response();
    
                if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                    $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                    $response->setStatusCode(404, 'Error');
                    return $response;
                }

                $solicitud  = count($_POST['data_old']) == 0 ? 'CREAR' : 'EDITAR';  
                FuncionesGlobales::saveBitacora($this->bitacora,$solicitud,'Se ejecuto la '.$solicitud.' del diagnostico del paciente: '.$_POST['data_old']['nombre_completo'],$_POST);

                $response->setJsonContent('Edición exitosa');
                $response->setStatusCode(200, 'OK');
                return $response;
            }
        }
    }

    public function scheduleappointmentsAction(){

        if ($this->request->isAjax()){
            $accion = $this->request->getPost('accion');

            if ($accion == 'get_horario_atencion'){

                $route          = $this->url_api.$this->rutas['ctlocaciones']['show'];
                $arr_locacion   = FuncionesGlobales::RequestApi('GET',$route,array('id' => $_POST['id_locacion']));
                $arr_locacion   = $arr_locacion[0];

                $route              = $this->url_api.$this->rutas['tbhorarios_atencion']['get_opening_hours'];
                $horario_atencion   = FuncionesGlobales::RequestApi('GET',$route,array('id_locacion' => $_POST['id_locacion']));
                
                $response = new Response();

                if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                    $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                    $response->setStatusCode(404, 'Error');
                    return $response;
                }

                if (count($horario_atencion) == 0){
                    $response->setJsonContent('Locación sin horario de atención asignado');
                    $response->setStatusCode(404, 'Error');
                    return $response;
                }

                $arr_return     = array();
                $citas_paciente = $this->get_citas_programadas(array('id_paciente'   => $_POST['id_paciente']));
                $citas_por_locacion = array();
                foreach($horario_atencion as $id => $horario){
                    $arr_return[$id]            = FuncionesGlobales::allStructureSchedule(array($horario));
                    $arr_return[$id]['titulo']          = $horario['titulo'];
                    $arr_return[$id]['intervalo_citas'] = $arr_locacion['intervalo_citas'];
                    $arr_return[$id]['id']              = $horario['id'];

                    //  FILTRA DE LAS CITAS DEL PACIENTE, LAS QUE CORRESPONDAN POR HORARIO
                    $arr_return[$id]['citas_paciente']  = FuncionesGlobales::AppoitmentByLocation($citas_paciente,$horario);
                }

                $response->setJsonContent($arr_return);
                $response->setStatusCode(200, 'OK');
                return $response;
            }

            if ($accion == 'get_date'){
                $route      = $this->url_api.$this->rutas['tbapertura_agenda']['show'];
                $arr_info   = FuncionesGlobales::RequestApi('GET',$route,$_POST);

                $response = new Response();
                $response->setJsonContent($arr_info);
                $response->setStatusCode(200, 'OK');
                return $response;
            }

            if ($accion == 'save_generate_agenda'){
                $obj_info   = $_POST['obj_info'];
                $route      = $this->url_api.$this->rutas['tbapertura_agenda']['save'];
                $result     = FuncionesGlobales::RequestApi('POST',$route,$obj_info);

                $response = new Response();

                if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                    $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                    $response->setStatusCode(404, 'Error');
                    return $response;
                }

                FuncionesGlobales::saveBitacora($this->bitacora,'PROGRAMAR CITAS','Se realizó la programacion de citas para el paciente: '.$obj_info['nombre_completo'].' con rango de fechas del : '.$obj_info['fecha_inicio'].' al '.$obj_info['fecha_termino'].' Mensaje de ejecución: '.$result['MSG'],$obj_info);

                $response->setJsonContent($result['MSG']);
                $response->setStatusCode(200, 'OK');
                return $response;
            }

            if ($accion == 'get_info_locacion'){

                $route          = $this->url_api.$this->rutas['ctlocaciones']['show'];
                $arr_locacion   = FuncionesGlobales::RequestApi('GET',$route,array('id' => $_POST['id_locacion']));
                $arr_locacion   = $arr_locacion[0];

                $arr_return = array(
                    'horario_atencion'  => array()
                );
                $route              = $this->url_api.$this->rutas['tbhorarios_atencion']['get_opening_hours'];
                $horario_atencion   = FuncionesGlobales::RequestApi('GET',$route,array('id_locacion' => $_POST['id_locacion'],'id' => $_POST['id_horario_atencion']));

                $response = new Response();

                if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400) || count($horario_atencion) == 0){
                    $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                    $response->setStatusCode(404, 'Error');
                    return $response;
                }

                $arr_return['horario_atencion'] = $horario_atencion;

                $arr_horas  = FuncionesGlobales::allStructureSchedule($horario_atencion);

                $arr_return['intervalo_citas']      = $arr_locacion['intervalo_citas'];
                $arr_return['min_hora_inicio']      = $arr_horas['min_hora_inicio'];
                $arr_return['max_hora_inicio']      = $arr_horas['max_hora_termino'];
                $arr_return['rangos_no_incluidos']  = $arr_horas['rangos_no_incluidos'];
                $tmp_json   = json_encode($arr_return['rangos_no_incluidos']);

                //  INFORMACION DE LOS SERVICIOS
                $route                      = $this->url_api.$this->rutas['ctservicios']['show'];
                $arr_return['all_services'] = FuncionesGlobales::RequestApi('GET',$route,array('id_locacion' => $_POST['id_locacion']));

                $response = new Response();
                if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400) || count($arr_return['all_services']) == 0){
                    $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                    $response->setStatusCode(404, 'Error');
                    return $response;
                }

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
                    'id_locacion'   => $_POST['id_locacion'],
                    'id'            => $_POST['id_horario_atencion']
                ));

                $horario_atencion_profesional   = FuncionesGlobales::RequestApi('GET',$route,array(
                    'id_locacion'           => $_POST['id_locacion'],
                    'id_profesional'        => $_POST['id_profesional'],
                ));

                $response = new Response();
                if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400) || count($horario_atencion_profesional) == 0){
                    $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                    $response->setStatusCode(404, 'Error');
                    return $response;
                }

                $hora_cierre    = (INT) $_POST['max_hora_inicio'] + 1;
                $hora_cierre    = $hora_cierre.':00';

                $result = array(
                    'rango_no_disponible'   => array(),
                    'citas_programadas'     => array()
                );

                $result['rango_no_disponible']  = FuncionesGlobales::obtenerRangosNoDisponiblesPorDia($horario_atencion_locacion,$horario_atencion_profesional,$hora_cierre);

                //  SE BUSCAN LAS CITAS PROGRAMADAS DEL PROFESIONAL
                $result['citas_paciente']   = $this->get_citas_programadas(array(
                    'id_locacion'           => $_POST['id_locacion'],
                    'id_profesional'        => $_POST['id_profesional'],
                ));

                $response = new Response();
                $response->setJsonContent($result);
                $response->setStatusCode(200, 'OK');
                return $response;
            }

            if($accion == 'save_program_date'){
                $route  = $this->url_api.$this->rutas['ctpacientes']['save_program_date'];
                $result = FuncionesGlobales::RequestApi('POST',$route,array(
                    'id_paciente'   => $_POST['id_paciente'],
                    'id_locacion'   => $_POST['id_locacion'],
                    'obj_info'      => $_POST['obj_info'],
                    'id_cita_programada_servicio_horario'   => $_POST['id_cita_programada_servicio_horario'] ?? null
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

                if (isset($_POST['generar_citas']) && $_POST['generar_citas']){
                    $msg_generar_citas  = ', y se programaron citas desde el ' . $_POST['fecha_inicio'].' Al '.$_POST['fecha_termino'];
                }

                FuncionesGlobales::saveBitacora($this->bitacora,'CITA PROGRAMADA','Se creo/modifico el registro de citas programadas para el paciente: '.$nombre.' el cual tendrá '.$servicios.' servicios programados'.$msg_generar_citas,$_POST);

                $route              = $this->url_api.$this->rutas['tbhorarios_atencion']['get_opening_hours'];
                $horario_atencion   = FuncionesGlobales::RequestApi('GET',$route,array('id_locacion' => $_POST['id_locacion'],'id' => $_POST['id_horario_atencion']));
                $horario_atencion   = $horario_atencion[0];

                //  OBTENER NUEVO HORARIO DEL PACIENTE
                //  SE BUSCAN LOS REGISTROS DEL PACIENTE
                $citas_paciente = $this->get_citas_programadas(array('id_paciente'   => $_POST['id_paciente']));
                $citas_paciente = FuncionesGlobales::AppoitmentByLocation($citas_paciente,$horario_atencion);

                $response->setJsonContent($citas_paciente);
                $response->setStatusCode(200, 'OK');
                return $response;
            }
            
            if ($accion == 'delete_program_date'){
                $route  = $this->url_api.$this->rutas['ctpacientes']['delete_program_date'];
                $result = FuncionesGlobales::RequestApi('POST',$route,$_POST); 
                
                $response = new Response();

                if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                    $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                    $response->setStatusCode(404, 'Error');
                    return $response;
                }

                $nombre     = $_POST['nombre_completo'];
                $label_cita = $_POST['label_servicio'].' el día '.$_POST['label_horario'];

                FuncionesGlobales::saveBitacora($this->bitacora,'CITA PROGRAMADA','Se elimino el registro de cita programada para el paciente: '.$nombre.' la cual era: '.$label_cita,$_POST);

                $response->setJsonContent('Captura exitosa');
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

        $this->view->id_paciente        = $info_paciente['id'];
        $this->view->nombre_completo    = $info_paciente['nombre_completo']."(".$info_paciente['edad_actual'].")" ;

        //  SE BUSCA LA INFORMACION DE LA LOCACION, ASI COMO SU HORARIO
        // $route              = $this->url_api.$this->rutas['tbhorarios_atencion']['get_opening_hours'];
        // $horario_atencion   = FuncionesGlobales::RequestApi('GET',$route,array('id_locacion' => $info_paciente['id_locacion_registro']));
        $this->view->horario_atencion   = array();

       // $arr_horas  = FuncionesGlobales::allStructureSchedule($horario_atencion);

        $this->view->min_hora_inicio    = '00:00';
        $this->view->max_hora_inicio    = '00:00';
        $this->view->rangos_no_incluidos = json_encode(array());


        // PLANTELES PERMITIDOS AL USUARIO
        $route                  = $this->url_api.$this->rutas['ctlocaciones']['show'];
        $_POST['onlyallowed']   = 1;
        $arr_locaciones = FuncionesGlobales::RequestApi('GET',$route,$_POST);
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
        $citas_paciente = $this->get_citas_programadas(array('id_paciente'   => $id_paciente));

        $this->view->citas_paciente = json_encode($citas_paciente);

        //  SE BUSCA LA INFORMACION DE LA AGENDA
        $route                  = $this->url_api.$this->rutas['tbapertura_agenda']['show'];
        $result['info_agenda']  = FuncionesGlobales::RequestApi('GET',$route,$_POST);

        $aqui = 1;

    } 

    function get_citas_programadas($params){
        $route                      = $this->url_api.$this->rutas['ctpacientes']['get_program_date'];
        $result['info_paciente']    = FuncionesGlobales::RequestApi('GET',$route,$params);

        $citas_paciente = array();
        $dias_semana    = ["Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado","Domingo"];
        foreach($result['info_paciente'] as $id_cita_programada_servicio => $info_citas){
            foreach($info_citas['horarios'] as $horario){
                $citas_paciente[]       = array(
                    'start' => $horario['hora_inicio'],
                    'end'   => $horario['hora_termino'],
                    'day'   => $dias_semana[$horario['dia'] - 1],
                    'servicio'      => $info_citas['servicio'],
                    'profesional'   => $info_citas['profesional'],
                    'duracion'      => $info_citas['duracion'],
                    'id_cita_programada_servicio'           => $id_cita_programada_servicio,
                    'id_cita_programada_servicio_horario'   => $horario['id_cita_programada_servicio_horario'],
                    'nombre_locacion'                       => $info_citas['nombre_locacion'],
                    'codigo_color'                          => $info_citas['codigo_color']
                );
            }
        }

        return $citas_paciente;
    }

    public function deleteAction(){
        if ($this->request->isAjax()){
            $route  = $this->url_api.$this->rutas['ctpacientes']['delete'];
            $result = FuncionesGlobales::RequestApi('DELETE',$route,$_POST);
            $response = new Response();

            if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                $response->setStatusCode(404, 'Error');
                return $response;
            }

            FuncionesGlobales::saveBitacora($this->bitacora,'ELIMINAR','Se elimino el registro del paciente: '.$_POST['nombre_completo'],$_POST);

            $response->setJsonContent('Borrado exitoso');
            $response->setStatusCode(200, 'OK');
            return $response;
        }
    }

    public function digitalRecordAction(){
        
    }

}