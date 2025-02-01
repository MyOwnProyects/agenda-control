<?php

use Phalcon\Mvc\View;
use Phalcon\Mvc\Application;
use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\Url as UrlProvider;
use Phalcon\Http\Response;
use Phalcon\Http\Request;
use Phalcon\Session\Manager;
use Phalcon\Session\Adapter\Stream;

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

$di->setShared('dispatcher', function () {
    $dispatcher = new Phalcon\Mvc\Dispatcher();
    $dispatcher->setDefaultNamespace('App\Controllers');
    return $dispatcher;
});

$di->set('rutas',function(){
    return require APP_PATH.'/config/rutas.php';
});

$di->set('config',function(){
    return require APP_PATH.'/config/config.php';
});

// CREACION DE INSTANCIA DE SESION
$di->setShared('session',function(){
    $session    = new Manager();
    $session->setAdapter(new Stream([
        'savePath' => BASE_PATH.'/public/tmp', // Puedes ajustar el directorio donde se almacenarán las sesiones
    ]));
    $session->start();
    return $session;
});