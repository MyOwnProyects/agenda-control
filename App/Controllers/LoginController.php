<?php

namespace App\Controllers;

use Phalcon\Mvc\Controller;
use Phalcon\Http\Request;  // Asegúrate de importar la clase Request
use Phalcon\Http\Response; // Asegúrate de importar la clase Response
use App\Library\FuncionesGlobales;


class LoginController extends Controller
{
    protected $rutas;
    protected $url_api;

    public function initialize(){
        $aqui = 1;
        $config         = $this->di;
        $this->rutas    = $config->get('rutas');
        $config         = $config->get('config');
        $this->url_api  = $config['BASEAPI'];
        $aqui = 1;
    }
    
    public function indexAction()
    {
        if ($this->request->isAjax() && $this->request->isPost()){
            // Obtener los datos del formulario
            $username = $this->request->getPost('username', 'string');
            $password = $this->request->getPost('password', 'string');

            // Validación de los datos (ejemplo simple)
            if (empty($username) || empty($password)) {
                // Si faltan los campos, retornamos un mensaje de error
                $response = new Response();
                $response->setStatusCode(400, "Bad Request");
                $response->setJsonContent([
                    'status' => 'error',
                    'message' => 'Por favor, ingrese todos los campos.'
                ]);
                return $response;
            }

            // URL de la API, usando el dominio configurado en Nginx
            $route      = $this->url_api.$this->rutas['ctusuarios']['show'];
            $params     = array(
                'username'  => $username,
                'accion'    => 'login'
            );
            
            $request    = FuncionesGlobales::RequestApi('GET',$route,$params);
            $response = new Response();
            if (!$request || isset($request['error'])){
                $request['error']   = 'Usuario o contrase&ntilde;a invalido';
                $response->setStatusCode(400, "Error");
                $response->setJsonContent($request['error']);
            } else {

                $contrasena = $request[0]['contrasena'];

                // VALIDAR CONTRASEÑA HASH
                $hash = hash('sha256', $password);

                if (!hash_equals($contrasena, $hash)) {
                    $request['error']   = 'Usuario o contrase&ntilde;a invalido';
                    $response->setStatusCode(400, "Error");
                    $response->setJsonContent($request['error']);
                    return $response;
                }

                $this->session->set("clave",$request[0]['clave']);
                $this->session->set("primer_apellido",$request[0]['clave']);
                $this->session->set("segundo_apellido",$request[0]['clave']);
                $this->session->set("nombre",$request[0]['clave']);
                $this->session->set("clave_tipo_usuario",$request[0]['clave']);
                $this->session->set("nombre_tipo_usuario",$request[0]['clave']);
                $this->session->set("permisos",array());

                //  CREACION DE SESION
                $route      = $this->url_api.$this->rutas['ctusuarios']['get_info_usuario'];
                $params     = array(
                    'id_usuario'    => $request[0]['id']
                );         
                $request    = FuncionesGlobales::RequestApi('GET',$route,$params);

                $this->session->set("permisos",$request);

                $response->setStatusCode(200, "OK");
                $response->setJsonContent([
                    'status'    => 'success',
                    'message'   => 'Inicio de sesión exitoso.',
                    'redirect'  => '/Menu'
                ]);
            }

            return $response;
        }
    }
}
