<?php
/**
 * CreateTime: 2019/7/11 13:56
 * Author: hhh
 * Description:
 */
namespace App\WebSocket;

use App\Utility\Pool\MysqlPool;
use App\Utility\Pool\RedisPool;
use EasySwoole\EasySwoole\ServerManager;
use EasySwoole\EasySwoole\Swoole\Task\TaskManager;
use EasySwoole\Socket\AbstractInterface\Controller;
use EasySwoole\FastCache\Cache;
use EasySwoole\Validate\Validate;

/**
 * Class Index
 *
 * 此类是默认的 websocket 消息解析后访问的 控制器
 *
 * @package App\WebSocket
 */
class Index extends Controller
{
    public function chatMessage()
    {
        $info = $this->caller()->getArgs();
        $info = $info['data'];
        //var_dump($info);
        $redisPool = RedisPool::defer();

        $user = $redisPool->get('user_token_'.$info['token']);
        $user = json_decode($user, true);
        if($user == null){
            $data = [
                'type' => 'token_expire'
            ];
            $this->response()->setMessage(json_encode($data));
            return;
        }

        $db = MysqlPool::defer();

        if($info['to']['type'] == 'friend'){
            $data = [
                'username' => $info['mine']['username'],
                'avatar'   => $info['mine']['avatar'],
                'id'       => $info['mine']['id'],
                'type'     => $info['to']['type'],
                'content'  => $info['mine']['content'],
                'cid'      => 0,
               // 'mine'     => $info['mine']['mine'],
                'mine'     => $user['id'] == $info['to']['id'] ? true : false,//要通过判断是否是我自己发的
                'fromid'   => $info['mine']['id'],
                'timestamp'=> time() * 1000
            ];
            if($user['id'] == $info['to']['id']){
                return;
            }

            $server = ServerManager::getInstance()->getSwooleServer();

            $fd     = Cache::getInstance()->get('uid'.$info['to']['id']);
            if($fd == false){
                $offline_message = [
                    'user_id' => $info['to']['id'],
                    'data'    => json_encode($data)
                ];
                $db->insert('offline_message', $offline_message);
            }else{
                $server->push($fd['value'], json_encode($data));
            }

            $record_data = [
                'user_id'   => $info['mine']['id'],
                'friend_id' => $info['to']['id'],
                'group_id'  => 0,
                'content'   => $info['mine']['content'],
                'time'      => time()
            ];
            $db->insert('chat_record', $record_data);
        }elseif($info['to']['type'] == 'group'){
            $data = [
                'username' => $info['mine']['username'],
                'avatar'   => $info['mine']['avatar'],
                'id'       => $info['to']['id'],
                'type'     => $info['to']['type'],
                'content'  => $info['mine']['content'],
                'cid'      => 0,
                'mine'     => false,
                'fromid'   => $info['mine']['id'],
                'timestamp'=> time() * 1000
            ];
            $list = $db->join('user u', 'u.id = gm.user_id')
                ->where('group_id', $info['to']['id'])
                ->get('group_member as gm', null, 'u.id');

            // 异步推送
            TaskManager::async(function() use ($list, $user, $data){
                $server = ServerManager::getInstance()->getSwooleServer();
                $db = MysqlPool::defer();

                foreach ($list as $k => $v){
                    if($v['id'] == $user['id']){
                        continue;
                    }
                    $fd = Cache::getInstance()->get('uid'.$v['id']);
                    if($fd == false){
                        $offline_message = [
                            'user_id' => $v['id'],
                            'data'    => json_encode($data)
                        ];
                        $db->insert('offline_message', $offline_message);
                    }else{
                        $server->push($fd['value'], json_encode($data));
                    }
                }
            });

            $record_data = [
                'user_id'   => $info['mine']['id'],
                'friend_id' => 0,
                'group_id'  => $info['to']['id'],
                'content'   => $info['mine']['content'],
                'time'      => time()
            ];
            $db->insert('chat_record', $record_data);
        }
    }
  
    public function joinNotify()
    {
        $info = $this->caller()->getArgs();
        if(!isset($info['token'])){
            $data = [
                'type' => 'token_expire'
            ];
            $this->response()->setMessage(json_encode($data));
            return;
        }
        $redisPool = RedisPool::defer();
        $user      = $redisPool->get('user_token_'.$info['token']);
        $user      = json_decode($user, true);
        if(!$user){
            $data = [
                'type' => 'token_expire'
            ];
            $this->response()->setMessage(json_encode($data));
            return;
        }
        $db   = MysqlPool::defer();
        $list = $db->where('group_id', $info['groupid'])->get('group_member');
        $data = [
            'type' => 'joinNotify',
            'data' => [
                'system' => true,
                'id'     => $info['groupid'],
                'type'   => 'group',
                'content'=> $user['nickname'].'加入了群聊，欢迎下新人吧~'
            ]
        ];
        TaskManager::async(function () use ($list, $user, $data){
            $server = ServerManager::getInstance()->getSwooleServer();
            foreach ($list as $k => $v){
                $fd = Cache::getInstance()->get('uid'.$v['user_id']);
                if($fd){
                    $server->push($fd['value'], json_encode($data));
                }
            }
        });
    }
  
    public function addGroup()
    {
        $info    = $this->caller()->getArgs();
        $valitor = new Validate();
        $valitor->addColumn('to_group_id')->required()->integer();
        $valitor->addColumn('token')->required();
        if(!$valitor->validate($info)){
            $data = [
                'type' => 'token_expire'
            ];
            $this->response()->setMessage(json_encode($data));
            return;
        }
        $redisPool = RedisPool::defer();
        $user      = $redisPool->get('user_token_'.$info['token']);
        if(!$user){
            $data = [
                'type' => 'token_expire'
            ];
            $this->response()->setMessage(json_encode($data));
            return;
        }
        $user   = json_decode($user, true);
        $db     = MysqlPool::defer();
        $isGroupid = $db->where('group_id', $info['to_group_id'])->where('user_id', $user['id'])->get('group_member');
        if($isGroupid){
            $data = [
                'type' => 'layer',
                'code' => '10001',
                'msg'  => '你已经是该群成员'
            ];
            $this->response()->setMessage(json_encode($data));
            return;
        }
        $groupOwner = $db->where('id', $info['to_group_id'])->getOne('`group`', 'user_id');
        $system_message_data = [
            'user_id' => $groupOwner['user_id'],
            'from_id' => $user['id'],
            'group_id' => $info['to_group_id'],
            'remark'  => $info['remark'],
            'type'    => 2,
            'time'    => time()
        ];
        $db->insert('system_message', $system_message_data);
        $count = $db->where('user_id', $groupOwner['user_id'])->where('`read`', 0)->count('system_message');
        $data  = [
            'type'  => 'msgBox',
            'count' => $count
        ];
        $fd     = Cache::getInstance()->get('uid'.$groupOwner['user_id']);
        if($fd == false){
            $offline_message = [
                'user_id' => $groupOwner['user_id'],
                'data' => json_encode($data),
            ];
            //插入离线消息
            $db->insert('offline_message', $offline_message);
        }else{
            $server = ServerManager::getInstance()->getSwooleServer();
            $server->push($fd['value'], json_encode($data));
        }
    }
  
    public function addFriend()
    {
        $info = $this->caller()->getArgs();
        $valitor = new Validate();
        $valitor->addColumn('to_user_id')->required()->integer();
        $valitor->addColumn('to_friend_group_id')->required()->integer();
        $valitor->addColumn('token')->required();
        if(!$valitor->validate($info)){
            $data = [
                'type' => 'token_expire'
            ];
            $this->response()->setMessage(json_encode($data));
            return;
        }
        $redisPool = RedisPool::defer();
        $user      = $redisPool->get('user_token_'.$info['token']);
        $user      = json_decode($user, true);
        if(!$user){
            $data = [
                'type' => 'token_expire'
            ];
            $this->response()->setMessage(json_encode($data));
            return;
        }
        if($info['to_user_id'] == $user['id']){
            $data = [
                'type' => 'layer',
                'code' => 500,
                'msg'  => '不能添加自己为好友'
            ];
            $this->response()->setMessage(json_encode($data));
            return;
        }
        $db = MysqlPool::defer();
        $isFriend = $db->where('friend_id', $info['to_user_id'])->where('user_id', $user['id'])->getOne('friend');
        if($isFriend){
            $data = [
                'type' => 'layer',
                'code' => 500,
                'msg'  => '对方已经是你的好友， 不可重复添加'
            ];
            $this->response()->setMessage(json_encode($data));
            return;
        }
        $system_message_data = [
            'user_id' => $info['to_user_id'],
            'from_id' => $user['id'],
            'remark'  => $info['remark'],
            'type'    => 0,
            'group_id' => $info['to_friend_group_id'],
            'time'    => time()
        ];
        $db->insert('system_message', $system_message_data);
        $count = $db->where('user_id', $info['to_user_id'])->where('`read`', 0)->count('system_message');
        $data  = [
            'type'  => 'msgBox',
            'count' => $count
        ];

        $fd     = Cache::getInstance()->get('uid'.$info['to_user_id']);
        if($fd == false){
            $offline_message = [
                'user_id' => $info['to_user_id'],
                'data' => json_encode($data),
            ];
            //插入离线消息 
            $db->insert('offline_message', $offline_message);
        }else{
            $server = ServerManager::getInstance()->getSwooleServer();
            $server->push($fd['value'], json_encode($data));
        }
    }
  
    public function refuseFriend()
    {
        $params = $this->caller()->getArgs();
        $id     = $params['id'];
        if(!is_numeric($id)){
            $data = [
                'type' => 'layer',
                'code' => 500,
                'msg'  => '参数错误'
            ];
            $this->response()->setMessage(json_encode($data));
            return;
        }
        $db      = MysqlPool::defer();
        $sys_msg = $db->where('id', $id)->getOne('system_message');
        if(!$sys_msg){
            $data = [
                'type' => 'layer',
                'code' => 500,
                'msg'  => '参数错误'
            ];
            $this->response()->setMessage(json_encode($data));
            return;
        }
        $count = $db->where('user_id', $sys_msg['from_id'])->where('`read`', 0)->count('system_message');
        $data  = [
            'type'  => 'msgBox',
            'count' => $count
        ];
        $fd = Cache::getInstance()->get('uid'.$sys_msg['from_id']);
        if($fd){
            $server = ServerManager::getInstance()->getSwooleServer();
            $server->push($fd['value'], json_encode($data));
        }
    }
  
    public function addList()
    {
        $info    = $this->caller()->getArgs();
        $valitor = new Validate();
        $valitor->addColumn('token')->required();
        $valitor->addColumn('fromgroup')->required()->integer();
        $valitor->addColumn('id')->required()->integer();
        if(!$valitor->validate($info)){
            $data = [
                'type' => 'layer',
                'code' => 500,
                'msg'  => '参数错误'
            ];
            $this->response()->setMessage(json_encode($data));
            return;
        }
        $redisPool = RedisPool::defer();
        $user      = $redisPool->get('user_token_'.$info['token']);
        $user      = json_decode($user, true);
        if(!$user) {
            $data = [
                'type' => 'token_expire'
            ];
            $this->response()->setMessage(json_encode($data));
            return;
        }
        $db = MysqlPool::defer();
        $data = [
            'type' => 'addList',
            'data' => [
                'type'   => 'friend',
                'avatar' => $user['avatar'],
                'username' => $user['nickname'],
                'groupid'  => $info['fromgroup'],
                'id'       => $user['id'],
                'sign'     => $user['sign']
            ]
        ];
        $count = $db->where('user_id', $info['id'])->where('`read`', 0)->count('system_message');
        $data1 = [
            'type'  => 'msgBox',
            'count' => $count
        ];
        $fd = Cache::getInstance()->get('uid'.$info['id']);
        if($fd == false){
            $offline_msg = [
                'user_id' => $info['id'],
                'data'    => json_encode($data1)
            ];
            $db->insert('offline_message', $offline_msg);
        }else{
            $server = ServerManager::getInstance()->getSwooleServer();
            $server->push($fd['value'], json_encode($data));
            $server->push($fd['value'], json_encode($data1));
        }
    }

    function hello()
    {
        $this->response()->setMessage('call hello with arg:'. json_encode($this->caller()->getArgs()));
    }

    public function who(){
        $this->response()->setMessage('your fd is '. $this->caller()->getClient()->getFd());
    }

    function delay()
    {
        $this->response()->setMessage('this is delay action');
        $client = $this->caller()->getClient() ;

        // 异步推送, 这里直接 use fd也是可以的
        TaskManager::async(function () use ($client){
            $server = ServerManager::getInstance()->getSwooleServer();
            $i = 0;
            while ($i < 5) {
                sleep(1);
                $server->push($client->getFd(),'push in http at '. date('H:i:s'));
                $i++;
            }
        });
    }
}