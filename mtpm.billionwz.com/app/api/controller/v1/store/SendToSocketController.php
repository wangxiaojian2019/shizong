<?php
namespace app\api\controller\v1\store;
use think\facade\Log;
class SendToSocketController{
    public function sendToSocket()
    {
        // Socket 服务器地址和端口
               // 1. 你的业务逻辑，例如更新数据库中的商品价格
        // ...
        
        // 2. 准备要推送的消息
        $pushData = [
            'type' => 'category', // 指定要推送的房间ID
            'data' => [
                'active' => 1,
               
            ]
        ];
        
        // 3. 使用Curl向本机的WebSocket服务的HTTP接口发送推送请求:cite[7]
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://124.221.232.159:8080"); // 地址和端口与WebSocket服务一致
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($pushData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            return json(['code' => 200, 'msg' => '价格更新并推送成功']);
        } else {
            Log::error('WebSocket推送失败: ' . $result);
            return json(['code' => 500, 'msg' => '价格更新成功，但推送失败']);
        }
        return 1;
    }   
}
