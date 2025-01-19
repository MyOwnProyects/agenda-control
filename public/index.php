<?php

use Phalcon\Mvc\View;
use Phalcon\Mvc\Application;
use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\Url as UrlProvider;

// Define some absolute path constants to aid in locating resources
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/App');

//INCLUIR AUTOLOAD
require BASE_PATH . '/vendor/autoload.php';

// Create a DI
$di = new FactoryDefault();

// Setup the view component
$di->set(
    'view',
    function () {
        $view = new View();
        $view->setViewsDir(APP_PATH . '/views/');
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

        $router->add(
            '/',
            [
                'controller' => 'index',
                'action'     => 'index',
            ]
        );

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
    // Manejar la solicitud con una instancia de Request
    $response = $application->handle('/index/index');  // Aquí se obtiene la respuesta

    // Enviar la respuesta al cliente
    $response->send();
} catch (\Exception $e) {
    echo 'Exception: ', $e->getMessage();
}
