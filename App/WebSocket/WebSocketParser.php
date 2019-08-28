<?php
namespace App\WebSocket;

use EasySwoole\Socket\AbstractInterface\ParserInterface;
use EasySwoole\Socket\Client\WebSocket;
use EasySwoole\Socket\Bean\Caller;
use EasySwoole\Socket\Bean\Response;
use http\QueryString;

class WebSocketParser implements ParserInterface
{
    /**
     * @param $raw
     * @param $client
     * @return Caller|null
     */
    public function decode($raw, $client) : ? Caller
    {
        $data = json_decode($raw, true);
        if(!is_array($data))
        {
            echo "decode msg error! \n".$raw.PHP_EOL;
            return null;
        }

        $caller = new Caller();
        $class  = '\\App\\WebSocket\\'.ucfirst($data['class'] ?? 'Index');
        $caller->setControllerClass($class);

        $caller->setAction($data['type'] ?? 'index');
        if (isset($data) && is_array($data)) {
            $args = $data;
        }
        $caller->setArgs($args ?? []);
        return $caller;
    }

    public function encode(Response $response, $client) : ? string
    {
        return $response->getMessage();
    }

}