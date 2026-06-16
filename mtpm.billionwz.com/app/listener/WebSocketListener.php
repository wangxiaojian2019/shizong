<?php
namespace app\listener;

use think\swoole\contract\websocket\HandlerInterface;
use think\Request;
use Swoole\WebSocket\Frame;
use think\Event;
use think\swoole\websocket\Event as WsEvent;
use think\swoole\Websocket;
use think\swoole\websocket\Room;
use app\dao\product\product\StoreCategoryDao;
use crmeb\services\CacheService;
use app\services\product\product\StoreCategoryServices;
use app\services\product\product\StoreBiddingServices;
use app\dao\product\product\StoreBiddingDao;
use app\dao\product\product\StoreProductDao;
use think\Collection;


class WebSocketListener implements HandlerInterface
{
    protected $event;
    protected $Websocket;
    protected $services;

    public function __construct(Event $event,Websocket $Websocket,StoreCategoryServices $storeCategoryServices)
    {
        $this->event = $event;
        $this->Websocket = $Websocket;
        //$this->room = $room;
        $this->services = $storeCategoryServices;
       
    }
 
    /**
     * "onOpen" listener.
     *
     * @param Request $request
     */ 
    public function onOpen(Request $request)
    {
        $fd = $request->fd;
        //echo $fd;
       // $this->Websocket->emit(123,$this->room->getClients("cate1"));
        $this->event->trigger('swoole.websocket.Open', $request);
    }

    /**
     * "onMessage" listener.
     *
     * @param Frame $frame
     */
    public function onMessage(Frame $frame)
    {
        echo "收到来自客户端 {$frame->fd} 的消息: {$frame->data}\n";
        
       // $fd = $this->websocket->getSender(); // 获取当前发送消息的连接fd
        $data = json_decode($frame->data, true);
        if (!is_array($data) || !isset($data['type'])) {
            return;
        }
        if($data['type'] == "category"){ 
            
           
            $startTime = time();
            $currentData = "";
            while (true) {
                // 在这里编写你的核心任务逻辑
                // 例如：检查数据库、处理消息等
                sleep(1);
                $info = $this->services->getDetails($data['data']['id']); 
                if (!$info) {
                    continue;
                }
                if($currentData!=$info){
                    $currentData = $info; 
                    //$info['success'] = "category";
                    $info = json_encode($info);
                   
                    $this->Websocket->emit("category",$info);
                }
                
                if (time() - $startTime >= 20) {
                    break;
                }
            }
           // $this->Websocket->emitToRoom("cate1","23",$jsonString)
           
        }else if($data['type'] == "category1"){
            
           
            $startTime = time();
            $currentData = "";
            while (true) {
                // 在这里编写你的核心任务逻辑
                // 例如：检查数据库、处理消息等
                sleep(1);
                $info = $this->services->getSynchronizationlist(1);
                if (!$info) {
                    continue;
                }
                if($currentData!=$info){
                    $currentData = $info; 
                    //$info['success'] = "category";
                    $info = json_encode($info);
                   
                    $this->Websocket->emit("category1",$info);
                }
                
                if (time() - $startTime >= 20) {
                    break;
                }
            }
           // $this->Websocket->emitToRoom("cate1","23",$jsonString)
           
        }
        else if($data['type'] == "product1"){
            
            $startTime = time();
            $currentData = "";
            $currentData1 = "";
            $currentData2 = "";
            while (true) {
                $info = $this->services->productDetails($data['data']['id']);
                if (!$info || !isset($info['cate_id'])) {
                    if (time() - $startTime >= 20) {
                        break;
                    }
                    continue;
                }
                if($currentData1!=$info){
                    $currentData1 = $info;
                  
                    $this->Websocket->emit("product21",$info);
                }
                
                $biddingName = "bidding_".$data['data']['id'];
                $bidding = CacheService::get($biddingName);
                if(!$bidding){
                    $bidding = [];
                    $storeBiddingDao = new StoreBiddingDao();
                    $count = $storeBiddingDao->getCount(['product_id' => $data['data']['id']]);
                    $bidding = $storeBiddingDao->getList(['product_id' => $data['data']['id']],1,$count);
                    
                   
                    CacheService::set($biddingName,$bidding,600,'bidding');
                }
                if ($bidding instanceof Collection) {
                    $bidding = $bidding->toArray();
                } elseif (!is_array($bidding)) {
                    $bidding = [];
                }
                $collection = collect($bidding);
                $reversed = $collection->reverse();
                $bidding = $reversed->toArray();
                if($currentData2 != $bidding){
                    $currentData2 = $bidding;
                    $this->Websocket->emit("product31",$bidding);
                }
                
                $category = $this->services->getSynchronizationlist($info['cate_id']);
                if($currentData!=$category){
                    $currentData = $category;
                  
                    $this->Websocket->emit("product11",$category);
                }
            
                
                
                if (time() - $startTime >= 20) {
                    break;
                }
                
            }
        }
         else if($data['type'] == "product2"){
            
            $startTime = time();
            $currentData = "";
            $currentData1 = "";
            $currentData2 = "";
            
            $category1 = CacheService::get('category1');
            if(!$category1){
                $limit= 1;
                $category1 = $this->services->getSynchronization();
                $storeProductDao = new StoreProductDao();
                foreach($category1 as $k => $vo){
                   
                    $category1[$k]['count'] = $storeProductDao->getCount(['cid' => $vo['id'],'is_show' => 1]);
                }
                CacheService::set('category1',$category1,600,'category1');
               
            
            }
            
            
            while (true) {
                if (!$category1 || !isset($category1[0]['id'])) {
                    if (time() - $startTime >= 20) {
                        break;
                    }
                    continue;
                }
                
                $category = $this->services->getSynchronizationlist($category1[0]['id']);
                if (!$category || !isset($category['product'], $category['category']['number'])) {
                    if (time() - $startTime >= 20) {
                        break;
                    }
                    continue;
                }
                if($currentData!=$category){
                    $currentData = $category;
                  
                    $this->Websocket->emit("product11",$category);
                }
                
                
                $currentIndex = $category['category']['number'] - 1;
                if (!isset($category['product'][$currentIndex]['id'])) {
                    if (time() - $startTime >= 20) {
                        break;
                    }
                    continue;
                }
                $info = $this->services->productDetails($category['product'][$currentIndex]['id']);
                if (!$info) {
                    if (time() - $startTime >= 20) {
                        break;
                    }
                    continue;
                }
                if($currentData1!=$info){
                    $currentData1 = $info;
                  
                    $this->Websocket->emit("product21",$info);
                }
                
                $biddingName = "bidding_".$category['product'][$currentIndex]['id'];
                $bidding = CacheService::get($biddingName);
                if(!$bidding){
                    $bidding = [];
                    $storeBiddingDao = new StoreBiddingDao();
                    $count = $storeBiddingDao->getCount(['product_id' => $category['product'][$currentIndex]['id']]);
                    $bidding = $storeBiddingDao->getList(['product_id' => $category['product'][$currentIndex]['id']],1,$count);
                    
                   
                    CacheService::set($biddingName,$bidding,600,'bidding');
                }
                if ($bidding instanceof Collection) {
                    $bidding = $bidding->toArray();
                } elseif (!is_array($bidding)) {
                    $bidding = [];
                }
                $collection = collect($bidding);
                $reversed = $collection->reverse();
                $bidding = $reversed->toArray();
                if($currentData2 != $bidding){
                    $currentData2 = $bidding;
                    $this->Websocket->emit("product31",$bidding);
                }
                
               
            
                
                
                if (time() - $startTime >= 20) {
                    break;
                }
                
            }
        }
        else if($data['type'] == "product"){
            
            $startTime = time();
            $currentData = "";
            $currentData1 = "";
            $currentData2 = "";
            while (true) {
                $info = $this->services->productDetails($data['data']['id']);
                if (!$info) {
                    if (time() - $startTime >= 28) {
                        break;
                    }
                    continue;
                }

                $cateId = (int)($info['cate_id'] ?? 0);
                $cate = [];
                if ($cateId > 0) {
                    $cateDetail = $this->services->getDetails($cateId);
                    $cate = $cateDetail['category'] ?? [];
                }
                if ($cate && $currentData != $cate) {
                    $currentData = $cate;
                    $this->Websocket->emit("product1", $cate);
                }
                if($currentData1!=$info){
                    $currentData1 = $info;
                      
                    $this->Websocket->emit("product2",$info);
                }
                    
                
                $productId = (int)$data['data']['id'];
                $biddingServices = app()->make(StoreBiddingServices::class);
                $bidding = $biddingServices->getBiddingList(['product_id' => $productId]);
                $biddingPayload = [
                    'product_id' => $productId,
                    'list' => $bidding,
                ];
                if ($currentData2 != $bidding) {
                    $currentData2 = $bidding;
                    $this->Websocket->emit("product3", $biddingPayload);
                }
                    
                if (time() - $startTime >= 28) {
                    break;
                }
                
            }
           // $this->Websocket->join($data['data']['room']);
            //$this->Websocket->emitToRoom("cate1","23",$this->room->getClients($data['data']['room']));
        }
        //$rooms = $this->room->getRooms($fd);
    }

    /**
     * "onClose" listener.
     */
    public function onClose()
    {
       // echo "客户端连接已关闭\n";
       $this->Websocket->close();
       $this->Websocket->leave("cate1");
        $this->event->trigger('swoole.websocket.Close');
    }

    /**
     * 解码消息
     */
    protected function decode($payload)
    {
        $data = json_decode($payload, true);
        return new WsEvent($data['type'] ?? null, $data['data'] ?? null);
    }

    /**
     * 编码消息
     */
    public function encodeMessage($message)
    {
        if ($message instanceof WsEvent) {
            return json_encode([
                'type' => $message->type,
                'data' => $message->data,
            ]);
        }
        return $message;
    }

    /**
     * 回复客户端消息
     */
    public function replyToClient($receivedData)
    {
        // 这里需要使用Swoole Server实例来发送消息
        // 由于在Handler中无法直接访问Server，我们可以通过事件系统或者其他方式
        // 这里先简单记录，实际发送需要在其他地方处理
        // $replyData = [
        //     'status' => 'success',
        //     'message' => '服务器已收到您的消息',
        //     'received' => $receivedData,
        //     'timestamp' => time()
        // ];
        
        // echo "准备回复客户端 {$fd}: " . json_encode($replyData) . "\n";
       
       // $this->Websocket->emitToRoom("cate1","23",$receivedData);
        
        // 实际的消息发送需要在控制器或通过WebSocket类处理
        // app('swoole.server')->push($fd, json_encode($replyData));
    }
}