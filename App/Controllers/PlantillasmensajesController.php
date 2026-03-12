<?php

namespace App\Controllers;

use Phalcon\Mvc\Controller;
use Phalcon\Http\Request;  // Asegúrate de importar la clase Request
use Phalcon\Http\Response; // Asegúrate de importar la clase Response
use App\Library\FuncionesGlobales;


class PlantillasmensajesController extends BaseController
{
    protected $rutas;
    protected $url_api;
    protected $bitacora;

    public function initialize(){
        $config         = $this->getDI();
        $this->rutas    = $config->get('rutas');
        $config         = $config->get('config');
        $this->url_api  = $config['BASEAPI'];
        $this->bitacora = 'Plantillasmensajes';
    }

    public function IndexAction(){
        
        $route      = $this->url_api.$this->rutas['ctlocaciones']['show'];
        $locacion   = FuncionesGlobales::RequestApi('GET',$route,array('onlyallowed' => true));

        $route  = $this->url_api.$this->rutas['plantillas_mensajes']['show'];
        $result = FuncionesGlobales::RequestApi('GET',$route,$_POST);

        $this->view->variables          = $result['variables'];
        $this->view->json_plantillas    = json_encode($result['plantillas']);
        $this->view->plantillas         = $result['plantillas'];
        $this->view->nombre_locacion    = $locacion[0]['nombre'];
        $this->view->latitud_locacion   = $locacion[0]['latitud'];
        $this->view->longitud_locacion  = $locacion[0]['longitud'];

        //  PERMISOS
        $this->view->save   = FuncionesGlobales::HasAccess("Plantillasmensajes","save");
        $this->view->delete = FuncionesGlobales::HasAccess("Plantillasmensajes","delete");
    }

    public function saveAction(){
        if ($this->request->isAjax()){

            $route  = $this->url_api.$this->rutas['plantillas_mensajes']['save_plantilla'];
            $result = FuncionesGlobales::RequestApi('POST',$route,$_POST['info_plantilla']);
            $response = new Response();

            if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                $response->setStatusCode(404, 'Error');
                return $response;
            }

            $accion = is_numeric($_POST['info_plantilla']['id']) ? 'EDITAR' : 'CREAR';
            FuncionesGlobales::saveBitacora($this->bitacora,$accion,'Se capturo la plantilla con clave: '.$_POST['info_plantilla']['clave'],$_POST['info_plantilla']);

            $route  = $this->url_api.$this->rutas['plantillas_mensajes']['show'];
            $result = FuncionesGlobales::RequestApi('GET',$route,$_POST);

            $response->setJsonContent($result['plantillas']);
            $response->setStatusCode(200, 'OK');
            return $response;
        }
    }

    public function deleteAction(){
        if ($this->request->isAjax()){
            $route  = $this->url_api.$this->rutas['cttipo_usuarios']['change_estatus'];
            $result = FuncionesGlobales::RequestApi('DELETE',$route,$_POST);
            $response = new Response();

            if ($response->getStatusCode() >= 400 || (isset($result['status_code']) && $result['status_code'] >= 400)){
                $response->setJsonContent(isset($result['error']) ? $result['error'] : $result);
                $response->setStatusCode(404, 'Error');
                return $response;
            }

            FuncionesGlobales::saveBitacora($this->bitacora,'BORRAR','Se mando '.$_POST['accion_bitacora'].' el tipo usuario: '.$_POST['clave'],$_POST);

            $response->setJsonContent('Captura exitosa');
            $response->setStatusCode(200, 'OK');
            return $response;
        }
    }
    
}