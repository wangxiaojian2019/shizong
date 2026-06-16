<?php
namespace app\api\controller\v1\wechat;

use think\Request;

class Login
{
    public function index(Request $request)
    {
        
        $appId = "wx85d110b272ae9810";
        $secre = "c757d1679e97e0b02968c075f6d57b58";
        $code = $request->param('code');
        //获取微信openid
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=".$appId."&secret=".$secre."&js_code=".$code."&grant_type=authorization_code";
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
        $result = curl_exec($curl);
        $res = json_decode($result,true);
        
        return app('json')->success($res);

    
    }
}