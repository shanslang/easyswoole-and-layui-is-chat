<?php
/**
 * CreateTime: 2019/8/3 13:43
 * Author: hhh
 * Description:
 */
namespace App\HttpController\Chat;

use EasySwoole\Validate\Validate;
use EasySwoole\VerifyCode\Conf;
use EasySwoole\EasySwoole\Logger;
use App\HttpController\Base;
use App\Utility\Pool\MysqlPool;
use App\Utility\Pool\MysqlObject;
use App\Utility\Pool\RedisObject;
use App\Utility\Pool\RedisPool;
use App\Model\UserModel;
use App\Model\GroupMemberModel;

class Chat extends Base
{

    public function isTest()
    {
        var_dump($this->isMobile());
    }

    public function index()
    {
        $token  =  $this->request()->getRequestParam('token');
        $redis  =  RedisPool::defer();
        $user   =  $redis->get('user_token_'.$token);
        if(!$user){
            $this->response()->redirect('/Chat/Chat/login');
        }
        $user  =  json_decode($user, true);
        $hostname = 'ws://192.168.23.130:9501';
        if($this->isMobile()){
            $db   = MysqlPool::defer();
            $group = $db->join('`group` as g','g.id = gm.group_id')->where('gm.user_id', $user['id'])->get('`group_member` as gm',null,'g.id,g.groupname,g.avatar');
            if(!$group){$group=[];}
            foreach($group as $k => $v){
                $group[$k]['groupname'] = $v['groupname'].'('.$v['id'].')';
            }
            $friend = $db->where('user_id', $user['id'])->get('`friend_group`', null, 'groupname,id');
            if(!$friend){$friend=[];}
            foreach ($friend as $k => $v){
                $friend[$k]['list'] = $db
                    ->join('`user` as u', 'f.friend_id = u.id')
                    ->where('f.user_id', $user['id'])
                    ->where('f.friend_group_id',$v['id'])
                    ->orderBy('status','DESC')
                    ->get('`friend` as f', null,'u.nickname as username,u.id,u.avatar,u.sign,u.status');
            }

            $data = [
                'mine'  => [
                    'username'  => $user['nickname'].'('.$user['id'].')',
                    'id'        => $user['id'],
                    'status'    => $user['status'],
                    'sign'      => $user['sign'],
                    'avatar'    => $user['avatar']
                ],
                "friend"    => $friend,
                "group"     => $group
            ];
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
            $this->render('mChat/index', [
                'server' => $hostname, 'token' => $token, 'user' => $user, 'data' => $data
            ]);
        }else{
            $this->render('Chat/index', [
                'server' => $hostname, 'token' => $token, 'user' => $user
            ]);
        }
    }

    // http://192.168.23.130:8989/Chat/Chat/login
    public function login()
    {
        if ($this->request()->getMethod() == 'POST') {
            $data = $this->request()->getRequestParam();
            $valitor = new Validate();
            $valitor->addColumn('username', '用户名')->required('用户名不能为空')->lengthMin(3, '最小长度不小于3');
            $valitor->addColumn('password', '密码')->required('密码必填')->lengthMin(6, '密码长度最小为6位');
            if ($valitor->validate($data)) {
                $db = MysqlPool::defer();
                $userModel = new UserModel($db);
                $user = $userModel->getUserByUsername($data['username']);
                if(!$user){
                    return $this->writeJson(10001,'', '用户不存在');
                }
                if(!password_verify($data['password'], $user['password'])){
                    return $this->writeJson(10001,'', '密码错误');
                }
                $token = uniqid().uniqid().$user['id'];
                RedisPool::invoke(function(RedisObject $redis) use ($token, $user){
                    $redis->set('user_token_'.$token, json_encode($user), 36000);
                });
                return $this->writeJson(200,['token' => $token], '登录成功');
            } else {
                $this->writeJson(10001, ['msg' => $valitor->getError()->__toString()], "error");
            }
        } else {
            if($this->isMobile()){
                $this->render('mChat/login');
            }else{
                $this->render('Chat/login');
            }
        }
    }

    public function loginout()
    {
        $token     = $this->request()->getRequestParam('token');
        $redisPool = RedisPool::defer();
        $redisPool->del('user_token_'.$token);
        $this->response()->redirect('/Chat/Chat/login');
    }

    public function register()
    {
        if($this->request()->getMethod() == 'POST'){
            $params  = $this->request()->getRequestParam();
            $valitor = new Validate();
            $valitor->addColumn('key')->required();
            $valitor->addColumn('code')->required()->lengthMax(10);
            $valitor->addColumn('nickname')->required();
            $valitor->addColumn('password')->required();
            $valitor->addColumn('username')->required();
            if(!$valitor->validate($params)){
                $err = $valitor->getError()->__toString();
                Logger::getInstance()->info($err);
                return $this->writeJson(10001, '', '参数错误');
            }

            $RedisPool = RedisPool::defer();
            $codeCache = $RedisPool->get('code'.$params['key']);
            if ($codeCache != $params['code']){
                return $this->writeJson(10001,'', '验证码错误');
            }

            $db        = MysqlPool::defer();
            $userModel = new UserModel($db);
            $uid       = $userModel->isNickName($params['nickname']);
            if($uid){
                return $this->writeJson(10001,'', '昵称 '.$params['nickname'].' 已存在');
            }
            $isuser = $userModel->getUserByUsername($params['username']);
            if($isuser){
                return $this->writeJson(10001,'', '用户名 '.$params['username'].' 已存在');
            }
            $data = [
                'avatar' => $params['avatar'],
                'nickname' => $params['nickname'],
                'username' => $params['username'],
                'password' => password_hash($params['password'], PASSWORD_DEFAULT),
                'sign'     => $params['sign'],
            ];
            $inid = $userModel->insertUser($data);
            if(!$inid){
                return $this->writeJson(10001,'', '注册失败');
            }
            $gdata = [
                'user_id'   => $inid,
                'groupname' => '默认分组'
            ];
            $Group   = new GroupMemberModel($db);
            $groupid = $Group->insertFriendGroup($gdata);
            return $this->writeJson(200, null, '注册成功');
        }else{
            $code_hash = uniqid().uniqid();
            if($this->isMobile()){
                $this->render('mChat/register', ['code_hash' => $code_hash]);
            }else{
                $this->render('Chat/register', ['code_hash' => $code_hash]);
            }
        }
    }

    public function getCode()
    {
        $key    = $this->request()->getRequestParam('key');
        $config = new Conf();
        $code   = new \EasySwoole\VerifyCode\VerifyCode($config);
        $num    = mt_rand(000, 999);

        RedisPool::invoke(function (RedisObject $redis) use ($key, $num){
            $redis->set('code'.$key, $num, 1000);
        });

        $this->response()->withHeader('Content-Type', 'image/png');
        $this->response()->write($code->DrawCode($num)->getImageByte());
    }

    public function editInfo()
    {
        if($this->request()->getMethod() == 'POST'){
            $params = $this->request()->getRequestParam();
            $params = array_filter($params);
            $valitor = new Validate();
            $valitor->addColumn('token')->required();
            $valitor->addColumn('key')->required();
            $valitor->addColumn('code')->required();
            if(!$valitor->validate($params)){
                return $this->writeJson(1001, null, '请重新登录');
            }

            $redisPool = RedisPool::defer();
            $code      = $redisPool->get('code'.$params['key']);
            if($code != $params['code']){
                return $this->writeJson(1001, '', '验证码不正确');
            }

            if(count($params) < 4){
                return $this->writeJson(1001, '', '请填写要修改的信息');
            }
            $data = [];
            if(isset($params['password'])){
                if($params['password'] != $params['conpassword']){
                    return $this->writeJson(1001, null, '密码不一致');
                }
                $data['password'] = password_hash($params['password'], PASSWORD_DEFAULT);
            }
            $db     = MysqlPool::defer();
            $umodel = new UserModel($db);
            if(isset($params['nickname'])){
                $uid    = $umodel->isNickName($params['nickname']);
                if($uid){
                    return $this->writeJson(1001, null, '昵称 '.$params['nickname'].' 已存在');
                }
                $data['nickname'] = $params['nickname'];
            }
            if(isset($params['avatar'])){
                $data['avatar'] = $params['avatar'];
            }
            $user = $redisPool->get('user_token_'.$params['token']);
            $user = json_decode($user, true);

            if(!$user){
                return $this->writeJson(1001, null, '请重新登录');
            }

            $up_res = $umodel->editUser($data, $user['id']);
            if(!$up_res){
                return $this->writeJson(1001, null ,'修改失败');
            }
            $redisPool->set('user_token_'.$params['token'], json_encode($up_res), 36000);
            return $this->writeJson(200, null, '修改成功');
        }else{
            $code_hash = uniqid().uniqid();
            $this->render('Chat/edit_info', ['code_hash' => $code_hash]);
        }
    }
}