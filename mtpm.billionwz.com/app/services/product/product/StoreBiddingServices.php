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


use app\dao\product\product\StoreBiddingDao;
use app\dao\product\product\StoreAgentPriceDao;
use app\services\BaseServices;
use app\dao\product\product\StoreProductDao;
use app\dao\product\product\StoreCategoryDao;
use app\dao\product\sku\StoreProductAttrValueDao;
use crmeb\services\CacheService;
use think\Collection;
use crmeb\services\sms\Sms;
use app\dao\user\UserDao;
use app\services\wechat\SendSubscribeMessageServices;
use app\dao\wechat\WechatUserDao;
use app\dao\product\product\StoreNumberPlateDao;
use think\facade\Log;
/**
 * Class StoreProductCateService
 * @package app\services\product\product
 * @method productIdByCateId(array $productId) 根据商品id获取分类id
 * @method cateIdByProduct(array $cate_id) 根据分类获取商品id
 */
class StoreBiddingServices extends BaseServices
{
    public function __construct(StoreBiddingDao $dao)
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
        $data['bidding_time'] = time();
        $this->dao->saveAll($data);
        CacheService::clear('bidding');
    }

    /** 清除拍品出价相关缓存，便于小程序立即拉取最新价与记录 */
    public function refreshProductAuctionCache(int $productId, int $cateId = 0): void
    {
        if ($productId <= 0) {
            return;
        }
        // clear() 按 tag 清空；拍品缓存 key 在 tag product/bidding/productList 下，须 delete 指定 key
        CacheService::delete('product_' . $productId);
        CacheService::delete('bidding_' . $productId);
        if ($cateId > 0) {
            CacheService::delete('productList_' . $cateId);
            $sessionServices = app()->make(StoreAuctionSessionServices::class);
            $ctx = $sessionServices->resolveContext($cateId);
            if (($ctx['mode'] ?? '') === 'session' && (int)($ctx['session_id'] ?? 0) > 0) {
                foreach ($sessionServices->getSessionCateIdsBySessionId((int)$ctx['session_id']) as $sessionCateId) {
                    if ((int)$sessionCateId !== $cateId) {
                        CacheService::delete('productList_' . (int)$sessionCateId);
                    }
                }
            }
        }
    }

    public function runAgentAutomatic(): void
    {
        try {
            $this->automatic();
        } catch (\Throwable $e) {
            Log::error('代理出价处理失败: ' . $e->getMessage());
        }
    }

    protected function formatBiddingListForApi(array $bidding): array
    {
        if ($bidding instanceof Collection) {
            $bidding = $bidding->toArray();
        }
        if ($bidding === []) {
            return [];
        }
        return array_values(collect($bidding)->reverse()->toArray());
    }
    
    public function addPrice($data){

        
        $StoreProductDao = new StoreProductDao();
        $categoryServices = app()->make(StoreCategoryServices::class);
        $productInfo = $categoryServices->getProductAuctionSnapshot((int)$data['product_id']);
        if (!$productInfo || empty($productInfo['id'])) {
            return '拍品不存在';
        }
        $name = 'product_' . $data['product_id'];

        $plateServices = app()->make(StoreNumberPlateServices::class);
        $cateId = (int)($productInfo['cate_id'] ?? 0);
        $bidCheck = $plateServices->validateBidEligibility((int)$data['uid'], $cateId, $data['plate'] ?? '');
        if (!$bidCheck['ok']) {
            return $bidCheck['msg'];
        }
        $data['plate'] = $bidCheck['plate'];

        $StoreCategoryDao = new StoreCategoryDao();
        $category = $this->loadTimedCategoryList($StoreCategoryDao, $StoreProductDao);
        $info = $this->resolveCategoryInfo($cateId, $category, $StoreCategoryDao);
        $StoreProductAttrValueDao = new StoreProductAttrValueDao();
        $time = time();
        
        if($productInfo['presale_end_time']>0){
            $msg = '已结拍'; 
        }else{
            if($productInfo['presale_start_time']>0){
                if($data['price']<= $productInfo['price']){
                    $msg = '出价失败,您的竞价已被超越'; 
                }else{
                    if($productInfo['uid'] != $data['uid']){
                        
                        if($StoreProductDao->automatic(['id' => $data['product_id'],'version' => $productInfo['version']],['price' =>$data['price'],'uid' =>  $data['uid'],'version' => $productInfo['version'] + 1,'pattern'=>1])){
                            if($productInfo['price'] <= $productInfo['cost']){
                                $delayCycle = $time + 120 - $info['startTime'];
                                foreach ($category as $ck => $cvo) {
                                    if ((int)$cvo['id'] === $cateId) {
                                        $category[$ck]['delayCycle'] = $delayCycle;
                                        break;
                                    }
                                }
                                CacheService::set('category',$category,600,'category');
                                $StoreCategoryDao->updateCate($productInfo['cate_id'],['delayCycle' => $delayCycle]);
                            }
                            
                            
                            if($productInfo['uid']!=0 && $productInfo['pattern']!=0){
                               
                                $wechatUserDao = new WechatUserDao();
                                $unionid = $wechatUserDao->getuniOnid($productInfo['uid']);
                                if($unionid){
                                    $sendSubscribeMessageServices = app()->make(SendSubscribeMessageServices::class);
                                    $openid = $wechatUserDao->getOpenid($unionid);
                                    if($openid){
                                        $templateId = "lrO06FsO_PwJey7MqS1PDVskwSgwoNqT-5C4DZ9a1as";
                                        $data2 = [
                                            "thing8" => [
                                                "value" => $info['cate_name'],
                                            ],
                                            "thing3" => [
                                                "value" => $productInfo['store_name'],
                                            ],
                                            "amount6" => [
                                                "value" => $productInfo['price'],
                                            ],
                                            "amount5" => [
                                                "value" => $data['price'],
                                            ]
                                        ];
                                        $pagepath = "pages/goods_cate/goods_cate2?id=".$productInfo['id']."&cate_id=".$productInfo['cate_id'];
                                        $res = $sendSubscribeMessageServices->sendMiniProgramTemplate($openid,$templateId,$data2,$pagepath);
                                    }
                                }
                        
                            }
                            
                            
                            
                            //更新出价
                            $productInfo['price'] = $data['price'];
                            $productInfo['uid'] = $data['uid'];
                            $productInfo['pattern'] = 1;
                            $productInfo['version'] = $productInfo['version'] + 1;
                            CacheService::set($name,$productInfo,600,'product');
                            
                            $data['pattern'] = 1;
                            
                            $biddingName = "bidding_".$data['product_id'];
                            $bidding = CacheService::get($biddingName);
                            if(!$bidding){
                                $bidding = [];
                                $count = $this->dao->getCount(['product_id' => $data['product_id']]);
                                $bidding = $this->dao->getList(['product_id' => $data['product_id']],1,$count);
                            }
                            
                            $bidding[] = [
                                'id' => 0,
                                'uid' => $data['uid'],
                                'product_id' => $data['product_id'],
                                'price' => $data['price'],
                                'plate' => $data['plate'],
                                'pattern' => 1,
                                'bidding_time' => $time,
                            ];
                            
                           // $collection = new Collection($bidding);
                           // $sorted = $collection->sortByDesc('price')->values();
                            
                            CacheService::set($biddingName,$bidding,600,'bidding');
                            
                            //$StoreProductDao->automatic($data['product_id'],['price' =>$data['price'],'uid' =>  $data['uid']]);
                            //$StoreProductDao->automatic([['id'=>$data['product_id'],'uid' => $data['uid'],'price'=> $data['price'],'pattern' => 1]]);
                            $StoreProductAttrValueDao->updateValue($data['product_id'],['price' =>$data['price']]);
                            
                            $this->dao->saveAll($data);
                            
                            $msg = '出价成功';
                            CacheService::delete('productList_' . $cateId);
                            // 有人出价后立即跑代理出价，避免仅依赖定时任务导致小程序端跟价延迟
                            try {
                                $this->automatic();
                            } catch (\Throwable $e) {
                                Log::error('addPrice后代理出价失败: ' . $e->getMessage());
                            }
                        }else{
                            $msg = '出价失败';
                        }
                    
                    }else{
                        $msg = '无需出价,您是当前最高';
                    }
                }
            }else{
                $msg = '拍卖未开始'; 
            }
        }
       
        return $msg;
        
    }
    
    //代理出价
    public function automatic()
    {
         //代理出价模型
        $StoreAgentPriceDao = new StoreAgentPriceDao();
        //产品模型
        $StoreProductDao = new StoreProductDao();
        $StoreProductAttrValueDao = new StoreProductAttrValueDao();
        $StoreCategoryDao = new StoreCategoryDao();
        $plateServices = app()->make(StoreNumberPlateServices::class);
        
        $list = $StoreAgentPriceDao->agentlist(['status' => 1,'model'=>0]);
       
        //代理出价失败  
        $data1 = [];
        
        //代理出价成功
        $data = [];
        
        $time = time();
        
        $categoryServices = app()->make(StoreCategoryServices::class);
        $category = $categoryServices->getTimedCategoryList(2);
        foreach($list as $vo){
            
            $name = 'product_'.$vo['product_id'];
            $productInfo = CacheService::get($name);
            
            if(!$productInfo){
                $productInfo = $StoreProductDao->getInfoone($vo['product_id']);
               
               
                CacheService::set($name,$productInfo,600,"product");
            }

            $cateInfo = $this->resolveCategoryInfo((int)$productInfo['cate_id'], $category, $StoreCategoryDao);

            $bidCheck = $plateServices->validateBidEligibility((int)$vo['uid'], (int)$productInfo['cate_id'], $vo['plate'] ?? '');
            if (!$bidCheck['ok']) {
                continue;
            }
            
            if($productInfo['presale_end_time']!=0){
                $data1[] = [
                    'id' => $vo['id'],
                    'status'=> 0,
                ];
                $name1 = 'agent_'.$vo['uid'].'_'.$vo['product_id'];
                $info1 = CacheService::get($name1);
                if(!$info1){
                    $info1 = [];
                    $info1 = $StoreAgentPriceDao->selectAgent(['product_id' => $vo['product_id'], 'uid' => $vo['uid'],'status' => 1]);
                    if(!$info1){
                        $info1 = $StoreAgentPriceDao->selectAgent(['product_id' => $vo['product_id'], 'uid' => $vo['uid'],'status' => 2]);
                    }
                    
                }
                
                $info1['status'] = 0;
                CacheService::set($name1, $info1,600,'agent');
                
            }else if($productInfo['uid'] != $vo['uid']){
                if($productInfo['price']==0 && $productInfo['ot_price'] != 0){
                    $price= $productInfo['ot_price'];
                }else if($productInfo['price']<50000){
                    $price = $productInfo['price'] + 500;
                }else{
                    $price = $productInfo['price'] + 1000;
                }
                
                if($price>$vo['agent_price']){
                    $data1[] = [
                        'id' => $vo['id'],
                        'status'=> 2,
                    ];
                    
                    
                    if($vo['uid']!=0){
                        
                        $wechatUserDao = new WechatUserDao();
                        $unionid = $wechatUserDao->getuniOnid($vo['uid']);
                      // return $unionid;
                        if($unionid){
                            $sendSubscribeMessageServices = app()->make(SendSubscribeMessageServices::class);
                            $openid = $wechatUserDao->getOpenid($unionid);
                            //return $openid;
                            if($openid){
                               
                                $templateId = "lrO06FsO_PwJey7MqS1PDVskwSgwoNqT-5C4DZ9a1as";
                                $data2 = [
                                    "thing8" => [
                                        "value" => $cateInfo['cate_name'],
                                    ],
                                    "thing3" => [
                                        "value" => $productInfo['store_name'],
                                    ],
                                    "amount6" => [
                                        "value" => $vo['agent_price'],
                                    ],
                                    "amount5" => [
                                        "value" => $productInfo['price'],
                                    ]
                                ];
                                
                                $pagepath = "pages/goods_cate/goods_cate2?id=".$productInfo['id']."&cate_id=".$productInfo['cate_id'];
                                $res =$sendSubscribeMessageServices->sendMiniProgramTemplate($openid,$templateId,$data2,$pagepath);
                                
                            }
                        }
                    
                    }
                    
                    $name2 = 'agent_'.$vo['uid'].'_'.$vo['product_id'];
                    $info2 = CacheService::get($name2);
                    if(!$info2){
                        $info2 = [];
                        $info2 =$StoreAgentPriceDao->selectAgent(['product_id' => $vo['product_id'], 'uid' => $vo['uid'],'status' => 1]);
                        if(!$info2){
                            $info2 = $StoreAgentPriceDao->selectAgent(['product_id' => $vo['product_id'], 'uid' => $vo['uid'],'status' => 2]);
                        }
                        
                    }
                    
                    $info2['status'] = 2;
                    CacheService::set($name2, $info2,600,'agent');
                    
                }else{
                    if($StoreProductDao->automatic(['id' => $vo['product_id'],'version' => $productInfo['version']],['price' =>$price,'uid' => $vo['uid'],'version' => $productInfo['version'] + 1,'pattern' => 0])){
                        if($productInfo['pattern'] == 1 && $productInfo['uid'] != 0){
                            $wechatUserDao = new WechatUserDao();
                            $unionid = $wechatUserDao->getuniOnid($productInfo['uid']);
                          // return $unionid;
                            if($unionid){
                                $sendSubscribeMessageServices = app()->make(SendSubscribeMessageServices::class);
                                $openid = $wechatUserDao->getOpenid($unionid);
                                //return $openid;
                                if($openid){
                                   
                                    $templateId = "lrO06FsO_PwJey7MqS1PDVskwSgwoNqT-5C4DZ9a1as";
                                    $data2 = [
                                        "thing8" => [
                                            "value" => $cateInfo['cate_name'],
                                        ],
                                        "thing3" => [
                                            "value" => $productInfo['store_name'],
                                        ],
                                        "amount6" => [
                                            "value" => $productInfo['price'],
                                        ],
                                        "amount5" => [
                                            "value" => $price,
                                        ]
                                    ];
                                    
                                    $pagepath = "pages/goods_cate/goods_cate2?id=".$productInfo['id']."&cate_id=".$productInfo['cate_id'];
                                    $res =$sendSubscribeMessageServices->sendMiniProgramTemplate($openid,$templateId,$data2,$pagepath);
                                    
                                }
                            }
                        }
                        $data[] = [
                            'uid' => $vo['uid'],
                            'product_id' => $vo['product_id'],
                            'price' => $price,
                            'plate' => $bidCheck['plate'],
                            'pattern' => 0,
                            'bidding_time' => $time,
                        ];
                        
                        $biddingName = "bidding_".$vo['product_id'];
                        $bidding = CacheService::get($biddingName);
                        if(!$bidding){
                            $bidding = [];
                            $count = $this->dao->getCount(['product_id' => $vo['product_id']]);
                            $bidding = $this->dao->getList(['product_id' => $vo['product_id']],1,$count);
                            if(!$bidding){
                                $bidding = [];
                            }
                        }
                        
                        $productInfo['price'] = $price;
                        $productInfo['uid'] = $vo['uid'];
                        $productInfo['pattern'] = 0;
                        $productInfo['version'] = $productInfo['version'] + 1;
                        CacheService::set($name,$productInfo,600,'product');
                            
                        $bidding[] = [
                            'id' => 0,
                            'uid' => $vo['uid'],
                            'product_id' => $vo['product_id'],
                            'price' => $price,
                            'plate' => $bidCheck['plate'],
                            'pattern' => 0,
                            'bidding_time' => $time,
                        ];
                       //collection($bidding)->sortByDesc('price')->values()->all();
                        //$collection = collect($bidding);
                       // $reversed = $collection->reverse();
                            
                        CacheService::set($biddingName,$bidding,600,'bidding');
                        
                       // $StoreProductDao->automatic([['id'=>$vo['product_id'],'uid' => $vo['uid'],'price'=>$price,'pattern' => 0]]);
                        $StoreProductAttrValueDao->updateValue($vo['product_id'],['price' =>$price]);
                    }
                }
            }
        }
        
        if($data!=[]){
            
            $this->dao->allSave($data);
            $StoreCategoryDao = new StoreCategoryDao();
            $StoreProductDao = new StoreProductDao();
            foreach ($this->loadTimedCategoryList($StoreCategoryDao, $StoreProductDao) as $cat) {
                CacheService::delete('productList_' . (int)$cat['id']);
            }
        }
        if($data1!=[]){
            $StoreAgentPriceDao->allsave($data1);
        }
      return 2;
    }
    
    public function getBidding($uid,$page){
        $list = $this->dao->getBidding(['uid' => $uid],$page);
        foreach($list as $k => $vo){
           
            $storeProductDao = new StoreProductDao();
            $info = $storeProductDao->getInfoone($vo['product_id']);

            
            $list[$k]['name'] = $info['store_name'];
            if(!$info['presale_start_time']){
                 $list[$k]['status'] = 0;
            }else if($info['presale_start_time'] && !$info['presale_end_time']){
                $list[$k]['status'] = 1;
            }else{
                $list[$k]['status'] = 2;
            }
            
        }
        return $list;
    }
    
    public function getBiddingList(array $where){
        $productId = (int)($where['product_id'] ?? 0);
        if ($productId <= 0) {
            return [];
        }
        $biddingName = 'bidding_' . $productId;
        if (!empty($where['refresh'])) {
            CacheService::delete($biddingName);
        }
        $bidding = CacheService::get($biddingName);
        if (!$bidding) {
            $list = $this->dao->getList(['product_id' => $productId]);
            if ($list instanceof Collection) {
                $list = $list->toArray();
            } elseif (!is_array($list)) {
                $list = [];
            }
            if ($list !== []) {
                CacheService::set($biddingName, $list, 600, 'bidding');
            }
            $bidding = $list;
        }
        if ($bidding instanceof Collection) {
            $bidding = $bidding->toArray();
        } elseif (!is_array($bidding)) {
            $bidding = [];
        }
        return $this->formatBiddingListForApi($bidding);
    }
    
    
    public function synchronousAddPrice($data){
        $StoreProductDao = new StoreProductDao();
        $categoryServices = app()->make(StoreCategoryServices::class);
        $productInfo = $categoryServices->getProductAuctionSnapshot((int)$data['product_id']);
        if (!$productInfo || empty($productInfo['id'])) {
            return '拍品不存在';
        }
        $name = 'product_' . $data['product_id'];
        $StoreCategoryDao = new StoreCategoryDao();
        
        $category = CacheService::get('category1');
        if(!$category){
            $limit= 1;
            $category = $StoreCategoryDao->getSynchronization($limit);
            //$storeProductDao = new StoreProductDao();
            foreach($category as $k => $vo){
               
                $category[$k]['count'] = $StoreProductDao->getCount(['cid' => $vo['id'],'is_show' => 1]);
            }
            CacheService::set('category1',$category,600,'category1');
        }
        
        $storeNumberPlateDao = new StoreNumberPlateDao();
        $plateServices = app()->make(StoreNumberPlateServices::class);
        $syncCateId = (int)($productInfo['cate_id'] ?? 0);
        $bidCheck = $plateServices->validateBidEligibility((int)$data['uid'], $syncCateId, $data['plate'] ?? '');
        if (!$bidCheck['ok']) {
            return $bidCheck['msg'];
        }
        $data['plate'] = $bidCheck['plate'];
        $info = $category[0];
        $StoreProductAttrValueDao = new StoreProductAttrValueDao();
        $time = time();
        
                if($productInfo['presale_end_time']>0){
            $msg = '已结拍'; 
        }else{
            if($productInfo['presale_start_time']>0){
                if($data['price']<= $productInfo['price']){
                    $msg = '出价失败,您的竞价已被超越'; 
                }else{
                    if($productInfo['uid'] != $data['uid']){
                        
                        if($StoreProductDao->automatic(['id' => $data['product_id'],'version' => $productInfo['version']],['price' =>$data['price'],'uid' =>  $data['uid'],'version' => $productInfo['version'] + 1,'pattern' => 1])){
                           
                            
                            
                            if($productInfo['uid']!=0 && $productInfo['pattern']!=0){
                               
                                $wechatUserDao = new WechatUserDao();
                                $unionid = $wechatUserDao->getuniOnid($productInfo['uid']);
                                if($unionid){
                                    $sendSubscribeMessageServices = app()->make(SendSubscribeMessageServices::class);
                                    $openid = $wechatUserDao->getOpenid($unionid);
                                    if($openid){
                                        $templateId = "lrO06FsO_PwJey7MqS1PDVskwSgwoNqT-5C4DZ9a1as";
                                        $data2 = [
                                            "thing8" => [
                                                "value" => $info['cate_name'],
                                            ],
                                            "thing3" => [
                                                "value" => $productInfo['store_name'],
                                            ],
                                            "amount6" => [
                                                "value" => $productInfo['price'],
                                            ],
                                            "amount5" => [
                                                "value" => $data['price'],
                                            ]
                                        ];
                                        $pagepath = "pages/goods_cate/synchronous_product_details?id=".$productInfo['id']."&cate_id=".$productInfo['cate_id'];
                                        $res = $sendSubscribeMessageServices->sendMiniProgramTemplate($openid,$templateId,$data2,$pagepath);
                                    }
                                }
                                $plateName = "Plates_".$productInfo['uid'].$productInfo['cate_id'];
                                $plate = CacheService::get($plateName);
                                if(!$plate){
                                    $plate = $storeNumberPlateDao->selectPlate(['cate_id' => $productInfo['cate_id'],'user_id' => $productInfo['uid']]);
                                }
                                
                            
                                $plate['remaining'] = $plate['remaining'] + $productInfo['price'];
                                 CacheService::set($plateName,$plate,600,$plateName);
                                $storeNumberPlateDao->editPlate(['cate_id' => $productInfo['cate_id'],'user_id' => $productInfo['uid']],$plate);      
                            }
                            
                            
                            
                            //更新出价
                            $productInfo['price'] = $data['price'];
                            $productInfo['uid'] = $data['uid'];
                            $productInfo['pattern'] = 1;
                            $productInfo['version'] = $productInfo['version'] + 1;
                            
                            
                            $plateName = "Plates_".$productInfo['uid'].$productInfo['cate_id'];
                            $plate = CacheService::get($plateName);
                            if(!$plate){
                                $plate = $storeNumberPlateDao->selectPlate(['cate_id' => $productInfo['cate_id'],'user_id' => $productInfo['uid']]);
                            }
                        
                            
                            $plate['remaining'] = $plate['remaining'] - $productInfo['price'];
                            $storeNumberPlateDao->editPlate(['cate_id' => $productInfo['cate_id'],'user_id' => $productInfo['uid']],$plate);
                            CacheService::set($plateName,$plate,600,$plateName);
                            CacheService::set($name,$productInfo,600,'product');
                            
                            $data['pattern'] = 1;
                            
                            $biddingName = "bidding_".$data['product_id'];
                            $bidding = CacheService::get($biddingName);
                            if(!$bidding){
                                $bidding = [];
                                $count = $this->dao->getCount(['product_id' => $data['product_id']]);
                                $bidding = $this->dao->getList(['product_id' => $data['product_id']],1,$count);
                            }
                            
                            $bidding[] = [
                                'id' => 0,
                                'uid' => $data['uid'],
                                'product_id' => $data['product_id'],
                                'price' => $data['price'],
                                'plate' => $data['plate'],
                                'pattern' => 1,
                                'bidding_time' => $time,
                            ];
                            
                           // $collection = new Collection($bidding);
                           // $sorted = $collection->sortByDesc('price')->values();
                            
                            CacheService::set($biddingName,$bidding,600,'bidding');
                            
                            //$StoreProductDao->automatic($data['product_id'],['price' =>$data['price'],'uid' =>  $data['uid']]);
                            //$StoreProductDao->automatic([['id'=>$data['product_id'],'uid' => $data['uid'],'price'=> $data['price'],'pattern' => 1]]);
                            $StoreProductAttrValueDao->updateValue($data['product_id'],['price' =>$data['price']]);
                            
                            $this->dao->saveAll($data);
                            
                            $msg = '出价成功';
                            CacheService::clear('productList1'); // 清除同步拍列表缓存，使count实时更新
                            try {
                                $this->synchronousAutomatic();
                            } catch (\Throwable $e) {
                                Log::error('synchronousAddPrice后代理出价失败: ' . $e->getMessage());
                            }
                        }else{
                            $msg = '出价失败';
                        }
                    
                    }else{
                        $msg = '无需出价,您是当前最高';
                    }
                }
            }else{
                $msg = '拍卖未开始'; 
            }
        }
       
        return $msg;
    }
    
    public function synchronousAutomatic()
    {
         //代理出价模型
        $StoreAgentPriceDao = new StoreAgentPriceDao();
        //产品模型
        $StoreProductDao = new StoreProductDao();
        $StoreProductAttrValueDao = new StoreProductAttrValueDao();
        $plateServices = app()->make(StoreNumberPlateServices::class);
        //获取代理出价
        
        $list = $StoreAgentPriceDao->agentlist(['status' => 1,'model'=>1]);
       
        //代理出价失败  
        $data1 = [];
        
        //代理出价成功
        $data = [];
        
        $time = time();
        $storeNumberPlateDao = new StoreNumberPlateDao();
        $StoreCategoryDao = new StoreCategoryDao();
        $category = CacheService::get('category1');
        if(!$category){
            $limit= 1;
            $category = $StoreCategoryDao->getSynchronization($limit);
            //$storeProductDao = new StoreProductDao();
            foreach($category as $k => $vo){
               
                $category[$k]['count'] = $StoreProductDao->getCount(['cid' => $vo['id'],'is_show' => 1]);
            }
            CacheService::set('category1',$category,600,'category1');
        }
        
        
        foreach($list as $vo){
            
            $name = 'product_'.$vo['product_id'];
            $productInfo = CacheService::get($name);
            
            if(!$productInfo){
                $productInfo = $StoreProductDao->getInfoone($vo['product_id']);
               
               
                CacheService::set($name,$productInfo,600,"product");
            }

            $syncBidCheck = $plateServices->validateBidEligibility((int)$vo['uid'], (int)$productInfo['cate_id'], $vo['plate'] ?? '');
            if (!$syncBidCheck['ok']) {
                continue;
            }
            
            if($productInfo['presale_end_time']!=0){
                $data1[] = [
                    'id' => $vo['id'],
                    'status'=> 0,
                ];
                $name1 = 'agent_'.$vo['uid'].'_'.$vo['product_id'];
                $info1 = CacheService::get($name1);
                if(!$info1){
                    $info1 = [];
                    $info1 = $StoreAgentPriceDao->selectAgent(['product_id' => $vo['product_id'], 'uid' => $vo['uid'],'status' => 1]);
                    if(!$info1){
                        $info1 = $StoreAgentPriceDao->selectAgent(['product_id' => $vo['product_id'], 'uid' => $vo['uid'],'status' => 2]);
                    }
                    
                }
                
                $plateName = "Plates_".$vo['uid'].$productInfo['cate_id'];
                $plate = CacheService::get($plateName);
                if(!$plate){
                     $plate = $storeNumberPlateDao->selectPlate(['cate_id' => $productInfo['cate_id'],'user_id' => $vo['uid']]);
                }
               
                if($productInfo['uid'] == $vo['uid'] && $productInfo['pattern'] == 0){
                    $plate['remaining'] = $plate['remaining'] + $info1['agent_price'] - $productInfo['price']; 
                     
                }else{
                   $plate['remaining'] = $plate['remaining'] + $info1['agent_price'];
                }
               
                $storeNumberPlateDao->editPlate(['cate_id' => $productInfo['cate_id'],'user_id' => $vo['uid']],$plate);
                 CacheService::set($plateName,$plate,600,$plateName);
                $info1['status'] = 0;
                CacheService::set($name1, $info1,600,'agent');
                
            }else if($productInfo['uid'] != $vo['uid']){
                if($productInfo['price']==0 && $productInfo['ot_price'] != 0){
                    $price= $productInfo['ot_price'];
                }else if($productInfo['price']<100000){
                   $number = ($productInfo['price']/1000)%10;
                   if($number == 0 || $number == 8){
                        $price = $productInfo['price'] + 2000;
                   }else{
                        $price = $productInfo['price'] + 3000;
                   }
                }else{
                    if($category[0]['bidding_ladder'] == 1){
                        $number = ($productInfo['price']/1000)%10;
                        if($number == 0 || $number == 8){
                            $price = $productInfo['price'] + 2000;
                        }else{
                            $price = $productInfo['price'] + 3000;
                        }   
                    }else{
                        $number = ($productInfo['price']/10000)%10;
                        if($number == 0 || $number == 8){
                            $price = $productInfo['price'] + 20000;
                        }else{
                            $price = $productInfo['price'] + 30000;
                        }  
                    }
                }
                
                if($price>$vo['agent_price']){
                    $data1[] = [
                        'id' => $vo['id'],
                        'status'=> 2,
                    ];
                    
                    
                    if($vo['uid']!=0){
                        
                        $wechatUserDao = new WechatUserDao();
                        $unionid = $wechatUserDao->getuniOnid($vo['uid']);
                       // return $unionid;
                        if($unionid){
                            $sendSubscribeMessageServices = app()->make(SendSubscribeMessageServices::class);
                            $openid = $wechatUserDao->getOpenid($unionid);
                            //return $openid;
                            if($openid){
                               
                                $templateId = "lrO06FsO_PwJey7MqS1PDVskwSgwoNqT-5C4DZ9a1as";
                                $data2 = [
                                    "thing8" => [
                                        "value" => $cateInfo['cate_name'],
                                    ],
                                    "thing3" => [
                                        "value" => $productInfo['store_name'],
                                    ],
                                    "amount6" => [
                                        "value" => $vo['agent_price'],
                                    ],
                                    "amount5" => [
                                        "value" => $productInfo['price'],
                                    ]
                                ];
                                
                                $pagepath = "pages/goods_cate/synchronous_product_details?id=".$productInfo['id']."&cate_id=".$productInfo['cate_id'];
                                $res =$sendSubscribeMessageServices->sendMiniProgramTemplate($openid,$templateId,$data2,$pagepath);
                                
                            }
                        }
                    
                    }
                    
                    
                    
                    $name2 = 'agent_'.$vo['uid'].'_'.$vo['product_id'];
                    $info2 = CacheService::get($name2);
                    if(!$info2){
                        $info2 = [];
                        $info2 =$StoreAgentPriceDao->selectAgent(['product_id' => $vo['product_id'], 'uid' => $vo['uid'],'status' => 1]);
                        if(!$info2){
                            $info2 = $StoreAgentPriceDao->selectAgent(['product_id' => $vo['product_id'], 'uid' => $vo['uid'],'status' => 2]);
                        }
                        
                    }
                    $plateName = "Plates_".$vo['uid'].$productInfo['cate_id'];
                    $plate = CacheService::get($plateName);
                    if(!$plate){
                         $plate = $storeNumberPlateDao->selectPlate(['cate_id' => $productInfo['cate_id'],'user_id' => $vo['uid']]);
                    }
                    $plate['remaining'] = $plate['remaining'] + $info2['agent_price'];
                    $storeNumberPlateDao->editPlate(['cate_id' => $productInfo['cate_id'],'user_id' => $vo['uid']],$plate);
                    CacheService::set($plateName,$plate,600,$plateName);
                    
                    $info2['status'] = 2;
                    CacheService::set($name2, $info2,600,'agent');
                    
                }else{
                    if($StoreProductDao->automatic(['id' => $vo['product_id'],'version' => $productInfo['version']],['price' =>$price,'uid' => $vo['uid'],'version' => $productInfo['version'] + 1,'pattern' => 0])){
                        if($productInfo['pattern'] == 1 && $productInfo['uid'] != 0){
                            $wechatUserDao = new WechatUserDao();
                            $unionid = $wechatUserDao->getuniOnid($productInfo['uid']);
                           // return $unionid;
                            if($unionid){
                                $sendSubscribeMessageServices = app()->make(SendSubscribeMessageServices::class);
                                $openid = $wechatUserDao->getOpenid($unionid);
                                //return $openid;
                                if($openid){
                                   
                                    $templateId = "lrO06FsO_PwJey7MqS1PDVskwSgwoNqT-5C4DZ9a1as";
                                    $data2 = [
                                        "thing8" => [
                                            "value" => $cateInfo['cate_name'],
                                        ],
                                        "thing3" => [
                                            "value" => $productInfo['store_name'],
                                        ],
                                        "amount6" => [
                                            "value" => $productInfo['price'],
                                        ],
                                        "amount5" => [
                                            "value" => $price,
                                        ]
                                    ];
                                    
                                    $pagepath = "pages/goods_cate/synchronous_product_details?id=".$productInfo['id']."&cate_id=".$productInfo['cate_id'];
                                    $res =$sendSubscribeMessageServices->sendMiniProgramTemplate($openid,$templateId,$data2,$pagepath);
                                    
            
                                }
                            }
                            $plateName = "Plates_".$productInfo['uid'].$productInfo['cate_id'];
                            $plate = CacheService::get($plateName);
                            if(!$plate){
                                 $plate = $storeNumberPlateDao->selectPlate(['cate_id' => $productInfo['cate_id'],'user_id' => $productInfo['uid']]);
                            }
                            $plate['remaining'] = $plate['remaining'] +$productInfo['price'];
                            $storeNumberPlateDao->editPlate(['cate_id' => $productInfo['cate_id'],'user_id' => $productInfo['uid']],$plate);
                            CacheService::set($plateName,$plate,600,$plateName);
                                    
                        }
                        $data[] = [
                            'uid' => $vo['uid'],
                            'product_id' => $vo['product_id'],
                            'price' => $price,
                            'plate' => $syncBidCheck['plate'],
                            'pattern' => 0,
                            'bidding_time' => $time,
                        ];
                        
                        $biddingName = "bidding_".$vo['product_id'];
                        $bidding = CacheService::get($biddingName);
                        if(!$bidding){
                            $bidding = [];
                            $count = $this->dao->getCount(['product_id' => $vo['product_id']]);
                            $bidding = $this->dao->getList(['product_id' => $vo['product_id']],1,$count);
                            if(!$bidding){
                                $bidding = [];
                            }
                        }
                        
                        $productInfo['price'] = $price;
                        $productInfo['uid'] = $vo['uid'];
                        $productInfo['pattern'] = 0;
                        $productInfo['version'] = $productInfo['version'] + 1;
                        CacheService::set($name,$productInfo,600,'product');
                            
                        $bidding[] = [
                            'id' => 0,
                            'uid' => $vo['uid'],
                            'product_id' => $vo['product_id'],
                            'price' => $price,
                            'plate' => $syncBidCheck['plate'],
                            'pattern' => 0,
                            'bidding_time' => $time,
                        ];
                       //collection($bidding)->sortByDesc('price')->values()->all();
                        //$collection = collect($bidding);
                       // $reversed = $collection->reverse();
                            
                        CacheService::set($biddingName,$bidding,600,'bidding');
                        
                       // $StoreProductDao->automatic([['id'=>$vo['product_id'],'uid' => $vo['uid'],'price'=>$price,'pattern' => 0]]);
                        $StoreProductAttrValueDao->updateValue($vo['product_id'],['price' =>$price]);
                    }
                }
            }
        }
        
        if($data!=[]){
            
            $this->dao->allSave($data);
            CacheService::clear('productList1'); // 清除同步拍列表缓存，使count实时更新
        }
        if($data1!=[]){
            $StoreAgentPriceDao->allsave($data1);
        }
      return 2;
    }

    private function loadTimedCategoryList(StoreCategoryDao $dao, StoreProductDao $productDao): array
    {
        $categoryServices = app()->make(StoreCategoryServices::class);
        return $categoryServices->getTimedCategoryList(2);
    }

    private function resolveCategoryInfo(int $cateId, array $categoryList, StoreCategoryDao $dao): array
    {
        foreach ($categoryList as $item) {
            if ((int)$item['id'] === $cateId) {
                return $item;
            }
        }
        $cate = $dao->get($cateId);
        if ($cate) {
            return $cate->toArray();
        }
        return $categoryList[0] ?? [];
    }
    
    
}
