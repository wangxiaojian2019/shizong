<?php
namespace app\services\product\product;
use app\services\BaseServices;
use crmeb\services\CacheService;
use app\dao\wechat\ScribeMessageDao;
use think\Request;

class SubscriptionInformation extends BaseServices
{
    
     public function __construct(ScribeMessageDao $dao)
    {
        $this->dao = $dao;
    }
    
    
    public function saveData($data)
    {
        $this->dao->insertData1($data);
    }
    
    public function getScribeMessages($where){
        return $this->dao->getScribeMessage($where);
    }
    
    public function getList($where){
        return $this->dao->getList($where);
    }
    
    private function getAccessToken()
    {
        $accessToken = CacheService::get('wx_access_token1');
        if (!$accessToken) {
            $appId = config('wechat.wechat_open_app_id');
            $secret = config('wechat.wechat_open_app_secret');
            $url =  $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appId."&secret=".$secret;
            $result = json_decode(file_get_contents($url), true);
            $accessToken = $result['access_token'];
            // 存入缓存，有效期设置为7100秒（比微信返回的7200秒稍短）
            CacheService::set('wx_access_token1', $accessToken, 7100);
           // CacheService::set('category',$category,600,'category');
        }
        return $accessToken;
    }
    
    public function send($openid,$data,$templateId){
        $accessToken = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token=".$accessToken;
        // $data = [
        //     'thing14' => ['value' => "2025年9月第一场拍卖会"],
        //     'date2' => ['value' => "2025年9月31日"],
        //     'number6' => ['value' => "23"],
        // ];
       // $templateId = "lzI5nOgDKOhAQoUexbLXDWrK2cSU05BgLA1dEbsYuEM";
       // $openid ="oUaVNvrh_pA9O7TJ7zQsMnhVZ_BI";
        $postData = [
            'touser' => $openid,
            'template_id' => $templateId,
            //'page' => $page, // 可选，点击消息跳转的小程序页面
            'data' => $data, // 模板数据，需按微信要求格式组装
        ];
        
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-type: application/json',
                'content' => json_encode($postData),
            ],
        ];
        
        
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        return $result;
    }
}