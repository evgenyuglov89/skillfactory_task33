<?php 

namespace App\controllers;

use App\core\Controller;

class Signup extends Controller
{

    public function index()
    {
        $this->view->render('signup.php', 'template.php');
    }
    
}