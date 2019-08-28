<?php
namespace App\HttpController;

use EasySwoole\Http\AbstractInterface\Controller;
use EasySwoole\Http\Message\Status;
use EasySwoole\EasySwoole\Trigger;

class Index extends Controller
{
    function index()
    {
        $response = $this->response();
        $response->withStatus(200);//设置响应状态码,必须设置
       // $response->setCookie('name','仙士可',time()+86400,'/');//设置一个cookie
        //$response->write('hello world');//向客户端发送一条数据(类似于常规web模式的 echo )
		$this->response()->redirect('/Chat/Chat/login');
        //$this->writeJson(200, [], 'success');
    }

    function test()
    {	
        $this->response()->withHeader('Content-type','text/html;charset=utf-8');
        $this->response()->write('中文');
        return '/test2';
    }

    function test2()
    {
        $this->response()->write('this is test2');
        return false;
    }
  
    function actionNotFound(?string $action)
    {
    	//$this->response()->write('不存在的方法'.$action);
        $this->response()->redirect('/Chat/Chat/login');
        return false;
    }
  
    function afterAction(?string $actionName):void
    {
    	$this->response()->write('after'.$actionName);
    }
}
