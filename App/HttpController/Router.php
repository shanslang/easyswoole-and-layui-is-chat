<?php

namespace App\HttpController;

use EasySwoole\Http\AbstractInterface\AbstractRouter;
use FastRoute\RouteCollector;

class Router extends AbstractRouter
{
    function initialize(RouteCollector $routeCollector)
    {
//        $this->setGlobalMode(true);
//        $this->setGlobalMode(false);
//        $this->setMethodNotAllowCallBack(function (Request $request,Response $response){
//            $response->write('未找到处理方法');
//        });
//        $this->setRouterNotFoundCallBack(function (Request $request,Response $response){
//            $response->write('未找到路由匹配');
//        });
        // TODO: Implement initialize() method.
        $routeCollector->get('/user', '/Test/user');
        $routeCollector->get('/test', '/Index/test');
        $routeCollector->post('/admin/user/create', '/Admin/User/create');
    }
}