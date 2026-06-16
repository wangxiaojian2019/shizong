<?php

namespace app\services\product\product;

use app\dao\product\product\StoreAuctionSessionDao;
use app\dao\product\product\StoreCategoryDao;
use app\dao\product\product\StoreNumberPlateDao;
use app\dao\product\product\StoreProductDao;
use app\services\BaseServices;
use crmeb\services\CacheService;

/**
 * 限时拍拍卖周期：仅在 eb_store_auction_session 用 cate_a_id、cate_b_id 绑定两个展示中的限时拍专场，共用号牌。
 * 未纳入周期的限时拍专场仍按单场办牌（resolveContext 为 mode=cate）；不修改 eb_store_category；不影响保证金、出价等其它业务。
 */
class StoreAuctionSessionServices extends BaseServices
{
    public const TIMED_DISPLAY_MAX = 2;

    /** 同周期多专场说明（号牌/保证金规则，不含「最多N场」前缀） */
    public const TIMED_DISPLAY_TIP_RULE = '号牌周期内共用。保证金独立：未退且足额可继续参拍，退清需重缴';

    public const CATE_STATUS_FINISHED = 2;

    /** 仅同周期多场时返回说明文案 */
    public static function buildTimedDisplayTip(int $maxDisplay, int $displayCount): string
    {
        if ($displayCount <= 1) {
            return '';
        }
        return '同一周期最多展示' . $maxDisplay . '场限时拍；' . self::TIMED_DISPLAY_TIP_RULE;
    }

    /** 列表中的限时拍是否同属一个拍卖周期 */
    public function isSameSessionTimedList(array $cates): bool
    {
        $sessionId = 0;
        $matched = 0;
        foreach ($cates as $cate) {
            $cateId = (int)($cate['id'] ?? 0);
            if ($cateId <= 0) {
                continue;
            }
            $ctx = $this->resolveContext($cateId);
            if (($ctx['mode'] ?? '') !== 'session' || (int)($ctx['session_id'] ?? 0) <= 0) {
                return false;
            }
            $sid = (int)$ctx['session_id'];
            if ($sessionId === 0) {
                $sessionId = $sid;
            } elseif ($sessionId !== $sid) {
                return false;
            }
            $matched++;
        }
        return $matched > 1;
    }

    public function __construct(StoreAuctionSessionDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @return array{mode:string,session_id:int,cate_id:int,model:int}
     */
    public function resolveContext(int $cateId): array
    {
        $default = ['mode' => 'cate', 'session_id' => 0, 'cate_id' => $cateId, 'model' => 0];
        if ($cateId <= 0) {
            return $default;
        }
        try {
            $categoryDao = new StoreCategoryDao();
            $cate = $categoryDao->get($cateId);
            if (!$cate) {
                return $default;
            }
            $cate = is_array($cate) ? $cate : $cate->toArray();
        } catch (\Throwable $e) {
            return $default;
        }
        $model = (int)($cate['model'] ?? 0);
        $session = $this->dao->findSessionContainingCate($cateId);
        if ($session) {
            $sessionId = (int)($session['id'] ?? 0);
            $this->maybeAutoUnbindSession($sessionId);
            $session = $this->dao->getById($sessionId);
        }
        if ($session && $this->isValidSessionRecord($session, $model)) {
            return [
                'mode' => 'session',
                'session_id' => (int)$session['id'],
                'cate_id' => $cateId,
                'model' => $model,
            ];
        }
        return [
            'mode' => 'cate',
            'session_id' => 0,
            'cate_id' => $cateId,
            'model' => $model,
        ];
    }

    public function findSessionByCateId(int $cateId): array
    {
        return $this->dao->findSessionContainingCate($cateId);
    }

    /** @return int[] */
    public function getSessionCateIdsBySessionId(int $sessionId): array
    {
        $session = $this->dao->getById($sessionId);
        return $session ? $this->dao->getSessionCateIds($session) : [];
    }

    public function isTimedAuction(int $cateId): bool
    {
        if ($cateId <= 0) {
            return false;
        }
        $categoryDao = new StoreCategoryDao();
        $cate = $categoryDao->get($cateId);
        if (!$cate) {
            return false;
        }
        $cate = $cate->toArray();
        return (int)($cate['model'] ?? 0) === 0;
    }

    public function canApplyDepositRefund(int $cateId): array
    {
        if ($cateId <= 0) {
            return ['ok' => false, 'msg' => '请指定专场'];
        }
        $categoryDao = new StoreCategoryDao();
        $ctx = $this->resolveContext($cateId);
        $checkIds = [$cateId];
        if (($ctx['mode'] ?? '') === 'session' && (int)($ctx['session_id'] ?? 0) > 0) {
            $sessionIds = $this->getSessionCateIdsBySessionId((int)$ctx['session_id']);
            if ($sessionIds) {
                $checkIds = $sessionIds;
            }
        }
        foreach ($checkIds as $cid) {
            $cate = $categoryDao->get((int)$cid);
            if (!$cate || (int)$cate['status'] !== self::CATE_STATUS_FINISHED) {
                return ['ok' => false, 'msg' => '您参与的拍卖未结束'];
            }
        }
        return ['ok' => true, 'msg' => ''];
    }

    public function isValidSessionMode(int $sessionId, int $model = 0): bool
    {
        if ($sessionId <= 0 || $model !== 0) {
            return false;
        }
        $session = $this->dao->getById($sessionId);
        return $session && $this->isValidSessionRecord($session, $model);
    }

    protected function isValidSessionRecord(array $session, int $model = 0): bool
    {
        if ($model !== 0) {
            return false;
        }
        $a = (int)($session['cate_a_id'] ?? 0);
        $b = (int)($session['cate_b_id'] ?? 0);
        return $a > 0 && $b > 0 && $a !== $b;
    }

    /**
     * 绑定专场 A、专场 B（必须两个不同专场）
     */
    public function bindTimedCategories(int $sessionId, int $cateAId, int $cateBId): array
    {
        $cateAId = (int)$cateAId;
        $cateBId = (int)$cateBId;
        if ($sessionId <= 0) {
            return ['ok' => false, 'msg' => '请指定拍卖周期'];
        }
        if ($cateAId <= 0 || $cateBId <= 0) {
            return ['ok' => false, 'msg' => '请选择专场A和专场B'];
        }
        if ($cateAId === $cateBId) {
            return ['ok' => false, 'msg' => '专场A与专场B不能相同'];
        }
        $session = $this->dao->getById($sessionId);
        if (!$session) {
            return ['ok' => false, 'msg' => '拍卖周期不存在'];
        }
        $categoryDao = new StoreCategoryDao();
        foreach ([$cateAId => 'A', $cateBId => 'B'] as $cateId => $label) {
            $err = $this->validateBindableCate($categoryDao, $cateId, $label, $sessionId);
            if ($err !== '') {
                return ['ok' => false, 'msg' => $err];
            }
        }
        $this->dao->getModelInstance()->where('id', $sessionId)->update([
            'cate_a_id' => $cateAId,
            'cate_b_id' => $cateBId,
        ]);
        CacheService::clear('category');
        return ['ok' => true, 'msg' => '绑定成功'];
    }

    protected function validateBindableCate(StoreCategoryDao $categoryDao, int $cateId, string $label, int $sessionId): string
    {
        $cate = $categoryDao->get($cateId);
        if (!$cate) {
            return '专场' . $label . '(#' . $cateId . ')不存在';
        }
        $row = $cate->toArray();
        if ((int)($row['model'] ?? 0) !== 0) {
            return '专场' . $label . '不是限时拍';
        }
        if ((int)($row['is_show'] ?? 0) !== 1) {
            return '专场' . $label . '未开启展示，不可绑定';
        }
        $boundElsewhere = $this->dao->getAllBoundCateIds($sessionId);
        if (in_array($cateId, $boundElsewhere, true)) {
            return '专场' . $label . '已绑定其它拍卖周期';
        }
        return '';
    }

    public function getSessionCategories(int $sessionId): array
    {
        $session = $this->dao->getById($sessionId);
        if (!$session) {
            return [];
        }
        $categoryDao = new StoreCategoryDao();
        $list = [];
        foreach ($this->dao->getSessionCateIds($session) as $cid) {
            $cate = $categoryDao->get($cid);
            if ($cate) {
                $list[] = is_array($cate) ? $cate : $cate->toArray();
            }
        }
        return $list;
    }

    /**
     * 当前限时拍专场所在周期的兄弟专场（未绑定周期则返回空数组）
     */
    public function getParallelTimedCatesForCate(int $cateId): array
    {
        $ctx = $this->resolveContext($cateId);
        if (($ctx['mode'] ?? '') !== 'session' || (int)($ctx['session_id'] ?? 0) <= 0) {
            return [];
        }
        $storeProductDao = new StoreProductDao();
        $cates = [];
        foreach ($this->getSessionCategories((int)$ctx['session_id']) as $cate) {
            if ((int)($cate['is_show'] ?? 0) !== 1) {
                continue;
            }
            $cate['count'] = $storeProductDao->getCount(['cid' => (int)$cate['id'], 'is_show' => 1]);
            $cates[] = $cate;
        }
        return $this->sortTimedCategories($cates);
    }

    public function listSessions(): array
    {
        try {
            return $this->dao->getModelInstance()->order('id desc')->select()->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function listSessionsWithDetail(): array
    {
        $this->autoUnbindFinishedSessions();
        $sessions = $this->listSessions();
        $categoryDao = new StoreCategoryDao();
        foreach ($sessions as $k => $session) {
            $sessionId = (int)($session['id'] ?? 0);
            $cateA = (int)($session['cate_a_id'] ?? 0);
            $cateB = (int)($session['cate_b_id'] ?? 0);
            $cates = $this->getSessionCategories($sessionId);
            $sessions[$k]['categories'] = $cates;
            $sessions[$k]['cate_ids'] = $this->dao->getSessionCateIds($session);
            $sessions[$k]['cate_a_name'] = $this->formatCateLabel($categoryDao, $cateA);
            $sessions[$k]['cate_b_name'] = $this->formatCateLabel($categoryDao, $cateB);
            $sessions[$k]['cate_names'] = array_values(array_filter([
                $sessions[$k]['cate_a_name'],
                $sessions[$k]['cate_b_name'],
            ]));
        }
        return $sessions;
    }

    protected function formatCateLabel(StoreCategoryDao $categoryDao, int $cateId): string
    {
        if ($cateId <= 0) {
            return '';
        }
        $cate = $categoryDao->get($cateId);
        if (!$cate) {
            return '#' . $cateId;
        }
        $row = is_array($cate) ? $cate : $cate->toArray();
        return ($row['cate_name'] ?? '') . '(#' . $cateId . ')';
    }

    public function listTimedCategoryOptions(int $currentSessionId = 0): array
    {
        $this->autoUnbindFinishedSessions();
        $categoryDao = new StoreCategoryDao();
        $statusMap = [0 => '预展', 1 => '拍卖中', 2 => '已结束'];
        $excludeIds = $this->dao->getAllBoundCateIds($currentSessionId);
        $currentSession = $currentSessionId > 0 ? $this->dao->getById($currentSessionId) : [];
        $currentBound = $currentSession ? $this->dao->getSessionCateIds($currentSession) : [];

        try {
            $list = $categoryDao->listTimedAuctionForSessionBind();
        } catch (\Throwable $e) {
            $list = [];
        }

        $filtered = [];
        foreach ($list as $row) {
            $id = (int)($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $isShow = (int)($row['is_show'] ?? 0) === 1;
            $isCurrentBound = in_array($id, $currentBound, true);
            if (!$isShow && !$isCurrentBound) {
                continue;
            }
            if (in_array($id, $excludeIds, true) && !$isCurrentBound) {
                continue;
            }
            $row['status_text'] = $statusMap[(int)($row['status'] ?? 0)] ?? '';
            $row['model_text'] = '限时拍';
            $row['is_show_text'] = $isShow ? '展示' : '隐藏';
            $filtered[] = $row;
        }
        return $filtered;
    }

    public function autoUnbindFinishedSessions(): int
    {
        $unbound = 0;
        try {
            foreach ($this->listSessions() as $session) {
                if ($this->maybeAutoUnbindSession((int)($session['id'] ?? 0))) {
                    $unbound++;
                }
            }
        } catch (\Throwable $e) {
        }
        return $unbound;
    }

    public function maybeAutoUnbindSession(int $sessionId): bool
    {
        if ($sessionId <= 0) {
            return false;
        }
        $session = $this->dao->getById($sessionId);
        if (!$session || !$this->isValidSessionRecord($session)) {
            return false;
        }
        $categoryDao = new StoreCategoryDao();
        foreach ($this->dao->getSessionCateIds($session) as $cid) {
            $cate = $categoryDao->get($cid);
            if (!$cate || (int)$cate['status'] !== self::CATE_STATUS_FINISHED) {
                return false;
            }
        }
        $this->dao->getModelInstance()->where('id', $sessionId)->update([
            'cate_a_id' => 0,
            'cate_b_id' => 0,
        ]);
        CacheService::clear('category');
        return true;
    }

    public function createSession(string $name): int
    {
        return (int)$this->dao->getModelInstance()->insertGetId([
            'name' => $name,
            'cate_a_id' => 0,
            'cate_b_id' => 0,
            'add_time' => time(),
        ]);
    }

    public function updateSession(int $id, string $name): array
    {
        if ($id <= 0) {
            return ['ok' => false, 'msg' => '请指定拍卖周期'];
        }
        if ($name === '') {
            return ['ok' => false, 'msg' => '请输入周期名称'];
        }
        if (!$this->dao->getById($id)) {
            return ['ok' => false, 'msg' => '拍卖周期不存在'];
        }
        $this->dao->getModelInstance()->where('id', $id)->update(['name' => $name]);
        return ['ok' => true, 'msg' => '修改成功'];
    }

    public function deleteSession(int $id): array
    {
        if ($id <= 0) {
            return ['ok' => false, 'msg' => '请指定拍卖周期'];
        }
        $session = $this->dao->getById($id);
        if (!$session) {
            return ['ok' => false, 'msg' => '拍卖周期不存在'];
        }
        $boundCateIds = $this->dao->getSessionCateIds($session);
        if ($boundCateIds) {
            try {
                $plateDao = new StoreNumberPlateDao();
                $plateCount = (int)$plateDao->getModel()->whereIn('cate_id', $boundCateIds)->count();
                if ($plateCount > 0) {
                    return ['ok' => false, 'msg' => '该周期专场已有 ' . $plateCount . ' 条号牌记录，无法删除'];
                }
            } catch (\Throwable $e) {
            }
        }
        $this->dao->getModelInstance()->where('id', $id)->delete();
        CacheService::clear('category');
        return ['ok' => true, 'msg' => '删除成功'];
    }

    public function getDisplayTimedCategories(int $limit = 2): array
    {
        $categoryDao = new StoreCategoryDao();
        try {
            $this->autoUnbindFinishedSessions();
            foreach ($this->listSessions() as $session) {
                if (!$this->isValidSessionRecord($session)) {
                    continue;
                }
                $cates = [];
                foreach ($this->getSessionCategories((int)$session['id']) as $cate) {
                    if ((int)($cate['is_show'] ?? 0) === 1) {
                        $cates[] = $cate;
                    }
                }
                if (count($cates) >= 2) {
                    return $this->sortTimedCategories(array_slice($cates, 0, $limit));
                }
            }
        } catch (\Throwable $e) {
        }
        return $categoryDao->getALlByIndex($limit);
    }

    protected function sortTimedCategories(array $cates): array
    {
        usort($cates, static function ($a, $b) {
            return (int)($b['sort'] ?? 0) <=> (int)($a['sort'] ?? 0);
        });
        return $cates;
    }
}
