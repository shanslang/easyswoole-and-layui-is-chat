<?php
namespace App\HttpController;


class Views extends Base
{
    function index()
    {
        $this->render('index', [
            'server' => 'hhhh'
        ]);
    }
}

