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
            $route      = $this->url_api.$this->rutas['autenticacion']['login'];
            $params     = array(
                'username'  => $username,
                'password'  => $password
            );
            
            $request    = FuncionesGlobales::RequestApi('POST',$route,$params);
            $response = new Response();
            if (!$request || isset($request['status']) && $request['status'] == 'error'){
                $request['error']   = empty($request['error']) ? 'Usuario o contrase&ntilde;a invalido' : $request['error'];
                $response->setStatusCode(400, "Error");
                $response->setJsonContent($request['error']);
            } else {

                // ============================================
                // LOGIN EXITOSO - GUARDAR TOKENS Y DATOS
                // ============================================
                
                $navegador = $this->get_browser_info($this->request->getUserAgent());
                
                // 1. GUARDAR TOKENS (CRÍTICO)
                $this->session->set("access_token", $request['access_token']);
                $this->session->set("refresh_token", $request['refresh_token']);
                $this->session->set("token_type", $request['token_type']); // "Bearer"
                $this->session->set("token_expires_in", $request['expires_in']);
                
                // 2. GUARDAR DATOS DEL USUARIO
                $this->session->set("id", $request['user']['id']);
                $this->session->set("clave", $request['user']['clave']);
                $this->session->set("primer_apellido",$request['user']['primer_apellido']);
                $this->session->set("segundo_apellido",$request['user']['segundo_apellido']);
                $this->session->set("nombre",$request['user']['nombre']);
                $this->session->set("clave_tipo_usuario",$request['user']['clave_tipo_usuario']);
                $this->session->set("nombre_tipo_usuario",$request['user']['nombre_tipo_usuario']);
                $this->session->set("id_profesional",$request[0]['id_profesional']);
                
                // 3. DATOS ADICIONALES (tu lógica actual)
                $this->session->set("permisos", array());
                $this->session->set("language", 'es');
                $this->session->set("bfp", $bfp);
                $this->session->set("navegador", $navegador);
                
                // 4. GUARDAR TIMESTAMP DEL LOGIN (útil para calcular expiración)
                $this->session->set("token_created_at", time());

                //  CREACION DE SESION
                $route      = $this->url_api.$this->rutas['ctusuarios']['get_info_usuario'];
                $params     = array(
                    'id_usuario'    => $request['user']['id']
                );         
                $request    = FuncionesGlobales::RequestApi('GET',$route,$params);

                $this->session->set("permisos",$request['permisos']);

                FuncionesGlobales::saveBitacora($this->bitacora,'ACCESO','Acceso a plataforma usando: '.$navegador,array());

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
        try {
            // ============================================
            // 1. REVOCAR TOKENS EN LA API
            // ============================================
            if ($this->session->has('refresh_token') && $this->session->has('access_token')) {
                $route = $this->url_api . $this->rutas['autenticacion']['logout'];
                
                $params = [
                    'refresh_token' => $this->session->get('refresh_token')
                ];
                
                // Llamar a la API para revocar tokens
                $request = FuncionesGlobales::RequestApi('POST', $route, $params);
            }
            
            // ============================================
            // 2. DESTRUIR SESIÓN DEL FRONTEND
            // ============================================
            $this->session->destroy();
            
            // ============================================
            // 3. REDIRIGIR A LOGIN
            // ============================================
            return $this->response->redirect('/');
            
        } catch (\Exception $e) {
            $this->session->destroy();
            return $this->response->redirect('/');
        }
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
