<?php



namespace app\services\product\product;



use app\dao\product\product\StoreCategoryDao;

use app\dao\product\product\StoreNumberPlateDao;

use app\dao\product\product\StoreProductDao;

use app\dao\product\product\StoreMarginDao;
use app\dao\product\product\StoreTimedDepositDao;
use app\services\order\StoreOrderCartInfoServices;

use app\services\order\StoreOrderServices;

use app\dao\system\config\SystemConfigDao;

use app\services\BaseServices;
use app\services\product\product\RefundServices;

use crmeb\services\CacheService;

use crmeb\services\SystemConfigService;



class StoreTimedDepositServices extends BaseServices

{

    public const CONFIG_KEY = 'timed_auction_deposit_amount';



    public const DEFAULT_DEPOSIT_AMOUNT = 1;

    /** 未支付订单取消后已扣保证金标记（幂等） */
    public const UNPAID_DEDUCT_MARK = '[timed_deposit_deducted]';

    /** @deprecated 请使用 getDepositAmount() */

    public const DEPOSIT_AMOUNT = self::DEFAULT_DEPOSIT_AMOUNT;



    public function __construct(StoreTimedDepositDao $dao)

    {

        $this->dao = $dao;

    }



    public static function getDepositAmount(): float

    {

        $val = sys_config(self::CONFIG_KEY, self::DEFAULT_DEPOSIT_AMOUNT);

        $amount = (float)$val;

        if ($amount <= 0) {

            return self::DEFAULT_DEPOSIT_AMOUNT;

        }

        return $amount;

    }



    public function setDepositAmount(float $amount): void

    {

        if ($amount <= 0) {

            throw new \InvalidArgumentException('保证金金额须大于0');

        }

        /** @var SystemConfigDao $configDao */

        $configDao = app()->make(SystemConfigDao::class);

        $exists = $configDao->getOne(['menu_name' => self::CONFIG_KEY]);

        if ($exists) {

            $configDao->update(['menu_name' => self::CONFIG_KEY], ['value' => (string)$amount]);

        } else {

            $configDao->save([

                'menu_name' => self::CONFIG_KEY,

                'type' => 'text',

                'input_type' => 'input',

                'config_tab_id' => 0,

                'parameter' => '',

                'upload_type' => 1,

                'required' => '',

                'width' => 100,

                'high' => 0,

                'value' => (string)$amount,

                'info' => '限时拍保证金金额',

                'desc' => '限时拍用户保证金金额（元），后台可修改',

                'sort' => 0,

                'status' => 1,

                'level' => 0,

                'link_id' => 0,

                'link_value' => 0,

            ]);

        }

        // 须 delete 具体 key；CacheService::clear() 按 tag 清池，无法清掉 remember 写入的单项配置
        $cacheKey = SystemConfigService::CACHE_SYSTEM . '_' . self::CONFIG_KEY;
        CacheService::delete($cacheKey);

    }



    public function getStatus(int $uid): array

    {

        $depositAmount = self::getDepositAmount();

        $cacheKey = 'timed_deposit_' . $uid;

        $info = CacheService::get($cacheKey);

        if (!is_array($info)) {

            $info = null;

            CacheService::clear($cacheKey);

        }

        if (!$info) {

            try {

                $info = $this->dao->getByUid($uid);

            } catch (\Throwable $e) {

                $info = [];

            }

            if (!$info) {

                $info = [

                    'uid' => $uid,

                    'paid_total' => 0,

                    'paystatus' => 0,

                ];

            }

            CacheService::set($cacheKey, $info, 600, $cacheKey);

        }

        $paid = (float)($info['paid_total'] ?? 0);

        $paystatus = (int)($info['paystatus'] ?? 0);

        $application = (int)($info['application'] ?? 0);

        $sufficient = ($paid >= $depositAmount) && $paystatus === 1;

        $needPay = $sufficient ? 0 : max(0, $depositAmount - $paid);

        return [

            'paid_total' => $paid,

            'paystatus' => $paystatus,

            'application' => $application,

            'deposit_amount' => $depositAmount,

            'deposit_sufficient' => $sufficient,

            'need_pay' => $needPay,

        ];

    }

    /**
     * 是否处于保证金缴纳中（部分已缴、或近期未支付的限时拍保证金单）
     */
    public function isDepositPaymentInProgress(int $uid): bool
    {
        if ($uid <= 0) {
            return false;
        }
        $status = $this->getStatus($uid);
        if (!empty($status['deposit_sufficient'])) {
            return false;
        }
        $paid = (float)($status['paid_total'] ?? 0);
        if ($paid > 0) {
            return true;
        }
        $storeMarginDao = new StoreMarginDao();
        $pending = $storeMarginDao->selectMarginList(['uid' => $uid, 'paystatus' => 0]);
        if (!$pending) {
            return false;
        }
        $sessionServices = app()->make(StoreAuctionSessionServices::class);
        foreach ($pending as $vo) {
            if (!$sessionServices->isTimedAuction((int)($vo['cate_id'] ?? 0))) {
                continue;
            }
            if ($this->isRecentPendingMarginOrder($vo)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 未支付 margin 单是否仍在有效支付窗口内（out_trade_no 前缀为下单时间）
     */
    protected function isRecentPendingMarginOrder(array $margin, int $ttlMinutes = 120): bool
    {
        $outTradeNo = (string)($margin['out_trade_no'] ?? '');
        if (strlen($outTradeNo) < 14 || !ctype_digit(substr($outTradeNo, 0, 14))) {
            return false;
        }
        $created = \DateTime::createFromFormat('YmdHis', substr($outTradeNo, 0, 14));
        if (!$created) {
            return false;
        }
        return (time() - $created->getTimestamp()) <= $ttlMinutes * 60;
    }



    public function onPaymentSuccess(int $uid, float $amount): void

    {

        $depositAmount = self::getDepositAmount();

        $current = $this->dao->getByUid($uid);

        $paid = (float)($current['paid_total'] ?? 0) + $amount;

        if ($paid > $depositAmount) {

            $paid = $depositAmount;

        }

        $paystatus = $paid >= $depositAmount ? 1 : 0;

        $this->dao->saveByUid($uid, [

            'paid_total' => $paid,

            'paystatus' => $paystatus,

            'application' => 0,

        ]);

        CacheService::clear('timed_deposit_' . $uid);

    }



    /**
     * 校验限时拍保证金是否允许退款（专场/周期须全部结拍）
     */
    public function assertAuctionEndedForRefund(int $uid, int $hintCateId = 0): array
    {
        $cateId = $hintCateId > 0 ? $hintCateId : $this->resolveRefundableCateId($uid);
        if ($cateId <= 0) {
            return ['ok' => false, 'msg' => '您参与的拍卖未结束'];
        }
        $sessionServices = app()->make(StoreAuctionSessionServices::class);
        return $sessionServices->canApplyDepositRefund($cateId);
    }

    /**
     * 限时拍保证金微信原路退（全部成功后才 markRefunded）
     * @param int $hintCateId 后台号牌管理点击退款时传入的专场，用于同步同周期号牌状态
     */
    public function processWechatRefund(int $uid, int $hintCateId = 0): array
    {
        $status = $this->getStatus($uid);
        if ((int)($status['paystatus'] ?? 0) === 2) {
            $syncCateId = $hintCateId > 0 ? $hintCateId : $this->resolveRefundableCateId($uid);
            if ($syncCateId > 0) {
                app()->make(StoreNumberPlateServices::class)->completeDepositRefund($uid, $syncCateId);
            }
            return ['ok' => true, 'msg' => '保证金已退款，已同步号牌状态'];
        }
        if (!$status['deposit_sufficient']) {
            return ['ok' => false, 'msg' => '保证金未处于可退状态'];
        }
        $endedCheck = $this->assertAuctionEndedForRefund($uid, $hintCateId);
        if (!$endedCheck['ok']) {
            return $endedCheck;
        }

        $sessionServices = app()->make(StoreAuctionSessionServices::class);
        $storeMarginDao = new StoreMarginDao();
        $refundServices = app()->make(RefundServices::class);
        $list = $storeMarginDao->selectMarginList(['uid' => $uid, 'paystatus' => 1]);
        $list = $this->filterTimedMarginListForRefund($list, $hintCateId, $sessionServices);
        $targetRefund = (float)$status['paid_total'];
        $refundedTotal = 0.0;
        $refunded = false;
        $failedMsg = '';

        foreach ($list as $vo) {
            if (!$sessionServices->isTimedAuction((int)$vo['cate_id'])) {
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
            $fee = (int)($vo['total_fee'] * 100);
            $result = $refundServices->refund($outTradeNo, $outRefundNo, $fee, $fee);
            if (empty($result['ok'])) {
                $failedMsg = $result['msg'] ?? '退款失败';
                break;
            }
            $refundedTotal += (float)$vo['total_fee'];
            $refunded = true;
        }

        if (!$refunded) {
            return ['ok' => false, 'msg' => '未找到限时拍支付单，请人工核对'];
        }
        if ($failedMsg !== '') {
            return ['ok' => false, 'msg' => '部分退款失败：' . $failedMsg . '，请人工核对'];
        }
        if ($refundedTotal + 0.001 < $targetRefund) {
            return [
                'ok' => false,
                'msg' => '微信实退' . $refundedTotal . '元，少于应退' . $targetRefund . '元，请人工核对',
            ];
        }

        $syncCateId = $hintCateId > 0 ? $hintCateId : $this->resolveRefundableCateId($uid);
        $this->markRefunded($uid, $syncCateId);
        return ['ok' => true, 'msg' => '保证金退款成功'];
    }

    /**
     * 限时拍退款：优先只退当前周期/专场相关支付单，避免误退历史未结清 margin 行
     *
     * @param array<int, array> $list
     * @return array<int, array>
     */
    protected function filterTimedMarginListForRefund(array $list, int $hintCateId, StoreAuctionSessionServices $sessionServices): array
    {
        $timed = array_values(array_filter($list, function ($vo) use ($sessionServices) {
            return $sessionServices->isTimedAuction((int)($vo['cate_id'] ?? 0));
        }));
        if ($hintCateId <= 0) {
            return $timed;
        }
        $ctx = $sessionServices->resolveContext($hintCateId);
        if (($ctx['mode'] ?? '') === 'session' && (int)($ctx['session_id'] ?? 0) > 0) {
            $sessionCateIds = $sessionServices->getSessionCateIdsBySessionId((int)$ctx['session_id']);
            return array_values(array_filter($timed, function ($vo) use ($sessionCateIds) {
                return in_array((int)($vo['cate_id'] ?? 0), $sessionCateIds, true);
            }));
        }
        return array_values(array_filter($timed, function ($vo) use ($hintCateId) {
            return (int)($vo['cate_id'] ?? 0) === $hintCateId;
        }));
    }

    public function markRefunded(int $uid, int $cateId = 0): void
    {
        $this->dao->saveByUid($uid, [
            'paid_total' => 0,
            'paystatus' => 2,
            'application' => 0,
        ]);
        CacheService::clear('timed_deposit_' . $uid);
        $syncCateId = $cateId > 0 ? $cateId : $this->resolveRefundableCateId($uid);
        if ($syncCateId > 0) {
            app()->make(StoreNumberPlateServices::class)->completeDepositRefund($uid, $syncCateId);
        }
    }



    public function markApplicationPending(int $uid): bool

    {

        $current = $this->dao->getByUid($uid);

        if ((int)($current['application'] ?? 0) === 1) {

            return false;

        }

        $this->dao->saveByUid($uid, [

            'application' => 1,

        ]);

        CacheService::clear('timed_deposit_' . $uid);

        return true;

    }



    public function clearCache(int $uid): void

    {

        CacheService::clear('timed_deposit_' . $uid);

    }



    /**

     * 违约扣减保证金（扣后不足配置金额则不可参拍，须再缴满）

     */

    public function deductForBreach(int $uid, float $amount): array

    {

        $depositAmount = self::getDepositAmount();

        if ($amount <= 0) {

            return ['ok' => false, 'msg' => '扣减金额无效'];

        }

        $current = $this->dao->getByUid($uid);

        $paid = (float)($current['paid_total'] ?? 0) - $amount;

        if ($paid < 0) {

            $paid = 0;

        }

        $paystatus = $paid >= $depositAmount ? 1 : 0;

        if ((int)($current['paystatus'] ?? 0) === 2) {

            $paystatus = 2;

        }

        $this->dao->saveByUid($uid, [

            'paid_total' => $paid,

            'paystatus' => $paystatus,

        ]);

        CacheService::clear('timed_deposit_' . $uid);

        $sufficient = $paid >= $depositAmount && $paystatus === 1;

        return [

            'ok' => true,

            'msg' => $sufficient ? '扣减后保证金仍足额' : '扣减后保证金不足，须再缴满' . $depositAmount . '元',

            'paid_total' => $paid,

            'deposit_sufficient' => $sufficient,

        ];

    }

    /**
     * 限时拍中拍订单未支付取消后扣减保证金（仅 timed，不动号牌表 / user.margin）
     */
    public function tryDeductForUnpaidOrder(int $orderId, array $orderRow = []): array
    {
        $skipped = ['ok' => false, 'skipped' => true, 'msg' => ''];

        if ($orderId <= 0) {
            return array_merge($skipped, ['msg' => '订单无效']);
        }

        /** @var StoreOrderServices $orderServices */
        $orderServices = app()->make(StoreOrderServices::class);
        if (empty($orderRow)) {
            $order = $orderServices->get($orderId);
            $orderRow = $order ? (is_array($order) ? $order : $order->toArray()) : [];
        }

        if (empty($orderRow)) {
            return array_merge($skipped, ['msg' => '订单不存在']);
        }

        if (!empty($orderRow['paid'])) {
            return array_merge($skipped, ['msg' => '订单已支付']);
        }

        $mark = (string)($orderRow['mark'] ?? '');
        if (strpos($mark, self::UNPAID_DEDUCT_MARK) !== false) {
            return array_merge($skipped, ['msg' => '已扣减保证金']);
        }

        if (!empty($orderRow['pink_id']) || !empty($orderRow['combination_id'])
            || !empty($orderRow['seckill_id']) || !empty($orderRow['bargain_id'])) {
            return array_merge($skipped, ['msg' => '活动订单跳过']);
        }

        $uid = (int)($orderRow['uid'] ?? 0);
        if ($uid <= 0) {
            return array_merge($skipped, ['msg' => '用户无效']);
        }

        /** @var StoreOrderCartInfoServices $cartServices */
        $cartServices = app()->make(StoreOrderCartInfoServices::class);
        $cartInfo = $cartServices->getOrderCartInfo($orderId);
        if (empty($cartInfo)) {
            return array_merge($skipped, ['msg' => '订单商品为空']);
        }

        $first = reset($cartInfo);
        $cart = $first['cart_info'] ?? $first;
        $productInfo = $cart['productInfo'] ?? [];
        $productId = (int)($productInfo['id'] ?? $cart['product_id'] ?? 0);
        if ($productId <= 0) {
            return array_merge($skipped, ['msg' => '未找到拍品']);
        }

        /** @var StoreProductDao $productDao */
        $productDao = app()->make(StoreProductDao::class);
        $product = $productDao->get($productId, ['id', 'cate_id', 'uid', 'presale_end_time']);
        if (!$product) {
            return array_merge($skipped, ['msg' => '拍品不存在']);
        }
        $product = is_array($product) ? $product : $product->toArray();

        $cateId = (int)($product['cate_id'] ?? 0);
        $sessionServices = app()->make(StoreAuctionSessionServices::class);
        if (!$sessionServices->isTimedAuction($cateId)) {
            return array_merge($skipped, ['msg' => '非限时拍订单']);
        }

        if ((int)($product['presale_end_time'] ?? 0) <= 0) {
            return array_merge($skipped, ['msg' => '拍品未结拍']);
        }

        if ((int)($product['uid'] ?? 0) !== $uid) {
            return array_merge($skipped, ['msg' => '非中拍者订单']);
        }

        $status = $this->getStatus($uid);
        if ((int)($status['paystatus'] ?? 0) === 2) {
            return array_merge($skipped, ['msg' => '保证金已退款']);
        }
        if ((int)($status['application'] ?? 0) === 1) {
            return array_merge($skipped, ['msg' => '退款审核中']);
        }
        if ((float)($status['paid_total'] ?? 0) <= 0) {
            return array_merge($skipped, ['msg' => '无可扣保证金']);
        }

        $deductResult = $this->deductForBreach($uid, self::getDepositAmount());
        if (empty($deductResult['ok'])) {
            return ['ok' => false, 'skipped' => false, 'msg' => $deductResult['msg'] ?? '扣减失败'];
        }

        $freshMark = (string)$orderServices->dao->value(['id' => $orderId], 'mark');
        $newMark = trim($freshMark . ' ' . self::UNPAID_DEDUCT_MARK);
        $orderServices->dao->update(['id' => $orderId], ['mark' => $newMark]);

        return [
            'ok' => true,
            'skipped' => false,
            'msg' => $deductResult['msg'] ?? '违约扣减成功',
        ];
    }



    public function listPendingRefund(int $page = 1, int $limit = 20): array

    {

        return $this->dao->listByApplication(1, $page, $limit);

    }



    /**

     * 用户端退款上下文（含可退专场 ID）

     */

    public function getRefundContext(int $uid): array

    {

        $status = $this->getStatus($uid);

        $result = array_merge($status, [

            'refundable_cate_id' => 0,

            'can_apply_refund' => false,

            'refund_msg' => '',

        ]);

        if (!$status['deposit_sufficient']) {

            $result['refund_msg'] = '您没有可退款的保证金';

            return $result;

        }

        if ((int)($status['application'] ?? 0) === 1) {

            $result['refund_msg'] = '退款申请已提交，请等待审核';

            return $result;

        }

        $cateId = $this->resolveRefundableCateId($uid);

        if ($cateId <= 0) {

            $result['refund_msg'] = '您参与的拍卖未结束';

            return $result;

        }

        $result['refundable_cate_id'] = $cateId;

        $result['can_apply_refund'] = true;

        return $result;

    }



    protected function resolveRefundableCateId(int $uid): int

    {

        $sessionServices = app()->make(StoreAuctionSessionServices::class);

        /** @var StoreNumberPlateDao $plateDao */

        $plateDao = app()->make(StoreNumberPlateDao::class);

        $categoryDao = new StoreCategoryDao();

        $cateCandidates = [];

        $plates = $plateDao->getPlate(['user_id' => $uid], 1, 50);

        foreach ($plates as $plate) {

            if (empty($plate['number_plate'])) {

                continue;

            }

            $plateCateId = (int)($plate['cate_id'] ?? 0);
            if ($plateCateId > 0) {
                $cateCandidates[$plateCateId] = true;
            }

        }

        if (empty($cateCandidates)) {

            foreach ($sessionServices->getDisplayTimedCategories(2) as $cate) {

                $cateCandidates[(int)$cate['id']] = true;

            }

        }

        foreach (array_keys($cateCandidates) as $cateId) {

            $check = $sessionServices->canApplyDepositRefund((int)$cateId);

            if ($check['ok']) {

                return (int)$cateId;

            }

        }

        return 0;

    }

}

