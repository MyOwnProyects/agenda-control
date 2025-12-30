<?php

namespace App\Controllers;

use Phalcon\Mvc\Controller;
use Phalcon\Http\Request;  // Asegúrate de importar la clase Request
use Phalcon\Http\Response; // Asegúrate de importar la clase Response
use App\Library\FuncionesGlobales;


class MenuController extends BaseController
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
        
    }

    public function route404Action(){
        
    }

    //  ACCION PARA DESCARGAR UN ARCHIVO
    public function downloadAction(){
        $response = new Response();

        $tipo_archivo   = $this->request->getQuery('tipo_archivo', 'string');
        $nombre_archivo = $this->request->getQuery('nombre_archivo', 'string');

        if (!$tipo_archivo || !$nombre_archivo) {
            $response->setStatusCode(400, 'Bad Request');
            $response->setJsonContent(['error' => 'Parámetros incompletos']);
            return $response;
        }

        // Obtén la ruta física según el tipo
        $path_base = FuncionesGlobales::get_path_file($tipo_archivo);
        $full_path = $path_base. $nombre_archivo;

        if (!file_exists($full_path)) {
            $response->setStatusCode(404, 'Archivo no encontrado');
            $response->setJsonContent(['error' => 'El archivo no existe en el servidor']);
            return $response;
        }

        // Detecta el MIME
        $mime = mime_content_type($full_path);

        // Encabezados HTTP
        $response->setHeader('Content-Type', $mime);
        $response->setHeader('Content-Disposition', 'inline; nombre_archivo="' . basename($full_path) . '"');
        $response->setFileToSend($full_path, basename($full_path), true);

        return $response;
    }

    
}