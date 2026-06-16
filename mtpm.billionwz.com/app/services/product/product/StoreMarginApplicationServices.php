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

namespace app\services\product\product;


use app\dao\product\product\StoreMarginApplicationDao;
use app\services\BaseServices;
use crmeb\services\CacheService;
use app\dao\product\product\StoreNumberPlateDao;


/**
 * Class StoreProductCateService
 * @package app\services\product\product
 * @method productIdByCateId(array $productId) 根据商品id获取分类id
 * @method cateIdByProduct(array $cate_id) 根据分类获取商品id
 */
class StoreMarginApplicationServices extends BaseServices
{
    public function __construct(StoreMarginApplicationDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 添加代理价格
     * @param $cateData
     * 
     */
    public function saveData($data)
    {
        return $this->dao->allSave($data);
       
    }


}
