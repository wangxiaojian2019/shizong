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

use app\adminapi\controller\AuthController;
use app\services\product\product\StoreCategoryServices;
use think\facade\App;
use app\dao\product\product\StoreProductDao;
use crmeb\services\CacheService;
use app\dao\wechat\WechatUserDao;
use app\services\wechat\SendSubscribeMessageServices;
/**
 * 商品分类控制器
 * Class StoreCategory
 * @package app\admin\controller\system
 */
class StoreCategory extends AuthController
{
    /**
     * @var StoreCategoryServices
     */
    protected $service;

    /**
     * StoreCategory constructor.
     * @param App $app
     * @param StoreCategoryServices $service
     */
    public function __construct(App $app, StoreCategoryServices $service)
    {
        parent::__construct($app);
        $this->service = $service;
    }

    /**
     * 分类列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['is_show', ''],
            ['pid', ''],
            ['cate_name', ''],
        ]);
        $data = $this->service->getList($where);
        return app('json')->success($data);
    }

    /**
     * 商品分类搜索
     * @param $type
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function tree_list($type)
    {
        $list = $this->service->getTierList(1, $type);
        return app('json')->success($list);
    }

    /**
     * 获取分类cascader格式数据
     * @param $type
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function cascader_list($type = 1)
    {
        return app('json')->success($this->service->cascaderList(1, $type));
    }

    /**
     * 修改状态
     * @param string $is_show
     * @param string $id
     */
    public function set_show($is_show = '', $id = '')
    {
        if ($is_show == '' || $id == '') return app('json')->fail(100100);
        $this->service->setShow($id, $is_show);
        return app('json')->success($is_show == 1 ? 100003 : 100004);
    }

    /**
     * 生成新增表单
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function create()
    {
        return app('json')->success($this->service->createForm());
    }

    /**
     * 保存新增分类
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function save()
    {
        $data = $this->request->postMore([
            ['cate_name', ''],
            ['big_pic', ''],
            ['sort', 0],
            ['delayCycle', 120],
            ['startTime', ''],
            ['number', 1],
            ['status', 0],
            ['is_show', 0],
            ['article_id', 0],
            ['model', 0],
            ['bidding_ladder', 0],
            ['sceneId', 0],
        ]);
        $data['startTime'] = strtotime($data['startTime']);
        $this->service->createData($data);
        return app('json')->success(100000);
    }

    /**
     * 生成更新表单
     * @param $id
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function edit($id)
    {
        return app('json')->success($this->service->editForm((int)$id));
    }

    /**
     * 更新分类
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function update($id,$state)
    {
        $data = $this->request->postMore([
            ['cate_name', ''],
            ['big_pic', ''],
            ['sort', 0],
            ['delayCycle', 120],
            ['startTime', ''],
            ['number', 1],
            ['status', 0],
            ['is_show', 0],
            ['article_id', 0],
            ['model', 0],
            ['bidding_ladder', 0],
            ['sceneId', 0],
            ['pid', 0],
            ['product',''],
           
        ]);
        //return app('json')->success($data['product']);
        $data['startTime'] = strtotime($data['startTime']);
        $this->service->editData($id, $data);
        $time = time();
        $storeProductDao = new StoreProductDao();
        CacheService::clear('productList1');
        if($state == 1){
            $product = $data['product'][$data['number']-2];
            $product = $storeProductDao->getInfoone($product['id']);
            $product['presale_end_time'] = $time;
            if($product['uid']!=0){
                $wechatUserDao = new WechatUserDao();
                $unionid = $wechatUserDao->getuniOnid($product['uid']);
                if($unionid){
                    $sendSubscribeMessageServices = app()->make(SendSubscribeMessageServices::class);
                    $openid = $wechatUserDao->getOpenid($unionid);
                    if($openid){
                        $templateId = "HltVEvU89cw2E-dzw7a5ZNv2Z2DxbF6snVsNSjSq1f0";
                        $data1 = [
                            "thing8" => [
                                "value" => $product['store_name'],
                            ],
                            "amount2" => [
                                "value" => $product['price'],
                            ]
                        ];
                        $pagepath = "pages/goods_cate/synchronous_product_details?id=".$product['id']."&cate_id=".$product['cate_id'];
                        $res = $sendSubscribeMessageServices->sendMiniProgramTemplate($openid,$templateId,$data1,$pagepath);
                    }
                }    
            }
               
            
            $storeProductDao->automatic(['id' => $product['id']],['presale_end_time'=> $time]);
            $name = 'product_'.$product['id'];
           // $product = $storeProductDao->getInfoone($product['id']);
            CacheService::set($name,$product,600,'product');
            
            $product1 = $data['product'][$data['number']-1]; 
            $product1 = $storeProductDao->getInfoone($product1['id']);
            $product1['presale_start_time'] = $time;
            $storeProductDao->automatic(['id' => $product1['id']],['presale_start_time'=> $time]);
            $name1 = 'product_'.$product1['id'];
           
            CacheService::set($name1,$product1,600,'product');
        }else if($state == 2){
            $product= $data['product'][0];
            $product['presale_start_time'] = $time;
            $storeProductDao->automatic(['id' => $product['id']],['presale_start_time'=> $time]);
            $name = 'product_'.$product['id'];
            CacheService::set($name,$product,600,'product');
            
            $wechatUserDao = new WechatUserDao();
             
            $list = $wechatUserDao->getlists();
            $date1 = date('Y-m-d H:i:s', $data['startTime']);
            foreach($list as $vo){
                $openid = $vo['openid'];
                $templateId = "RmZLl7wTknwPgjVh5mTGlr46u65212qO7AfcTjdsZ3M";
                $data2 = [
                    "thing16" => [
                        "value" => $data['cate_name'],
                    ],
                    "time4" => [
                        "value" => $date1,
                    ],
                    "thing27" => [
                        "value" => "拍卖专场正式开拍",
                    ]
                ];
                $pagepath = "pages/goods_cate/synchronouslist?id=".$id;
                $res = $sendSubscribeMessageServices->sendMiniProgramTemplate($openid,$templateId,$data2,$pagepath);
            }
        }else if($state == 3){
            $product = $data['product'][$data['number']-1];
            $product = $storeProductDao->getInfoone($product['id']);
            $product['presale_end_time'] = $time;
            if($product['uid']!=0){
                $wechatUserDao = new WechatUserDao();
                $unionid = $wechatUserDao->getuniOnid($product['uid']);
                if($unionid){
                    $sendSubscribeMessageServices = app()->make(SendSubscribeMessageServices::class);
                    $openid = $wechatUserDao->getOpenid($unionid);
                    if($openid){
                        $templateId = "HltVEvU89cw2E-dzw7a5ZNv2Z2DxbF6snVsNSjSq1f0";
                        $data1 = [
                            "thing8" => [
                                "value" => $product['store_name'],
                            ],
                            "amount2" => [
                                "value" => $product['price'],
                            ]
                        ];
                        $pagepath = "pages/goods_cate/synchronous_product_details?id=".$product['id']."&cate_id=".$product['cate_id'];
                        $res = $sendSubscribeMessageServices->sendMiniProgramTemplate($openid,$templateId,$data1,$pagepath);
                    }
                }
            }
            
            $storeProductDao->automatic(['id' => $product['id']],['presale_end_time'=> $time]);
            $name = 'product_'.$product['id'];
            CacheService::set($name,$product,600,'product');
        }
        return app('json')->success(100001);
    }

    /**
     * 回显专场围观人数
     */
    public function get_view($id = '')
    {
        $cateId = (int)$id;
        if ($cateId <= 0) {
            return app('json')->fail('参数错误');
        }
        return app('json')->success($this->service->getViewConfig($cateId));
    }

    /**
     * 配置专场围观人数（小程序展示为「围观 N 次」）
     */
    public function set_view($id = '')
    {
        $cateId = (int)$id;
        if ($cateId <= 0) {
            return app('json')->fail('参数错误');
        }
        $data = $this->request->postMore([
            ['view', 0],
        ]);
        $view = (int)$data['view'];
        $this->service->setViewCount($cateId, $view);
        return app('json')->success('保存成功', ['view' => $view]);
    }

    /**
     * 删除分类
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        $this->service->del((int)$id);
        return app('json')->success(100002);
    }
}
