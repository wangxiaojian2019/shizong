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

namespace app\dao\product\product;

use app\dao\BaseDao;
use app\model\product\product\StoreNumberPlate;


/**
 * Class StoreCategoryDao
 * @package app\dao\product\product
 */
class StoreNumberPlateDao extends BaseDao
{
     /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreNumberPlate::class;
    }
    
    public function insertData1(array $data)
    {
        $this->insertPlateUnique($data);
    }

    /**
     * 插入号牌行：依赖 uk_user_cate(user_id,cate_id)，重复则去重后视为成功
     */
    public function insertPlateUnique(array $data): bool
    {
        $userId = (int)($data['user_id'] ?? 0);
        $cateId = (int)($data['cate_id'] ?? 0);
        if ($userId <= 0 || $cateId <= 0) {
            return false;
        }
        if ($this->findPlateRecordByCate($userId, $cateId)) {
            return true;
        }
        try {
            $this->getModel()->insert($data);
            return true;
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (strpos($msg, '1062') !== false || stripos($msg, 'Duplicate') !== false) {
                $this->dedupePlatesByCates($userId, [$cateId]);
                return true;
            }
            throw $e;
        }
    }
    
    public function selectPlate(array $where){
        if( $this->search($where)->find()){
            return $this->search($where)->find()->toArray();
        }
        else{
            $data['number_plate'] = "";
            return $data;
        }
        
    }
    
    /**
     * 后台号牌列表：直接按号牌表条件查询（与改造前一致）
     */
    public function adminListSimple(int $cateId, int $paystatus, int $page, int $limit): array
    {
        $query = $this->getModel();
        if ($cateId > 0) {
            $query = $query->where('cate_id', $cateId);
        }
        if ($paystatus >= 0) {
            $query = $query->where('paystatus', $paystatus);
        }
        $count = (int)(clone $query)->count();
        $list = $query->page($page, $limit)->order('id desc')->select()->toArray();
        return ['list' => $list, 'count' => $count];
    }

    public function plateList($status, array $where = [], int $page = 0, int $limit = 0){
        if($status == 1){
            return $this->search($where)->when($page && $limit, function ($query) use ($page, $limit) {
                $query->page($page, $limit);
            })->order('id desc')->select()->toArray();     
        }else{
            return $this->getModel()->when($page && $limit, function ($query) use ($page, $limit) {
                $query->page($page, $limit);
            })->order('id desc')->select()->toArray();
        }
        
    }

    /**
     * 后台号牌列表：按专场筛选时，同拍卖周期内共享号牌一并展示
     */
    public function adminPlateList(int $cateId, int $sessionId, array $sessionCateIds, int $paystatus, bool $useFilter, int $page = 0, int $limit = 0, array $timedPayFilter = []): array
    {
        $query = $this->buildAdminPlateQuery($cateId, $sessionId, $sessionCateIds, $paystatus, $useFilter, $timedPayFilter);
        if ($page > 0 && $limit > 0) {
            $query->page($page, $limit);
        }
        return $query->order('id desc')->select()->toArray();
    }

    public function adminPlateCount(int $cateId, int $sessionId, array $sessionCateIds, int $paystatus, bool $useFilter, array $timedPayFilter = []): int
    {
        return (int)$this->buildAdminPlateQuery($cateId, $sessionId, $sessionCateIds, $paystatus, $useFilter, $timedPayFilter)->count();
    }

    /**
     * 专场筛选：当前专场 + 同周期 session_id + 周期内其它绑定专场 cate_id（如 3、4 场共用号牌）
     */
    protected function buildAdminPlateQuery(int $cateId, int $sessionId, array $sessionCateIds, int $paystatus, bool $useFilter, array $timedPayFilter = [])
    {
        $query = $this->getModel();
        if (!$useFilter) {
            return $query;
        }
        if ($paystatus >= 0) {
            $query = $this->applyAdminPaystatusFilter($query, $paystatus, $timedPayFilter);
        }
        if ($cateId > 0) {
            $sessionCateIds = array_values(array_unique(array_filter(array_map('intval', $sessionCateIds))));
            $hasSessionCol = $this->plateTableHasSessionId();
            if ($sessionId > 0 && $hasSessionCol && $sessionCateIds) {
                $ids = $sessionCateIds;
                if (!in_array($cateId, $ids, true)) {
                    $ids[] = $cateId;
                }
                $query = $query->where(function ($q) use ($sessionId, $ids) {
                    $q->where('session_id', $sessionId)->whereOr('cate_id', 'in', $ids);
                });
            } else {
                $query = $query->where('cate_id', $cateId);
            }
        }
        return $query;
    }

    /**
     * 支付状态：同步拍看号牌表 paystatus；限时拍（session_id>0）看保证金账户
     * @param array $timedPayFilter ['mode'=>'include'|'exclude','uids'=>int[]]
     */
    protected function applyAdminPaystatusFilter($query, int $paystatus, array $timedPayFilter)
    {
        if (!empty($timedPayFilter['legacy_only']) || !$this->plateTableHasSessionId()) {
            return $query->where('paystatus', $paystatus);
        }
        $mode = (string)($timedPayFilter['mode'] ?? '');
        $uids = array_values(array_unique(array_filter(array_map('intval', $timedPayFilter['uids'] ?? []))));
        if ($mode === 'exclude') {
            return $query->where(function ($q) use ($paystatus, $uids) {
                $q->where(function ($legacy) use ($paystatus) {
                    $legacy->where('session_id', '<=', 0)->where('paystatus', $paystatus);
                });
                $q->whereOr(function ($timed) use ($uids) {
                    $timed->where('session_id', '>', 0);
                    if ($uids) {
                        $timed->whereNotIn('user_id', $uids);
                    }
                });
            });
        }
        if ($mode === 'include' && $uids) {
            return $query->where(function ($q) use ($paystatus, $uids) {
                $q->where(function ($legacy) use ($paystatus) {
                    $legacy->where('session_id', '<=', 0)->where('paystatus', $paystatus);
                });
                $q->whereOr(function ($timed) use ($uids) {
                    $timed->where('session_id', '>', 0)->whereIn('user_id', $uids);
                });
            });
        }
        return $query->where(function ($q) use ($paystatus) {
            $q->where('session_id', '<=', 0)->where('paystatus', $paystatus);
        });
    }

    protected function plateTableHasSessionId(): bool
    {
        static $has = null;
        if ($has !== null) {
            return $has;
        }
        try {
            $fields = $this->getModel()->getTableFields();
            $has = in_array('session_id', $fields, true);
        } catch (\Throwable $e) {
            $has = false;
        }
        return $has;
    }
    
    public function getContent($status,array $where = []){
        if($status == 1){
            return $this->search($where)->count();
        }else{
            return $this->getModel()->count();
        }
    }
    
    public function getPlate(array $where = [],$page = 1,$limit = 10 ){
        return $this->search($where)->page($page, $limit)->order('id desc')->select()->toArray();
    }
    
    public function editPlate(array $where,array $data){
        
        return $this->search($where)->update($data); 
    }

    public function selectPlateBySession(int $userId, int $sessionId): array
    {
        return $this->findPlateRecordBySession($userId, $sessionId);
    }

    /** 周期内任一场专场的号牌记录（按专场 cate_id 查询，不依赖号牌表 session_id） */
    public function findPlateRecordBySession(int $userId, int $sessionId): array
    {
        if ($sessionId <= 0 || $userId <= 0) {
            return [];
        }
        $categoryDao = new StoreCategoryDao();
        foreach ($categoryDao->getTimedBySessionId($sessionId) as $cate) {
            $row = $this->findPlateRecordByCate($userId, (int)($cate['id'] ?? 0));
            if ($row) {
                return $row;
            }
        }
        return [];
    }

    /** 专场号牌记录（含已登记未分配号牌）；同用户同专场多条时保留已分配号牌或最新一条 */
    public function findPlateRecordByCate(int $userId, int $cateId): array
    {
        if ($cateId <= 0 || $userId <= 0) {
            return [];
        }
        $rows = $this->search(['cate_id' => $cateId, 'user_id' => $userId])
            ->order('id desc')
            ->select()
            ->toArray();
        if (!$rows) {
            return [];
        }
        foreach ($rows as $row) {
            $num = trim((string)($row['number_plate'] ?? ''));
            if ($num !== '' && $num !== '0') {
                return $row;
            }
        }
        return $rows[0];
    }

    /** 同专场内号牌号码是否已被占用（不同用户不可重复） */
    public function numberExistsInCateIds(array $cateIds, int $number): bool
    {
        $cateIds = array_values(array_unique(array_filter(array_map('intval', $cateIds))));
        if (!$cateIds || $number <= 0) {
            return false;
        }
        return (bool)$this->getModel()
            ->whereIn('cate_id', $cateIds)
            ->where('number_plate', $number)
            ->where('number_plate', '>', 0)
            ->find();
    }

    /** 按专场去重：同一用户同一专场只保留一条 */
    public function dedupePlatesByCates(int $userId, array $cateIds): void
    {
        if ($userId <= 0) {
            return;
        }
        $cateIds = array_values(array_unique(array_filter(array_map('intval', $cateIds))));
        if (!$cateIds) {
            return;
        }
        $rows = $this->getModel()
            ->where('user_id', $userId)
            ->whereIn('cate_id', $cateIds)
            ->order('id desc')
            ->select()
            ->toArray();
        $byCate = [];
        foreach ($rows as $row) {
            $byCate[(int)($row['cate_id'] ?? 0)][] = $row;
        }
        foreach ($byCate as $group) {
            if (count($group) <= 1) {
                continue;
            }
            $keepId = (int)$group[0]['id'];
            foreach ($group as $row) {
                $num = trim((string)($row['number_plate'] ?? ''));
                if ($num !== '' && $num !== '0') {
                    $keepId = (int)$row['id'];
                    break;
                }
            }
            foreach ($group as $row) {
                if ((int)$row['id'] !== $keepId) {
                    $this->getModel()->where('id', (int)$row['id'])->delete();
                }
            }
        }
    }

    /**
     * 同一用户、同一号牌号码所在的所有专场 ID（不同 cate_id 各一条记录）
     * @return int[]
     */
    public function getCateIdsByUserAndPlateNumber(int $userId, $plateNumber): array
    {
        if ($userId <= 0) {
            return [];
        }
        $num = trim((string)$plateNumber);
        if ($num === '' || $num === '0') {
            return [];
        }
        $plateInt = (int)$num;
        if ($plateInt <= 0) {
            return [];
        }
        return array_values(array_unique(array_map('intval', $this->getModel()
            ->where('user_id', $userId)
            ->where('number_plate', $plateInt)
            ->where('number_plate', '>', 0)
            ->column('cate_id'))));
    }

    /** 批量更新用户在同周期各专场下的号牌行 */
    public function updateUserPlateByCateIds(int $userId, array $cateIds, array $data): void
    {
        if ($userId <= 0 || !$data) {
            return;
        }
        $cateIds = array_values(array_unique(array_filter(array_map('intval', $cateIds))));
        if (!$cateIds) {
            return;
        }
        $this->getModel()
            ->where('user_id', $userId)
            ->whereIn('cate_id', $cateIds)
            ->update($data);
    }
}