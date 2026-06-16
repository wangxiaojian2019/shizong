<?php
namespace app\websocket;

use think\swoole\Websocket;
use think\swoole\websocket\Room;

class ProductWebSocket
{
    protected $websocket;
    protected $room;

    public function __construct(Websocket $websocket, Room $room)
    {
        $this->websocket = $websocket;
        $this->room = $room;
    }

    public function onOpen($server, $request)
    {
        // 客户端连接时触发
        $fd = $request->fd;
        echo "客户端 {$fd} 连接成功\n";

        // 将客户端加入房间（可选）
        $this->room->add($fd, 'product_room');

        // 向客户端发送欢迎消息
        $this->websocket->to($fd)->emit('message', [
            'type' => 'welcome',
            'message' => '连接成功'
        ]);
    }

    public function onMessage($server, $frame)
    {
        // 收到消息时触发
        $data = json_decode($frame->data, true);
        if (!$data) {
            return;
        }

        switch ($data['type'] ?? '') {
            case 'subscribe':
                // 客户端订阅产品更新
                $this->handleSubscribe($frame->fd, $data);
                break;
            case 'unsubscribe':
                // 取消订阅
                $this->handleUnsubscribe($frame->fd);
                break;
            case 'get_products':
                // 获取产品数据
                $this->sendProducts($frame->fd);
                break;
            default:
                // 未知消息类型
                $this->websocket->to($frame->fd)->emit('message', [
                    'type' => 'error',
                    'message' => '未知消息类型'
                ]);
        }
    }

    public function onClose($server, $fd)
    {
        // 客户端关闭连接时触发
        echo "客户端 {$fd} 断开连接\n";

        // 从房间中移除
        $this->room->delete($fd, 'product_room');
    }

    private function handleSubscribe($fd, $data)
    {
        // 将客户端加入订阅者房间
        $this->room->add($fd, 'product_subscribers');

        $this->websocket->to($fd)->emit('message', [
            'type' => 'success',
            'message' => '订阅成功'
        ]);
    }

    private function handleUnsubscribe($fd)
    {
        // 将客户端从订阅者房间移除
        $this->room->delete($fd, 'product_subscribers');

        $this->websocket->to($fd)->emit('message', [
            'type' => 'success',
            'message' => '取消订阅成功'
        ]);
    }

    private function sendProducts($fd)
    {
        // 这里模拟产品数据，实际应从数据库或其它地方获取
        $products = [
            [
                'id' => 1,
                'name' => '产品1',
                'price' => 100,
                'stock' => 10
            ],
            [
                'id' => 2,
                'name' => '产品2',
                'price' => 200,
                'stock' => 20
            ]
        ];

        $this->websocket->to($fd)->emit('message', [
            'type' => 'products',
            'data' => $products
        ]);
    }

    // 广播产品更新给所有订阅者
    public function broadcastProductUpdate($product)
    {
        $this->websocket->to('product_subscribers')->emit('message', [
            'type' => 'product_update',
            'product' => $product
        ]);
    }
}