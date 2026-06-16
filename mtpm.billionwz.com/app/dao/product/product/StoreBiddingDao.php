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
use app\model\product\product\StoreBidding;
use crmeb\services\CacheService;


/**
 * Class StoreCategoryDao
 * @package app\dao\product\product
 */
class StoreBiddingDao extends BaseDao
{
     /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreBidding::class;
    }
    
    public function saveAll(array $data){
        $this->getModel()->insert($data);
    }
    
     public function allSave(array $data){
        $this->getModel()->insertALL($data);
    }
    
    public function getList(array $where){
        return $this->search($where)->order('price asc')->select();
    }
   
    public function getCount(array $where){
        return $this->search($where)->count();
    }
    
    public function getBidding(array $where = [],$page = 1,$limit = 10 ){
         return $this->search($where)->page($page, $limit)->order('price asc')->select()->toArray();
    }
}