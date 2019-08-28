<?php

namespace App\HttpController\Chat;

use App\HttpController\Base;
use App\Utility\Pool\MysqlPool;
use App\Utility\Pool\RedisPool;
use EasySwoole\Validate\Validate;

class User extends Base
{
    public function userinfo()
    {
        $token = $this->request()->getRequestParam('token');
        //var_dump($token);
        $RedisPool = RedisPool::defer();
        $user  = $RedisPool->get('user_token_'.$token);
        if(!$user){
            return $this->writeJson(1001, null, "获取用户信息失败");
        }
        $user = json_decode($user, true);
        $db   = MysqlPool::defer();
        $group = $db->join('`group` as g','g.id = gm.group_id')->where('gm.user_id', $user['id'])->get('`group_member` as gm',null,'g.id,g.groupname,g.avatar');
        //$sql  = $db->getLastQuery();
        if(!$group){$group=[];}
        foreach($group as $k => $v){
            $group[$k]['groupname'] = $v['groupname'].'('.$v['id'].')';
        }
        $friend = $db->where('user_id', $user['id'])->get('`friend_group`', null, 'groupname,id');
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
        return $this->writeJson(0, $data, 'success');
    }

    public function members()
    {
        $params = $this->request()->getRequestParam();
        $valitor  = new Validate();
        $valitor->addColumn('id', '参数')->required('参数不能为空')->integer('格式不合法');
        if($valitor->validate($params)){
            $db = MysqlPool::defer();
            $data['list'] = $db
                ->join('`user` u','g.user_id = u.id')
                ->where('g.group_id', $params['id'])
                ->orderBy('u.status','DESC')
                ->get('`group_member` as g', null, 'u.nickname as username,u.id,u.avatar,u.sign');
            return $this->writeJson(0, $data, 'success');
        }else{
            $err = $valitor->getError()->__toString();
            $this->writeLog($err);
            return $this->writeJson(10001, null, '参数错误');
        }

    }

    public function messageBox()
    {
        $token = $this->request()->getRequestParam('token') ?? '';
        if(!$token){
            return $this->writeJson(10001, '获取用户信息失败！');
        }
        $redisPool = RedisPool::defer();
        $user = $redisPool->get('user_token_'.$token);
        if(!$user){
            return $this->writeJson(10001, '身份已过期，请重新登录');
        }
        $user = json_decode($user, true);
        $db   = MysqlPool::defer();
        $db->where('user_id', $user['id'])->update('system_message',['read' => 1]);
        //$list = $db->join('user as f','f.id = sm.from_id')
           // ->where('user_id',$user['id'])
            //->orderBy('id', 'DESC')
            //->get('system_message as sm',50,'sm.id,f.id as uid,f.avatar,f.nickname,sm.remark,sm.time,sm.type,sm.group_id,sm.status');
        $list = $db->join('user as f','f.id = sm.from_id')
            ->where('user_id',$user['id'])
            ->where('sm.type', '2', '<')
            ->orderBy('id', 'DESC')
            ->get('system_message as sm',50,'sm.id,f.id as uid,f.avatar,f.nickname,sm.remark,sm.time,sm.type,sm.group_id,sm.status');
        $list2 = $db->join('`group` as f', 'f.id = sm.group_id')
            ->join('`user` as u','u.id = sm.from_id')
            ->where('sm.user_id', $user['id'])
            ->where('sm.type', 2)
            ->orderBy('sm.id', 'DESC')
            ->get('system_message as sm', 10, 'sm.id,u.id as uid,f.groupname,sm.remark,sm.time,sm.type,sm.group_id,sm.status,u.avatar,u.nickname,f.id gid,sm.from_id,sm.user_id');
        if($list2){
        	$list = array_merge($list, $list2);
        }
        arsort($list);
        if(!$list){$list=[];}
        foreach ($list as $k => $v){
            $list[$k]['time'] = $this->__time_tranx($v['time']);
        }
        $this->render('Chat/message_box', ['list' => $list]);
    }

    public function find()
    {
        $params = $this->request()->getRequestParam();
        $type   = $params['type'] ?? '';
        $wd     = $params['wd'] ?? '';
        $user_list  = [];
        $group_list = [];
        $key    = '%'.$wd.'%';
        if($type){$db = MysqlPool::defer();}
        switch ($type){
            case 'user':
                $user_list = $db->whereOr('id', $key, 'like')->whereOr('username', $key, 'like')->whereOr('nickname', $key, 'like')->get('`user`', null, 'id,nickname,avatar');
                break;
            case 'group':
                $group_list = $db->whereOr('id', $key, 'like')->whereOr('groupname', $key, 'like')->get('`group`', null, 'id,groupname,avatar');
                break;
            default:
                break;
        }
        if(!$user_list){$user_list=[];}
        if(!$group_list){$group_list=[];}
        $this->render('Chat/find', ['user_list' => $user_list, 'group_list' => $group_list,'type' => $type, 'wd' => $wd]);
    }

    public function chatLog()
    {
        $params = $this->request()->getRequestParam();
        if($this->request()->getMethod() == 'POST'){
            $valitor = new Validate();
            $valitor->addColumn('id', '标识')->required('必须存在')->integer();
            $valitor->addColumn('type', '类型')->required()->lengthMax(12);
            $valitor->addColumn('token', '令牌')->required();
            $valitor->addColumn('page', '页面')->required()->integer();
            if(!$valitor->validate($params)){
                $err = $valitor->getError()->__toString();
                $this->writeLog($err);
                return $this->writeJson(10001, null, '参数错误');
            }
            $redisPool = RedisPool::defer();
            $user      = $redisPool->get('user_token_'.$params['token']);
            if(!$user){
                $this->writeLog($params['id'].'no token exists');
                return $this->writeJson(10001, null, '重新登录');
            }
            $user = json_decode($user, true);

            $pagesize = 10;
            $db   = MysqlPool::defer();
            if($params['type'] == 'group'){
                $count = $db->join('user as u', 'u.id = cr.user_id')
                    ->where('cr.group_id', $params['id'])
                    ->count('chat_record as cr', null, 'u.id');
                $list  = $db->join('user as u', 'u.id = cr.user_id')
                    ->where('cr.group_id', $params['id'])
                    ->orderBy('time', 'DESC')
                    ->get('chat_record as cr', [($params['page']-1)*$pagesize, $pagesize], 'u.nickname as username,u.id,u.avatar,time as timestamp,cr.content');
            }else{
                $count = $db->join('user as u', 'u.id = cr.user_id')
                    ->where('cr.user_id', $user['id'])
                    ->where('cr.friend_id', $params['id'])
                    ->whereOr('cr.user_id', $params['id'])
                    ->where('cr.friend_id', $user['id'])
                    ->count('chat_record as cr', null, 'u.id');
                $list = $db->join('user as u', 'u.id = cr.user_id')
                    ->where('cr.user_id', $user['id'])
                    ->where('cr.friend_id', $params['id'])
                    ->whereOr('cr.user_id', $params['id'])
                    ->where('cr.friend_id', $user['id'])
                    ->orderBy('time', 'DESC')
                    ->get('chat_record as cr', [($params['page']-1)*$pagesize, $pagesize], 'u.nickname as username,u.id,u.avatar,time as timestamp,cr.content');
            }
            if(!$list){$list=[];}
            foreach($list as $k => $v){
                $list[$k]['timestamp'] = $v['timestamp']*1000;
            }
            $data['data']      = $list;
            $data['last_page'] = ceil($count/$pagesize);
            return $this->writeJson(0, $data, '');
        }else{
            $type   = $params['type'];
            $id     = $params['id'];
            $this->render('Chat/chat_log', ['id' => $id, 'type' => $type]);
        }
    }

    public function signEdit()
    {
        $params = $this->request()->getRequestParam();
        if(!isset($params['token'])){
            return $this->writeJson(10001, '', '刷新页面');
        }
        $sign = $params['sign'] ?? '';
        $redisPool = RedisPool::defer();
        $user      = $redisPool->get('user_token_'.$params['token']);
        if(!$user){
            return $this->writeJson(10001, '', '刷新页面');
        }
        $user = json_decode($user, true);
        $db = MysqlPool::defer();
        $db->where('id', $user['id'])->update('`user`', ['sign' => $sign]);
        return $this->writeJson(200, '', '修改成功');
    }

    public function createGroup()
    {
        if($this->request()->getMethod() == 'POST'){
            $params  = $this->request()->getRequestParam();
            $valitor = new Validate();
            $valitor->addColumn('token', '凭证')->required();
            $valitor->addColumn('groupname', '群名')->required();
            $img = $params['avatar'] ?? '/Static/upload/group.jpg';
            if(!$valitor->validate($params)){
                $err = $valitor->getError()->__toString();
                $this->writeLog('createGroup: ');
                $this->writeLog($err);
                return $this->writeJson('10001', '', '参数错误');
            }

            $redisPool = RedisPool::defer();
            $user      = $redisPool->get('user_token_'.$params['token']);
            if(!$user){
                return $this->writeJson('10001', '', '重新登录');
            }
            $user = json_decode($user, true);
            $data = [
                'groupname' => $params['groupname'],
                'user_id'   => $user['id'],
                'avatar'    => $img
            ];
            $db = MysqlPool::defer();
            $old_group = $db->where('groupname', $params['groupname'])->count('`group`', null, 'id');
            if($old_group > 0){
                return $this->writeJson('10001', '', '群名已存在！');
            }
            $group_id = $db->insert('`group`', $data);
            if(!$group_id){
                return $this->writeJson('10001', '', '请重新创建');
            }
            $group_id = $db->getInsertId();
            $res_join = $db->insert('group_member', ['group_id' => $group_id, 'user_id' => $user['id']]);
            if($res_join){
                $data = [
                    'type'   => 'group',
                    'avatar' => $img,
                    'groupname' => $params['groupname'],
                    'id'        => $group_id
                ];
                return $this->writeJson(200, $data, '创建成功！');
            }else{
                return $this->writeJson(10001, '', '创建失败！');
            }
        }else{
            $this->render('Chat/create_group');
        }
    }

    public function joinGroup()
    {
        if($this->request()->getMethod() == 'POST'){
            $params = $this->request()->getRequestParam();
            $valitor = new Validate();
            $valitor->addColumn('token')->required();
            $valitor->addColumn('groupid')->required()->integer();
            if(!$valitor->validate($params)){
                $err = $valitor->getError()->__toString();
                $this->writeLog('joinGroup');
                $this->writeLog($err);
                return $this->writeJson(10001, '', '错误');
            }
            $redisPool = RedisPool::defer();
            $user      = $redisPool->get('user_token_'.$params['token']);
            if(!$user){
                return $this->writeJson(10001, '', '重新登录');
            }
            $user = json_decode($user, true);
            $db   = MysqlPool::defer();
            $joinid = $db->where('group_id', $params['groupid'])
                ->where('user_id', $user['id'])
                ->get('group_member', null, 'id');
            if($joinid){
                return $this->writeJson(10001, '', '你已是该群成员!');
            }
            $data = [
                'group_id' => $params['groupid'],
                'user_id'  => $user['id']
            ];
            $join = $db->insert('group_member', $data);
            if(!$join){
                return $this->writeJson(10001, '', '加入失败！');
            }
            $group = $db->where('id', $params['groupid'])->get('`group`', null, 'id,user_id,groupname,avatar');
            $data  = [
                'type'      => 'group',
                'avatar'    => $group[0]['avatar'],
                'groupname' => $group[0]['groupname'],
                'id'        => $group[0]['id']
            ];
            return $this->writeJson(200, $data, '加入成功！');
        }
    }
  
    public function refuseFriend()
    {
        if($this->request()->getMethod() == 'POST'){
            $id = $this->request()->getRequestParam('id');
            if(!is_numeric($id)){
                return $this->writeJson(10001, null, '参数错误');
            }
            $db      = MysqlPool::defer();
            $sys_msg = $db->where('id', $id)->getOne('system_message');
            if(!$sys_msg){
                return $this->writeJson(10001, null, '参数错误');
            }
            $res  = $db->where('id', $id)->update('system_message', ['status' => 2]);
            $data = [
                'user_id'  => $sys_msg['from_id'],
                'from_id'  => $sys_msg['user_id'],
                'type'     => 1,
                'status'   => 2,
                'time'     => time()
            ];
            $res1 = $db->insert('system_message', $data);
            if($res && $res1){
                return $this->writeJson(200, '', '已拒绝');
            }else{
                return $this->writeJson(1001, '', '操作失败');
            }
        }
    }
  
    public function joinGroup2()
    {
        if($this->request()->getMethod() == 'POST'){
            $params = $this->request()->getRequestParam();
            $valitor = new Validate();
            $valitor->addColumn('uid')->required()->integer();
            $valitor->addColumn('id')->required()->integer();
            $valitor->addColumn('groupid')->required()->integer();
            $valitor->addColumn('fid')->required()->integer();
            if(!$valitor->validate($params)){
                $err = $valitor->getError()->__toString();
                $this->writeLog('joinGroup');
                $this->writeLog($err);
                return $this->writeJson(10001, '', '错误');
            }
            $db   = MysqlPool::defer();
            $joinid = $db->where('group_id', $params['groupid'])
                ->where('user_id', $params['uid'])
                ->get('group_member', null, 'id');
            if($joinid){
                return $this->writeJson(10001, '', '你已是该群成员!');
            }
            $data = [
                'group_id' => $params['groupid'],
                'user_id'  => $params['uid']
            ];
            $join = $db->insert('group_member', $data);
            if(!$join){
                return $this->writeJson(10001, '', '加入失败！');
            }
            $db->where('id', $params['id'])->update('system_message',['status' => 1]);
            $group = $db->where('id', $params['groupid'])->get('`group`', null, 'id,user_id,groupname,avatar');
            $system_message_data = [
                'user_id'   => $params['uid'],
                'from_id'   => $params['fid'],
                'type'      => 1,
                'status'    => 1,
                'time'      => time()
            ];
            $db->insert('system_message', $system_message_data);
            $data  = [
                'type'      => 'group',
                'avatar'    => $group[0]['avatar'],
                'groupname' => $group[0]['groupname'],
                'id'        => $group[0]['id']
            ];
            return $this->writeJson(200, $data, '对方加入成功！');
        }
    }
  
    public function addFriend()
    {
        if($this->request()->getMethod() == 'POST'){
            $params  = $this->request()->getRequestParam();
            $valitor = new Validate();
            $valitor->addColumn('id')->required()->integer();
            $valitor->addColumn('groupid')->required()->integer();
            $valitor->addColumn('token')->required();
            if(!$valitor->validate($params)){
                return $this->writeJson(10001, null, '参数错误');
            }
            $redisPool = RedisPool::defer();
            $user      = $redisPool->get('user_token_'.$params['token']);
            $user      = json_decode($user, true);
            if(!$user){
                return $this->writeJson(10001, null, '重新登录');
            }
            $db = MysqlPool::defer();
            $sys_msg = $db->where('id', $params['id'])->getOne('system_message');
            $isFriend = $db->where('user_id', $sys_msg['user_id'])->where('friend_id', $sys_msg['from_id'])->get('friend');
            
            if($isFriend){
                return $this->writeJson(10001, null, '已经是好友了');
            }
            $gid = $db->where('user_id', $sys_msg['from_id'])->getOne('`friend_group`', 'id');
            $data = [
                [
                    'user_id'   => $sys_msg['user_id'],
                    'friend_id' => $sys_msg['from_id'],
                    'friend_group_id' => $params['groupid']
                ], [
                    'user_id'   => $sys_msg['from_id'],
                    'friend_id' => $sys_msg['user_id'],
                    'friend_group_id' => $gid['id']
                ]
            ];
            $res = $db->insertMulti('friend', $data);
            if(!$res){
                return $this->writeJson(10001, null, '添加失败');
            }
            $db->where('id', $params['id'])->update('system_message',['status' => 1]);

            $user = $db->where('id',$sys_msg['from_id'])->getOne('user');
            $data = [
                "type"     => "friend",
                "avatar"   => $user['avatar'],
                "username" => $user['nickname'],
                "groupid"  => $params['groupid'],
                "id"       => $user['id'],
                "sign"     => $user['sign']
            ];
            $system_message_data = [
                'user_id'   => $sys_msg['from_id'],
                'from_id'   => $sys_msg['user_id'],
                'type'      => 1,
                'status'    => 1,
                'time'      => time()
            ];
            $db->insert('system_message', $system_message_data);
            return $this->writeJson(200, $data, '添加成功');
        }
    }
}