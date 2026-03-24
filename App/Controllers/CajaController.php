<?php

namespace App\Controllers;

use Phalcon\Mvc\Controller;
use Phalcon\Http\Request;  // Asegúrate de importar la clase Request
use Phalcon\Http\Response; // Asegúrate de importar la clase Response
use App\Library\FuncionesGlobales;


class CajaController extends BaseController
{
    protected $rutas;
    protected $url_api;
    protected $bitacora;

    public function initialize(){
        $config         = $this->getDI();
        $this->rutas    = $config->get('rutas');
        $config         = $config->get('config');
        $this->url_api  = $config['BASEAPI'];
        $this->bitacora = 'Caja';
    }

    public function IndexAction(){

        if ($this->request->isAjax()){

            $accion = $this->request->getPost('accion');

            if ($accion == 'fill_pacientes'){
                $route      = $this->url_api.$this->rutas['ctpacientes']['fill_combo'];
                $arr_info   = FuncionesGlobales::RequestApi('GET',$route,$_POST);

                $response = new Response();
                $response->setJsonContent($arr_info);
                $response->setStatusCode(200, 'OK');
                return $response;
            }

            if ($accion == 'get_citas_adeudos'){
                $route      = $this->url_api.$this->rutas['caja']['get_citas_adeudos'];
                $arr_info   = FuncionesGlobales::RequestApi('GET',$route,$_POST);

                $response = new Response();
                $response->setJsonContent($arr_info);
                $response->setStatusCode(200, 'OK');
                return $response;
            }

            if ($accion == 'get_fecha_hora'){
                $route      = $this->url_api.$this->rutas['caja']['get_fecha_hora'];
                $arr_info   = FuncionesGlobales::RequestApi('GET',$route,$_POST);

                $response = new Response();
                $response->setJsonContent($arr_info);
                $response->setStatusCode(200, 'OK');
                return $response;
            }

            if ($accion == 'save_pago'){
                $route      = $this->url_api.$this->rutas['caja']['save_pago'];
                $result     = FuncionesGlobales::RequestApi('POST',$route,$_POST);

                $response = new Response();

                if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                    $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                    $response->setStatusCode(404, 'Error');
                    return $response;
                }

                FuncionesGlobales::saveBitacora($this->bitacora,'CAPTURA_PAGO','Se realizó la captura de un pago del paciente: : '.$__POST['nombre_paciente'],$_POST);

                $response->setJsonContent('Apertura de agenda exitosa!');
                $response->setStatusCode(200, 'OK');
                return $response;
            }
        }

        
    }

    

    
}