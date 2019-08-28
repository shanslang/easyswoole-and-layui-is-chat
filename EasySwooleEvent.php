<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/5/28
 * Time: 下午6:33
 */

namespace EasySwoole\EasySwoole;


use App\Utility\Pool\RedisPool;
use EasySwoole\Component\Pool\PoolManager;
use EasySwoole\EasySwoole\Swoole\EventRegister;
use EasySwoole\EasySwoole\AbstractInterface\Event;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use EasySwoole\Socket\Dispatcher;
use EasySwoole\FastCache\Cache;
use EasySwoole\Component\Process\AbstractProcess;
use App\WebSocket\WebSocketParser;
use App\IpList;
use App\WebSocket\WebSocketEvents;


class EasySwooleEvent implements Event
{

    public static function initialize()
    {
        // TODO: Implement initialize() method.
        date_default_timezone_set('Asia/Shanghai');
    }

    public static function mainServerCreate(EventRegister $register)
    {
        // 开启IP限流
        //IpList::getInstance();
        // $class = new class('IpAccessCount') extends AbstractProcess{
        //protected function run($arg)
        // {
        // $this->addTick(5*1000, function (){
        /**
         * 正常用户不会有一秒超过6次的api请求
         * 做列表记录并清空
         */
        // $list = IpList::getInstance()->accessList(30);
        //var_dump($list);
        //IpList::getInstance()->clear();
        //  });
        //}
        //};
        $redis_pool = PoolManager::getInstance()->register(RedisPool::class, Config::getInstance()->getConf('REDIS.POOL_MAX_NUM'));
        // var_dump($redis_pool);

        // TODO: Implement mainServerCreate() method.
        $conf = new \EasySwoole\Socket\Config();
        $conf->setType(\EasySwoole\Socket\Config::WEB_SOCKET);
        $conf->setParser(new WebSocketParser());
        $dispatch = new Dispatcher($conf);
        $register->set(EventRegister::onMessage, function (\swoole_websocket_server $server, \swoole_websocket_frame $frame) use ($dispatch){
            $dispatch->dispatch($server, $frame->data, $frame);
        });
        $register->add(EventRegister::onOpen, [WebSocketEvents::class, 'onOpen']);
        $register->add(EventRegister::onClose, [WebSocketEvents::class, 'onClose']);

        Cache::getInstance()->setTempDir(EASYSWOOLE_TEMP_DIR)->attachToServer(ServerManager::getInstance()->getSwooleServer());
    }

    public static function onRequest(Request $request, Response $response): bool
    {
        // $fd = $request->getSwooleRequest()->fd;
        //  $ip = ServerManager::getInstance()->getSwooleServer()->getClientInfo($fd)['remote_ip'];

        // 如果当前周期的访问频率已经超过设置的值，则拦截
        // 测试的时候可以将30改小，比如3
        //if (IpList::getInstance()->access($ip) > 2) {
        /**
         * 直接强制关闭连接
         */
        // ServerManager::getInstance()->getSwooleServer()->close($fd);
        // 调试输出 可以做逻辑处理
        //echo '被拦截'.PHP_EOL;
        // return false;
        // }
        // 调试输出 可以做逻辑处理
        //echo '正常访问'.PHP_EOL;
        // TODO: Implement onRequest() method.
        return true;
    }

    public static function afterRequest(Request $request, Response $response): void
    {
        // TODO: Implement afterAction() method.
    }
}
