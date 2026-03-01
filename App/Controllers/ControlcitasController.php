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
                    "recordsFiltered"   => 20,
                    "data"              => array()
                );
        
                // SE REALIZA LA BUSQUEDA DEL COUNT

                $route          = $this->url_api.$this->rutas['tbagenda_citas']['count'];
                $num_registros  = FuncionesGlobales::RequestApi('GET',$route,$_POST);
        
                if (!is_numeric($num_registros) || $num_registros == 0){
                    $result = array(
                        "draw"              => $this->request->getPost('draw'),
                        "recordsTotal"      => count($result),
                        "recordsFiltered"   => 0,
                        "data"              => $result
                    );
                } else {
                    $route  = $this->url_api.$this->rutas['tbagenda_citas']['show'];
                    $result = FuncionesGlobales::RequestApi('GET',$route,$_POST);
            
                    $result = array(
                        "draw"              => $this->request->getPost('draw'),
                        "recordsTotal"      => $num_registros,
                        "recordsFiltered"   => $num_registros,
                        "data"              => $result
                    );
                }
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

                FuncionesGlobales::saveBitacora($this->bitacora,'CREARAPERTURA','Se realizó la apertura de agenda para la locaci&oacute;n: '.$obj_info['nombre_locacion'].' con rango de fechas del : '.$obj_info['fecha_inicio'].' al '.$obj_info['fecha_termino'].' Mensaje de ejecución: '.$result['MSG'],$obj_info);
                FuncionesGlobales::deleteCacheByPattern('info_location_');

                $response->setJsonContent($result['MSG']);
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

                $mensaje_inicial    = $_POST['tipo_movimiento'] == 'cancelar' ? 'Se realizó la cancelación de la cita: ' : 'Se marco la cita como pendiente a reagendar: ';
                $accion             = $_POST['tipo_movimiento'] == 'cancelar' ? 'BORRAR' : 'EDITAR';

                if (isset($_POST['multiappoitment']) && $_POST['multiappoitment']){
                    $mensaje_inicial    = $_POST['tipo_movimiento'] == 'cancelar' ? 'Se realizó la cancelación masiva de '.count($_POST['arr_id_agenda_cita']).' citas' : 'Se marcaron como pendientes de reagendar de forma masiva : '.count($_POST['arr_id_agenda_cita']).' citas';
                    $accion             = $_POST['tipo_movimiento'] == 'cancelar' ? 'BORRARMASIVO' : 'EDITARMASIVO';
                }
                
                FuncionesGlobales::saveBitacora($this->bitacora,$accion,$mensaje_inicial.$_POST['id_agenda_cita']. ' '.$_POST['texto_cita'] ,$_POST);

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

                FuncionesGlobales::saveBitacora($this->bitacora,'BORRAR','Se realizó la cancelación de la cita con identificar: '.$_POST['id_agenda_cita'],$obj_info);

                $response->setJsonContent('Cancelacion exitosa!');
                $response->setStatusCode(200, 'OK');
                return $response;
            }

            if ($accion == 'get_info_locacion'){
                FuncionesGlobales::deleteCacheByPattern('info_location_');
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
                        'id_profesional'        => $profesional['id'],
                        'omitir_dias_inhabiles' => true,
                        'fecha_inicio'          => $_POST['rango_fechas']['fecha_inicio'],
                        'fecha_termino'         => $_POST['rango_fechas']['fecha_termino'],
                    ));

                    $hora_cierre    = (INT) $arr_return['max_hora_inicio'] + 1;
                    $hora_cierre    = $hora_cierre.':00';

                    $result = array(
                        'rango_no_disponible'   => array(),
                        'citas_programadas'     => array()
                    );

                    foreach ($arr_return['horario_atencion'] as $horario_local){
                        $parametros_locacion    = array(
                            'id'                => $horario_local['id'],
                            'id_locacion'       => null,
                            'id_profesional'    => null,
                            'hora_inicio'       => $horario_local['min_hora_inicio'],
                            'hora_termino'      => $horario_local['max_hora_termino'],
                            'titulo'            => $horario_local['titulo'],
                            'dias'              => $horario_local['dias']
                        );
                        $arr_return['all_professionals'][$index]['rango_no_disponible'][$horario_local['id']]   = FuncionesGlobales::obtenerRangosNoDisponiblesPorDia(array($parametros_locacion),$horario_atencion_profesional,$hora_cierre);
                    }
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

                $accion_bitacota    = '';
                $accion_mensaje     = '';

                if ($_POST['obj_info']['accion'] == 'crear_cita'){
                    $accion_bitacota    = 'CREAR'; 
                    $accion_mensaje     = 'programo';
                }

                if ($_POST['obj_info']['accion'] == 'reagendar_cita'){
                    $accion_bitacota    = 'REAGENDAR'; 
                    $accion_mensaje     = 'Reagendo';
                }

                if ($_POST['obj_info']['accion'] == 'modificar_cita'){
                    $accion_bitacota    = 'EDITAR'; 
                    $accion_mensaje     = 'Modifico la fecha de';
                }

                FuncionesGlobales::saveBitacora($this->bitacora,$accion_bitacota,'Se '.$accion_mensaje.' la cita para el paciente: '.$_POST['info_bitacora']['nombre'].' para el día y hora: '. $_POST['info_bitacora']['fecha_cita'].' de '.$_POST['info_bitacora']['hora_inicio'].' a '.$_POST['info_bitacora']['hora_termino'],$_POST['obj_info']);

                $response->setJsonContent('Captura exitosa!');
                $response->setStatusCode(200, 'OK');
                return $response;
            }


            if ($accion == 'cancelar_pago' || $accion == 'capturar_pago'){
                $info_cita  = $_POST['info_cita'];

                $route  = '';

                $mensaje_bitacora   = '';
                if ($accion == 'capturar_pago'){
                    $route  = $this->url_api.$this->rutas['tbagenda_citas']['capturar_pago'];
                    $mensaje_bitacora   = 'Se registro el pago del paciente: '.$info_cita['nombre_completo'].' de la cita con fecha: '.$info_cita['fecha_completa'];
                }

                if ($accion == 'cancelar_pago'){
                    $route  = $this->url_api.$this->rutas['tbagenda_citas']['cancelar_pago'];
                    $mensaje_bitacora   = 'Se cancelo el pago del paciente: '.$info_cita['nombre_completo'].' de la cita con fecha: '.$info_cita['fecha_completa'];
                }

                $result = FuncionesGlobales::RequestApi('PUT',$route,$_POST);

                $response = new Response();

                if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                    $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                    $response->setStatusCode(404, 'Error');
                    return $response;
                }
                
                FuncionesGlobales::saveBitacora($this->bitacora,'EDITAR',$mensaje_bitacora,array());

                $response->setJsonContent('Captura exitosa!');
                $response->setStatusCode(200, 'OK');
                return $response;
            }

            if ($accion == 'captura_multipago'){
                $info_cita  = $_POST['info_cita'];

                $route  = $this->url_api.$this->rutas['tbagenda_citas']['capturar_pago'];
                $mensaje_bitacora   = 'Se registro el pago de '.count($_POST['arr_id_agenda_cita']).' citas de forma simultanea, registrando un total de '.$_POST['total_pagar'];

                if (strpos($_POST['cadena_pacientes'],",") !== false){
                    $mensaje_bitacora   = $mensaje_bitacora.' a los pacientes: '.$_POST['cadena_pacientes'];
                } else {
                    $mensaje_bitacora   = $mensaje_bitacora.' al paciente: '.$_POST['cadena_pacientes'];
                }

                $result = FuncionesGlobales::RequestApi('PUT',$route,$_POST);

                $response = new Response();

                if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                    $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                    $response->setStatusCode(404, 'Error');
                    return $response;
                }

                FuncionesGlobales::saveBitacora($this->bitacora,'MULTIPAGO',$mensaje_bitacora,$_POST['arr_id_agenda_cita']);

                $response->setJsonContent('Captura exitosa!');
                $response->setStatusCode(200, 'OK');
                return $response;
            }

            if ($accion == 'get_info_cita_simultanea'){
                $route      = $this->url_api.$this->rutas['tbagenda_citas']['show'];
                $arr_info   = FuncionesGlobales::RequestApi('GET',$route,$_POST);

                $response = new Response();
                $response->setJsonContent($arr_info);
                $response->setStatusCode(200, 'OK');
                return $response;
            }

            if ($accion == 'get_plantillas_whatsapp'){
                $route      = $this->url_api.$this->rutas['plantillas_mensajes']['plantilla_por_cita'];
                $result     = FuncionesGlobales::RequestApi('GET',$route,$_POST);

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

            if ($accion == 'save_envio_whatsapp'){
                $route      = $this->url_api.$this->rutas['plantillas_mensajes']['plantilla_enviada'];
                $result     = FuncionesGlobales::RequestApi('POST',$route,$_POST);

                $response = new Response();

                if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                    $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                    $response->setStatusCode(404, 'Error');
                    return $response;
                }

                FuncionesGlobales::saveBitacora($this->bitacora,'ENVIOMENSAJE','Plantilla: '.$_POST['nombre_plantilla'].' enviada al número: '.$_POST['celular'].' Para el paciente: '.$_POST['nombre_completo'],$_POST);

                $response->setJsonContent($result);
                $response->setStatusCode(200, 'OK');
                return $response;
            }

            $response = new Response();
            $response->setJsonContent($result);
            $response->setStatusCode(200, 'OK');
            return $response;
        }

        //  SE BUSCAN LOS SERVICIOS QUE PUEDE OFRECER EL USUARIO
        $route                  = $this->url_api.$this->rutas['ctlocaciones']['show'];
        $arr_locaciones= FuncionesGlobales::RequestApi('GET',$route,array('onlyallowed' => 1));

        $this->view->arr_locaciones     = $arr_locaciones; 
        
        //  PERMISOS     register_payment
        $this->view->apertura_agenda    = FuncionesGlobales::HasAccess("Controlcitas","agenda_opening");
        $this->view->registrar_pago     = FuncionesGlobales::HasAccess("Controlcitas","register_payment");

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

        //  PARA SABER SI EL USUARIO PERMITE MAS DE UN SERVICIO POR CITA
        $route          = $this->url_api.$this->rutas['ctvariables_sistema']['show'];
        $multiservicios = FuncionesGlobales::RequestApi('GET',$route,array(
            'clave' => 'multiservicios'  
        ));

        if (!is_array($multiservicios)){
            $multiservicios = 1;
        } else {
            $multiservicios = $multiservicios[0]['valor'];
        }

        $this->view->multiservicios   = $multiservicios;

        //  MOTIVOS PARA CANCELAR UNA CITA
        $route                      = $this->url_api.$this->rutas['ctmotivos_cancelacion_cita']['show'];
        $motivos_cancelacion_cita   = FuncionesGlobales::RequestApi('GET',$route);
        $this->view->motivos_cancelacion_cita   = $motivos_cancelacion_cita;

        //  MOTIVOS CITAS FUERA DE HORARIO
        $route                          = $this->url_api.$this->rutas['motivos_citas_fuera_horario']['show'];
        $motivos_citas_fuera_horario    = FuncionesGlobales::RequestApi('GET',$route);
        $this->view->motivos_citas_fuera_horario    = $motivos_citas_fuera_horario;
    }

    function get_info_by_location(){
        $cacheKey   = 'info_location_cc_'.$_POST['id_locacion'];

        //$arr_return = FuncionesGlobales::searchCache($cacheKey);
        $horario_atencion   = array();

        if ($arr_return == null){
            $arr_return = array(
                'horario_atencion'  => array()
            );

            //  SE CREA LA ESTRUCTURA POR LOS HORARIOS DE ATENCION
            $route                          = $this->url_api.$this->rutas['tbhorarios_atencion']['get_opening_hours'];
            $arr_return['horario_atencion'] = FuncionesGlobales::RequestApi('GET',$route,array(
                'id_locacion' => $_POST['id_locacion'],
                'omitir_dias_inhabiles' => true,
                'fecha_inicio'          => $_POST['rango_fechas']['fecha_inicio'],
                'fecha_termino'         => $_POST['rango_fechas']['fecha_termino'],
            ));
            $horario_atencion               = $arr_return['horario_atencion'];

            $response = new Response();

            if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400) || count($horario_atencion) == 0){
                return 'No existe un horario de atenci&oacute;n registrado a la locaci&oacute;n';
            }

            //  SE BUSCA LA ULTIMA FECHA DISPONIBLE ANTES DEL CIERRE DE AGENDA
            $route      = $this->url_api.$this->rutas['tbapertura_agenda']['show'];
            $arr_return['cierre_agenda']    = FuncionesGlobales::RequestApi('GET',$route,$_POST);

            //  INFORMACION DE LOS SERVICIOS
            $route                      = $this->url_api.$this->rutas['ctservicios']['show'];
            $arr_return['all_services'] = FuncionesGlobales::RequestApi('GET',$route,array('id_locacion' => $_POST['id_locacion']));

            // INFORMACION DE LOS PROFESIONALES
            $route              = $this->url_api.$this->rutas['ctprofesionales']['show'];
            $all_professionals  = FuncionesGlobales::RequestApi('GET',$route,array('id_locacion' => $_POST['id_locacion'],'get_servicios' => true));

            foreach($all_professionals as $profesional){
                $arr_return['all_professionals'][$profesional['id']]    = $profesional;
            }

            //FuncionesGlobales::saveCache($cacheKey,$arr_return);
        } else {
            $arr_return = FuncionesGlobales::cacheToArray($arr_return);
            $horario_atencion   = $arr_return['horario_atencion'];
            $all_professionals  = $arr_return['all_professionals'];
        }
        
        $arr_return['horario_atencion'] = array();

        //  SE BUSCA EL INTERVALO DE CITAS POR LOCACION
        $route          = $this->url_api.$this->rutas['ctlocaciones']['show'];
        $arr_locaciones = FuncionesGlobales::RequestApi('GET',$route,array('id' => $_POST['id_locacion']));
        $arr_locaciones = $arr_locaciones[0];

        //  SE BUSCAN LAS CITAS AGENDADAS EN EL RANGO DE FECHAS
        $route                          = $this->url_api.$this->rutas['tbagenda_citas']['show'];
        $_POST['activa']                = 1;
        $_POST['get_servicios']         = 1;
        //$arr_return['citas_agendadas']  = FuncionesGlobales::RequestApi('GET',$route,$_POST);
        $arr_citas_agendadas            = FuncionesGlobales::RequestApi('GET',$route,$_POST);
        
        $arr_citas_ordinarias       = array();
        $arr_citas_fuera_horario    = array();
        foreach($arr_citas_agendadas as $info_cita){
            if (isset($info_cita['id_motivo_cita_fuera_horario']) && is_numeric($info_cita['id_motivo_cita_fuera_horario'])){
                $arr_citas_fuera_horario[]  = $info_cita;
            } else {
                $arr_citas_ordinarias[] = $info_cita;
            }
        }

        foreach($horario_atencion as $id => $horario){
            $arr_return['horario_atencion'][$id]                    = FuncionesGlobales::allStructureSchedule(array($horario));
            $arr_return['horario_atencion'][$id]['titulo']          = $horario['titulo'];
            $arr_return['horario_atencion'][$id]['intervalo_citas'] = $arr_locaciones['intervalo_citas'];
            $arr_return['horario_atencion'][$id]['id']              = $horario['id'];
            $arr_return['horario_atencion'][$id]['dias']            = $horario['dias'];

            //  FILTRA DE LAS CITAS DEL PACIENTE, LAS QUE CORRESPONDAN POR HORARIO
            $arr_return['horario_atencion'][$id]['citas_paciente']  = FuncionesGlobales::AppoitmentByLocation($arr_citas_ordinarias,$horario);
        }
        
        return $arr_return;
    }
}