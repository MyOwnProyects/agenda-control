<?php

namespace App\Controllers;

use Phalcon\Mvc\Controller;

class BaseController extends Controller
{
    public function beforeExecuteRoute()
    {
        // Excluir el controlador y la acción de login/index de la validación
        $currentController  = $this->dispatcher->getControllerName();
        $currentAction      = $this->dispatcher->getActionName();

        if ($currentController === 'login') {
            return; // No verificar sesión en login/index
        }

        //  BANDERAS DE SESSION Y PERMISOS
        $msg_error      = '';
        $route_error    = '';

        // Verificar si el usuario tiene sesión
        if (!$this->session->has('clave')) {
            $msg_error      = "Sin sesión activa";
            $route_error    = 'login/logout';
        } else {
            //  EN CASO DE QUE EXISTA SESION, SE VERIFICA SI EL USUARIOI
            //  TIENE LOS PERMISOS PARA REALIZAR LA ACCION
            $permisos   = $this->session->get('permisos');
            $has_access = false;
            foreach($permisos as $permiso){
                if ($permiso['controlador'] == $currentController &&
                    $permiso['accion'] == $currentAction
                ) {
                    $has_access = true;
                    break;
                }
            }

            $msg_error      = !$has_access ? 'No tienes acceso a la ruta' : '';
            $route_error    = 'Menu/route404';
        }

        if ($msg_error != ''){
            if ($this->request->isAjax()) {
                // Responder con un error si es una solicitud AJAX
                $this->response->setJsonContent([
                    'status'        => 'error',
                    'message'       => $msg_error,
                    'route_error'   => $route_error
                ]);
                $this->response->setStatusCode(401, 'Unauthorized'); // Código HTTP 401
                $this->response->send();
                exit;
            } else {
                // Redirigir a login/index si es una solicitud normal
                $this->response->redirect($route_error);
                $this->response->send();
                exit;
            }
        } else {
            //  SECCION PARA INCLUIR LENGUAJE EN TODOS LOS CONTROLADORES
            // Define el idioma deseado (puedes obtenerlo de la sesión, navegador o configuración)
            $language = $this->session->get('language', 'es'); // Idioma por defecto: español

            // Ruta del archivo de idioma
            $languageFile = BASE_PATH . "/public/language/{$language}.php";

            // Verifica que el archivo de idioma exista
            if (file_exists($languageFile)) {
                $translations = include $languageFile; // Carga las traducciones
            } else {
                $translations = []; // Evita errores si no se encuentra el archivo
            }

            // Pasa las traducciones a todas las vistas
            $this->view->translations = $translations;
        }
        
    }
}
