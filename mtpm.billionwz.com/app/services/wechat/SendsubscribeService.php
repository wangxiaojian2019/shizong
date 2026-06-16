<?php
namespace app\services\wechat;
use app\services\BaseServices;
use crmeb\services\CacheService;
use app\dao\wechat\ScribeMessageDao;
use think\Request;

class SendsubscribeService
{
    
    /**
     * 发送带小程序跳转的模板消息
     */
    public function sendSubscribeMessage($data) {
        $access_token = $this->getAccessToken();
        //return $access_token;
        if(!$access_token){
            return false;
        }
        $url = "https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token=".$access_token;
       // $postData = json_encode($data, JSON_UNESCAPED_UNICODE);
       // $response = httpRequest($url, $postData);
        //$result = json_decode($response, true);
        
        $result = $this->curlPost($url, json_encode($data));
        if (isset($result['errcode']) && $result['errcode'] !== 0) {
            throw new Exception("发送订阅消息失败: " . $result['errmsg']);
        }
        return true;
    }
    
    
     private function getAccessToken()
    {
        $accessToken = CacheService::get('accessToken11');
        if(!$accessToken){
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx85d110b272ae9810&secret=c757d1679e97e0b02968c075f6d57b58";
            $result = $this->curlRequest($url, '');
            $tokenData = json_decode($result, true);
                        
                 
            $accessToken = $tokenData['access_token'];    
            CacheService::set('accessToken11',$accessToken,600,'accessToken11');
           // return 1;
        }
        return $accessToken;
    }
    
    
    private function curlPost($url, $xml)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
    
    
    private function curlRequest($url, $data = '', $method = 'GET', $headers = [], $isJson = false)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
    
        // 如果是HTTPS，则跳过证书验证（根据实际情况，生产环境建议配置证书）
        if (strpos($url, 'https://') !== false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
    
        // 如果设置JSON格式，则设置Content-Type
        if ($isJson) {
            $headers[] = 'Content-Type: application/json';
        }
    
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
    
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
    
        $output = curl_exec($ch);
        curl_close($ch);
    
        return $output;
    }
    
   
}