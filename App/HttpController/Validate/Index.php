<?php
/**
 * CreateTime: 2019/7/18 15:13
 * Author: hhh
 * Description:
 */

namespace App\HttpController\Validate;

use EasySwoole\Http\AbstractInterface\Controller;
use EasySwoole\Validate\Validate;

class Index extends Controller
{

    // 访问http://192.168.23.130:9501/Validate/Index/index?hh=99&kk=ll
    function index()
    {
        $request = $this->request();
        $params  = $request->getRequestParam();
//        var_dump($request->getRequestParam());
        $validate = new Validate();
        $validate->addColumn('name', '姓名')->required('请填写姓名')->lengthMax(5); // 限制name必填，且不能大于5个字符
        if(!$validate->validate($params))
        {
            $this->writeJson(400, [], $validate->getError()->__toString());
            return false;
        }
        $this->writeJson(200, [], 'success');
    }
}

