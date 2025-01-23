<?php

use Phalcon\Mvc\Application;

// Define some absolute path constants to aid in locating resources
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/App');

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
    echo 'Exception: ', $e->getMessage();
}
