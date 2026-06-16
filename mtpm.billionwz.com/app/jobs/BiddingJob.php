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

namespace app\jobs;


use app\services\system\log\SystemLogServices;
use crmeb\basic\BaseJobs;
use crmeb\services\CacheService;
use crmeb\traits\QueueTrait;
use think\facade\Log;
use think\facade\Route;
use app\dao\product\product\StoreBiddingDao;
use app\dao\product\product\StoreAgentPriceDao;
use app\dao\product\product\StoreProductDao;
use app\dao\product\product\StoreCategoryDao;
use app\dao\product\sku\StoreProductAttrValueDao;
use think\Collection;
use crmeb\services\sms\Sms;
use app\dao\user\UserDao;
use app\services\wechat\SendSubscribeMessageServices;
use app\dao\wechat\WechatUserDao;

/**
 * Class TemplateJob
 * @package app\jobs
 */
class BiddingJob extends BaseJobs
{
    use QueueTrait;

    /**
     * @param $type
     * @param $openid
     * @param $tempCode
     * @param $data
     * @param $link
     * @param $color
     * @return bool|mixed
     */
    public function doJob($productInfo,$data,$info,$category,$time,$status)
    { 
        $StoreCategoryDao = new StoreCategoryDao();
        $StoreProductDao = new StoreProductDao(); 
        $StoreProductAttrValueDao = new StoreProductAttrValueDao();
        $storeBiddingDao = new StoreBiddingDao();
        $wechatUserDao = new WechatUserDao();
        if($status == 0){
            if($productInfo['price'] <= $productInfo['cost']){
                $delayCycle = $time + 120 - $info['startTime'];
                $category[0]['delayCycle'] = $delayCycle;
                CacheService::set('category',$category,600,'category');
               
                $StoreCategoryDao->updateCate($productInfo['cate_id'],['delayCycle' => $delayCycle]);
            }
                        
                        
           if($productInfo['uid']!=0 && $productInfo['pattern']!=0){
                           
                
                $unionid = $wechatUserDao->getuniOnid($productInfo['uid']);
                if($unionid){
                    $sendSubscribeMessageServices = app()->make(SendSubscribeMessageServices::class);
                    $openid = $wechatUserDao->getOpenid($unionid);
                    if($openid){
                        $templateId = "lrO06FsO_PwJey7MqS1PDVskwSgwoNqT-5C4DZ9a1as";
                        $data2 = [
                            "thing8" => [
                                "value" => $category[0]['cate_name'],
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
            $productInfo1 = $productInfo;
            $productInfo1['price'] = $data['price'];
            $productInfo1['uid'] = $data['uid'];
            $productInfo1['pattern'] = 1;
            CacheService::set($name,$productInfo1,600,'product');
                        
            $data['pattern'] = 1;
                        
            $biddingName = "bidding_".$data['product_id'];
            $bidding = CacheService::get($biddingName);
            if(!$bidding){
                $bidding = [];
                
                $count = $storeBiddingDao->getCount(['product_id' => $data['product_id']]);
                $bidding = $storeBiddingDao->getList(['product_id' => $data['product_id']],1,$count);
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
            
            CacheService::set($biddingName,$bidding,600,'bidding');
           
                        //$StoreProductDao->automatic($data['product_id'],['price' =>$data['price'],'uid' =>  $data['uid']]);
            $StoreProductDao->automatic([['id'=>$data['product_id'],'uid' => $data['uid'],'price'=> $data['price'],'pattern' => 1]]);
            $StoreProductAttrValueDao->updateValue($data['product_id'],['price' =>$data['price']]);
                        
            $storeBiddingDao->saveAll($data);
                        
            $msg = '出价成功';
            CacheService::clear('productList');
        }
    }

}
