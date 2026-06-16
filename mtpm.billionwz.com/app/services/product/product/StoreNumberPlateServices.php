<?php
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------

namespace app\services\product\product;

use app\dao\product\product\StoreNumberPlateDao;
use app\dao\product\product\StoreAgentPriceDao;
use app\services\BaseServices;
use crmeb\services\CacheService;
use app\dao\product\product\StoreCategoryDao;
use app\dao\product\product\StoreProductDao;
use app\services\user\UserServices;

class StoreNumberPlateServices extends BaseServices
{
    /** 号牌获得类型：暂无 */
    public const PLATE_SOURCE_NONE = 0;
    /** 号牌获得类型：管理员设置 */
    public const PLATE_SOURCE_ADMIN = 1;
    /** 号牌获得类型：保证金缴纳 */
    public const PLATE_SOURCE_DEPOSIT = 2;

    /** @var StoreAuctionSessionServices */
    protected $sessionServices;

    /** @var StoreTimedDepositServices */
    protected $depositServices;

    public function __construct(StoreNumberPlateDao $dao)
    {
        $this->dao = $dao;
        $this->sessionServices = app()->make(StoreAuctionSessionServices::class);
        $this->depositServices = app()->make(StoreTimedDepositServices::class);
    }

    /**
     * 限时拍：办理号牌（按当前专场插入记录）；保证金足额则分配并同步同周期其它专场行
     */
    public function applyTimedPlate(array $cateData): array
    {
        $cateId = (int)($cateData['cate_id'] ?? 0);
        $userId = (int)($cateData['user_id'] ?? 0);
        if ($cateId <= 0 || $userId <= 0) {
            return ['msg' => '参数错误', 'plate_registered' => false, 'number_plate' => ''];
        }

        $cateErr = $this->assertTimedPlateCate($cateId);
        if ($cateErr !== '') {
            return ['msg' => $cateErr, 'plate_registered' => false, 'number_plate' => ''];
        }

        $ctx = $this->sessionServices->resolveContext($cateId);
        $record = $this->dao->findPlateRecordByCate($userId, $cateId);
        if ($record && $this->hasAssignedPlate($record)) {
            return [
                'msg' => '不要重复办理号牌',
                'plate_registered' => true,
                'number_plate' => (string)$record['number_plate'],
                'deposit_sufficient' => $this->depositServices->getStatus($userId)['deposit_sufficient'],
            ];
        }

        $deposit = $this->depositServices->getStatus($userId);

        if ($record && !$this->hasAssignedPlate($record)) {
            if ($deposit['deposit_sufficient']) {
                return $this->assignPlateNumber($userId, $ctx, $cateId);
            }
            return [
                'msg' => '已办理号牌登记，请等待分配号牌或缴纳保证金',
                'plate_registered' => true,
                'number_plate' => '',
                'deposit_sufficient' => false,
                'need_pay' => $deposit['need_pay'],
            ];
        }

        $this->ensurePlateRecord($userId, $cateId, $ctx);
        $this->clearPlateCache($userId, $cateId, $ctx);
        $record = $this->dao->findPlateRecordByCate($userId, $cateId);

        if ($deposit['deposit_sufficient']) {
            return $this->assignPlateNumber($userId, $ctx, $cateId);
        }

        return [
            'msg' => '号牌登记成功，请等待分配号牌或缴纳保证金',
            'plate_registered' => true,
            'number_plate' => '',
            'deposit_sufficient' => false,
            'need_pay' => $deposit['need_pay'],
        ];
    }

    public function tryAssignPlateAfterDeposit(int $userId, int $cateId): array
    {
        if ($userId <= 0 || $cateId <= 0 || !$this->sessionServices->isTimedAuction($cateId)) {
            return ['ok' => false, 'msg' => ''];
        }
        $deposit = $this->depositServices->getStatus($userId);
        if (!$deposit['deposit_sufficient']) {
            return ['ok' => false, 'msg' => '保证金未缴足'];
        }
        $ctx = $this->sessionServices->resolveContext($cateId);
        $record = $this->dao->findPlateRecordByCate($userId, $cateId);
        if (!$record) {
            $this->ensurePlateRecord($userId, $cateId, $ctx);
            $record = $this->dao->findPlateRecordByCate($userId, $cateId);
        }
        if (!$record) {
            return ['ok' => false, 'msg' => '请先办理号牌'];
        }
        if ($this->isAdminAssignedPlate($record)) {
            return [
                'ok' => true,
                'msg' => '号牌已由管理员分配',
                'number_plate' => (string)$record['number_plate'],
            ];
        }
        if ($this->hasAssignedPlate($record)) {
            if ((int)($record['paystatus'] ?? 0) !== 2) {
                return ['ok' => true, 'msg' => '号牌已分配', 'number_plate' => (string)$record['number_plate']];
            }
        }
        $assigned = $this->findAssignedPlateInSession($userId, $ctx);
        if ($assigned) {
            if ($this->isAdminAssignedPlate($assigned)) {
                return [
                    'ok' => true,
                    'msg' => '号牌已由管理员分配',
                    'number_plate' => (string)$assigned['number_plate'],
                ];
            }
            $this->syncSessionPlatesAfterAssign($userId, $ctx, $cateId, [
                'number_plate' => (int)$assigned['number_plate'],
                'state' => (int)($assigned['state'] ?? 1),
                'paystatus' => 1,
                'plate_source' => self::PLATE_SOURCE_DEPOSIT,
            ], true);
            $this->clearPlateCache($userId, $cateId, $ctx);
            return ['ok' => true, 'msg' => '号牌已分配', 'number_plate' => (string)$assigned['number_plate']];
        }
        $res = $this->assignPlateNumber($userId, $ctx, $cateId);
        return ['ok' => true, 'msg' => $res['msg'], 'number_plate' => $res['number_plate'] ?? ''];
    }

    public function resolveBidPlateNumber(int $userId, int $cateId): string
    {
        if ($userId <= 0 || $cateId <= 0) {
            return '';
        }
        if (!$this->sessionServices->isTimedAuction($cateId)) {
            $plate = $this->dao->selectPlate(['user_id' => $userId, 'cate_id' => $cateId]);
            return $this->normalizePlateNumber($plate['number_plate'] ?? '');
        }
        $details = $this->buildParticipationDetails($userId, $cateId);
        return $this->normalizePlateNumber($details['number_plate'] ?? '');
    }

    public function change($cateData)
    {
        $cateId = (int)($cateData['cate_id'] ?? 0);
        $userId = (int)($cateData['user_id'] ?? 0);
        if ($this->sessionServices->isTimedAuction($cateId)) {
            return $this->applyTimedPlate($cateData);
        }

        if ($this->dao->findPlateRecordByCate($userId, $cateId)) {
            $plate = $this->dao->selectPlate(['cate_id' => $cateId, 'user_id' => $userId]);
            if ($this->normalizePlateNumber($plate['number_plate'] ?? '') !== '') {
                return ['msg' => '不要重复办理号牌'];
            }
            return ['msg' => '已办理号牌登记'];
        }
        unset($cateData['session_id']);
        $this->dao->insertPlateUnique($cateData);
        $this->clearPlateCache($userId, $cateId, ['mode' => 'cate', 'cate_id' => $cateId, 'session_id' => 0]);
        return ['msg' => '号牌办理成功'];
    }

    public function selectPlate(array $where)
    {
        return $this->dao->selectPlate($where);
    }

    public function selectPlatedetails($where)
    {
        $userId = (int)($where['user_id'] ?? 0);
        $cateId = (int)($where['cate_id'] ?? 0);
        return $this->buildParticipationDetails($userId, $cateId);
    }

    public function buildParticipationDetails(int $userId, int $cateId): array
    {
        $ctx = $this->sessionServices->resolveContext($cateId);
        $plateInfo = $this->getPlateRow($userId, $ctx);
        $deposit = $this->depositServices->getStatus($userId);

        if ($this->sessionServices->isTimedAuction($cateId) && $plateInfo && !$this->hasAssignedPlate($plateInfo) && $deposit['deposit_sufficient']) {
            $shouldAutoAssign = !$this->isAdminAssignedPlate($plateInfo)
                && !$this->isAdminAssignedInSession($userId, $ctx);
            if ($shouldAutoAssign) {
                try {
                    $this->assignPlateNumber($userId, $ctx, $cateId);
                    $plateInfo = $this->getPlateRow($userId, $ctx);
                } catch (\Throwable $e) {
                }
            }
        }
        if (!is_array($plateInfo)) {
            $plateInfo = [];
        }

        if (!$this->hasAssignedPlate($plateInfo) && ($ctx['mode'] ?? '') === 'session') {
            $assigned = $this->findAssignedPlateInSession($userId, $ctx);
            if ($assigned) {
                $plateInfo['number_plate'] = $assigned['number_plate'];
            }
        }

        $numberPlate = $this->normalizePlateNumber($plateInfo['number_plate'] ?? '');
        $plateRegistered = !empty($this->dao->findPlateRecordByCate($userId, $cateId));
        $hasPlate = $numberPlate !== '';
        $assignFailed = $plateRegistered && $deposit['deposit_sufficient'] && !$hasPlate;

        $isTimed = $this->sessionServices->isTimedAuction($cateId);
        if ((int)($deposit['application'] ?? 0) === 1) {
            $state = 2;
        } elseif ($isTimed) {
            $plateRow = $this->resolveParticipationPlateRow($userId, $ctx, $plateInfo);
            $state = $this->resolveTimedParticipationState($hasPlate, $plateRow, $deposit);
        } else {
            $platePaystatus = (int)($plateInfo['paystatus'] ?? 0);
            $plateState = (int)($plateInfo['state'] ?? 0);
            if ($platePaystatus === 2) {
                $state = 2;
            } elseif ($hasPlate && ($platePaystatus === 1 || $plateState === 1)) {
                $state = 1;
            } else {
                $state = 0;
            }
        }

        return array_merge($plateInfo ?: [], [
            'number_plate' => $numberPlate,
            'has_plate' => $hasPlate,
            'plate_registered' => $plateRegistered,
            'plate_mode' => $ctx['mode'],
            'session_id' => (int)$ctx['session_id'],
            'paid_total' => $deposit['paid_total'],
            'deposit_amount' => $deposit['deposit_amount'],
            'deposit_sufficient' => $deposit['deposit_sufficient'],
            'need_pay' => $deposit['need_pay'],
            'application' => (int)($deposit['application'] ?? 0),
            'state' => $state,
            'paystatus' => $deposit['deposit_sufficient'] ? 1 : (int)($plateInfo['paystatus'] ?? 0),
            'assign_failed' => $assignFailed,
            'need_assign_plate' => $assignFailed,
        ]);
    }

    public function checkCanParticipate(int $userId, int $cateId): array
    {
        if ($userId <= 0 || $cateId <= 0) {
            return ['ok' => false, 'msg' => '参数错误'];
        }
        $details = $this->buildParticipationDetails($userId, $cateId);
        if ((int)($details['application'] ?? 0) === 1) {
            return ['ok' => false, 'msg' => '保证金退款审核中，暂不可参拍'];
        }

        if ($this->sessionServices->isTimedAuction($cateId)) {
            if (!$details['plate_registered']) {
                return ['ok' => false, 'msg' => '请先办理号牌'];
            }
            if (!$details['has_plate']) {
                $amount = StoreTimedDepositServices::getDepositAmount();
                return ['ok' => false, 'msg' => '号牌待分配，请联系客服分配或缴纳保证金' . $amount . '元后系统自动分配'];
            }
            $ctx = $this->sessionServices->resolveContext($cateId);
            $record = $this->dao->findPlateRecordByCate($userId, $cateId) ?: [];
            $plateRow = $this->resolveParticipationPlateRow($userId, $ctx, $record);
            if ($this->isDepositSourcedPlate($plateRow)) {
                $deposit = $this->depositServices->getStatus($userId);
                if (!$this->isTimedDepositActive($deposit)) {
                    return ['ok' => false, 'msg' => '保证金已失效，请重新缴纳保证金'];
                }
            }
            return ['ok' => true, 'msg' => ''];
        }

        // 同步拍：必须有号牌且保证金状态有效（后台收回号牌后 has_plate=false）
        if (!$details['has_plate']) {
            return ['ok' => false, 'msg' => '请先办理号牌'];
        }
        if ((int)($details['state'] ?? 0) !== 1) {
            return ['ok' => false, 'msg' => '请缴纳保证金后再出价'];
        }
        return ['ok' => true, 'msg' => ''];
    }

    /**
     * 出价/代理出价前统一校验（实时读库号牌，避免后台切换分配状态后仍用旧号牌出价）
     *
     * @param int $userId
     * @param int $cateId
     * @param mixed $clientPlate 客户端提交的号牌（可选）
     * @return array{ok:bool,msg:string,plate:string}
     */
    public function validateBidEligibility(int $userId, int $cateId, $clientPlate = null): array
    {
        $check = $this->checkCanParticipate($userId, $cateId);
        if (!$check['ok']) {
            return ['ok' => false, 'msg' => $check['msg'], 'plate' => ''];
        }
        $bidPlate = $this->resolveBidPlateNumber($userId, $cateId);
        if ($bidPlate === '') {
            return ['ok' => false, 'msg' => '号牌无效，请刷新专场后重试', 'plate' => ''];
        }
        $client = $this->normalizePlateNumber($clientPlate ?? '');
        if ($client !== '' && $client !== $bidPlate) {
            return ['ok' => false, 'msg' => '号牌状态已变更，请刷新后重试', 'plate' => $bidPlate];
        }
        return ['ok' => true, 'msg' => '', 'plate' => $bidPlate];
    }

    /**
     * 清理号牌与代理出价缓存（不修改代理出价记录状态）
     */
    public function clearBidCachesForUserCate(int $userId, int $cateId): void
    {
        if ($userId <= 0 || $cateId <= 0) {
            return;
        }
        $ctx = $this->sessionServices->resolveContext($cateId);
        $this->clearPlateCache($userId, $cateId, $ctx);

        $agentDao = app()->make(StoreAgentPriceDao::class);
        $agents = $agentDao->getActiveByUserCate($userId, $cateId);
        foreach ($agents as $agent) {
            $productId = (int)($agent['product_id'] ?? 0);
            if ($productId > 0) {
                CacheService::clear('agent_' . $userId . '_' . $productId);
            }
        }
    }

    protected function assignPlateNumber(int $userId, array $ctx, int $cateId, bool $markPaid = true): array
    {
        $cateErr = $this->assertTimedPlateCate($cateId);
        if ($cateErr !== '') {
            return ['msg' => $cateErr, 'plate_registered' => false, 'number_plate' => ''];
        }

        if (!$markPaid && $this->depositServices->isDepositPaymentInProgress($userId)) {
            return [
                'msg' => '该用户正在缴纳保证金，请待支付完成后再分配号牌',
                'plate_registered' => false,
                'number_plate' => '',
            ];
        }

        if ($markPaid) {
            $deposit = $this->depositServices->getStatus($userId);
            if (!$this->isTimedDepositActive($deposit)) {
                return [
                    'msg' => (int)($deposit['paystatus'] ?? 0) === 2
                        ? '保证金已退款，请重新缴纳后再分配号牌'
                        : '保证金未缴足',
                    'plate_registered' => false,
                    'number_plate' => '',
                ];
            }
        }

        $assigned = $this->findAssignedPlateInSession($userId, $ctx);
        if ($assigned) {
            if ($this->isAdminAssignedPlate($assigned)) {
                return [
                    'msg' => '号牌已由管理员分配',
                    'plate_registered' => true,
                    'number_plate' => (string)$assigned['number_plate'],
                    'deposit_sufficient' => $markPaid,
                ];
            }
            $plateUpdate = [
                'number_plate' => (int)$assigned['number_plate'],
            ];
            if ($markPaid) {
                $plateUpdate['state'] = (int)($assigned['state'] ?? 1);
                $plateUpdate['paystatus'] = 1;
                $plateUpdate['plate_source'] = self::PLATE_SOURCE_DEPOSIT;
            }
            $this->syncSessionPlatesAfterAssign($userId, $ctx, $cateId, $plateUpdate, true);
            $this->clearPlateCache($userId, $cateId, $ctx);
            return [
                'msg' => '号牌已分配',
                'plate_registered' => true,
                'number_plate' => (string)$assigned['number_plate'],
                'deposit_sufficient' => $markPaid,
            ];
        }

        if (!$this->dao->findPlateRecordByCate($userId, $cateId)) {
            $this->ensurePlateRecord($userId, $cateId, $ctx);
        }

        $number = $this->generateUniquePlateNumber($ctx, $cateId);
        if ($number <= 0) {
            return ['msg' => '号牌生成失败，请重试', 'plate_registered' => true, 'number_plate' => ''];
        }

        $plateUpdate = [
            'number_plate' => $number,
            'plate_source' => $markPaid ? self::PLATE_SOURCE_DEPOSIT : self::PLATE_SOURCE_ADMIN,
        ];
        if ($markPaid) {
            $plateUpdate['state'] = 1;
            $plateUpdate['paystatus'] = 1;
        }
        $this->syncSessionPlatesAfterAssign($userId, $ctx, $cateId, $plateUpdate, true);
        $this->clearPlateCache($userId, $cateId, $ctx);

        return [
            'msg' => '号牌办理成功',
            'plate_registered' => true,
            'number_plate' => (string)$number,
            'deposit_sufficient' => $markPaid,
        ];
    }

    /**
     * 限时拍办牌校验：未纳入周期的限时拍专场按单场办牌；仅当该专场已写入周期 A/B 且周期有效时才要求双场绑定完整。
     */
    protected function assertTimedPlateCate(int $cateId): string
    {
        if ($cateId <= 0) {
            return '参数错误';
        }
        if (!$this->sessionServices->isTimedAuction($cateId)) {
            return '该专场不是限时拍';
        }
        $ctx = $this->sessionServices->resolveContext($cateId);
        // mode=session：该专场属于某周期的 A 或 B，须周期已同时绑定两个不同专场后才共用号牌
        if (($ctx['mode'] ?? '') === 'session') {
            $sessionId = (int)($ctx['session_id'] ?? 0);
            if ($sessionId <= 0 || !$this->sessionServices->isValidSessionMode($sessionId)) {
                return '该专场所属拍卖周期未配置完整（需专场A+专场B），请联系管理员';
            }
        }
        // mode=cate：普通限时拍单场，无需绑周期
        return '';
    }

    /**
     * 登记号牌：当前专场必插一条；同周期双场时另一专场也先占登记行（号牌仍为 0）
     */
    protected function ensurePlateRecord(int $userId, int $cateId, array $ctx): void
    {
        $cateIds = $this->resolvePlateCateIds($ctx, $cateId);
        foreach ($cateIds as $cid) {
            $cid = (int)$cid;
            if ($cid <= 0 || $this->dao->findPlateRecordByCate($userId, $cid)) {
                continue;
            }
            $this->dao->dedupePlatesByCates($userId, [$cid]);
            $this->insertPendingPlate($userId, $cid);
        }
    }

    protected function insertPendingPlate(int $userId, int $cateId, int $numberPlate = 0): void
    {
        $row = [
            'user_id' => $userId,
            'cate_id' => $cateId,
            'number_plate' => $numberPlate > 0 ? $numberPlate : 0,
            'orderId' => '',
            'out_refund_no' => '',
            'state' => $numberPlate > 0 ? 1 : 0,
            'paystatus' => $numberPlate > 0 ? 1 : 0,
            'plate_source' => $numberPlate > 0 ? self::PLATE_SOURCE_DEPOSIT : self::PLATE_SOURCE_NONE,
        ];
        $this->dao->insertPlateUnique($row);
    }

    protected function generateUniquePlateNumber(array $ctx, int $cateId): int
    {
        $cateIds = $this->resolvePlateCateIds($ctx, $cateId);
        $maxTry = 100;
        while ($maxTry-- > 0) {
            $number = rand(1000, 9999);
            $exists = $this->dao->numberExistsInCateIds($cateIds, $number);
            if (!$exists) {
                return $number;
            }
        }
        return 0;
    }

    protected function findAssignedPlateInSession(int $userId, array $ctx): array
    {
        foreach ($this->resolvePlateCateIds($ctx, (int)$ctx['cate_id']) as $cid) {
            $row = $this->dao->findPlateRecordByCate($userId, $cid);
            if ($row && $this->hasAssignedPlate($row) && (int)($row['paystatus'] ?? 0) !== 2) {
                return $row;
            }
        }
        return [];
    }

    protected function resolvePlateCateIds(array $ctx, int $registerCateId): array
    {
        if (($ctx['mode'] ?? '') === 'session' && (int)($ctx['session_id'] ?? 0) > 0) {
            return $this->getSessionCateIds((int)$ctx['session_id'], $registerCateId);
        }
        return $registerCateId > 0 ? [$registerCateId] : [];
    }

    protected function isDepositSourcedPlate(array $record): bool
    {
        if (!$this->hasAssignedPlate($record)) {
            return false;
        }
        $source = (int)($record['plate_source'] ?? 0);
        if ($source === self::PLATE_SOURCE_ADMIN) {
            return false;
        }
        if ($source === self::PLATE_SOURCE_DEPOSIT) {
            return true;
        }
        return (int)($record['paystatus'] ?? 0) === 1;
    }

    protected function isTimedDepositActive(array $deposit): bool
    {
        return !empty($deposit['deposit_sufficient'])
            && (int)($deposit['paystatus'] ?? 0) !== 2;
    }

    protected function resolveTimedParticipationState(bool $hasPlate, array $plateInfo, array $deposit): int
    {
        if (!$hasPlate) {
            return 0;
        }
        if ($this->isDepositSourcedPlate($plateInfo)) {
            return $this->isTimedDepositActive($deposit) ? 1 : 0;
        }
        return 1;
    }

    protected function resolveParticipationPlateRow(int $userId, array $ctx, array $plateInfo): array
    {
        if ($this->hasAssignedPlate($plateInfo)) {
            return $plateInfo;
        }
        if (($ctx['mode'] ?? '') === 'session') {
            $assigned = $this->findAssignedPlateInSession($userId, $ctx);
            if ($assigned) {
                return $assigned;
            }
        }
        return $plateInfo;
    }

    protected function hasAssignedPlate(array $record): bool
    {
        return $this->normalizePlateNumber($record['number_plate'] ?? '') !== '';
    }

    protected function isAdminAssignedPlate(array $record): bool
    {
        return $this->hasAssignedPlate($record)
            && (int)($record['plate_source'] ?? 0) === self::PLATE_SOURCE_ADMIN;
    }

    protected function isAdminAssignedInSession(int $userId, array $ctx): bool
    {
        $assigned = $this->findAssignedPlateInSession($userId, $ctx);
        return $assigned && $this->isAdminAssignedPlate($assigned);
    }

    protected function resolvePlateAcquireDisplay(int $uid, int $cateId, array $record): array
    {
        if (!$this->hasAssignedPlate($record)) {
            if ($this->sessionServices->isTimedAuction($cateId)) {
                $deposit = $this->depositServices->getStatus($uid);
                if ((int)($deposit['paystatus'] ?? 0) === 1) {
                    $amount = StoreTimedDepositServices::getDepositAmount();
                    return [
                        'plate_acquire_type' => 'deposit',
                        'plate_acquire_label' => '保证金缴纳(' . $this->formatDepositAmount($amount) . ')',
                        'plate_acquire_amount' => $amount,
                    ];
                }
            }
            return [
                'plate_acquire_type' => 'none',
                'plate_acquire_label' => '暂无号牌',
                'plate_acquire_amount' => 0,
            ];
        }
        $source = (int)($record['plate_source'] ?? 0);
        if ($source === self::PLATE_SOURCE_ADMIN) {
            return [
                'plate_acquire_type' => 'admin',
                'plate_acquire_label' => '管理员设置',
                'plate_acquire_amount' => 0,
            ];
        }
        $isTimed = $this->sessionServices->isTimedAuction($cateId);
        $amount = $isTimed
            ? StoreTimedDepositServices::getDepositAmount()
            : 5000.0;
        if ($source === self::PLATE_SOURCE_DEPOSIT || (int)($record['paystatus'] ?? 0) === 1) {
            return [
                'plate_acquire_type' => 'deposit',
                'plate_acquire_label' => '保证金缴纳(' . $this->formatDepositAmount($amount) . ')',
                'plate_acquire_amount' => $amount,
            ];
        }
        return [
            'plate_acquire_type' => 'admin',
            'plate_acquire_label' => '管理员设置',
            'plate_acquire_amount' => 0,
        ];
    }

    protected function formatDepositAmount(float $amount): string
    {
        if (floor($amount) == $amount) {
            return (string)(int)$amount;
        }
        return rtrim(rtrim(number_format($amount, 2, '.', ''), '0'), '.');
    }

    protected function normalizePlateNumber($value): string
    {
        $num = trim((string)$value);
        if ($num === '' || $num === '0') {
            return '';
        }
        return $num;
    }

    protected function getPlateRow(int $userId, array $ctx): array
    {
        $cateId = (int)$ctx['cate_id'];
        try {
            $cacheKey = $this->cateCacheKey($userId, $cateId);
            // 号牌状态涉及前台参拍按钮判断，优先读库避免后台分配后的缓存延迟
            $info = $this->dao->findPlateRecordByCate($userId, $cateId);
            if ($info) {
                CacheService::set($cacheKey, $info, 600, $cacheKey);
            } else {
                CacheService::clear($cacheKey);
            }
            return is_array($info) ? $info : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function clearPlateCache(int $userId, int $cateId, array $ctx): void
    {
        foreach ($this->resolvePlateCateIds($ctx, $cateId) as $cid) {
            CacheService::clear($this->cateCacheKey($userId, $cid));
        }
    }

    /**
     * 保证金退款完成后的统一收尾：收回号牌、标已退、暂停代理、清缓存
     */
    public function completeDepositRefund(int $userId, int $cateId): void
    {
        if ($userId <= 0 || $cateId <= 0) {
            return;
        }
        $cateIds = $this->resolveRefundSyncCateIds($userId, $cateId);
        if (!$cateIds) {
            return;
        }
        $this->dao->updateUserPlateByCateIds($userId, $cateIds, [
            'paystatus' => 2,
            'number_plate' => 0,
            'plate_source' => self::PLATE_SOURCE_NONE,
            'state' => 0,
        ]);
        $agentServices = app()->make(StoreAgentPriceServices::class);
        foreach ($cateIds as $cid) {
            $cid = (int)$cid;
            CacheService::clear($this->cateCacheKey($userId, $cid));
            $agentServices->suspendAgentsForUserCate($userId, $cid);
        }
        $this->clearBidCachesForUserCate($userId, $cateId);
        CacheService::clear('timed_deposit_' . $userId);
    }

    /**
     * @deprecated 请使用 completeDepositRefund
     */
    public function markSessionPlatesRefunded(int $userId, int $cateId): void
    {
        $this->completeDepositRefund($userId, $cateId);
    }

    protected function findPlateRecordInSession(int $userId, int $cateId): array
    {
        $record = $this->dao->findPlateRecordByCate($userId, $cateId) ?: [];
        if ($record) {
            return $record;
        }
        $ctx = $this->sessionServices->resolveContext($cateId);
        foreach ($this->resolvePlateCateIds($ctx, $cateId) as $cid) {
            $row = $this->dao->findPlateRecordByCate($userId, (int)$cid);
            if ($row) {
                return $row;
            }
        }
        return [];
    }

    /**
     * 后台发起保证金退款前的校验；保证金账户已退时仅同步号牌收尾
     *
     * @return array{ok:bool,msg:string,sync_only?:bool}
     */
    public function resolveAdminDepositRefund(int $userId, int $cateId): array
    {
        if ($userId <= 0 || $cateId <= 0) {
            return ['ok' => false, 'msg' => '参数错误'];
        }
        $cateId = $this->resolveRefundPrimaryCateId($userId, $cateId);
        $record = $this->findPlateRecordInSession($userId, $cateId);
        if ($record && (int)($record['plate_source'] ?? 0) === self::PLATE_SOURCE_ADMIN) {
            return ['ok' => false, 'msg' => '该号牌为管理员设置，不支持保证金退款'];
        }
        if ($this->sessionServices->isTimedAuction($cateId)) {
            $deposit = $this->depositServices->getStatus($userId);
            if ((int)($deposit['paystatus'] ?? 0) === 2) {
                $this->completeDepositRefund($userId, $cateId);
                return ['ok' => true, 'msg' => '保证金已退款，已同步号牌状态', 'sync_only' => true];
            }
            if ((int)($deposit['paystatus'] ?? 0) !== 1) {
                return ['ok' => false, 'msg' => '保证金未处于可退状态'];
            }
            return ['ok' => true, 'msg' => ''];
        }
        $acquire = $this->resolvePlateAcquireDisplay($userId, $cateId, $record);
        if ((int)($record['paystatus'] ?? 0) === 2) {
            $this->completeDepositRefund($userId, $cateId);
            return ['ok' => true, 'msg' => '保证金已退款，已同步号牌状态', 'sync_only' => true];
        }
        if (!$this->canRefundDeposit($userId, $cateId, $record, $acquire)) {
            return ['ok' => false, 'msg' => '当前号牌非保证金获得或保证金未处于可退状态'];
        }
        return ['ok' => true, 'msg' => ''];
    }

    /**
     * 需同步为已退款的专场：周期内专场 + 同号牌号码的其它专场
     * @return int[]
     */
    protected function resolveRefundSyncCateIds(int $userId, int $cateId): array
    {
        $ctx = $this->sessionServices->resolveContext($cateId);
        $cateIds = $this->resolvePlateCateIds($ctx, $cateId);
        $record = $this->findPlateRecordInSession($userId, $cateId);
        $plateNum = $this->normalizePlateNumber($record['number_plate'] ?? '');
        if ($plateNum !== '') {
            $samePlateCates = $this->dao->getCateIdsByUserAndPlateNumber($userId, $plateNum);
            // 双场周期：同号牌仅同步本周期专场，避免误改历史专场行
            if (($ctx['mode'] ?? '') === 'session' && (int)($ctx['session_id'] ?? 0) > 0) {
                $sessionCateIds = $this->getSessionCateIds((int)$ctx['session_id'], $cateId);
                if ($sessionCateIds) {
                    $samePlateCates = array_values(array_intersect($samePlateCates, $sessionCateIds));
                }
            }
            $cateIds = array_values(array_unique(array_merge($cateIds, $samePlateCates)));
        }
        if (!$cateIds) {
            $cateIds = [$cateId];
        }
        return $cateIds;
    }

    protected function cateCacheKey(int $userId, int $cateId): string
    {
        return 'Plates_' . $userId . $cateId;
    }

    public function getList($status = 0, array $where = [])
    {
        [$page, $limit] = $this->getPageValue();
        return $this->dao->plateList($status, $where, $page, $limit);
    }

    public function getAdminList(int $filterCateId, int $paystatus): array
    {
        [$page, $limit, $defaultLimit] = $this->getPageValue();
        if ($page <= 0) {
            $page = 1;
        }
        if ($limit <= 0) {
            $limit = $defaultLimit > 0 ? $defaultLimit : 20;
        }
        if ($paystatus < 0 || $paystatus > 2) {
            $paystatus = -1;
        }
        $result = $this->dao->adminListSimple($filterCateId, $paystatus, $page, $limit);
        return [
            'product' => $this->formatAdminPlateList($result['list']),
            'count' => $result['count'],
        ];
    }

    /**
     * 同步同周期号牌：仅更新已存在行；分配号牌时可补建周期内各专场行（各专场 user+cate 唯一）
     * @param bool $createMissingRows true=分配号牌时为本周期各专场各建一条（若尚未登记）
     */
    protected function syncSessionPlatesAfterAssign(int $userId, array $ctx, int $registerCateId, array $fields, bool $createMissingRows = false): void
    {
        if ($userId <= 0) {
            return;
        }
        $cateIds = $this->resolvePlateCateIds($ctx, $registerCateId);
        if (!$cateIds) {
            return;
        }
        if ($createMissingRows && ($ctx['mode'] ?? '') === 'session') {
            foreach ($cateIds as $cid) {
                if (!$this->dao->findPlateRecordByCate($userId, $cid)) {
                    $this->insertPendingPlate($userId, $cid);
                }
            }
        }
        $allowed = ['number_plate', 'state', 'paystatus', 'remaining', 'plate_source'];
        $update = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $fields)) {
                $update[$key] = $fields[$key];
            }
        }
        if (!$update) {
            return;
        }
        $targetCateIds = $createMissingRows
            ? $cateIds
            : array_values(array_filter($cateIds, function ($cid) use ($userId) {
                return (bool)$this->dao->findPlateRecordByCate($userId, (int)$cid);
            }));
        if (!$targetCateIds) {
            return;
        }
        $this->dao->updateUserPlateByCateIds($userId, $targetCateIds, $update);
        $this->dao->dedupePlatesByCates($userId, $targetCateIds);
    }

    protected function getSessionCateIds(int $sessionId, int $fallbackCateId = 0): array
    {
        $ids = $this->sessionServices->getSessionCateIdsBySessionId($sessionId);
        if (!$ids && $fallbackCateId > 0) {
            $ids = [$fallbackCateId];
        }
        return $ids;
    }

    protected function formatAdminPlateList(array $list): array
    {
        if (!$list) {
            return [];
        }
        $userServices = app()->make(UserServices::class);
        $categoryDao = new StoreCategoryDao();
        foreach ($list as $k => $vo) {
            $uid = (int)($vo['user_id'] ?? 0);
            $cateId = (int)($vo['cate_id'] ?? 0);
            if ($uid > 0) {
                $user = $this->modelToArray($userServices->getUserInfo($uid, 'nickname,phone'));
                $list[$k]['name'] = $user['nickname'] ?? '';
                $list[$k]['phone'] = $user['phone'] ?? '';
            } else {
                $list[$k]['name'] = '';
                $list[$k]['phone'] = '';
            }
            if ($cateId > 0) {
                $cate = $this->modelToArray($categoryDao->get($cateId, ['id', 'cate_name']));
                $list[$k]['special'] = trim((string)($cate['cate_name'] ?? ''));
            } else {
                $list[$k]['special'] = '';
            }
            if ($list[$k]['special'] === '') {
                $list[$k]['special'] = '-';
            }
            $plateRaw = trim((string)($vo['number_plate'] ?? ''));
            $list[$k]['number_plate'] = ($plateRaw !== '' && $plateRaw !== '0') ? $plateRaw : '待分配';
            if ($cateId > 0 && $uid > 0) {
                $list[$k]['stock'] = (int)$this->getproduct(['uid' => $uid, 'cate_id' => $cateId]);
                $list[$k]['sales'] = (int)$this->getproduct(['uid' => $uid, 'sales' => 1, 'cate_id' => $cateId]);
            } else {
                $list[$k]['stock'] = 0;
                $list[$k]['sales'] = 0;
            }
            $acquire = $this->resolvePlateAcquireDisplay($uid, $cateId, $vo);
            $list[$k]['plate_acquire_type'] = $acquire['plate_acquire_type'];
            $list[$k]['plate_acquire_label'] = $acquire['plate_acquire_label'];
            $list[$k]['plate_acquire_amount'] = $acquire['plate_acquire_amount'];
            $list[$k]['can_refund_deposit'] = $this->canRefundDeposit($uid, $cateId, $vo, $acquire);
            $list[$k]['plate_status_locked'] = $list[$k]['can_refund_deposit'];
            $refundPrimaryCateId = $this->resolveRefundPrimaryCateId($uid, $cateId);
            $list[$k]['refund_primary_cate_id'] = $refundPrimaryCateId;
        }
        $refundBtnAssigned = [];
        foreach ($list as $k => $row) {
            $uid = (int)($row['user_id'] ?? 0);
            $primaryCateId = (int)($row['refund_primary_cate_id'] ?? 0);
            $groupKey = $uid . '_' . $primaryCateId;
            if (!empty($row['can_refund_deposit']) && empty($refundBtnAssigned[$groupKey])) {
                $list[$k]['show_refund_btn'] = true;
                $refundBtnAssigned[$groupKey] = true;
            } else {
                $list[$k]['show_refund_btn'] = false;
            }
        }
        return $list;
    }

    /**
     * 同周期保证金退款入口专场：取周期内最小 cate_id；非周期专场取当前行
     */
    protected function resolveRefundPrimaryCateId(int $uid, int $cateId): int
    {
        if ($uid <= 0 || $cateId <= 0) {
            return $cateId;
        }
        $ctx = $this->sessionServices->resolveContext($cateId);
        if (($ctx['mode'] ?? '') === 'session' && (int)($ctx['session_id'] ?? 0) > 0) {
            $cateIds = $this->getSessionCateIds((int)$ctx['session_id'], $cateId);
            if ($cateIds) {
                return min($cateIds);
            }
        }
        return $cateId;
    }

    protected function canRefundDeposit(int $uid, int $cateId, array $record, array $acquire): bool
    {
        if ($this->sessionServices->isTimedAuction($cateId)) {
            $deposit = $this->depositServices->getStatus($uid);
            if ((int)($deposit['paystatus'] ?? 0) !== 1) {
                return false;
            }
            $ctx = $this->sessionServices->resolveContext($cateId);
            $plateRow = $this->resolveParticipationPlateRow($uid, $ctx, $record);
            return !$this->isAdminAssignedPlate($plateRow);
        }
        if ($acquire['plate_acquire_type'] !== 'deposit') {
            return false;
        }
        return (int)($record['paystatus'] ?? 0) === 1;
    }

    protected function assertAdminPlateStateEditable(int $userId, int $cateId, array $record, bool $assignRequested): void
    {
        $hasPlate = $this->hasAssignedPlate($record);
        $stateChanging = ($assignRequested && !$hasPlate) || (!$assignRequested && $hasPlate);
        if (!$stateChanging) {
            return;
        }
        // 限时拍：仅锁定保证金获得的号牌；管理员设置的号牌不受保证金账户影响
        if ($this->sessionServices->isTimedAuction($cateId)) {
            $deposit = $this->depositServices->getStatus($userId);
            if ((int)($deposit['paystatus'] ?? 0) === 1) {
                $ctx = $this->sessionServices->resolveContext($cateId);
                $plateRow = $this->resolveParticipationPlateRow($userId, $ctx, $record);
                if ($this->isDepositSourcedPlate($plateRow)) {
                    throw new \RuntimeException('保证金获得的号牌请通过「退款」操作处理，不可切换分配状态');
                }
                if ($assignRequested && !$hasPlate) {
                    throw new \RuntimeException('用户已缴纳保证金，请通过「退款」处理后再分配号牌');
                }
            }
            return;
        }
        $acquire = $this->resolvePlateAcquireDisplay($userId, $cateId, $record);
        if ($this->canRefundDeposit($userId, $cateId, $record, $acquire)) {
            throw new \RuntimeException('保证金获得的号牌请通过「退款」操作处理，不可切换分配状态');
        }
    }

    public function updateAdminPlateMeta(int $userId, int $cateId, $state, $remaining): bool
    {
        if ($userId <= 0 || $cateId <= 0) {
            return false;
        }
        $remaining = is_numeric($remaining) ? (int)$remaining : 0;
        if ($remaining < 0) {
            $remaining = 0;
        }
        $assignRequested = $this->normalizeAdminState($state) === 1;
        $ctx = $this->sessionServices->resolveContext($cateId);
        $record = $this->dao->findPlateRecordByCate($userId, $cateId);
        if ($record) {
            $this->assertAdminPlateStateEditable($userId, $cateId, $record, $assignRequested);
        } elseif ($assignRequested && $this->sessionServices->isTimedAuction($cateId)) {
            $deposit = $this->depositServices->getStatus($userId);
            if ((int)($deposit['paystatus'] ?? 0) === 1) {
                throw new \RuntimeException('用户已缴纳保证金，请通过「退款」处理后再分配号牌');
            }
        }
        $plateRevoked = false;

        // 后台开启 = 分配号牌；关闭 = 收回号码（不修改号牌表 state 字段）
        if ($assignRequested) {
            if (!$record) {
                $this->ensurePlateRecord($userId, $cateId, $ctx);
                $record = $this->dao->findPlateRecordByCate($userId, $cateId);
            }
            if ($record && !$this->hasAssignedPlate($record)) {
                if (!$this->assignPlateByAdmin($userId, $cateId, $ctx)) {
                    return false;
                }
                $record = $this->dao->findPlateRecordByCate($userId, $cateId);
            }
        } elseif ($record && $this->hasAssignedPlate($record)) {
            if (!$this->revokePlateByAdmin($userId, $cateId, $ctx)) {
                return false;
            }
            $plateRevoked = true;
            $record = $this->dao->findPlateRecordByCate($userId, $cateId);
        }

        if ($record) {
            $record = $this->dao->findPlateRecordByCate($userId, $cateId) ?: $record;
            $record['remaining'] = $remaining;
            CacheService::set($this->cateCacheKey($userId, $cateId), $record, 600, $this->cateCacheKey($userId, $cateId));
        } elseif ($assignRequested) {
            $this->clearPlateCache($userId, $cateId, $ctx);
        }

        if (!$record) {
            return !$assignRequested;
        }

        $this->editPlate(['cate_id' => $cateId, 'user_id' => $userId], [
            'remaining' => $remaining,
        ]);
        if ($plateRevoked) {
            app()->make(StoreAgentPriceServices::class)->suspendAgentsForUserCate($userId, $cateId);
        }
        if ($assignRequested || $plateRevoked) {
            $this->clearBidCachesForUserCate($userId, $cateId);
        }
        return true;
    }

    protected function normalizeAdminState($state): int
    {
        if ($state === true || $state === 1 || $state === '1') {
            return 1;
        }
        return 0;
    }

    /**
     * 后台开启状态时分配号牌（不要求保证金，不修改 paystatus）
     */
    protected function assignPlateByAdmin(int $userId, int $cateId, array $ctx): bool
    {
        if ($this->depositServices->isDepositPaymentInProgress($userId)) {
            throw new \RuntimeException('该用户正在缴纳保证金，请待支付完成后再分配号牌');
        }
        if ($this->sessionServices->isTimedAuction($cateId)) {
            $assignErr = $this->assertTimedPlateCate($cateId);
            if ($assignErr !== '') {
                throw new \RuntimeException($assignErr);
            }
            $res = $this->assignPlateNumber($userId, $ctx, $cateId, false);
            if ($this->normalizePlateNumber($res['number_plate'] ?? '') === '') {
                $msg = trim((string)($res['msg'] ?? ''));
                throw new \RuntimeException($msg !== '' ? $msg : '号牌分配失败');
            }
            return true;
        }

        if (!$this->dao->findPlateRecordByCate($userId, $cateId)) {
            $this->insertPendingPlate($userId, $cateId);
        }
        $number = $this->generateUniquePlateNumber($ctx, $cateId);
        if ($number <= 0) {
            throw new \RuntimeException('号牌生成失败，请重试');
        }
        $this->editPlate(['user_id' => $userId, 'cate_id' => $cateId], [
            'number_plate' => $number,
            'state' => 1,
            'plate_source' => self::PLATE_SOURCE_ADMIN,
        ]);
        $this->clearPlateCache($userId, $cateId, $ctx);
        return true;
    }

    /**
     * 后台关闭分配：收回号牌号码（同周期专场一并同步），不修改 state/paystatus
     */
    protected function revokePlateByAdmin(int $userId, int $cateId, array $ctx): bool
    {
        $record = $this->dao->findPlateRecordByCate($userId, $cateId);
        if (!$record || !$this->hasAssignedPlate($record)) {
            return true;
        }

        $plateUpdate = ['number_plate' => 0, 'plate_source' => self::PLATE_SOURCE_NONE];
        if ($this->sessionServices->isTimedAuction($cateId)) {
            $this->syncSessionPlatesAfterAssign($userId, $ctx, $cateId, $plateUpdate, false);
        } else {
            $this->editPlate(['user_id' => $userId, 'cate_id' => $cateId], $plateUpdate);
        }
        $this->clearPlateCache($userId, $cateId, $ctx);
        return true;
    }

    protected function batchLoadCategoryBrief(array $cateIds): array
    {
        $cateIds = array_values(array_unique(array_filter(array_map('intval', $cateIds))));
        if (!$cateIds) {
            return [];
        }
        $categoryDao = new StoreCategoryDao();
        $fields = ['id', 'cate_name'];
        try {
            $tableFields = $categoryDao->getModel()->getTableFields();
            if (in_array('status', $tableFields, true)) {
                $fields[] = 'status';
            }
        } catch (\Throwable $e) {
        }
        $rows = $categoryDao->getModel()->whereIn('id', $cateIds)->field(implode(',', $fields))->select()->toArray();
        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['id']] = $row;
        }
        return $map;
    }

    protected function modelToArray($row): array
    {
        if (is_array($row)) {
            return $row;
        }
        if (is_object($row) && method_exists($row, 'toArray')) {
            return $row->toArray();
        }
        return [];
    }

    public function editPlate(array $where = [], array $data = [])
    {
        $userId = (int)($where['user_id'] ?? 0);
        $cateId = (int)($where['cate_id'] ?? 0);
        if ($userId > 0 && $cateId > 0) {
            $ctx = $this->sessionServices->resolveContext($cateId);
            if (($ctx['mode'] ?? '') === 'session') {
                $this->clearPlateCache($userId, $cateId, $ctx);
                $ret = $this->dao->editPlate(['user_id' => $userId, 'cate_id' => $cateId], $data);
                $this->syncSessionPlatesAfterAssign($userId, $ctx, $cateId, $data, false);
                return $ret;
            }
        }
        return $this->dao->editPlate($where, $data);
    }

    public function editPlate1(array $where = [], array $data = [])
    {
        return $this->dao->editPlate($where, $data);
    }

    public function getPlate($uid, $page)
    {
        $list = $this->dao->getPlate(['user_id' => $uid], $page);
        $cateIds = [];
        foreach ($list as $vo) {
            $cid = (int)($vo['cate_id'] ?? 0);
            if ($cid > 0) {
                $cateIds[$cid] = $cid;
            }
        }
        $cateMap = $this->batchLoadCategoryBrief(array_values($cateIds));
        foreach ($list as $k => $vo) {
            $cateId = (int)($vo['cate_id'] ?? 0);
            $plateNum = $this->normalizePlateNumber($vo['number_plate'] ?? '');
            $list[$k]['number_plate_display'] = $plateNum !== '' ? $plateNum : '待分配';
            if ($cateId > 0) {
                $cateRow = $cateMap[$cateId] ?? [];
                $list[$k]['name'] = $cateRow['cate_name'] ?? '';
                $list[$k]['status'] = (int)($cateRow['status'] ?? 0);
            }
        }
        return $list;
    }

    public function getCount($status, array $where = [])
    {
        return $this->dao->getContent($status, $where);
    }

    public function getproduct($where)
    {
        $storeProductDao = new StoreProductDao;
        return $storeProductDao->getproduct($where);
    }
}
