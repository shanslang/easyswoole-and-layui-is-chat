<?php
/**
 * CreateTime: 2019/7/18 16:26
 * Author: hhh
 * Description:
 */
namespace App\HttpController\Verifycode;

use EasySwoole\Http\AbstractInterface\Controller;
use EasySwoole\VerifyCode\Conf;

class Index extends Controller
{
    function index()
    {
        $config = new Conf();
        $code   = new \EasySwoole\VerifyCode\VerifyCode($config);
        $this->response()->withHeader('Content-Type', 'image/png');
        $this->response()->write($code->DrawCode()->getImageByte());
    }

    function getBase64()
    {
        $config = new Conf();
        $code   = new \EasySwoole\VerifyCode\VerifyCode($config);
        $this->response()->write($code->DrawCode()->getImageBase64());
    }
}

