<?php

namespace App\Controllers;  // Esto debe coincidir con 'App\Controllers'

use Phalcon\Mvc\Controller;

class IndexController extends Controller
{
    public function indexAction()
    {
        echo "Hello, Agenda Control!";
    }
}
