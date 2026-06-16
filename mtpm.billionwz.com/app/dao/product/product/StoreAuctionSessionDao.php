<?php

namespace app\dao\product\product;

use app\dao\BaseDao;
use app\model\product\product\StoreAuctionSession;

class StoreAuctionSessionDao extends BaseDao
{
    protected function setModel(): string
    {
        return StoreAuctionSession::class;
    }

    public function getById(int $id): array
    {
        $row = $this->getModel()->where('id', $id)->find();
        return $row ? $row->toArray() : [];
    }

    public function getModelInstance()
    {
        return $this->getModel();
    }

    /** @return int[] */
    public function getSessionCateIds(array $session): array
    {
        $a = (int)($session['cate_a_id'] ?? 0);
        $b = (int)($session['cate_b_id'] ?? 0);
        $ids = [];
        if ($a > 0) {
            $ids[] = $a;
        }
        if ($b > 0 && $b !== $a) {
            $ids[] = $b;
        }
        return $ids;
    }

    public function findSessionContainingCate(int $cateId): array
    {
        if ($cateId <= 0) {
            return [];
        }
        $row = $this->getModel()
            ->where(function ($q) use ($cateId) {
                $q->where('cate_a_id', $cateId)->whereOr('cate_b_id', $cateId);
            })
            ->find();
        return $row ? $row->toArray() : [];
    }

    /**
     * 已被其它周期占用的专场 ID（专场 A/B 任一字段匹配）
     * @return int[]
     */
    public function getAllBoundCateIds(int $excludeSessionId = 0): array
    {
        $query = $this->getModel()->where(function ($q) {
            $q->where('cate_a_id', '>', 0)->whereOr('cate_b_id', '>', 0);
        });
        if ($excludeSessionId > 0) {
            $query->where('id', '<>', $excludeSessionId);
        }
        $ids = [];
        foreach ($query->select()->toArray() as $row) {
            foreach ($this->getSessionCateIds($row) as $id) {
                $ids[$id] = $id;
            }
        }
        return array_values($ids);
    }
}
