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

use app\services\product\product\StoreCategoryServices;
use app\services\product\product\StoreAuctionSessionServices;
use app\services\product\product\StoreTimedDepositServices;
use think\Request;
use think\swoole\Websocket;
use app\listener\WebSocketListener;
use think\Event;
use think\facade\App;
use crmeb\services\sms\Sms;
/**
 * Class CategoryController
 * @package app\api\controller\v1\store
 */
class CategoryController
{
    protected $services;

    public function __construct(StoreCategoryServices $services)
    {
        $this->services = $services;
    }

    /**
     * 获取分类列表
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function category(Request $request)
    {
        return app('json')->success(
            $this->services->getTimedCategoryListResponse(StoreAuctionSessionServices::TIMED_DISPLAY_MAX)
        );
    }
    
   
    
    public function details(Request $request)
    {
        $where = $request->getMore([
            ['id', 0],
        ]);
        $details =  $this->services->getDetails($where['id']);
        //$websocketHandler = App::invokeClass('app\listener\WebSocketListener');
        //$websocket->broadcast()->emit("success",$details);
        //$websocketHandler->to("cate1")->emit("success",$details);
        return app('json')->success($details);
    }

    /**
     * @return mixed
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2022/11/11
     */
    public function getCategoryVersion()
    {
        $data = $this->services->getCategoryVersion();
        return app('json')->success(['version' => $data['version'], 'is_diy' => $data['is_diy']]);
    }
    
    public function limitedTimeSale(){
        $this->services->auction();
        return app('json')->success();
    }
    
    public function addView(Request $request){
        $where = $request->getMore([
            ['id', 0],
        ]);
        $id = $where['id'];
        $this->services->getView($id);
        return app('json')->success();
    }
    
    
    public function getcatList(){
        return app('json')->success(
            $this->services->getTimedCategoryListResponse(StoreAuctionSessionServices::TIMED_DISPLAY_MAX)
        );
    }
    
    public function getsynchronization()
    {
        $category = $this->services->getSynchronization();
        return app('json')->success($category);
    }
    
    
    public function getproductDetails(Request $request){
        
        $where = $request->getMore([
            ['id', 0],
            
        ]);
        $list = $this->services->productDetails($where['id']);
         return app('json')->success($list);
    }
    
    
    public function getSynchronizationlist(Request $request){
        
        $where = $request->getMore([
            ['id', 0],
            
        ]);
        $list = $this->services->getSynchronizationlist($where['id']);
         return app('json')->success($list);
    }
    
    
    public function auctionDetails(Request $request){
        $data = $request->getMore([
            ['id', 0],
        ]);
        $id = (int)($data['id'] ?? 0);
        if ($id <= 0) {
            return app('json')->fail('暂未配置拍卖详情');
        }
        try {
            return app('json')->success($this->services->auctionDetails($id));
        } catch (\Throwable $e) {
            return app('json')->fail($e->getMessage() ?: '拍卖详情不存在');
        }
    }

    /**
     * 限时拍保证金金额（公开，供专场详情页展示）
     */
    public function timedDepositAmount()
    {
        return app('json')->success([
            'deposit_amount' => StoreTimedDepositServices::getDepositAmount(),
        ]);
    }
}
