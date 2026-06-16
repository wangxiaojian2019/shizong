<?php

namespace app\services\product\product;

use think\swoole\Websocket;
use think\swoole\contract\websocket\HandlerInterface;

class WebsocketServices extends Websocket{
    protected $websocket;

    public function __construct(Websocket $websocket)
    {
        $this->websocket = $websocket;
    }
    
    public function onOpen($server, $request)
    {
        $fd = $request->fd;
        
    }
}