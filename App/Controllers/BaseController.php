<?php

namespace App\Controllers;

use Phalcon\Mvc\Controller;

class BaseController extends Controller
{
    public function beforeExecuteRoute()
    {
        // Excluir el controlador y la acción de login/index de la validación
        $currentController = $this->dispatcher->getControllerName();
        $currentAction = $this->dispatcher->getActionName();

        if ($currentController === 'login') {
            return; // No verificar sesión en login/index
        }

        // Verificar si el usuario tiene sesión
        if (!$this->session->has('clave')) {
            if ($this->request->isAjax()) {
                // Responder con un error si es una solicitud AJAX
                $this->response->setJsonContent([
                    'status' => 'error',
                    'message' => 'No hay sesión activa.',
                ]);
                $this->response->setStatusCode(401, 'Unauthorized'); // Código HTTP 401
                $this->response->send();
                exit;
            } else {
                // Redirigir a login/index si es una solicitud normal
                $this->response->redirect('login/logout');
                $this->response->send();
                exit;
            }
        }
    }
}
