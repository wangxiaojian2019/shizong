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
use think\facade\Log;
use app\services\product\product\StoreNumberPlateServices;
use app\services\product\product\StoreAuctionSessionServices;
use app\services\product\product\StoreTimedDepositServices;
use app\dao\user\UserDao;
use app\dao\product\product\StoreMarginDao;
use crmeb\services\CacheService;

class NotifyController {
    /** 同步拍基础保证金档位（元），与 synchronouslist 首缴选项一致，非限时拍配置 */
    private const SYNC_BASE_DEPOSIT = 5000;

    protected $services;
    public function __construct(StoreNumberPlateServices $services)
    {
        $this->services = $services;
    }
    
    public function handleNotify(Request $request)
    {
        // 1. 获取微信回调的原始 XML 数据
        $xmlData = $request->getContent();
        
        // 2. 解析 XML 数据
        $result = $this->parseXml($xmlData);
        
        // 3. 验证签名（需自行实现签名校验逻辑）
        if (!$this->verifySign($result)) {
           // Log::error('1');
            return $this->replyWechat('FAIL', '签名验证失败');
        }
        
        // 4. 检查支付状态（SUCCESS 表示支付成功）
        if ($result['return_code'] !== 'SUCCESS' || $result['result_code'] !== 'SUCCESS') {
            // Log::error('2');
            return $this->replyWechat('FAIL', '支付未成功');
        }
        
        // 5. 获取订单号（商户订单号）
        $orderNo = $result['out_trade_no'];
        
        // 6. 查询本地订单
        $storeMarginDao = new StoreMarginDao();
        $order = $storeMarginDao->selectMargin(['out_trade_no'=>$orderNo]);
        if (!$order) {
            //Log::error('3');
            return $this->replyWechat('FAIL', '订单不存在');
        }
        
        // 7. 检查订单是否已处理（避免重复回调）
        if ($order['paystatus'] == 1) {
            //Log::error('4');
            return $this->replyWechat('SUCCESS', 'OK');
        }
        
       
        
        // 8. 更新订单状态
        $storeMarginDao->editMargin(['out_trade_no'=>$orderNo],['paystatus'=>1]);
        
        $cateId = (int)$order['cate_id'];
        $uid = (int)$order['uid'];
        $sessionServices = app()->make(StoreAuctionSessionServices::class);
        $isTimed = $sessionServices->isTimedAuction($cateId);

        if ($isTimed && (float)$order['total_fee'] > 0) {
            $depositServices = app()->make(StoreTimedDepositServices::class);
            $depositServices->onPaymentSuccess($uid, (float)$order['total_fee']);
            $this->services->tryAssignPlateAfterDeposit($uid, $cateId);
            $ctx = $sessionServices->resolveContext($cateId);
            if ($ctx['mode'] === 'cate') {
                CacheService::clear('Plates_' . $uid . $cateId);
                $deposit = $depositServices->getStatus($uid);
                $plateRow = $this->services->selectPlate(['cate_id' => $cateId, 'user_id' => $uid]);
                $adminAssigned = (int)($plateRow['plate_source'] ?? 0) === StoreNumberPlateServices::PLATE_SOURCE_ADMIN;
                if ($deposit['deposit_sufficient'] && !$adminAssigned) {
                    $this->services->editPlate1(['cate_id' => $cateId, 'user_id' => $uid], [
                        'state' => 1,
                        'paystatus' => 1,
                        'plate_source' => StoreNumberPlateServices::PLATE_SOURCE_DEPOSIT,
                    ]);
                }
            } elseif ($ctx['mode'] === 'session' && (int)$ctx['session_id'] > 0) {
                foreach ($sessionServices->getSessionCateIdsBySessionId((int)$ctx['session_id']) as $cid) {
                    CacheService::clear('Plates_' . $uid . $cid);
                }
                CacheService::clear('Plates_' . $uid . $cateId);
            }
        } elseif (!$isTimed) {
            $plate = $this->services->selectPlatedetails(['cate_id' => $cateId, 'user_id' => $uid]);
            $name = "Plates_" . $uid . $cateId;
            if ((float)$order['total_fee'] == self::SYNC_BASE_DEPOSIT) {
                $plate['state'] = 1;
                $plate['paystatus'] = 1;
                CacheService::clear($name);
                $this->services->editPlate1(['cate_id' => $cateId, 'user_id' => $uid], [
                    'state' => 1,
                    'paystatus' => 1,
                    'plate_source' => StoreNumberPlateServices::PLATE_SOURCE_DEPOSIT,
                ]);
            } else {
                $plate['state'] = 1;
                $plate['paystatus'] = 1;
                $plate['freeze'] = $plate['freeze'] + $order['total_fee'];
                if ($plate['freeze'] >= 100000) {
                    $plate['remaining'] = 1000000000;
                } else {
                    $plate['remaining'] = $plate['remaining'] + $order['total_fee'] * 5;
                }
                CacheService::clear($name);
                $this->services->editPlate1(['cate_id' => $cateId, 'user_id' => $uid], $plate);
            }
        }
        
        if (!$isTimed) {
            $userDao = new UserDao();
            $userDao->updateMargin($order['uid'], $order['total_fee'], 0);
        }
        
        // 9. 其他业务逻辑（如发货、会员积分等）
        // $this->deliverGoods($orderNo);
        
        // 10. 返回成功响应给微信（必须返回 SUCCESS，否则微信会重复通知）
        return $this->replyWechat('SUCCESS', 'OK');
    }
    
    /**
     * 解析微信 XML 数据
     */
    private function parseXml($xml)
    {
        $data = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        return json_decode(json_encode($data), true);
    }
    
    /**
     * 验证微信签名（需自行实现）
     */
    private function verifySign($data)
    {
        // 获取微信支付密钥（商户平台设置的 API Key）
        $apiKey = config('wxpay.key');
        
        // 1. 提取签名
        $sign = $data['sign'];
        unset($data['sign']);
        
        // 2. 参数按字典序排序
        ksort($data);
       
        // 3. 拼接字符串
        $string = urldecode(http_build_query($data)) . '&key=' . config('wxpay.key');
       // $string .= 'key=' . $apiKey;
        
        // 4. 生成签名并比对
        $calculatedSign = strtoupper(md5($string));
        return $sign === $calculatedSign;
    }
    
    /**
     * 回复微信服务器
     */
    private function replyWechat($returnCode, $returnMsg = 'OK')
    {
        $xml = '<xml><return_code><![CDATA[' . $returnCode . ']]></return_code><return_msg><![CDATA[' . $returnMsg . ']]></return_msg></xml>';
        return response($xml)->contentType('text/xml');
    }
}