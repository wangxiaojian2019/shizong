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
use app\model\product\product\StoreMargin;


/**
 * Class StoreCategoryDao
 * @package app\dao\product\product
 */
class StoreMarginDao extends BaseDao
{
     /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreMargin::class;
    }
    
    public function insertData1(array $data){
        
        $this->getModel()->insert($data);
    }
    
    public function selectMargin(array $where){
        
        return $this->search($where)->find()->toArray();
    }
    
    public function selectMarginList(array $where){
        
        return $this->search($where)->select()->toArray();
    }
    
    public function editMargin(array $where,array $data){
        
        return $this->search($where)->update($data); 
    }
    

   
}