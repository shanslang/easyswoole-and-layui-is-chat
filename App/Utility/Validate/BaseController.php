<?php
/**
 * CreateTime: 2019/7/18 15:42
 * Author: hhh
 * Description:
 */

namespace App\Utility\Validate;

use EasySwoole\Http\Message\Status;
use EasySwoole\Validate\Validate;
use EasySwoole\Http\AbstractInterface\Controller;

class BaseController extends Controller
{
    function index()
    {

    }

    protected function onRequest(?string $action): ?bool
    {
        $ret =  parent::onRequest($action);
        if($ret === false){
            return false;
        }
        $v = $this->validateRule($action);
        if($v){
            $ret = $this->validate($v);
            if($ret == false){
                $this->writeJson(Status::CODE_BAD_REQUEST,null,"{$v->getError()->getField()}@{$v->getError()->getFieldAlias()}:{$v->getError()->getErrorRuleMsg()}");
                return false;
            }
        }
        return true;
    }

    protected function validateRule(?string $action):?Validate
    {
    }
}




