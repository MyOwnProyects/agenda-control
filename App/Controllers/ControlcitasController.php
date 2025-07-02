<?php

namespace App\Controllers;

use Phalcon\Mvc\Controller;
use Phalcon\Http\Request;  // Asegúrate de importar la clase Request
use Phalcon\Http\Response; // Asegúrate de importar la clase Response
use App\Library\FuncionesGlobales;

class ControlcitasController extends BaseController
{
    protected $rutas;
    protected $url_api;
    protected $bitacora;

    public function initialize(){
        $config         = $this->getDI();
        $this->rutas    = $config->get('rutas');
        $config         = $config->get('config');
        $this->url_api  = $config['BASEAPI'];
        $this->bitacora = 'Controlcitas';
    }

    public function indexAction(){

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

                $route          = $this->url_api.$this->rutas['tbagenda_citas']['count'];
                $num_registros  = FuncionesGlobales::RequestApi('GET',$route,$_POST);
        
                if ($num_registros == 0){
                    $result = array(
                        "draw"              => $this->request->getPost('draw'),
                        "recordsTotal"      => count($result),
                        "recordsFiltered"   => 10,
                        "data"              => $result
                    );
                }

                $route  = $this->url_api.$this->rutas['tbagenda_citas']['show'];
                $result = FuncionesGlobales::RequestApi('GET',$route,$_POST);
        
                $result = array(
                    "draw"              => $this->request->getPost('draw'),
                    "recordsTotal"      => $num_registros,
                    "recordsFiltered"   => $num_registros,
                    "data"              => $result
                );
        
            }

            if ($accion == 'fill_combo'){
                $route      = $this->url_api.$this->rutas['ctpacientes']['fill_combo'];
                $arr_info   = FuncionesGlobales::RequestApi('GET',$route,$_POST);

                $response = new Response();
                $response->setJsonContent($arr_info);
                $response->setStatusCode(200, 'OK');
                return $response;
            }

            if ($accion == 'get_date'){
                $route      = $this->url_api.$this->rutas['tbapertura_agenda']['show'];
                $arr_info           = FuncionesGlobales::RequestApi('GET',$route,$_POST);

                $response = new Response();
                $response->setJsonContent($arr_info);
                $response->setStatusCode(200, 'OK');
                return $response;
            }

            if ($accion == 'save_agenda_opening'){
                $obj_info   = $_POST['obj_info'];
                $route      = $this->url_api.$this->rutas['tbapertura_agenda']['save'];
                $result     = FuncionesGlobales::RequestApi('POST',$route,$obj_info);

                $response = new Response();

                if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                    $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                    $response->setStatusCode(404, 'Error');
                    return $response;
                }

                FuncionesGlobales::saveBitacora($this->bitacora,'CREAR','Se realizó la apertura de agenda para la locaci&oacuite;n: '.$obj_info['nombre'].' con rango de fechas del : '.$obj_info['fecha_inicio'].' al '.$obj_info['fecha_limite'],$obj_info);

                $response->setJsonContent('Apertura de agenda exitosa!');
                $response->setStatusCode(200, 'OK');
                return $response;
            }

            if ($accion == 'cancelar_cita'){
                $route      = $this->url_api.$this->rutas['tbagenda_citas']['cancelar_cita'];
                $result     = FuncionesGlobales::RequestApi('DELETE',$route,$_POST);

                $response = new Response();

                if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                    $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                    $response->setStatusCode(404, 'Error');
                    return $response;
                }

                FuncionesGlobales::saveBitacora($this->bitacora,'DELETE','Se realizó la cancelación de la cita con identificar: '.$_POST['id_agenda_cita'],$obj_info);

                $response->setJsonContent('Cancelacion exitosa!');
                $response->setStatusCode(200, 'OK');
                return $response;
            }

            if ($accion == 'fill_profesionales'){
                $route      = $this->url_api.$this->rutas['ctprofesionales']['show'];
                $arr_info   = FuncionesGlobales::RequestApi('GET',$route,$_POST);

                $response = new Response();
                $response->setJsonContent($arr_info);
                $response->setStatusCode(200, 'OK');
                return $response;
            }

            if ($accion == 'modificar_asistencia'){
                $route      = $this->url_api.$this->rutas['tbagenda_citas']['modificar_asistencia'];
                $result     = FuncionesGlobales::RequestApi('PUT',$route,$_POST);

                $response = new Response();

                if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                    $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                    $response->setStatusCode(404, 'Error');
                    return $response;
                }

                FuncionesGlobales::saveBitacora($this->bitacora,'DELETE','Se realizó la cancelación de la cita con identificar: '.$_POST['id_agenda_cita'],$obj_info);

                $response->setJsonContent('Cancelacion exitosa!');
                $response->setStatusCode(200, 'OK');
                return $response;
            }

            if ($accion == 'get_info_locacion'){
                $arr_return = $this->get_info_by_location();

                if (!is_array($arr_return)){
                    $response = new Response();
                    $response->setJsonContent($arr_return);
                    $response->setStatusCode(404, 'Error');
                    return $response;
                }

                //  SE RECORREN TODOS LOS PROFESIONALES PARA 
                //  BUSCAR SU HORARIO DE ATENCION
                $route  = $this->url_api.$this->rutas['tbhorarios_atencion']['get_opening_hours'];
                foreach($arr_return['all_professionals'] as $index => $profesional){
                    $horario_atencion_profesional   = FuncionesGlobales::RequestApi('GET',$route,array(
                        'id_locacion'           => $_POST['id_locacion'],
                        'id_profesional'        => $profesional['id']
                    ));

                    $hora_cierre    = (INT) $arr_return['max_hora_inicio'] + 1;
                    $hora_cierre    = $hora_cierre.':00';

                    $result = array(
                        'rango_no_disponible'   => array(),
                        'citas_programadas'     => array()
                    );

                    $arr_return['all_professionals'][$index]['rango_no_disponible']  = FuncionesGlobales::obtenerRangosNoDisponiblesPorDia($arr_return['horario_atencion'],$horario_atencion_profesional,$hora_cierre);
                }


                $response = new Response();
                $response->setJsonContent($arr_return);
                $response->setStatusCode(200, 'OK');
                return $response;
            }

            if ($accion == 'save_appoinment'){
                $route      = $this->url_api.$this->rutas['tbagenda_citas']['save'];
                $result     = FuncionesGlobales::RequestApi('POST',$route,$_POST['obj_info']);

                $response = new Response();

                if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                    $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                    $response->setStatusCode(404, 'Error');
                    return $response;
                }
                $nombre_paciente    = $_POST['primer_apellido'].' '. $_POST['segundo_apellido'].' '.$_POST['nombre'];
                FuncionesGlobales::saveBitacora($this->bitacora,'CREATE','Se programo la cita para el paciente: '.$nombre_paciente,$_PST['obj_info']);

                $response->setJsonContent('Captura exitosa!');
                $response->setStatusCode(200, 'OK');
                return $response;
            }

            $response = new Response();
            $response->setJsonContent($result);
            $response->setStatusCode(200, 'OK');
            return $response;
        }

        //  SE BUSCAN LOS SERVICIOS QUE PUEDE OFRECER EL USUARIO
        $route          = $this->url_api.$this->rutas['ctlocaciones']['show'];
        $arr_locaciones= FuncionesGlobales::RequestApi('GET',$route,array());

        $this->view->arr_locaciones     = $arr_locaciones; 
        $this->view->apertura_agenda    = FuncionesGlobales::HasAccess("Controlcitas","agenda_opening");

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

        //  OBTENER MARGEN DE MINUTOS PARA EMPALADOS
        $route                      = $this->url_api.$this->rutas['ctvariables_sistema']['show'];
        $margen_minutos_empalmado   = FuncionesGlobales::RequestApi('GET',$route,array(
            'clave' => 'margen_minutos_empalmado'  
        ));

        if (!is_array($margen_minutos_empalmado)){
            $margen_minutos_empalmado   = 0;
        } else {
            $margen_minutos_empalmado   = $margen_minutos_empalmado[0]['valor'];
        }

        $this->view->margen_minutos_empalmado   = $margen_minutos_empalmado;

        //  MOTIVOS PARA CANCELAR UNA CITA
        $route                      = $this->url_api.$this->rutas['ctmotivos_cancelacion_cita']['show'];
        $motivos_cancelacion_cita   = FuncionesGlobales::RequestApi('GET',$route);
        $this->view->motivos_cancelacion_cita   = $motivos_cancelacion_cita;
    }

    function get_info_by_location(){
        $arr_return = array(
            'horario_atencion'  => array()
        );
        $route              = $this->url_api.$this->rutas['tbhorarios_atencion']['get_opening_hours'];
        $horario_atencion   = FuncionesGlobales::RequestApi('GET',$route,array('id_locacion' => $_POST['id_locacion']));

        $response = new Response();

        if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400) || count($horario_atencion) == 0){
            return 'No existe un horario de atenci&oacute;n registrado a la locaci&oacute;n';
        }

        $arr_return['horario_atencion'] = $horario_atencion;

        $arr_horas  = FuncionesGlobales::allStructureSchedule($horario_atencion);

        $arr_return['min_hora_inicio']      = $arr_horas['min_hora'];
        $arr_return['max_hora_inicio']      = $arr_horas['max_hora'];
        $arr_return['rangos_no_incluidos']  = $arr_horas['rangos_no_incluidos'];

        //  SE BUSCA LA ULTIMA FECHA DISPONIBLE ANTES DEL CIERRE DE AGENDA
        $route      = $this->url_api.$this->rutas['tbapertura_agenda']['show'];
        $arr_return['cierre_agenda']    = FuncionesGlobales::RequestApi('GET',$route,$_POST);

        //  INFORMACION DE LOS SERVICIOS
        $route                      = $this->url_api.$this->rutas['ctservicios']['show'];
        $arr_return['all_services'] = FuncionesGlobales::RequestApi('GET',$route,array('id_locacion' => $_POST['id_locacion']));

        // INFORMACION DE LOS PROFESIONALES
        $route                              = $this->url_api.$this->rutas['ctprofesionales']['show'];
        $arr_return['all_professionals']    = FuncionesGlobales::RequestApi('GET',$route,array('id_locacion' => $_POST['id_locacion'],'get_servicios' => true));

        //  SE BUSCAN LAS CITAS AGENDADAS EN EL RANGO DE FECHAS
        $route                          = $this->url_api.$this->rutas['tbagenda_citas']['show'];
        $_POST['activa']                = 1;
        $_POST['get_servicios']         = 1;
        $arr_return['citas_agendadas']  = FuncionesGlobales::RequestApi('GET',$route,$_POST);

        //  SI VIENEN VACIOS ESTOS ESPACIOS, SE UNIFICAN LAS HORAS PARA QUE SEA UN SOLO DIV CORRIDO
        // if (empty($_POST['id_profesional']) && empty($_POST['id_paciente'])) {
        //     $arr_return['citas_unificadas'] =   $this->unificar_citas_agendadas($arr_return['citas_agendadas']);
        // }
        
        return $arr_return;
    }
}