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


use app\dao\product\product\StoreAgentPriceDao;
use app\services\BaseServices;
use crmeb\services\CacheService;
use app\dao\product\product\StoreNumberPlateDao;
use app\dao\product\product\StoreBiddingDao;
use app\dao\product\product\StoreProductDao;
use think\facade\Log;

/**
 * Class StoreProductCateService
 * @package app\services\product\product
 * @method productIdByCateId(array $productId) 根据商品id获取分类id
 * @method cateIdByProduct(array $cate_id) 根据分类获取商品id
 */
class StoreAgentPriceServices extends BaseServices
{
    public function __construct(StoreAgentPriceDao $dao)
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
        $plateServices = app()->make(StoreNumberPlateServices::class);
        $cateId = (int)$data['cate_id'];
        $bidCheck = $plateServices->validateBidEligibility((int)$data['uid'], $cateId, $data['plate'] ?? '');
        if (!$bidCheck['ok']) {
            return $bidCheck['msg'];
        }
        $data['plate'] = $bidCheck['plate'];

        if($this->dao->selectAgent(['product_id' => $data['product_id'], 'uid' => $data['uid'],'status' => 1])){
             $res = "请不要重复设置代理出价";
        }else if($this->dao->selectAgent(['product_id' => $data['product_id'], 'uid' => $data['uid'],'status' => 2])){
             $res = "请不要重复设置代理出价";
        }else{
            $this->dao->saveAll($data);
            $name = 'agent_'.$data['uid'].'_'.$data['product_id'];

            $data['status'] = 1;
            
            if($data['model']==1){
                $storeNumberPlateDao = new StoreNumberPlateDao();
                $plate = $storeNumberPlateDao->selectPlate(['cate_id' => $data['cate_id'], 'user_id' => $data['uid']]);
                if (!$plate || !isset($plate['remaining'])) {
                    return '号牌信息无效，请刷新专场后重试';
                }
                $plate['remaining'] = $plate['remaining'] - $data['agent_price'];
                $storeNumberPlateDao->editPlate(['cate_id' => $data['cate_id'], 'user_id' => $data['uid']], $plate);
                $name1 = 'Plates_' . $data['uid'] . $data['cate_id'];
                CacheService::set($name1, $plate, 600, $name1);
                // $plate = $storeNumberPlateDao->selectPlate(['cate_id' => $data['cate_id'],'user_id' => $data['uid']]);
                // $plate['remaining'] = $plate['remaining'] - $data['agent_price'];
                // $storeNumberPlateDao->editPlate(['cate_id' => $data['cate_id'],'user_id' => $data['uid']],$plate);
            }
            
            CacheService::set($name, $data,600,'agent');
            $this->afterAgentPriceChanged((int)$data['product_id'], (int)$data['cate_id']);
            $res = "代理出价成功";    
        }
        
        return $res;
    }

    protected function afterAgentPriceChanged(int $productId, int $cateId): void
    {
        if ($productId <= 0) {
            return;
        }
        $biddingServices = app()->make(StoreBiddingServices::class);
        $biddingServices->refreshProductAuctionCache($productId, $cateId);
        $biddingServices->runAgentAutomatic();
    }

    // public function selectPlatedetails($where)
    // {
    //     return $this->dao->selectAgent($where);
    // }
    
    public function updateAgents(array $where = [], array $data = []){
        
        if(($data['status'] ?? 1) != 0){
            $info = $this->dao->selectAgent(['product_id' =>  $where['product_id'], 'uid' =>  $where['uid'],'status' => 1]);
            if(!$info){
                $info = $this->dao->selectAgent(['product_id' =>  $where['product_id'], 'uid' =>  $where['uid'],'status' => 2]);
            }
            if (!$info) {
                return '代理出价记录不存在';
            }
            $storeProductDao = new StoreProductDao();
            $product = $storeProductDao->getInfoone($where['product_id']);
            $cateId = (int)($product['cate_id'] ?? ($info['cate_id'] ?? 0));
            if ($cateId <= 0) {
                return '专场信息无效';
            }
            $plateServices = app()->make(StoreNumberPlateServices::class);
            $bidCheck = $plateServices->validateBidEligibility((int)$where['uid'], $cateId, $info['plate'] ?? '');
            if (!$bidCheck['ok']) {
                return $bidCheck['msg'];
            }
        }

        if($data['status'] == 0){
            $info = $this->dao->selectAgent(['product_id' =>  $where['product_id'], 'uid' =>  $where['uid'],'status' => 1]);
            if($info){
                if($info['model'] == 1){
                    $storeProductDao = new StoreProductDao();
                    $name = 'product_'.$where['product_id'];
                    $info1 = CacheService::get($name);
                    if(!$info1){
                        //$storeProductDao = new StoreProductDao();
                        $info1 = $storeProductDao->getInfoone($where['product_id']);
                        CacheService::set($name,$info1,600,'product');
                    }
                    $storeNumberPlateDao = new StoreNumberPlateDao();
                    if($info1['uid'] == $where['uid'] && $info1['pattern'] == 0){
                        $plateName = "Plates_".$where['uid'].$info1['cate_id'];
                        $plate = CacheService::get($plateName);
                        if(!$plate){
                            $plate = $storeNumberPlateDao->selectPlate(['cate_id' => $info1['cate_id'],'user_id' => $where['uid']]);
                        }
                                    
                        $plate['remaining'] = $plate['remaining'] - $info1['price'] + $info['agent_price'];
                        CacheService::set($plateName,$plate,600,$plateName);
                        $storeNumberPlateDao->editPlate(['cate_id' => $info1['cate_id'],'user_id' => $where['uid']],$plate);   
                        $info1['pattern'] = 1;
                        CacheService::set($name,$info1,600,'product');
                       //return $info
                    }else{
                        
                        $plateName = "Plates_".$where['uid'].$info1['cate_id'];
                        $plate = CacheService::get($plateName);
                        if(!$plate){
                            $plate = $storeNumberPlateDao->selectPlate(['cate_id' => $info1['cate_id'],'user_id' => $where['uid']]);
                        }
                                    
                        $plate['remaining'] = $plate['remaining'] + $info['agent_price']; 
                        CacheService::set($plateName,$plate,600,$plateName);
                        $storeNumberPlateDao->editPlate(['cate_id' => $info1['cate_id'],'user_id' => $where['uid']],$plate);   
                    }
                }
            }
        }
        
        
        $this->dao->updateAgent($where,$data);
        $name = 'agent_'.$where['uid'].'_'.$where['product_id'];
        $info = CacheService::get($name);
        if(!$info){
            $info = [];
            $info = $this->dao->selectAgent(['product_id' =>  $where['product_id'], 'uid' =>  $where['uid'],'status' => 1]);
            if(!$info){
                $info = $this->dao->selectAgent(['product_id' =>  $where['product_id'], 'uid' =>  $where['uid'],'status' => 2]);
            }
        } 
        
        if($data['status'] != 0){
            $info['agent_price'] = $data['agent_price'];
        }
        
        $info['status'] = $data['status'];
        CacheService::set($name, $info,600,'agent');
        $productId = (int)($where['product_id'] ?? 0);
        $cateId = (int)($info['cate_id'] ?? 0);
        if ($cateId <= 0 && $productId > 0) {
            $product = app()->make(StoreProductDao::class)->getInfoone($productId);
            $cateId = (int)($product['cate_id'] ?? 0);
        }
        if ($productId > 0 && ((int)($data['status'] ?? 0) === 0 || isset($data['agent_price']))) {
            if ((int)($data['status'] ?? 0) === 0) {
                app()->make(StoreBiddingServices::class)->refreshProductAuctionCache($productId, $cateId);
            } else {
                $this->afterAgentPriceChanged($productId, $cateId);
            }
        }
        return;
        
    }

    public function getList(array $where = []){
        [$page, $limit] = $this->getPageValue();
        return $this->dao->plateList($where,$page,$limit);
    }
    
    public function getCount($uid = 0){
        
        $StoreBiddingDao = new StoreBiddingDao();
        $data['bidding'] = $StoreBiddingDao->getcount(['uid' => $uid]);
        $StoreNumberPlateDao = new StoreNumberPlateDao();
        $data['plate'] = $StoreNumberPlateDao->getcount(['user_id' => $uid]);
        return $data;
    }
    
    public function detail(array $where){
        $name = 'agent_'.$where['uid'].'_'.$where['product_id'];
        $info = CacheService::get($name);
        if(!$info){
            $info = [];
            $info = $this->dao->selectAgent(['product_id' =>  $where['product_id'], 'uid' =>  $where['uid'],'status' => 1]);
            if(!$info){
                $info = $this->dao->selectAgent(['product_id' =>  $where['product_id'], 'uid' =>  $where['uid'],'status' => 2]);
            }
            CacheService::set($name, $info,600,'agent');
        }
        
        
        return $info;
    }
    
    public function getSAgentsCount($status,array $where = []){
        $count = $this->dao->getContent($status,$where);
        return $count;
    }

    /**
     * 后台收回号牌后，取消该用户在本专场下仍生效的代理出价，避免定时任务继续代出价
     */
    public function suspendAgentsForUserCate(int $userId, int $cateId): void
    {
        if ($userId <= 0 || $cateId <= 0) {
            return;
        }
        $agents = $this->dao->getActiveByUserCate($userId, $cateId);
        if (!$agents) {
            return;
        }
        $this->dao->suspendActiveByUserCate($userId, $cateId);
        foreach ($agents as $agent) {
            $productId = (int)($agent['product_id'] ?? 0);
            if ($productId > 0) {
                CacheService::clear('agent_' . $userId . '_' . $productId);
            }
        }
    }
}
