<?php
/**
 * CreateTime: 2019/7/15 11:41
 * Author: hhh
 * Description:
 */
namespace App\HttpController\Admin;

use App\Model\UserModel;
use App\Utility\Pool\MysqlObject;
use App\Utility\Pool\MysqlPool;
use EasySwoole\Http\AbstractInterface\Controller;

class User extends Controller
{
    function index()
    {

    }

    public function create()
    {
        $data['uid'] = 61456;
        $data['fd']  = 2;
        // $data['psw'] = password_hash('password',);
        $db = MysqlPool::defer();
        $userModel = new UserModel($db);
        $userId = $userModel->create($data);
        $this->writeJson(200, ['user_id' => $userId]);
    }
}