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

use app\services\product\product\StoreAgentPriceServices;
use think\Request;
use app\services\product\product\StoreProductServices;
/**
 * Class CategoryController
 * @package app\api\controller\v1\store
 */
class StoreAgentPriceController
{
    protected $services;

    public function __construct(StoreAgentPriceServices $services)
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
        $StoreProductServices = app()->make(StoreProductServices::class);
        $data = $request->getMore([
            ['product_id', 0],
            ['agent_price', 0],
            ['status', 1],
            ['plate', ''],
            ['model',0],
        ]);
        $info = $StoreProductServices->getInfo($data['product_id']);
        $data['cate_id'] = $info['productInfo']['cate_id'][0];
        $data['uid'] = $uid;
        
        $res = $this->services->saveData($data);
        if (is_string($res) && $res !== '代理出价成功') {
            return app('json')->fail($res);
        }
        return app('json')->success($res ?: '代理出价成功');
    }
    
    
    /**
     * 更新代理
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function updateAgent(Request $request){
       $uid = (int)$request->uid();
        $where = $request->getMore([
            ['product_id', 0],
        ]);
        $where['uid'] = $uid;
       
        $data = $request->getMore([
            ['agent_price', 0],
            ['status', 1],
        ]);
        $res = $this->services->updateAgents($where,$data);
        if (is_string($res) && $res !== '') {
            return app('json')->fail($res);
        }
       
        return app('json')->success('保存成功');
    }
    
    /**
     * 更新代理
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
   
    public function cancelAgent(Request $request){
        $uid = (int)$request->uid();
        $where = $request->getMore([
            ['product_id', 0],
        ]);
        $where['uid'] = $uid;
       
        $data['status'] = 0;
        $this->services->updateAgents($where,$data);
        
        return app('json')->success();
    }
 
    public function storeCount(Request $request){
        $uid = (int)$request->uid();
        
        return app('json')->success($this->services->getCount($uid));
         
    }
    public function detail(Request $request){
        $uid = (int)$request->uid();
        $where = $request->getMore([
            ['product_id', 0],
        ]);
        $where['uid'] = $uid;
       
        return app('json')->success($this->services->detail($where));
    }
    
}
