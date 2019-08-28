<?php
/**
 * CreateTime: 2019/7/18 15:45
 * Author: hhh
 * Description:
 */
namespace App\HttpController\Validate;

use App\Utility\Validate\BaseController;
use EasySwoole\Validate\Validate;

class Common extends BaseController
{
    // http://192.1xxx.130:9501/Validate/Common/index?phone=12111111111&kk=ll  // 返回{"code":400,"result":null,"msg":"verifyCode@验证码:验证码必须填写"}
    function index()
    {
        var_dump($this->request()->getRequestParam());
    }

    protected function validateRule(?string $action): ?Validate
    {
        $v = new Validate();
        switch ($action){
            case 'index':{
                $v->addColumn('phone', '手机号')->required()->length(11, '长度错误');
                $v->addColumn('verifyCode', '验证码')->required()->length('4');
                break;
            }
        }
        return $v;
    }
}
