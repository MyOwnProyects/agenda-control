<?php

namespace App\Controllers;

use Phalcon\Mvc\Controller;
use Phalcon\Http\Request;  // Asegúrate de importar la clase Request
use Phalcon\Http\Response; // Asegúrate de importar la clase Response
use App\Library\FuncionesGlobales;


class ReporteadorController extends BaseController
{
    protected $rutas;
    protected $url_api;

    public function initialize(){
        $config         = $this->getDI();
        $this->rutas    = $config->get('rutas');
        $config         = $config->get('config');
        $this->url_api  = $config['BASEAPI'];
    }

    public function IndexAction(){

        if ($this->request->isAjax()){
            $route          = $this->url_api.$this->rutas['dashboard_menu']['show'];
            $data_dashboard = FuncionesGlobales::RequestApi('GET',$route);

            $response = new Response();

            if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                $response->setStatusCode(404, 'Error');
                return $response;
            }

            $response->setJsonContent(array(
                'citas' => $data_dashboard['citas'],
                'fecha_actual_label'    => $data_dashboard['fecha_actual_label']
            ));
            $response->setStatusCode(200, 'OK');
            return $response;
        }
    }

}