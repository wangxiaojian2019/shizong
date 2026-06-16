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

use app\services\product\product\StoreNumberPlateServices;
use app\services\product\product\StoreAuctionSessionServices;
use app\services\product\product\StoreTimedDepositServices;
use think\Request;
use app\services\wechat\WechatUserServices;
use app\services\product\product\StoreCategoryServices;
use app\dao\product\product\StoreNumberPlateDao;
use think\facade\Log;

/**
 * Class CategoryController
 * @package app\api\controller\v1\store
 */
class StoreNumberPlateController
{
    protected $services;

    public function __construct(StoreNumberPlateServices $services, WechatUserServices $wechatUser)
    {
        $this->services = $services;
        $this->wechatUser = $wechatUser;
    }

    /**
     * 添加号牌
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function add(Request $request, $cate_id = 0)
    {
        $uid = (int)$request->uid();
        $cateId = (int)$cate_id;
        if ($cateId <= 0) {
            $cateId = (int)$request->param('cate_id', 0);
        }
        if ($cateId <= 0 || $uid <= 0) {
            return app('json')->fail('请先登录并选择专场');
        }

        $data = [
            'cate_id' => $cateId,
            'user_id' => $uid,
            'orderId' => '',
            'out_refund_no' => '',
        ];
        $sessionServices = app()->make(StoreAuctionSessionServices::class);

        if ($sessionServices->isTimedAuction($cateId)) {
            try {
                return app('json')->success($this->services->applyTimedPlate($data));
            } catch (\Throwable $e) {
                Log::error('numberPlate add cate=' . $cateId . ' uid=' . $uid . ': ' . $e->getMessage());
                return app('json')->fail('办理号牌失败，请稍后重试');
            }
        }

        $ctx = $sessionServices->resolveContext($cateId);
        $StoreNumberPlateDao = new StoreNumberPlateDao();
        $maxTry = 100;
        while ($maxTry-- > 0) {
            $number = rand(1000, 9999);
            $exists = false;
            if ($ctx['mode'] === 'session') {
                $cateIds = $sessionServices->getSessionCateIdsBySessionId((int)$ctx['session_id']);
                if (!$cateIds) {
                    $cateIds = [$cateId];
                }
                $exists = $StoreNumberPlateDao->numberExistsInCateIds($cateIds, $number);
            } else {
                $plate = $StoreNumberPlateDao->selectPlate(['number_plate' => $number, 'cate_id' => $cateId]);
                $exists = !empty($plate['number_plate']);
            }
            if (!$exists) {
                $data['number_plate'] = $number;
                return app('json')->success($this->services->change($data));
            }
        }
        return app('json')->fail('号牌生成失败，请重试');
      
    }
    
    public function details(Request $request, $cate_id = 0)
    {
        $uid = (int)$request->uid();
        $cateId = (int)$cate_id;
        if ($cateId <= 0) {
            $cateId = (int)$request->param('cate_id', 0);
        }
        try {
            $details = $this->services->buildParticipationDetails($uid, $cateId);
        } catch (\Throwable $e) {
            Log::error('numberPlate details cate=' . $cateId . ' uid=' . $uid . ': ' . $e->getMessage());
            return app('json')->fail('号牌信息查询失败，请稍后重试');
        }
        if (!is_array($details)) {
            $details = [];
        }
        $details['openid'] = $this->resolveOpenidPayload($uid);
        return app('json')->success($details);
    }

    /**
     * 微信用户信息转数组，避免 Model 序列化导致 500
     */
    protected function resolveOpenidPayload(int $uid): array
    {
        if ($uid <= 0) {
            return ['openid' => ''];
        }
        try {
            $wechatUser = $this->wechatUser->getWechatUserInfo(['uid' => $uid]);
            if (is_array($wechatUser)) {
                return $wechatUser;
            }
            if (is_object($wechatUser) && method_exists($wechatUser, 'toArray')) {
                return $wechatUser->toArray();
            }
        } catch (\Throwable $e) {
        }
        return ['openid' => ''];
    }
    
    public function getPlate(Request $request){
        
        $uid = (int)$request->uid();
        $data = $request->getMore([
            ['page',1]
        ]);
        return app('json')->success($this->services->getPlate($uid,$data['page']));
    
    }
    
    
    public function refund(Request $request){
        $uid = (int)$request->uid();
        $cateId = (int)$request->param('cate_id', 0);
        $depositServices = app()->make(StoreTimedDepositServices::class);
        $refundContext = $depositServices->getRefundContext($uid);
        if ($cateId <= 0) {
            $cateId = (int)($refundContext['refundable_cate_id'] ?? 0);
        }
        if (!$refundContext['deposit_sufficient']) {
            return app('json')->success($refundContext['refund_msg'] ?: '您没有可退款的保证金');
        }
        if ((int)($refundContext['application'] ?? 0) === 1) {
            return app('json')->success('退款申请已提交，请等待审核');
        }
        if ($cateId <= 0) {
            return app('json')->success($refundContext['refund_msg'] ?: '请指定专场');
        }
        $sessionServices = app()->make(StoreAuctionSessionServices::class);
        $refundCheck = $sessionServices->canApplyDepositRefund($cateId);
        if (!$refundCheck['ok']) {
            return app('json')->success($refundCheck['msg']);
        }
        if (!$depositServices->markApplicationPending($uid)) {
            return app('json')->success('退款申请已提交，请等待审核');
        }
        return app('json')->success('保证金退款申请提交成功');
    }

    public function depositStatus(Request $request)
    {
        $uid = (int)$request->uid();
        $depositServices = app()->make(StoreTimedDepositServices::class);
        return app('json')->success($depositServices->getRefundContext($uid));
    }
  
 
}
