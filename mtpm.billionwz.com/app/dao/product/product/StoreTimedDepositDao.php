<?php

namespace app\dao\product\product;

use app\dao\BaseDao;
use app\model\product\product\StoreTimedDeposit;

class StoreTimedDepositDao extends BaseDao
{
    protected function setModel(): string
    {
        return StoreTimedDeposit::class;
    }

    public function getByUid(int $uid): array
    {
        $row = $this->getModel()->where('uid', $uid)->find();
        if (!$row) {
            return [];
        }
        return [
            'id' => (int)($row['id'] ?? 0),
            'uid' => (int)($row['uid'] ?? 0),
            'paid_total' => (float)($row['paid_total'] ?? 0),
            'paystatus' => (int)($row['paystatus'] ?? 0),
            'application' => (int)($row['application'] ?? 0),
            'update_time' => (int)($row['update_time'] ?? 0),
        ];
    }

    public function saveByUid(int $uid, array $data): void
    {
        $exists = $this->getModel()->where('uid', $uid)->find();
        $data['update_time'] = time();
        if ($exists) {
            $this->getModel()->where('uid', $uid)->update($data);
        } else {
            $data['uid'] = $uid;
            $this->getModel()->insert($data);
        }
    }

    public function listByApplication(int $application, int $page = 1, int $limit = 20): array
    {
        $query = $this->getModel()->where('application', $application);
        $count = (int)$query->count();
        $list = $query->page($page, $limit)->order('update_time desc')->select()->toArray();
        return ['list' => $list, 'count' => $count];
    }

    /**
     * 批量读取限时拍保证金 paystatus（uid => paystatus）
     */
    public function getPaystatusMapByUids(array $uids): array
    {
        $uids = array_values(array_unique(array_filter(array_map('intval', $uids))));
        if (!$uids) {
            return [];
        }
        return $this->getModel()->whereIn('uid', $uids)->column('paystatus', 'uid');
    }

    /** 已缴足保证金的用户 uid（与 getStatus deposit_sufficient 一致） */
    public function getPaidUserIds(float $depositAmount): array
    {
        return $this->getModel()
            ->where('paystatus', 1)
            ->where('paid_total', '>=', $depositAmount)
            ->column('uid');
    }

    /** 已退款用户 uid */
    public function getRefundedUserIds(): array
    {
        return $this->getModel()->where('paystatus', 2)->column('uid');
    }
}
