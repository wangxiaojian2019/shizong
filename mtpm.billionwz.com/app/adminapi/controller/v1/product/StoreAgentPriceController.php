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
namespace app\adminapi\controller\v1\product;


use app\services\product\product\StoreAgentPriceServices;
use app\services\product\product\StoreCategoryServices;
use app\services\product\product\StoreProductServices;
use app\services\user\UserServices;
use think\Request;
use crmeb\services\CacheService;
use app\adminapi\controller\AuthController;


/**
 * Class CategoryController
 * @package app\api\controller\v1\store
 */
class StoreAgentPriceController extends AuthController
{
    protected $services;

    public function __construct( StoreAgentPriceServices $services)
    {
       
        $this->services = $services;
    }

    /**
     * 添加号牌
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getlist(Request $request)
    {
        $where = $request->getMore([
            ['name', 0]
        ]);
        $cid = 0;
        $StoreCategoryServices = app()->make(StoreCategoryServices::class);
        $UserServices = app()->make(UserServices::class);
        $StoreProductServices = app()->make(StoreProductServices::class);
        $list['record'] = $this->services->getList(['cate_id' => $cid]);
        foreach($list['record'] as $k => $vo){
            $info = $StoreProductServices->getInfo($vo['product_id']);
            
            $special = $StoreCategoryServices->Details($info['productInfo']['cate_id'][0]);
            $user = $UserServices->getUserInfo($vo['uid']);
            $list['record'][$k]['name'] = $user['nickname'];
            $list['record'][$k]['special'] = $special['cate_name'];
            $list['record'][$k]['product_name'] = $info['productInfo']['store_name'];
            
          
        }
        if($where['name'] == 0){
            $list['count'] = $this->services->getSAgentsCount(0);
        }else{
            $list['count'] = $this->services->getSAgentsCount(1,$where);
        }
        
        return app('json')->success($list);
    }
    
  
    
    
 
}
