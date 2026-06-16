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
namespace app\adminapi\controller\v1\product;

use think\Request;
use app\services\product\product\RefundServices;
use app\services\product\product\StoreNumberPlateServices;
use app\services\product\product\StoreAuctionSessionServices;
use app\services\product\product\StoreTimedDepositServices;
use app\adminapi\controller\AuthController;
use app\dao\product\product\StoreMarginDao;
use app\dao\product\product\StoreNumberPlateDao;
use app\dao\user\UserDao;

/**
 * 保证金退款（限时拍走 timed_deposit，同步拍/旧逻辑走号牌表）
 */
class RefundController extends AuthController
{
    protected $services;
    protected $storeNumberPlateServices;

    public function __construct(RefundServices $services, StoreNumberPlateServices $storeNumberPlateServices)
    {
        $this->services = $services;
        $this->storeNumberPlateServices = $storeNumberPlateServices;
    }

    /**
     * 处理退款请求
     */
    public function refundOrder(Request $request)
    {
        $data = $request->getMore([
            ['cate_id', 0],
            ['uid', 0],
        ]);
        $uid = (int)$data['uid'];
        $cateId = (int)$data['cate_id'];
        if ($uid <= 0) {
            return app('json')->fail('请指定用户');
        }
        if ($cateId <= 0) {
            return app('json')->fail('请指定专场');
        }

        $prep = $this->storeNumberPlateServices->resolveAdminDepositRefund($uid, $cateId);
        if (!$prep['ok']) {
            return app('json')->fail($prep['msg']);
        }
        if (!empty($prep['sync_only'])) {
            return app('json')->success($prep['msg']);
        }

        $sessionServices = app()->make(StoreAuctionSessionServices::class);
        if ($sessionServices->isTimedAuction($cateId)) {
            return $this->refundTimedDeposit($uid, $cateId);
        }

        return $this->refundLegacyPlate($uid, $cateId);
    }

    protected function refundTimedDeposit(int $uid, int $cateId = 0)
    {
        $depositServices = app()->make(StoreTimedDepositServices::class);
        $result = $depositServices->processWechatRefund($uid, $cateId);
        if (!$result['ok']) {
            return app('json')->fail($result['msg']);
        }
        return app('json')->success($result['msg']);
    }

    /**
     * 同步拍等旧逻辑：微信秒退（校验结果），同周期共用号牌只退一笔款、多行标已退
     */
    protected function refundLegacyPlate(int $uid, int $cateId)
    {
        $sessionServices = app()->make(StoreAuctionSessionServices::class);

        $endedCheck = $sessionServices->canApplyDepositRefund($cateId);
        if (!$endedCheck['ok']) {
            return app('json')->fail($endedCheck['msg']);
        }

        $storeMarginDao = new StoreMarginDao();

        $list = $storeMarginDao->selectMarginList(['uid' => $uid, 'paystatus' => 1]);
        $list = $this->filterLegacyMarginList($list, $uid, $cateId, $sessionServices);
        if (!$list) {
            return app('json')->fail('未找到可退款的支付单');
        }

        $targetRefund = $this->resolveLegacyRefundTarget($list, $cateId, $sessionServices);
        $refundedTotal = 0.0;
        $refunded = false;
        $failedMsg = '';

        foreach ($list as $vo) {
            if ($sessionServices->isTimedAuction((int)($vo['cate_id'] ?? 0))) {
                continue;
            }
            if ($refundedTotal >= $targetRefund) {
                break;
            }
            $outTradeNo = $vo['out_trade_no'];
            $date = date('YmdHis');
            $microtime = microtime();
            list($msec,) = explode(' ', $microtime);
            $micro = sprintf('%06d', $msec * 1000000);
            $rand = sprintf('%05d', mt_rand(1, 99999));
            $outRefundNo = $date . $micro . $rand;
            $storeMarginDao->editMargin(['out_trade_no' => $outTradeNo], ['out_refund_no' => $outRefundNo]);
            $fee = (int)((float)$vo['total_fee'] * 100);
            $result = $this->services->refund($outTradeNo, $outRefundNo, $fee, $fee);
            if (empty($result['ok'])) {
                $failedMsg = $result['msg'] ?? '退款失败';
                break;
            }
            $refundedTotal += (float)$vo['total_fee'];
            $refunded = true;
        }

        if (!$refunded) {
            return app('json')->fail('未找到可退款的支付单');
        }
        if ($failedMsg !== '') {
            return app('json')->fail($failedMsg);
        }
        if ($refundedTotal + 0.001 < $targetRefund) {
            return app('json')->fail('微信实退' . $refundedTotal . '元，少于应退' . $targetRefund . '元，请人工核对');
        }

        $this->storeNumberPlateServices->completeDepositRefund($uid, $cateId);
        $storeNumberPlateDao = new StoreNumberPlateDao();
        $plate = $storeNumberPlateDao->selectPlate(['user_id' => $uid, 'cate_id' => $cateId]);
        $userDao = new UserDao();
        $userDao->updateMargin($uid, $plate['freeze'] ?? 0, 1);

        return app('json')->success('保证金退款成功');
    }

    /**
     * @param array<int, array> $list
     * @return array<int, array>
     */
    protected function filterLegacyMarginList(array $list, int $uid, int $cateId, StoreAuctionSessionServices $sessionServices): array
    {
        if ($cateId <= 0) {
            return $list;
        }
        $ctx = $sessionServices->resolveContext($cateId);
        if (($ctx['mode'] ?? '') === 'session' && (int)($ctx['session_id'] ?? 0) > 0) {
            $sessionCateIds = $sessionServices->getSessionCateIdsBySessionId((int)$ctx['session_id']);
            return array_values(array_filter($list, function ($vo) use ($sessionCateIds) {
                return in_array((int)($vo['cate_id'] ?? 0), $sessionCateIds, true);
            }));
        }
        return array_values(array_filter($list, function ($vo) use ($cateId) {
            return (int)($vo['cate_id'] ?? 0) === $cateId;
        }));
    }

    /**
     * 同周期共用号牌：只按一笔保证金金额原路退，避免重复退款
     *
     * @param array<int, array> $list
     */
    protected function resolveLegacyRefundTarget(array $list, int $cateId, StoreAuctionSessionServices $sessionServices): float
    {
        $total = 0.0;
        foreach ($list as $vo) {
            $total += (float)($vo['total_fee'] ?? 0);
        }
        if ($cateId <= 0 || $total <= 0) {
            return $total;
        }
        $ctx = $sessionServices->resolveContext($cateId);
        if (($ctx['mode'] ?? '') === 'session' && count($list) > 1) {
            return (float)($list[0]['total_fee'] ?? $total);
        }
        return $total;
    }
}
