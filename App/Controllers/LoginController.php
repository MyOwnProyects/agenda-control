<?php

namespace App\Controllers;

use Phalcon\Mvc\Controller;
use Phalcon\Http\Request;  // Asegúrate de importar la clase Request
use Phalcon\Http\Response; // Asegúrate de importar la clase Response

class LoginController extends Controller
{
    
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

            // Lógica de autenticación (aquí solo es un ejemplo)
            if ($username === 'admin' && $password === '1234') {
                // Si la autenticación es exitosa, se puede redirigir o devolver un mensaje
                $response = new Response();
                $response->setStatusCode(200, "OK");
                $response->setJsonContent([
                    'status' => 'success',
                    'message' => 'Inicio de sesión exitoso.',
                    'redirect' => '/dashboard'  // Aquí puedes enviar la URL a la que se redirige al usuario
                ]);
                return $response;
            } else {
                // Si las credenciales son incorrectas
                $response = new Response();
                $response->setStatusCode(401, "Unauthorized");
                $response->setJsonContent([
                    'status' => 'error',
                    'message' => 'Credenciales incorrectas.'
                ]);
                return $response;
            }
        }
    }
}
