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

use app\services\product\product\StoreBiddingServices;
use think\Request;


/**
 * Class CategoryController
 * @package app\api\controller\v1\store
 */
class StoreBiddingController
{
    protected $services;

    public function __construct(StoreBiddingServices $services)
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
     public function indexs(){
        ;
        return app('json')->success($this->services->automatic());
     }
    
    public function auction(Request $request){
        $uid = (int)$request->uid();
        $data = $request->getMore([
            ['product_id', 5],
            ['plate', 0],
            ['price',0]
        ]);
        $data['product_id'] = (int)$data['product_id'];
        $data['price'] = (int)$data['price'];  
        $data['uid'] = $uid;
        $data['bidding_time'] = time();
        $data['pattern'] = 1;
        $msg = '';
        $sum = 0;
        while($msg == '' && $sum<3){
            $msg = $this->services->addPrice($data);
            $sum = $sum + 1;
        }
        if($msg == ''){
            $msg = "出价失败";   
        }
        if ($msg === '出价成功') {
            return app('json')->success($msg);
        }
        return app('json')->fail($msg);
    }
    
    public function getBidding(Request $request){
        $uid = (int)$request->uid();
        $data = $request->getMore([
            ['page',1]
        ]);
        return app('json')->success($this->services->getBidding($uid,$data['page']));
    }
    
    public function productBiddingList(Request $request, $product_id = 0){
        $where = $request->getMore([
            ['refresh', 0],
        ]);
        $where['product_id'] = (int)$product_id;
        $where['refresh'] = (int)($where['refresh'] ?? 0);
        return app('json')->success($this->services->getBiddingList($where));
    }
    
    public function synchronousAddPrice(Request $request){
        $uid = (int)$request->uid();
        $data = $request->getMore([
            ['product_id', 5],
            ['plate', 0],
            ['price',0]
        ]);
        $data['product_id'] = (int)$data['product_id'];
        $data['price'] = (int)$data['price'];  
        $data['uid'] = $uid;
        $data['bidding_time'] = time();
        $data['pattern'] = 1;
        $msg = '';
        $sum = 0;
        while($msg == '' && $sum<3){
            $msg = $this->services->synchronousAddPrice($data);
            $sum = $sum + 1;
        }
        if($msg == ''){
            $msg = "出价失败";   
        }
        if ($msg === '出价成功') {
            return app('json')->success($msg);
        }
        return app('json')->fail($msg);
    }
 
}
