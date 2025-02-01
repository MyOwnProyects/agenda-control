<?php

namespace App\Controllers;

use Phalcon\Mvc\Controller;

class BaseController extends Controller
{
    public function beforeExecuteRoute()
    {
        // Excluir el controlador y la acción de login/index de la validación
        $dispatcher = $this->getDI()->get('dispatcher');
        $session    = $this->getDI()->get('session');
        $request    = $this->getDI()->get('request');
        $response   = $this->getDI()->get('response');
        $currentController  = $dispatcher->getControllerName();
        $currentAction      = $dispatcher->getActionName();

        if ($currentController === 'login') {
            return; // No verificar sesión en login/index
        }

        //  BANDERAS DE SESSION Y PERMISOS
        $msg_error      = '';
        $route_error    = '';

        // Verificar si el usuario tiene sesión
        if (!$session->has('clave')) {
            $msg_error      = "Sin sesi&oacute;n activa! seras enviado nuevamente a la vista inicial para iniciar sesi&oacute;n";
            $route_error    = 'login/logout';
        } else {
            //  EN CASO DE QUE EXISTA SESION, SE VERIFICA SI EL USUARIOI
            //  TIENE LOS PERMISOS PARA REALIZAR LA ACCION
            $permisos   = $session->get('permisos');
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
            if ($request->isAjax()) {
                // Responder con un error si es una solicitud AJAX
                $response->setJsonContent([
                    'status'        => 'error',
                    'message'       => $msg_error,
                    'route_error'   => $route_error
                ]);
                $response->setStatusCode(401, 'Unauthorized'); // Código HTTP 401
                $response->send();
                exit;
            } else {
                // Redirigir a login/index si es una solicitud normal
                $response->redirect($route_error);
                $response->send();
                exit;
            }
        } else {
            //  SECCION PARA INCLUIR LENGUAJE EN TODOS LOS CONTROLADORES
            // Define el idioma deseado (puedes obtenerlo de la sesión, navegador o configuración)
            $language = $session->get('language', 'es'); // Idioma por defecto: español

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
