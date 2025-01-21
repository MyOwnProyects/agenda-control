<?php

use Phalcon\Mvc\View;
use Phalcon\Mvc\Application;
use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\Url as UrlProvider;
use Phalcon\Http\Response;
use Phalcon\Http\Request;

// Define some absolute path constants to aid in locating resources
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/App');

// INCLUIR AUTOLOAD
require BASE_PATH . '/vendor/autoload.php';

// Create a DI
$di = new FactoryDefault();

// Registrar el servicio Response
$di->setShared('response', function () {
    return new Response();
});

// Registrar el servicio Request
$di->setShared('request', function () {
    return new Request();
});

// Setup the view component
$di->set(
    'view',
    function () {
        $view = new \Phalcon\Mvc\View();
        $view->setViewsDir(APP_PATH . '/views/'); // Carpeta donde están las vistas
        $view->setLayoutsDir('layouts/'); // Carpeta donde estarán los layouts
        $view->setLayout('main'); // Nombre del archivo del layout principal (main.phtml)
        return $view;
    }
);


// Setup a base URI
$di->set(
    'url',
    function () {
        $url = new UrlProvider();
        $url->setBaseUri('/');
        return $url;
    }
);

$di->set(
    'router',
    function () {
        $router = new Phalcon\Mvc\Router();

        // Activar el enrutamiento predeterminado
        $router->setDefaultController('login');
        $router->setDefaultAction('index');

        return $router;
    }
);

$di->set(
    'dispatcher',
    function () {
        $dispatcher = new Phalcon\Mvc\Dispatcher();
        $dispatcher->setDefaultNamespace('App\Controllers'); // Ajusta si usas namespaces
        return $dispatcher;
    }
);

$application = new Application($di);

try {
    // Manejar la solicitud basada en la ruta actual
    $response = $application->handle($_SERVER['REQUEST_URI']);

    // Enviar la respuesta al cliente
    $response->send();
} catch (\Exception $e) {
    echo 'Exception: ', $e->getMessage();
}
