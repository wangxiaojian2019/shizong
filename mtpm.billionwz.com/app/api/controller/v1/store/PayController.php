<?php
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2023 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------
namespace app\api\controller\v1\store;

use think\Request;
use app\dao\product\product\StoreMarginDao;
use app\services\product\product\StoreAuctionSessionServices;
use app\services\product\product\StoreTimedDepositServices;
use app\services\product\product\StoreNumberPlateServices;

/**
 * Class CategoryController
 * @package app\api\controller\v1\store
 */
class PayController
{
    
    public function index(Request $request) 
    {
        $uid = (int)$request->uid();
        $cid = (int)request()->param('cate_id');
        $openid = request()->param('openid');
        $total_fee = (float)request()->param('price');

        $sessionServices = app()->make(StoreAuctionSessionServices::class);
        if ($sessionServices->isTimedAuction($cid)) {
            $plateServices = app()->make(StoreNumberPlateServices::class);
            $participation = $plateServices->buildParticipationDetails($uid, $cid);
            if (!$participation['plate_registered']) {
                return json(['status' => 500, 'msg' => '请先办理号牌']);
            }
            if (!empty($participation['need_assign_plate'])) {
                return json(['status' => 500, 'msg' => '保证金已缴足，请返回专场点击「获取号牌」']);
            }
            if (!empty($participation['has_plate'])) {
                return json(['status' => 500, 'msg' => '您已有号牌，无需缴纳保证金']);
            }
            $depositServices = app()->make(StoreTimedDepositServices::class);
            $deposit = $depositServices->getStatus($uid);
            if ((int)($deposit['application'] ?? 0) === 1) {
                return json(['status' => 500, 'msg' => '退款审核中，暂不可缴纳保证金']);
            }
            if ($deposit['deposit_sufficient']) {
                return json(['status' => 500, 'msg' => '保证金已缴足，无需重复支付']);
            }
            $needPay = (float)$deposit['need_pay'];
            if ($needPay <= 0) {
                return json(['status' => 500, 'msg' => '保证金已缴足，无需重复支付']);
            }
            if ((float)$total_fee !== $needPay) {
                return json(['status' => 500, 'msg' => '限时拍保证金应缴纳' . $needPay . '元']);
            }
        }
        
        $date = date('YmdHis');
        // 获取微秒部分
        $microtime = microtime();
        list($msec, $sec) = explode(' ', $microtime);
        $micro = sprintf("%06d", $msec * 1000000); // 微秒部分补足6位
        // 生成随机数（5位）
        $rand = sprintf("%05d", mt_rand(1, 99999));
        $out_trade_no = $date . $micro . $rand;
        
        $storeMarginDao = new StoreMarginDao();
        $storeMarginDao->insertData1(['uid'=>$uid,'cate_id'=>$cid,'out_trade_no'=>$out_trade_no,'total_fee' => $total_fee]);
        
      
        // 支付配置参数
        $appid = config('wxpay.app_id');
        $mch_id = config('wxpay.mch_id');
        $nonce_str = $this->generateNonceStr(); // 生成随机字符串
       // $out_trade_no = request()->param('out_trade_no'); // 生成商户订单号

        $total_fee = $total_fee * 100;
        $body = '保证金支付';
        $notify_url = 'https://mtpm.billionwz.com/api/wxnotify'; // 支付结果通知地址
        $trade_type = 'JSAPI';
        $spbill_create_ip = request()->ip();
        
        // 构造参数数组
        $post = [
            'appid' => $appid,
            'mch_id' => $mch_id,
            'nonce_str' => $nonce_str,
            'body' => $body,
            'out_trade_no' => $out_trade_no,
            'total_fee' => $total_fee,
            'spbill_create_ip' => $spbill_create_ip,
            'notify_url' => $notify_url,
            'trade_type' => $trade_type,
            'openid' => $openid
        ];
        
        // 生成签名
        $post['sign'] = $this->generateSign($post);
        
        // 数组转XML
        $xml = $this->arrayToXml($post);
        
        // 请求微信统一下单接口
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $responseXml = $this->curlPost($url, $xml);
        $response = $this->xmlToArray($responseXml);
        
        if ($response['return_code'] == 'SUCCESS' && $response['result_code'] == 'SUCCESS') {
            // 生成小程序支付所需参数
            $timeStamp = (string)time();
            $payParams = [
                'appId' => $appid,
                'timeStamp' => $timeStamp,
                'nonceStr' => $nonce_str,
                'package' => 'prepay_id=' . $response['prepay_id'],
                'signType' => 'MD5'
            ];
            // 生成支付签名（注意这次签名的参数和下单时不同）
            $payParams['paySign'] = $this->generateSign($payParams);
            
            // 返回支付参数给小程序
            return json([
                'status' => 200,
                'data' => [
                    'timeStamp' => $payParams['timeStamp'],
                    'nonceStr' => $payParams['nonceStr'],
                    'package' => $payParams['package'],
                    'signType' => $payParams['signType'],
                    'paySign' => $payParams['paySign']
                ]
            ]);
        } else {
            return json(['status' => 500, 'msg' => '统一下单失败']);
        }
    }
    
    /**
     * 生成签名 (MD5)
     */
    private function generateSign($params)
    {
        // 参数按ASCII码从小到大排序（字典序）
        ksort($params);
        // 拼接成URL键值对字符串
        $string = urldecode(http_build_query($params)) . '&key=' . config('wxpay.key'); // 你的APIv2密钥
        // 生成MD5签名并转为大写
        return strtoupper(md5($string));
    }
    
    /**
     * 数组转XML
     */
    private function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }
    
    /**
     * XML转数组
     */
    private function xmlToArray($xml)
    {
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $values;
    }
    
    /**
     * CURL POST请求
     */
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
    
    /**
     * 生成随机字符串
     */
    private function generateNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }

}