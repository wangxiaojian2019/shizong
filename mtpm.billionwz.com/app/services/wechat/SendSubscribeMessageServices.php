<?php
namespace app\services\wechat;
use app\services\BaseServices;
use crmeb\services\CacheService;
use app\dao\wechat\ScribeMessageDao;
use think\Request;

class SendSubscribeMessageServices
{
    
    /**
     * 发送带小程序跳转的模板消息
     */
    public function sendMiniProgramTemplate($openid,$templateId,$data,$pagepath)
    {
        $access_token = $this->getAccessToken();
        //return $access_token;
        if(!$access_token){
            return false;
        }
        $apiUrl = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=" . $access_token;
        
        $postData = [
            'touser' => $openid,
            'template_id' => $templateId,
            'data' => $data
        ];
            
            // 如果设置了小程序跳转参数，则加入请求体
        $miniprogramConfig = [
            'appid' => "wx85d110b272ae9810",
            'pagepath' => $pagepath
        ];
        
        $postData['miniprogram'] = $miniprogramConfig;
        
        $result = $this->curlPost($apiUrl, json_encode($postData));
        return $result;
    }
    
    
    /**
     * 获取微信access_token（带缓存）
     */
    private function getAccessToken()
    {
        $accessToken = CacheService::get('accessToken');
        if(!$accessToken){
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx58bc8784598a6921&secret=2f84c323d5b1a7624c93ceef95745904";
            $result = $this->curlRequest($url, '');
            $tokenData = json_decode($result, true);
                        
                 
            $accessToken = $tokenData['access_token'];    
            CacheService::set('accessToken',$accessToken,600,'accessToken');
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