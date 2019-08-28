<?php

namespace App\WebSocket;

use App\Utility\Pool\MysqlPool;
use App\Utility\Pool\RedisPool;
use EasySwoole\FastCache\Cache;
use \swoole_server;
use \swoole_websocket_server;
use \swoole_http_request;
use \Exception;

class WebSocketEvents
{
    static function onOpen(\swoole_websocket_server $server, \swoole_http_request $request)
    {
        $token = $request->get['token'];

        if(!isset($token)){
            $data = [
                'type' => 'token_expire'
            ];
            $server->push($request->fd, json_encode($data));
            return;
        }

        $db = MysqlPool::defer();
        $redisPool = RedisPool::defer();

        $user = $redisPool->get('user_token_'.$token);
        $user = json_decode($user, true);
        if($user == null){
            $data = [
                'type' => 'token_expire'
            ];
            $server->push($request->fd, json_encode($data));
            return;
        }

        Cache::getInstance()->set('uid'.$user['id'], ['value' => $request->fd], 3600);
        Cache::getInstance()->set('fd'.$request->fd, ['value' => $user['id']], 3600);

        $db->where('id', $user['id'])->update('user', ['status' => 'online']);

        $friend_list = $db->where('user_id', $user['id'])->get('friend');
        $data = [
            'type' => 'friendStatus',
            'uid'  => $user['id'],
            'status' => 'online'
        ];
        if(!$friend_list){$friend_list=[];}
        foreach($friend_list as $k => $v){
            $fd = Cache::getInstance()->get('uid'.$v['friend_id']);
            if($fd){
                $server->push($fd['value'], json_encode($data));
            }
        }

        $count = $db->where('user_id', $user['id'])->where('`read`', 0)->count('system_message');
        $data  = [
            'type'  => 'msgBox',
            'count' => $count
        ];

        $offline_msg = $db->where('user_id', $user['id'])->where('`status`', 0)->get('offline_message');
        if($offline_msg){
            foreach($offline_msg as $k => $v){
                $fd = Cache::getInstance()->get('uid'.$user['id']);
                if($fd){
                    $server->push($fd['value'], $v['data']);
                    $db->where('id', $v['id'])->update('offline_message', ['status' => 1]);
                }
            }
        }
        $server->push($request->fd, json_encode($data));
    }

    static function onClose(\swoole_server $server, int $fd, int $reactorId)
    {
        $uid = Cache::getInstance()->get('fd'.$fd);
        $db  = MysqlPool::defer();

        $friend_list = $db->where('user_id', $uid['value'])->get('friend');
        $data = [
            'type' => 'friendStatus',
            'uid'  => $uid['value'],
            'status' => 'offline'
        ];
        if($friend_list){
            foreach($friend_list as $k => $v){
                $ffd = Cache::getInstance()->get('uid'.$v['friend_id']);
                if($ffd) {
                    $server->push($ffd['value'], json_encode($data));
                }
            }
        }

        if($uid !== false){
            Cache::getInstance()->unset('uid'.$uid['value']);
        }
        Cache::getInstance()->unset('fd'.$fd);

        $db->where('id', $uid['value'])->update('user', ['status' => 'offline']);
    }
}