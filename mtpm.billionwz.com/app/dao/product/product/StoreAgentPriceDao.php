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
use app\model\product\product\StoreAgentPrice;


/**
 * Class StoreCategoryDao
 * @package app\dao\product\product
 */
class StoreAgentPriceDao extends BaseDao
{
     /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreAgentPrice::class;
    }
    
    public function saveAll(array $data){
        
        $this->getModel()->insert($data);
    }
    
    public function allsave(array $data){
        
        $this->getModel()->saveAll($data);
    }
    
    public function selectAgent(array $where){
        if($this->search($where)->find()){
            return $this->search($where)->find()->toArray();
        }
        else{
            $data = [];
            return $data;
        }
        
    }
    
    
    public function updateAgent(array $where, array $data){
        if($this->search($where)->find()){
           $this->search($where)->where('status','<>',0)->update($data);
        }
    }
    
    public function agentlist(array $where){
        return $this->search($where)->order('id asc')->select()->toArray();
    }
    
    public function plateList(array $where = [], int $page = 0, int $limit = 0){
        if($where['cate_id']!=0){
            return $this->search($where)->when($page && $limit, function ($query) use ($page, $limit) {
                $query->page($page, $limit);
            })->order('id desc')->select()->toArray();     
        }else{
            return $this->search()->when($page && $limit, function ($query) use ($page, $limit) {
                $query->page($page, $limit);
            })->order('id desc')->select()->toArray();
        }
        
    }
    
    
    public function getContent($status,array $where = []){
        if($status == 0){
            return $this->getModel()->count();
        }else{
            return $this->search($where)->count();
        }
        
    }

    /**
     * 查询用户在专场下仍生效的代理出价（status: 1/2）
     */
    public function getActiveByUserCate(int $userId, int $cateId): array
    {
        if ($userId <= 0 || $cateId <= 0) {
            return [];
        }
        return $this->getModel()
            ->where('uid', $userId)
            ->where('cate_id', $cateId)
            ->whereIn('status', [1, 2])
            ->select()
            ->toArray();
    }

    /**
     * 批量暂停用户在专场下的生效代理出价（status: 1/2 -> 0）
     */
    public function suspendActiveByUserCate(int $userId, int $cateId): void
    {
        if ($userId <= 0 || $cateId <= 0) {
            return;
        }
        $this->getModel()
            ->where('uid', $userId)
            ->where('cate_id', $cateId)
            ->whereIn('status', [1, 2])
            ->update(['status' => 0]);
    }
}