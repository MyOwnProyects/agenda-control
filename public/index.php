<?php

use Phalcon\Mvc\Application;

// Define some absolute path constants to aid in locating resources
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/App');
define('MPDF_TEMP_DIR', BASE_PATH . '/storage/tmp');


// INCLUIR AUTOLOAD
require BASE_PATH . '/vendor/autoload.php';

// INCLUIR SERVICES
require APP_PATH . '/config/services.php';

$application = new Application($di);

try {
    // Manejar la solicitud basada en la ruta actual
    $response = $application->handle($_SERVER['REQUEST_URI']);

    // Enviar la respuesta al cliente
    $response->send();
} catch (\Exception $e) {
    // Acceso al servicio de sesión desde el contenedor de dependencias ($di)
    if ($di->getSession()->has('clave')) {
        // Redirigir al error personalizado si hay sesión activa
        $di->getResponse()->redirect('/Menu/route404')->send();
        exit;
    } else {
        // Redirigir a la página de inicio si no hay sesión activa
        $di->getResponse()->redirect('/login/logout')->send();
        exit;
    }
}
