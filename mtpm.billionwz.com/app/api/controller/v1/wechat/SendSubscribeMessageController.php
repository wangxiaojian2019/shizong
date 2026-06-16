<?php
namespace app\api\controller\v1\wechat;

use think\Request;
use app\services\product\product\SubscriptionInformation;
use app\services\product\product\StoreCategoryServices;
use app\dao\product\product\StoreMarginDao;
use app\services\product\product\StoreNumberPlateServices;
use crmeb\services\CacheService;
use app\services\wechat\SendSubscribeMessageServices;

class SendSubscribeMessageController
{
    protected $services;
    public function __construct(SubscriptionInformation $services)
    {
        $this->services = $services;
    }
   
    public function SendSubscribeMessage(Request $request)
    {
        $data = $request->getMore([
            ['openId',''],
            ['product_id', 0],
        ]);
        $data['uid'] = (int)$request->uid();
        $data['created_at'] = time();
        $data['status'] = 0;
        if($this->services->getScribeMessages(['product_id'=>$data['product_id'],'uid'=>$data['uid']])!='' && $data['openId']!=''){
             $this->services->saveData($data);
        }
       
        return app('json')->success();
    }
    
    public function getScribeMessage(Request $request){
        
        // $data = $request->getMore([
        //     ['cate_id', 0],
        // ]);
        // $data['uid'] = (int)$request->uid();
        // return app('json')->success( $this->services->getScribeMessages($data));
        //$this->services->StartSale();
        $data = $request->getMore([
           
            ['product_id', 0],
        ]);
        $data['uid'] = (int)$request->uid();
        $data['status'] = 0;
        $list = $this->services->getScribeMessages($data);
        
        return app('json')->success($list);
    }
}