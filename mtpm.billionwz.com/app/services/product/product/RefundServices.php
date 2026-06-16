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

namespace app\services\product\product;


use app\dao\product\product\StoreNumberPlateDao;
use app\services\BaseServices;
use crmeb\services\CacheService;
use app\dao\product\product\StoreCategoryDao;
use think\Request;
use app\dao\user\UserDao;
use app\dao\product\product\StoreMarginDao;

/**
 * Class StoreProductCateService
 * @package app\services\product\product
 * @method productIdByCateId(array $productId) 根据商品id获取分类id
 * @method cateIdByProduct(array $cate_id) 根据分类获取商品id
 */
class RefundServices extends BaseServices
{
    public function __construct(StoreNumberPlateDao $dao)
    {
        $this->dao = $dao;
    }

    
    public function refund($out_trade_no,$out_refund_no,$total_fee,$refund_fee){
        // 退款请求地址
        $url = "https://api.mch.weixin.qq.com/secapi/pay/refund";
        // $out_trade_no = "2025101013560053488092232";
        // $out_refund_no = "2025101315574379484464190";
        // $total_fee = 1;
        // $refund_fee = 1;
        // 准备退款参数
        $params = [
            'appid' => config('wxpay.app_id'),
            'mch_id' => config('wxpay.mch_id'),
            'nonce_str' => $this->createNonceStr(), // 生成随机字符串
            'out_trade_no' => $out_trade_no,
            'out_refund_no' => $out_refund_no,
            'total_fee' => $total_fee,
            'refund_fee' => $refund_fee,
        ];
        
        // 生成签名并加入数组
        $params['sign'] = $this->makeSign($params);
        
        // 将数组转换为XML
        $xml = $this->arrayToXml($params);
        
        // 携带双向证书发送POST请求
        $response = $this->curlPostSsl($url, $xml);
        
        // 将返回的XML解析为数组
        $result = $this->xmlToArray($response);
        
        // 处理退款结果
        if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
            //$this->dao->editPlate(['orderId' => $out_trade_no],['paystatus' => 2]);
            
            $storeMarginDao = new StoreMarginDao();
            $order = $storeMarginDao->selectMargin(['out_trade_no'=> $out_trade_no]);
            
            $storeMarginDao->editMargin(['out_trade_no'=>$out_trade_no],['paystatus'=>2]);
            
            return ['ok' => true, 'msg' => '退款成功'];
        }

        return ['ok' => false, 'msg' => $result['err_code_des'] ?? $result['return_msg'] ?? '退款失败'];
    } 
    
    /**
     * 生成随机字符串
     * @param int $length
     * @return string
     */
    private function createNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
    
    /**
     * 生成签名
     * @param array $params
     * @return string
     */
    private function makeSign($params)
    {
        // 过滤空值并按字典序排序
        ksort($params);
        $string = "";
        foreach ($params as $k => $v) {
            if ($v != '' && $k != 'sign' && !is_array($v)) {
                $string .= $k . "=" . $v . "&";
            }
        }
        // 拼接API密钥
        $string = $string . "key=" .  config('wxpay.key');
        // MD5签名并转为大写
        $sign = strtoupper(md5($string));
        return $sign;
    }
    
    /**
     * 数组转XML
     * @param array $values
     * @return string
     */
    private function arrayToXml($values)
    {
        $xml = "<xml>";
        foreach ($values as $key => $val) {
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
     * 携带双向证书的cURL请求
     * @param string $url 请求地址
     * @param string $xml 要发送的XML数据
     * @param int $second 超时时间
     * @return mixed
     */
    private function curlPostSsl($url, $xml, $second = 30)
    {
        $ch = curl_init();
        
        // 设置curl选项
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // 严格校验
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        
        // 设置证书
        // 证书路径请填写绝对路径，例如：/www/wwwroot/cert/apiclient_cert.pem
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLCERT, 'pem/d15b45717af1bef0b9f3324af97a5c2b.pem');
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLKEY, 'pem//aa12e0c29994e9440904538c762a6317.pem');
        
        // 以上证书文件需要从微信商户平台下载，并妥善保管
        
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        
        $data = curl_exec($ch);
        
        if ($data === false) {
            $error = curl_error($ch);
            // 记录错误日志
            // ...
        }
        
        curl_close($ch);
        return $data;
    }
    
    
    /**
     * XML转数组
     * @param string $xml
     * @return array
     */
    private function xmlToArray($xml)
    {
        if (!$xml) {
            return [];
        }
        // 禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $values;
    }

}
