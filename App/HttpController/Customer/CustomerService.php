<?php

namespace App\HttpController\Customer;

use App\HttpController\Base;
use App\Utility\Pool\MysqlPool;
use App\Utility\Pool\RedisPool;
use App\Utility\Pool\RedisObject;
use EasySwoole\VerifyCode\Conf;
use EasySwoole\Validate\Validate;

class CustomerService extends Base
{
    public function index()
    {
        $token = $this->request()->getRequestParam('token');
        if(!$token){
            $this->response()->redirect('/Customer/CustomerService/login');
        }
        $redisPool = RedisPool::defer();
        $user      = $redisPool->get('cSer_token_'.$token);
        if(!$user){
            $this->response()->redirect('/Customer/CustomerService/login');
        }
        $user = json_decode($user, true);
        $hostname = 'ws://192.168.23.130:9501';
        $this->render('Customer/index', [
            'server' => $hostname, 'token' => $token, 'user' => $user
        ]);
    }

    public function login()
    {
        if($this->request()->getMethod() == 'POST'){
            $valitor = new Validate();
            $valitor->addColumn('username', '用户名')->required();
            $valitor->addColumn('psw', '密码')->required();
            $valitor->addColumn('code', '验证码')->required()->betweenLen(1, 7);
            $valitor->addColumn('key')->required();
            $params = $this->request()->getRequestParam();
            if(!$valitor->validate($params)){
                $err = $valitor->getError()->__toString();
                return $this->writeJson(1001, '', $err);
            }
            if(!ctype_alnum($params['username'])){
                return $this->writeJson(1001, '', '用户名只能包含字母和数字');
            }
            $redisPool = RedisPool::defer();
            $codeCash  = $redisPool->get('code'.$params['key']);
            if($codeCash != $params['code']){
                return $this->writeJson(1001, '', '验证码不正确');
            }

            $db   = MysqlPool::defer();
            $user = $db->where('username', $params['username'])->getOne('`customer_service`');
            if(!$user){
                return $this->writeJson(1001, null, '用户名或密码错误！');
            }
            if(!password_verify($params['psw'], $user['password'])){
                return $this->writeJson(1001, null, '用户名或密码错误！');
            }
            $token = uniqid().uniqid().$user['id'];
            RedisPool::invoke(function(RedisObject $redis) use ($token, $user){
                $redis->set('cSer_token_'.$token, json_encode($user), 3600);
            });
            return $this->writeJson(200, ['token' => $token], '登录成功!');
        }else{
            $code_hash = uniqid().uniqid();
            $this->render('Customer/login', ['code_hash' => $code_hash]);
        }
    }
}