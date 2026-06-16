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
namespace app\api\controller\v1\store;

use app\services\product\product\StoreMarginApplicationServices;
use think\Request;
use app\services\product\product\StoreProductServices;
/**
 * Class CategoryController
 * @package app\api\controller\v1\store
 */
class StoreMarginApplicationController
{
    protected $services;

    public function __construct(StoreMarginApplicationServices $services)
    {
        $this->services = $services;
    }

    /**
     * 添加代理
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function add(Request $request)
    {
        $uid = (int)$request->uid();
        
    }
    
    
}
