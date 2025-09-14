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
    protected $bitacora;

    public function initialize(){
        $config         = $this->getDI();
        $this->rutas    = $config->get('rutas');
        $config         = $config->get('config');
        $this->url_api  = $config['BASEAPI'];
        $this->bitacora = 'Login';
    }
    
    public function indexAction()
    {
        if ($this->session->has('clave')){
            $this->response->redirect('Menu/');
        }

        if ($this->request->isAjax() && $this->request->isPost()){
            // Obtener los datos del formulario
            $username   = $this->request->getPost('username', 'string');
            $password   = $this->request->getPost('password', 'string');
            $bfp        = $this->request->getPost('bfp', 'string');

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
                $request['error']   = empty($request['error']) ? 'Usuario o contrase&ntilde;a invalido' : $request['error'];
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

                $tmp_navegador  = $this->get_browser_info($_SERVER['HTTP_USER_AGENT']);

                $this->session->set("clave",$request[0]['clave']);
                $this->session->set("primer_apellido",$request[0]['primer_apellido']);
                $this->session->set("segundo_apellido",$request[0]['segundo_apellido']);
                $this->session->set("nombre",$request[0]['nombre']);
                $this->session->set("clave_tipo_usuario",$request[0]['clave_tipo_usuario']);
                $this->session->set("nombre_tipo_usuario",$request[0]['nombre_tipo_usuario']);
                $this->session->set("permisos",array());
                $this->session->set("language",'es');
                $this->session->set("bfp",$bfp);
                $this->session->set("navegador",$this->get_browser_info($_SERVER['HTTP_USER_AGENT']));

                //  CREACION DE SESION
                $route      = $this->url_api.$this->rutas['ctusuarios']['get_info_usuario'];
                $params     = array(
                    'id_usuario'    => $request[0]['id']
                );         
                $request    = FuncionesGlobales::RequestApi('GET',$route,$params);

                $this->session->set("permisos",$request['permisos']);

                FuncionesGlobales::saveBitacora($this->bitacora,'ACCESO','Acceso a plataforma',array());

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

    public function logoutAction(){
        // Destruir la sesión y redirigir a login
        $this->session->destroy();
        $this->response->redirect('/');
    }

    function get_browser_info($user_agent){
        $browser = 'Other';
        if (strpos($user_agent, 'Opera') || strpos($user_agent, 'OPR/')) $browser = 'Opera';
        elseif (strpos($user_agent, 'Edge')) $browser = 'Edge';
        elseif (strpos($user_agent, 'Chrome')) $browser = 'Chrome';
        elseif (strpos($user_agent, 'Safari')) $browser = 'Safari';
        elseif (strpos($user_agent, 'Firefox')) $browser = 'Firefox';
        elseif (strpos($user_agent, 'MSIE') || strpos($user_agent, 'Trident/7')) $browser = 'Internet Explorer';

        $is_mobile = preg_match('/android|iphone|ipad|ipod|blackberry|windows phone|opera mini|mobile/i', strtolower($user_agent));

        return $browser . ($is_mobile ? ' Mobile' : '');
    }

}
