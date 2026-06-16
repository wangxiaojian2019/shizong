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


use app\dao\product\product\StoreCategoryDao;
use app\dao\product\product\StoreProductDao;
use app\services\BaseServices;
use app\services\diy\DiyServices;
use crmeb\exceptions\AdminException;
use crmeb\services\CacheService;
use crmeb\services\FormBuilder as Form;
use crmeb\utils\Arr;
use think\facade\Route as Url;
use app\dao\product\product\StoreBiddingDao;
use app\dao\product\product\StoreAgentPriceDao;
use app\dao\article\ArticleDao;
use app\dao\wechat\ScribeMessageDao;
use app\dao\wechat\WechatUserDao;
use app\services\wechat\SendSubscribeMessageServices;
use think\swoole\Websocket;
use crmeb\services\sms\Sms;
use app\dao\user\UserDao;
use think\Collection;
use app\services\product\product\SubscriptionInformation;
use app\services\product\product\StoreAuctionSessionServices;
use app\services\wechat\SendsubscribeService;

/**
 * Class StoreCategoryService
 * @package app\services\product\product
 * @method cateIdByPid(array $cateId) 根据分类id获取上级id
 * @method byIndexList(int $limit, ?string $field) 根据分类id获取上级id
 * @method getCateParentAndChildName(string $cateIds) 获取一级分类和二级分类组成的集合
 * @method value(array $where, string $field) 获取某个字段的值
 * @method getColumn(array $where, string $field, string $key = '') 获取某个字段数组
 */
class StoreCategoryServices extends BaseServices
{
    public function __construct(StoreCategoryDao $dao, StoreBiddingDao $storeBiddingDao)
    {
        $this->dao = $dao;
        $this->storeBiddingDao = $storeBiddingDao;
    }

    /**
     * 获取分类列表
     * @param $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList($where)
    {
        $list = $this->dao->getTierList($where);
        $storeProductDao = new StoreProductDao();
        if (!empty($list) && ($where['cate_name'] !== '' || $where['pid'] !== '')) {
            $pids = Arr::getUniqueKey($list, 'pid');
            $parentList = $this->dao->getTierList(['id' => $pids]);
            $list = array_merge($list, $parentList);
            foreach ($list as $key => $item) {
                $arr = $item;
                unset($list[$key]);
                if (!in_array($arr, $list)) {
                    $list[] = $arr;
                }
               
            }
        }
        foreach ($list as $k => &$value) {
            if ($value['pid'] == 0) {
                $value['url'] = '/pages/goods/goods_list/index?cid=' . $value['id'] . '&title=' . $value['cate_name'];
            } else {
                $value['url'] = '/pages/goods/goods_list/index?sid=' . $value['id'] . '&title=' . $value['cate_name'];
            } 
            $value['startTime'] = date('Y-m-d H:i:s', $value['startTime']);
            $list[$k]['count'] = $storeProductDao->getCount(['cid' => $value['id'],'is_show' => 1]);
            $list[$k]['product'] = $storeProductDao->getList(['cate_id' => $value['id'], 'is_show' => 1],1,$list[$k]['count']);
        }
        $list = get_tree_children($list);
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 商品分类搜索下拉
     * @param string $show
     * @param string $type
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getTierList($show = '', $type = 0)
    {
        $where = [];
        //if ($show !== '') $where['is_show'] = $show;
        if (!$type) $where['pid'] = 0;
        return sort_list_tier($this->dao->getTierList($where));
    }

    /**
     * 获取分类cascader
     * @param string $show
     * @param int $type
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function cascaderList($show = '', $type = 0)
    {
        $where = [];
        //if ($show !== '') $where['is_show'] = $show;
        if (!$type) $where['pid'] = 0;
        $data = get_tree_children($this->dao->getTierList($where, ['id', 'id as value', 'cate_name as label', 'cate_name as title', 'pid']), 'children', 'id');
//        foreach ($data as &$item) {
//            if (!isset($item['children'])) {
//                $item['disabled'] = true;
//            }
//        }
        return $data;
    }

    /**
     * 设置分类状态
     * @param int $id
     * @param int $is_show
     */
    public function setShow(int $id, int $is_show)
    {
        $res = $this->dao->update($id, ['is_show' => $is_show]);
        $res = $res && $this->dao->update($id, ['is_show' => $is_show], 'pid');
        CacheService::clear('category');
        CacheService::clear('category1');
        if (!$res) {
            throw new AdminException(100005);
        }
    }

    /**
     * 创建新增表单
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function createForm()
    {
        return create_form('添加分类', $this->form(), Url::buildUrl('/product/category'), 'POST');
    }

    /**
     * 创建编辑表单
     * @param $id
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function editForm(int $id)
    {
        $info = $this->dao->get($id);
        return create_form('编辑分类', $this->form($info), $this->url('/product/category/' . $id), 'PUT');
    }

    /**
     * 生成表单参数
     * @param array $info
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function form($info = [])
    {
        // if (isset($info['pid'])) {
        //     $f[] = Form::select('pid', '上级分类', (int)($info['pid'] ?? ''))->setOptions($this->menus($info['pid']))->filterable(1);
        // } else {
        //     $f[] = Form::select('pid', '上级分类', (int)($info['pid'] ?? ''))->setOptions($this->menus())->filterable(1);
        // }
        // $f[] = Form::input('cate_name', '分类名称', $info['cate_name'] ?? '')->maxlength(8)->required();
        // $f[] = Form::frameImage('pic', '分类图标(180*180)', Url::buildUrl(config('app.admin_prefix', 'admin') . '/widget.images/index', array('fodder' => 'pic')), $info['pic'] ?? '')->icon('el-icon-picture-outline')->width('950px')->height('560px')->props(['footer' => false]);
        // 
        // $f[] = Form::number('sort', '排序', (int)($info['sort'] ?? 0))->min(0)->precision(0);
        // $f[] = Form::radio('is_show', '状态', $info['is_show'] ?? 1)->options([['label' => '显示', 'value' => 1], ['label' => '隐藏', 'value' => 0]]);
        $f[] = Form::input('cate_name', '分类名称', $info['cate_name'] ?? '')->maxlength(64)->required();
       
        $f[] = Form::dateTime('startTime', '拍卖时间', '')->placeholder('请选择评论时间(不选择默认当前添加时间)')->style(['width' => '300px']);
       
       
        $f[] = Form::number('delayCycle', '延时周期', (int)($info['delayCycle'] ?? 0))->min(0)->precision(0)->required();
        $f[] = Form::frameImage('big_pic', '分类大图', Url::buildUrl(config('app.admin_prefix', 'admin') . '/widget.images/index', array('fodder' => 'big_pic')), $info['big_pic'] ?? '')->icon('el-icon-picture-outline')->width('950px')->height('560px')->props(['footer' => false]);
        $f[] = Form::select('article_id', '专场介绍', (int)($info['article_id'] ?? ''))->setOptions($this->articlelist())->filterable(1);
        $f[] = Form::number('sort', '排序', (int)($info['sort'] ?? 0))->min(0)->precision(0);
        $f[] = Form::radio('status', '状态', (int)($info['status'] ?? 0))->options([['label' => '未开始', 'value' => 0],['label' => '拍卖中', 'value' => 1], ['label' => '已结拍', 'value' => 2]]);
        
         $f[] = Form::radio('is_show', '是否展示', (int)($info['is_show'] ?? 0))->options([['label' => '隐藏', 'value' => 0],['label' => '显示', 'value' => 1]]);
        return $f;
    }

    /**
     * 获取一级分类组合数据
     * @param string $pid
     * @return array[]
     */
    public function menus($pid = '')
    {
        $list = $this->dao->getMenus(['pid' => 0]);
        $menus = [['value' => 0, 'label' => '顶级分类']];
        if ($pid === 0) return $menus;
//        if ($pid != '') $menus = [];
        foreach ($list as $menu) {
            $menus[] = ['value' => $menu['id'], 'label' => $menu['cate_name']];
        }
        return $menus;
    }
    
    public function articlelist()
    {
        $ArticleDao = new ArticleDao();
        $menus = [['value' => 0, 'label' => '请选择专场介绍']];
        $id = 4;
        $list = $ArticleDao->getList(['cid'=>$id],1,20);
        foreach ($list as $menu) {
            $menus[] = ['value' => $menu['id'], 'label' => $menu['title']];
        }
        return $menus;
    }

    /**
     * 保存新增数据
     * @param $data
     * @return int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createData($data)
    {
        // if (!$data['cate_name']) {
        //     throw new AdminException(400100);
        // }

        // if ($this->dao->getOne(['cate_name' => $data['cate_name'], 'pid' => $data['pid']])) {
        //     throw new AdminException(400101);
        // }

        // $parent = $this->dao->getOne(['id' => $data['pid']]);
        // if ($data['pid'] && (!$parent || $parent['pid'] > 0)) {
        //     throw new AdminException(400740);
        // }

        $data['add_time'] = time();
      
        $res = $this->dao->save($data);
        if (!$res) throw new AdminException(100006);

        //CacheService::clear();
        CacheService::clear('category');
        CacheService::clear('productList');
        CacheService::clear('category1');
        return (int)$res->id;
    }

    /**
     * 保存修改数据
     * @param $id
     * @param $data
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 吴汐
     * @email 442384644@qq.com
     * @date 2023/02/23
     */
    public function editData($id, $data)
    {
        if (!$data['cate_name']) {
            throw new AdminException(400100);
        }

        $parent = $this->dao->getOne(['id' => $data['pid']]);
        if ($parent && $parent['pid'] > 0) {
            throw new AdminException(400740);
        }

        $cate = $this->dao->getOne(['cate_name' => $data['cate_name'], 'pid' => $data['pid']]);
        if ($cate && $cate['id'] != $id) {
            throw new AdminException(400101);
        }
        $this->transaction(function () use ($id, $data) {
            if(!$data['startTime']){
                $info = $this->dao->get($id);
                $data['startTime'] = $info['startTime'];
            }
            
            $res = $this->dao->update($id, $data);
            /** @var StoreProductCateServices $productCate */
            $productCate = app()->make(StoreProductCateServices::class);
            $res = $res && $productCate->update(['cate_id' => $id], ['cate_pid' => $data['pid']]);
            if (!$res) throw new AdminException(100007);
        });
        CacheService::clear('category');
        CacheService::clear('category1');
        CacheService::clear('productList');
        CacheService::clear('productList1');
    }

    /**
     * 删除数据
     * @param int $id
     */
    public function del(int $id)
    {
        if ($this->dao->count(['pid' => $id])) {
            throw new AdminException(400102);
        }
        $res = $this->dao->delete($id);
        if (!$res) throw new AdminException(100008);
        CacheService::clear('category');
        CacheService::clear('productList');
    }

    /**
     * @return mixed
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/2/8
     */
    public function getCategoryVersion()
    {
        return CacheService::remember('category_version', function () {
            return [
               
                'version' => uniqid()
            ];
        });
    }

    /**
     * 获取指定id下的分类,一=以数组形式返回
     * @param string $cateIds
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 吴汐
     * @email 442384644@qq.com
     * @date 2023/02/23
     */
    public function getCateArray(string $cateIds)
    {
        return $this->dao->getCateArray($cateIds);
    }

    /**
     * 前台分类列表
     * @param array $where
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 吴汐
     * @email 442384644@qq.com
     * @date 2023/02/23
     */
    public function getCategory(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $limit = StoreAuctionSessionServices::TIMED_DISPLAY_MAX;
        if ($limit) {
            return $this->getTimedCategoryList($limit);
        }
        return CacheService::remember('CATEGORY', function () {
            return $this->dao->getCategory();
        });
    }
    
    
    

    /**
     * 分类详情
     * @param int $id
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getInfo(int $id)
    {
        $info = $this->dao->get($id);
        if ($info) {
            $info = $info->toArray();
        }
        $wherex = [
            'cid' => $id
        ];
        $storeProductDao = new StoreProductDao();
        $info['count'] = $storeProductDao->getCount($wherex,1);
        $where = [
            'cate_id' => $id
        ];
        $info['product'] = $storeProductDao->getList($where,1,$info['count']);
        $storeBiddingDao = new StoreBiddingDao();
        foreach ($info['product'] as $k => $vo){
            $info['product'][$k]['count'] = $storeBiddingDao->getCount(['product_id' => $vo['id']]);
        }
        return $info;
    }

    /**
     * 分类列表
     * @return mixed
     */
    public function getCategoryList(array $where)
    {
        return CacheService::remember('CATEGORY_LIST', function () use ($where) {
            return $this->dao->getALlByIndex($where, 'id, cate_name, pid, pic, big_pic, sort, add_time');
        }, 86400);
    }
    
    
    //更新拍卖专场开始状态
    public function StartSale(){
        $storeProductDao = new StoreProductDao();
        
        //获取当前专场信息
        $category = $this->getTimedCategoryList(2);
        if (!$category) {
            return;
        }
        $time = time();
        foreach ($category as $catIndex => $class) {
            $listKey = 'productList_' . (int)$class['id'];
        if($class['status']==0 && $class['startTime'] <= $time){
            //获取专场产品列表
            $productList = CacheService::get($listKey);
            if(!$productList){
                $productList = $storeProductDao->getList(['cate_id' => $class['id'], 'is_show' => 1],1, $class['count']);
                CacheService::set($listKey, $productList,600,'productList');
            }
            if (empty($productList[0]['id'])) {
                continue;
            }
            
            $category[$catIndex]['status'] = 1;
            CacheService::set('category',$category,600,'category');
            
            $this->dao->updateCate($class['id'],['status' => 1]);
            $data = [];
            $data[] = [
                'id' => $productList[0]['id'],
                'presale_start_time'=> $time,
            ];
            $name = 'product_'.$productList[0]['id'];
            $info = CacheService::get($name);
            if(!$info){
               
                $info = $storeProductDao->getInfoone($productList[0]['id']);
                
            }
            $info['presale_start_time'] = $time;
            
            CacheService::set($name,$info,600,'product');
            $storeProductDao->automatic(['id' => $productList[0]['id']],['presale_start_time'=> $time]);
            
            //CacheService::clear('category');
            CacheService::clear($listKey);
            
                
                
            $productList = CacheService::get($listKey);
            if(!$productList){
                $productList = $storeProductDao->getList(['cate_id' => $class['id'], 'is_show' => 1],1, $class['count']);
                CacheService::set($listKey, $productList,600,'productList');
            }
            $subscriptionInformation = app()->make(SubscriptionInformation::class);
            if (isset($productList[1]['id'])) {
            $list = $subscriptionInformation->getList(['product_id'=>$productList[1]['id']]);
            $sendsubscribeService = app()->make(SendsubscribeService::class);
            foreach($list as $vo){
                $data1 = [
                     'touser' => $vo['openId'],
                    'template_id' => 'TOK4bhRLg3mya7fnZP8fUj5WoGm8wZeu4QKeVSuebg0',
                    'page' => "pages/goods_cate/goods_cate2?id=".$productList[1]['id']."&cate_id=".$class['id'], // 点击消息跳转的页面
                    'data' => [ // 模板中的字段，需与微信后台模板匹配
                        'thing2' => ['value' => $productList[1]['store_name']],
                        'thing7' => ['value' => '拍品即将开拍，点击参与竞拍'],
                    ]
                ];
               
                $sendsubscribeService->sendSubscribeMessage($data1);
               
            }
            }
            
        }else{
            if($class['status']==0 && $class['startTime'] - $time == 7200){
                $sendSubscribeMessageServices = app()->make(SendSubscribeMessageServices::class);
                $wechatUserDao = new WechatUserDao();
                $list = $wechatUserDao->getlists();
                $date1 = date('Y-m-d H:i:s', $class['startTime']);
                foreach($list as $vo){
                    $openid = $vo['openid'];
                    $templateId = "RmZLl7wTknwPgjVh5mTGlr46u65212qO7AfcTjdsZ3M";
                    $data2 = [
                        "thing16" => [
                            "value" => $class['cate_name'],
                        ],
                        "time4" => [
                            "value" => $date1,
                        ],
                        "thing27" => [
                            "value" => "拍卖专场即将开始",
                        ]
                    ];
                    $pagepath = "pages/goods_cate/goods_cate1?id=".$class['id'];
                    $res = $sendSubscribeMessageServices->sendMiniProgramTemplate($openid,$templateId,$data2,$pagepath);
                } 
                
                
                
                
                // $sms = new Sms();
                // $user = new UserDao();
                // $userList = $user->allUser();
                // foreach($userList as $vo){
                //     $phone = $vo['phone'];
                //     $templateId = '1020525020';
                //     $title = $class['cate_name'];
                //     $datetime = date('Y年m月d日 H时i分s秒', $class['startTime']);
                //     $count = $class['count'];
                //     $data = [
                //         $title,
                //         $datetime,
                //         $count,
                //     ];//短信模板变量内容
                            
                //     $res = $sms->send($phone, $templateId, $data);
                // }
                
                
            
                // $scribeMessageDao = new ScribeMessageDao();
                // $list = $scribeMessageDao->getList(['cate_id' => $class['id'],'status'=>0]);
                // $wechatUserDao = new WechatUserDao();
                // $subscriptionInformation = app()->make(SubscriptionInformation::class);
                // foreach($list as $vo){
                //     $openid = $wechatUserDao->getWechatOpenid($vo['uid'],'routine');
                //     $date1 = date('Y-m-d H:i:s', $class['startTime']);
                //     $data =[
                //         'thing14' => ['value' => $class['cate_name']],
                //         'date2' => ['value' => $date1],
                //         'number6' => ['value' => $class['count']],
                //     ];
                //     $res = $subscriptionInformation->send($openid,$data,$vo["template_id"]);
                //     $scribeMessageDao->editscribe(['id' => $vo['id']],['status'=>1]);
               // }
                
            }
            else if($class['status']==0 && $class['startTime'] - $time == 10800){
                $sendSubscribeMessageServices = app()->make(SendSubscribeMessageServices::class);
                $wechatUserDao = new WechatUserDao();
                $list = $wechatUserDao->getlists();
                $date1 = date('Y-m-d H:i:s', $class['startTime']);
                foreach($list as $vo){
                    $openid = $vo['openid'];
                    $templateId = "RmZLl7wTknwPgjVh5mTGlr46u65212qO7AfcTjdsZ3M";
                    $data2 = [
                        "thing16" => [
                            "value" => $class['cate_name'],
                        ],
                        "time4" => [
                            "value" => $date1,
                        ],
                        "thing27" => [
                            "value" => "拍卖专场即将开始",
                        ]
                    ];
                    $pagepath = "pages/goods_cate/goods_cate1?id=".$class['id'];
                    $res = $sendSubscribeMessageServices->sendMiniProgramTemplate($openid,$templateId,$data2,$pagepath);
                } 
                
                
                
                
                // $sms = new Sms();
                // $user = new UserDao();
                // $userList = $user->allUser();
                // foreach($userList as $vo){
                //     $phone = $vo['phone'];
                //     $templateId = '1020525020';
                //     $title = $class['cate_name'];
                //     $datetime = date('Y年m月d日 H时i分s秒', $class['startTime']);
                //     $count = $class['count'];
                //     $data = [
                //         $title,
                //         $datetime,
                //         $count,
                //     ];//短信模板变量内容
                            
                //     $res = $sms->send($phone, $templateId, $data);
                // }
                
                
            
                // $scribeMessageDao = new ScribeMessageDao();
                // $list = $scribeMessageDao->getList(['cate_id' => $class['id'],'status'=>0]);
                // $wechatUserDao = new WechatUserDao();
                // $subscriptionInformation = app()->make(SubscriptionInformation::class);
                // foreach($list as $vo){
                //     $openid = $wechatUserDao->getWechatOpenid($vo['uid'],'routine');
                //     $date1 = date('Y-m-d H:i:s', $class['startTime']);
                //     $data =[
                //         'thing14' => ['value' => $class['cate_name']],
                //         'date2' => ['value' => $date1],
                //         'number6' => ['value' => $class['count']],
                //     ];
                //     $res = $subscriptionInformation->send($openid,$data,$vo["template_id"]);
                //     $scribeMessageDao->editscribe(['id' => $vo['id']],['status'=>1]);
                // }
                
            }
            
            
            else if($class['status']==0 && $class['startTime'] - $time == 14400){
                $sendSubscribeMessageServices = app()->make(SendSubscribeMessageServices::class);
                $wechatUserDao = new WechatUserDao();
                $list = $wechatUserDao->getlists();
                $date1 = date('Y-m-d H:i:s', $class['startTime']);
                foreach($list as $vo){
                    $openid = $vo['openid'];
                    $templateId = "RmZLl7wTknwPgjVh5mTGlr46u65212qO7AfcTjdsZ3M";
                    $data2 = [
                        "thing16" => [
                            "value" => $class['cate_name'],
                        ],
                        "time4" => [
                            "value" => $date1,
                        ],
                        "thing27" => [
                            "value" => "拍卖专场即将开始",
                        ]
                    ];
                    $pagepath = "pages/goods_cate/goods_cate1?id=".$class['id'];
                    $res = $sendSubscribeMessageServices->sendMiniProgramTemplate($openid,$templateId,$data2,$pagepath);
                } 
                
                
                
                
                // $sms = new Sms();
                // $user = new UserDao();
                // $userList = $user->allUser();
                // foreach($userList as $vo){
                //     $phone = $vo['phone'];
                //     $templateId = '1020525020';
                //     $title = $class['cate_name'];
                //     $datetime = date('Y年m月d日 H时i分s秒', $class['startTime']);
                //     $count = $class['count'];
                //     $data = [
                //         $title,
                //         $datetime,
                //         $count,
                //     ];//短信模板变量内容
                            
                //     $res = $sms->send($phone, $templateId, $data);
                // }
                
                
            
                // $scribeMessageDao = new ScribeMessageDao();
                // $list = $scribeMessageDao->getList(['cate_id' => $class['id'],'status'=>0]);
                // $wechatUserDao = new WechatUserDao();
                // $subscriptionInformation = app()->make(SubscriptionInformation::class);
                // foreach($list as $vo){
                //     $openid = $wechatUserDao->getWechatOpenid($vo['uid'],'routine');
                //     $date1 = date('Y-m-d H:i:s', $class['startTime']);
                //     $data =[
                //         'thing14' => ['value' => $class['cate_name']],
                //         'date2' => ['value' => $date1],
                //         'number6' => ['value' => $class['count']],
                //     ];
                //     $res = $subscriptionInformation->send($openid,$data,$vo["template_id"]);
                //     $scribeMessageDao->editscribe(['id' => $vo['id']],['status'=>1]);
                // }
                
            }
            
            else if($class['status']==0 && $class['startTime'] - $time == 86400){
                $sendSubscribeMessageServices = app()->make(SendSubscribeMessageServices::class);
                $wechatUserDao = new WechatUserDao();
                $list = $wechatUserDao->getlists();
                $date1 = date('Y-m-d H:i:s', $class['startTime']);
                foreach($list as $vo){
                    $openid = $vo['openid'];
                    $templateId = "RmZLl7wTknwPgjVh5mTGlr46u65212qO7AfcTjdsZ3M";
                    $data2 = [
                        "thing16" => [
                            "value" => $class['cate_name'],
                        ],
                        "time4" => [
                            "value" => $date1,
                        ],
                        "thing27" => [
                            "value" => "拍卖专场预展中",
                        ]
                    ];
                    $pagepath = "pages/goods_cate/goods_cate1?id=".$class['id'];
                    $res = $sendSubscribeMessageServices->sendMiniProgramTemplate($openid,$templateId,$data2,$pagepath);
                } 
                 
            }
            
            else if($class['status']==0 && $class['startTime'] - $time == 172800){
                $sendSubscribeMessageServices = app()->make(SendSubscribeMessageServices::class);
                $wechatUserDao = new WechatUserDao();
                $list = $wechatUserDao->getlists();
                $date1 = date('Y-m-d H:i:s', $class['startTime']);
                foreach($list as $vo){
                    $openid = $vo['openid'];
                    $templateId = "RmZLl7wTknwPgjVh5mTGlr46u65212qO7AfcTjdsZ3M";
                    $data2 = [
                        "thing16" => [
                            "value" => $class['cate_name'],
                        ],
                        "time4" => [
                            "value" => $date1,
                        ],
                        "thing27" => [
                            "value" => "拍卖专场预展中",
                        ]
                    ];
                    $pagepath = "pages/goods_cate/goods_cate1?id=".$class['id'];
                    $res = $sendSubscribeMessageServices->sendMiniProgramTemplate($openid,$templateId,$data2,$pagepath);
                } 
                 
            }
            else if($class['status']==0 && $class['startTime'] - $time == 259200){
                $sendSubscribeMessageServices = app()->make(SendSubscribeMessageServices::class);
                $wechatUserDao = new WechatUserDao();
                $list = $wechatUserDao->getlists();
                $date1 = date('Y-m-d H:i:s', $class['startTime']);
                foreach($list as $vo){
                    $openid = $vo['openid'];
                    $templateId = "RmZLl7wTknwPgjVh5mTGlr46u65212qO7AfcTjdsZ3M";
                    $data2 = [
                        "thing16" => [
                            "value" => $class['cate_name'],
                        ],
                        "time4" => [
                            "value" => $date1,
                        ],
                        "thing27" => [
                            "value" => "拍卖专场预展中",
                        ]
                    ];
                    $pagepath = "pages/goods_cate/goods_cate1?id=".$class['id'];
                    $res = $sendSubscribeMessageServices->sendMiniProgramTemplate($openid,$templateId,$data2,$pagepath);
                } 
                 
            }else if($class['status']==0 && $class['startTime'] - $time == 600){
                $productList = CacheService::get($listKey);
                if(!$productList){
                    $productList = $storeProductDao->getList(['cate_id' => $class['id'], 'is_show' => 1],1, $class['count']);
                    CacheService::set($listKey, $productList,600,'productList');
                }
                $subscriptionInformation = app()->make(SubscriptionInformation::class);
                $list = $subscriptionInformation->getList(['product_id'=>$productList[0]['id']]);
                $sendsubscribeService = app()->make(SendsubscribeService::class);
                foreach($list as $vo){
                    $data = [
                        'touser' => $vo['openId'],
                        'template_id' => 'TOK4bhRLg3mya7fnZP8fUj5WoGm8wZeu4QKeVSuebg0',
                        'page' => "pages/goods_cate/goods_cate2?id=".$productList[0]['id']."&cate_id=".$class['id'], // 点击消息跳转的页面
                        'data' => [ // 模板中的字段，需与微信后台模板匹配
                            'thing2' => ['value' => $productList[0]['store_name']],
                            'thing7' => ['value' => '拍品即将开拍，点击参与竞拍'],
                        ]
                    ];
                    $vo['status'] = 1;
                    $sendsubscribeService->sendSubscribeMessage($data);
                    $subscriptionInformation->saveData($vo);
                }
                
                
                // $subscriptionInformation = app()->make(SubscriptionInformation::class);
                // $list = $subscriptionInformation->getList(['product_id'=> $productList[0]['id']]);
            
                // foreach($list as $vo){
                //     $wechatUserDao = new WechatUserDao();
                //     $unionid = $wechatUserDao->getuniOnid($vo['uid']);
                //     if($unionid){
                //         $sendSubscribeMessageServices = app()->make(SendSubscribeMessageServices::class);
                //         $openid = $wechatUserDao->getOpenid($unionid);
                //         if($openid){
                //             $templateId = "RmZLl7wTknwPgjVh5mTGlr46u65212qO7AfcTjdsZ3M";
                //             $data = [
                //                 "thing16" => [
                //                     "value" => $class['cate_name'],
                //                 ],
                //                 "time4" => [
                //                     "value" => date('Y-m-d H:i:s', $class['startTime']),
                //                 ],
                //                 "thing27" => [
                //                     "value" => "您关注的拍品".$productList[0]['store_name']."即将开拍",
                //                 ]
                //             ];
                //             $pagepath = "pages/goods_cate/goods_cate2?id=".$productList[0]['id']."&cate_id=".$class['id'];
                //             $res = $sendSubscribeMessageServices->sendMiniProgramTemplate($openid,$templateId,$data,$pagepath);        
                //         }
                        
                //     }
                   
                // }
            }
        }
        }
    }
    
    
    public function auction(){
        $lockKey = 'store_category_auction';
        if (!CacheService::setMutex($lockKey, 30)) {
            return;
        }
        try {
            $category = CacheService::get('category');
            $storeProductDao = new StoreProductDao();
            if (!$category) {
                $category = $this->buildTimedCategoryCacheList(2);
                CacheService::set('category', $category, 600, 'category');
            }
            if (!$category) {
                return;
            }
            $time = time();
            foreach ($category as $catIndex => $class) {
                if (!isset($class['id'])) {
                    continue;
                }
                $listKey = 'productList_' . (int)$class['id'];
                if ($class['status'] != 1 || $class['startTime'] + $class['delayCycle'] > $time) {
                    continue;
                }
                $productList = CacheService::get($listKey);
                if (!$productList) {
                    $productList = $storeProductDao->getList(['cate_id' => $class['id'], 'is_show' => 1], 1, $class['count']);
                    CacheService::set($listKey, $productList, 600, 'productList');
                }
                if (!$productList) {
                    continue;
                }
                $delayCycle = $class['delayCycle'] + 120;
                $number = $class['number'];
                $index = $number - 1;
                $flag = 0;
                $info = [];
                if ($index < $class['count'] && isset($productList[$index]['id'])) {
                    $name = 'product_' . $productList[$index]['id'];
                    $info = CacheService::get($name);
                    if (!$info) {
                        $info = $storeProductDao->getInfoone($productList[$index]['id']);
                    }
                    if ($info) {
                        $info['presale_end_time'] = $time;
                        CacheService::set($name, $info, 600, 'product');
                    }
                    $storeProductDao->automatic(['id' => $productList[$index]['id']], ['presale_end_time' => $time]);
                }
                if ($number < $class['count'] && isset($productList[$number]['id'])) {
                    $flag = 1;
                    $name1 = 'product_' . $productList[$number]['id'];
                    $info1 = CacheService::get($name1);
                    if (!$info1) {
                        $info1 = $storeProductDao->getInfoone($productList[$number]['id']);
                    }
                    if ($info1) {
                        $info1['presale_start_time'] = $time;
                        CacheService::set($name1, $info1, 600, 'product');
                    }
                    $category[$catIndex]['number'] = $class['number'] + 1;
                    $category[$catIndex]['delayCycle'] = $delayCycle;
                    CacheService::set('category', $category, 600, 'category');
                    $storeProductDao->automatic(['id' => $productList[$number]['id']], ['presale_start_time' => $time]);
                } else {
                    $category[$catIndex]['status'] = 2;
                    CacheService::set('category', $category, 600, 'category');
                    $this->dao->updateCate($class['id'], ['status' => 2]);
                    try {
                        $sessionServices = app()->make(StoreAuctionSessionServices::class);
                        $session = $sessionServices->findSessionByCateId((int)$class['id']);
                        if ($session) {
                            $sessionServices->maybeAutoUnbindSession((int)($session['id'] ?? 0));
                        }
                    } catch (\Throwable $e) {
                    }
                }
                if ($flag == 1) {
                    $this->dao->updateCate($class['id'], ['number' => $number + 1, 'delayCycle' => $delayCycle]);
                }
                CacheService::clear($listKey);
                if ($index < $class['count'] && $info && isset($info['uid']) && $info['uid'] != 0) {
                    $sms = new Sms();
                    $userDao = new UserDao();
                    $user = $userDao->get($info['uid']);
                    $user = json_decode($user, true);
                    if ($user && !empty($user['phone'])) {
                        $phone = $user['phone'];
                        $templateId = '1020524954';
                        $title = $info['store_name'];
                        $data1 = [
                            $title
                        ];
                        $sms->send($phone, $templateId, $data1);
                    }
                    $wechatUserDao = new WechatUserDao();
                    $unionid = $wechatUserDao->getuniOnid($info['uid']);
                    if ($unionid) {
                        $sendSubscribeMessageServices = app()->make(SendSubscribeMessageServices::class);
                        $openid = $wechatUserDao->getOpenid($unionid);
                        if ($openid) {
                            $templateId = "HltVEvU89cw2E-dzw7a5ZNv2Z2DxbF6snVsNSjSq1f0";
                            $data = [
                                "thing8" => [
                                    "value" => $info['store_name'],
                                ],
                                "amount2" => [
                                    "value" => $info['price'],
                                ]
                            ];
                            $pagepath = "pages/goods_cate/goods_cate2?id=" . $info['id'] . "&cate_id=" . $info['cate_id'];
                            $sendSubscribeMessageServices->sendMiniProgramTemplate($openid, $templateId, $data, $pagepath);
                        }
                    }
                }
                if ($index < $class['count'] && ($index + 1) < $class['count']) {
                    $productList = CacheService::get($listKey);
                    if (!$productList) {
                        $productList = $storeProductDao->getList(['cate_id' => $class['id'], 'is_show' => 1], 1, $class['count']);
                        CacheService::set($listKey, $productList, 600, 'productList');
                    }
                    if (isset($productList[$number + 1]['id'], $productList[$number + 1]['store_name'])) {
                        $subscriptionInformation = app()->make(SubscriptionInformation::class);
                        $list = $subscriptionInformation->getList(['product_id' => $productList[$number + 1]['id']]);
                        $sendsubscribeService = app()->make(SendsubscribeService::class);
                        foreach ($list as $vo) {
                            $data1 = [
                                'touser' => $vo['openId'],
                                'template_id' => 'TOK4bhRLg3mya7fnZP8fUj5WoGm8wZeu4QKeVSuebg0',
                                'page' => "pages/goods_cate/goods_cate2?id=" . $productList[$number + 1]['id'] . "&cate_id=" . $class['id'],
                                'data' => [
                                    'thing2' => ['value' => $productList[$number + 1]['store_name']],
                                    'thing7' => ['value' => '拍品即将开拍，点击参与竞拍'],
                                ]
                            ];
                            $sendsubscribeService->sendSubscribeMessage($data1);
                        }
                    }
                }
            }
        } finally {
            CacheService::delMutex($lockKey);
        }
    }
     
     
    /**
     * 用户进入拍品页时围观 +3（写库并清专场列表缓存）
     */
    public function getView($id)
    {
        $id = (int)$id;
        if ($id <= 0) {
            return;
        }
        $info = $this->dao->get($id);
        if (!$info) {
            return;
        }
        $view = (int)($info['view'] ?? 0) + 3;
        $this->dao->updateCate($id, ['view' => $view]);
        CacheService::clear('category');
        CacheService::clear('category1');
    }

    /**
     * 后台回显专场围观人数
     */
    public function getViewConfig(int $id): array
    {
        if ($id <= 0) {
            throw new AdminException('参数错误');
        }
        $info = $this->dao->get($id);
        if (!$info) {
            throw new AdminException('专场不存在');
        }
        return [
            'id' => $id,
            'cate_name' => (string)($info['cate_name'] ?? ''),
            'view' => (int)($info['view'] ?? 0),
        ];
    }

    /**
     * 后台配置专场围观人数（用户进入专场时仍会 +3 累加）
     */
    public function setViewCount(int $id, int $view): void
    {
        if ($id <= 0) {
            throw new AdminException('参数错误');
        }
        if ($view < 0) {
            throw new AdminException('围观人数不能小于0');
        }
        $info = $this->dao->get($id);
        if (!$info) {
            throw new AdminException('专场不存在');
        }
        if ((int)($info['view'] ?? 0) === $view) {
            return;
        }
        $res = $this->dao->update($id, ['view' => $view]);
        if ($res === false) {
            throw new AdminException(100007);
        }
        CacheService::clear('category');
        CacheService::clear('category1');
    }
    
    
    //  前端限时拍专场列表（纯数组，供 WebSocket 等内部调用）
    public function getcatlist(){
        return $this->getTimedCategoryList(StoreAuctionSessionServices::TIMED_DISPLAY_MAX);
    }

    /**
     * 限时拍列表 + 展示规则（小程序「最多两场」）
     */
    public function getTimedCategoryListResponse(int $limit = 2): array
    {
        $list = $this->getTimedCategoryList($limit);
        $max = StoreAuctionSessionServices::TIMED_DISPLAY_MAX;
        $sessionServices = app()->make(StoreAuctionSessionServices::class);
        $isSessionGroup = $sessionServices->isSameSessionTimedList($list);
        $tipCount = $isSessionGroup ? count($list) : 0;
        return [
            'list' => $list,
            'timed_meta' => [
                'max_display' => $max,
                'display_count' => count($list),
                'is_session_group' => $isSessionGroup,
                'tip' => StoreAuctionSessionServices::buildTimedDisplayTip($max, $tipCount),
            ],
        ];
    }

    /**
     * 限时拍专场列表（带 count，走 category 缓存）
     */
    public function getTimedCategoryList(int $limit = 2): array
    {
        $category = CacheService::get('category');
        $category = $this->normalizeTimedCategoryCache($category);
        if (!$category) {
            $category = $this->buildTimedCategoryCacheList($limit);
            CacheService::set('category', $category, 600, 'category');
        }
        return $category ?: [];
    }

    /**
     * 纠正误写入的包装结构，保证缓存内为专场数组
     */
    protected function normalizeTimedCategoryCache($category): array
    {
        if (!$category) {
            return [];
        }
        if (is_array($category) && isset($category['list']) && is_array($category['list'])) {
            return $category['list'];
        }
        if (is_array($category) && isset($category['timed_meta'])) {
            return [];
        }
        return is_array($category) ? $category : [];
    }

    protected function buildTimedCategoryCacheList(int $limit = 2): array
    {
        $sessionServices = app()->make(StoreAuctionSessionServices::class);
        $category = $sessionServices->getDisplayTimedCategories($limit);
        $storeProductDao = new StoreProductDao();
        foreach ($category as $k => $vo) {
            $category[$k]['count'] = $storeProductDao->getCount(['cid' => $vo['id'], 'is_show' => 1]);
        }
        return $category;
    }
    
    //前端同步拍列表
    public function getSynchronization()
    {
        $category = CacheService::get('category1');
        if(!$category){
            $limit= 1;
            $category = $this->dao->getSynchronization($limit);
            $storeProductDao = new StoreProductDao();
            foreach($category as $k => $vo){
               
                $category[$k]['count'] = $storeProductDao->getCount(['cid' => $vo['id'],'is_show' => 1]);
            }
            CacheService::set('category1',$category,600,'category1');
           
        
        }
        return $category;
    }
    
    //产品分类详情
    public function getDetails($id){
       // CacheService::clearAll();
        $storeProductDao = new StoreProductDao();

        // 以传入分类ID为准，避免缓存 category[0] 串数据
        $categoryInfo = $this->dao->get((int)$id);
        $categoryInfo = $categoryInfo ? $categoryInfo->toArray() : [];
        $categoryInfo['count'] = $storeProductDao->getCount(['cid' => (int)$id, 'is_show' => 1]);

        $productListCacheKey = 'productList_' . (int)$id;
        $productList = CacheService::get($productListCacheKey);

        if(!$productList){
            $productList = $storeProductDao->getList(['cate_id' => (int)$id, 'is_show' => 1], 1, $categoryInfo['count']);
            $storeBiddingDao = new StoreBiddingDao();
            foreach($productList as $k => $vo){
                $productList[$k]['count'] = $storeBiddingDao->getCount(['product_id' => $vo['id']]);
            }
            CacheService::set($productListCacheKey, $productList, 600, 'productList');
        }

        $info['category'] = $categoryInfo;
        // 兼容前端直接读取 data.count
        $info['count'] = $categoryInfo['count'];
        $info['product'] = $productList;
        $info['article'] = null;
        $articleId = (int)($categoryInfo['article_id'] ?? 0);
        if ($articleId > 0) {
            try {
                $info['article'] = $this->auctionDetails($articleId);
            } catch (\Throwable $e) {
                $info['article'] = null;
            }
        }
        $info['timed_deposit'] = [
            'deposit_amount' => StoreTimedDepositServices::getDepositAmount(),
        ];
        $sessionServices = app()->make(StoreAuctionSessionServices::class);
        $parallelCates = $sessionServices->getParallelTimedCatesForCate((int)$id);
        $displayCount = count($parallelCates);
        $maxDisplay = StoreAuctionSessionServices::TIMED_DISPLAY_MAX;
        $info['parallel_cates'] = $parallelCates;
        $info['timed_meta'] = [
            'max_display' => $maxDisplay,
            'display_count' => $displayCount,
            'is_session_group' => $displayCount > 1,
            'tip' => StoreAuctionSessionServices::buildTimedDisplayTip($maxDisplay, $displayCount),
        ];
        //$info['time'] = time();
       // $server = app('swoole.server');
       // $websocket->emitToRoom("cate1","123",$info);
       
        return $info;
    }
    
    
    public function getSynchronizationlist($id){
       // CacheService::clearAll();
       $category = CacheService::get('category1');
       $storeProductDao = new StoreProductDao();
        if(!$category){
            $limit= 1;
            $category = $this->dao->getSynchronization($limit);
           // $storeProductDao = new StoreProductDao();
            foreach($category as $k => $vo){
               
                $category[$k]['count'] = $storeProductDao->getCount(['cid' => $vo['id'],'is_show' => 1]);
            }
            CacheService::set('category1',$category,600,'category1');
           
        
        }
        
        $productList = CacheService::get('productList1');
        $info['category'] = $category[0];
        
        if(!$productList){
            $productList = $storeProductDao->getList(['cate_id' => $id, 'is_show' => 1],1, $info['category']['count']);
            $storeBiddingDao = new StoreBiddingDao();
            foreach($productList as $k => $vo){
                $productList[$k]['count'] = $storeBiddingDao->getCount(['product_id' => $vo['id']]);
            }
            CacheService::set('productList1', $productList,600,'productList1');
        }
     
        $info['product'] = $productList;
        //$info['time'] = time();
       // $server = app('swoole.server');
       // $websocket->emitToRoom("cate1","123",$info);
       
        return $info;
    }
    
    
    public function Details($id){
        $category = $this->dao->get($id);
        return $category;
    }
    
    
    /**
     * 拍品详情：描述等走缓存，拍卖态字段（开拍/结拍/现价等）始终与数据库对齐
     */
    public function getProductAuctionSnapshot(int $productId): array
    {
        if ($productId <= 0) {
            return [];
        }
        $storeProductDao = new StoreProductDao();
        $fresh = $storeProductDao->getInfoone($productId);
        if (!$fresh || empty($fresh['id'])) {
            return [];
        }
        $name = 'product_' . $productId;
        $cached = CacheService::get($name);
        $info = is_array($cached) ? array_merge($cached, $fresh) : $fresh;
        $liveFields = [
            'presale_start_time', 'presale_end_time', 'price', 'uid', 'version',
            'pattern', 'cost', 'cate_id', 'ot_price', 'is_show',
        ];
        foreach ($liveFields as $field) {
            if (array_key_exists($field, $fresh)) {
                $info[$field] = $fresh[$field];
            }
        }
        CacheService::set($name, $info, 600, 'product');
        return $info;
    }

    public function productDetails(int $id){
        $info = $this->getProductAuctionSnapshot($id);
        if (!$info) {
            return [];
        }
        
        $description = 'description'.$id;
        $info['description'] = CacheService::get($description);
        if(!$info['description']){
            
            $storeDescriptionServices = app()->make(StoreDescriptionServices::class);
 
            $info['description'] = $storeDescriptionServices->getDescription(['product_id' => $id, 'type' => 0]);
            CacheService::set($description, $info['description'],600,'product');
        }
       
      // CacheService::clear('product');
            
        return $info;
    }
    
    public function auctionDetails($id)
    {
        $id = (int)$id;
        if ($id <= 0) {
            throw new \crmeb\exceptions\ApiException('暂未配置拍卖详情', [], 400);
        }
        $name = 'article' . $id;
        $article = CacheService::get($name);
        if (!$article) {
            $ArticleDao = new ArticleDao();
            $article = $ArticleDao->read($id);
            CacheService::set($name, $article, 600, 'article');
        }
        return $article;
    }
    
    public function getBiddingList($id){
        $biddingName = "bidding_".$id;
        $bidding = CacheService::get($biddingName);
        if(!$bidding){
            $bidding = [];
            $storeBiddingDao = new StoreBiddingDao();
            $count = $storeBiddingDao->getCount(['product_id' => $id]);
            $bidding = $storeBiddingDao->getList(['product_id' => $id],1,$count);
            
           
            CacheService::set($biddingName,$bidding,600,'bidding');
        }
        //CacheService::clear('bidding');
        
        //CacheService::clear($biddingName);
        return $bidding;
    }
    
    public function sendSubscribeMessage(){
        $category = CacheService::get('category1');
        $storeProductDao = new StoreProductDao();
        $sendSubscribeMessageServices = app()->make(SendSubscribeMessageServices::class);
        $wechatUserDao = new WechatUserDao();
        if(!$category){
            $limit= 1;
            $category = $this->dao->getSynchronization($limit);
           // $storeProductDao = new StoreProductDao();
            foreach($category as $k => $vo){
               
                $category[$k]['count'] = $storeProductDao->getCount(['cid' => $vo['id'],'is_show' => 1]);
            }
            CacheService::set('category1',$category,600,'category1');
           
        }
        $class = $category[0];
        $time = time();
        
        if($class['startTime'] - $time == 172800){
            $list = $wechatUserDao->getlists();
            $date1 = date('Y-m-d H:i:s', $class['startTime']);
            foreach($list as $vo){
                $openid = $vo['openid'];
                $templateId = "RmZLl7wTknwPgjVh5mTGlr46u65212qO7AfcTjdsZ3M";
                $data = [
                    "thing16" => [
                        "value" => $class['cate_name'],
                    ],
                    "time4" => [
                        "value" => $date1,
                    ],
                    "thing27" => [
                        "value" => "拍卖专场预展开始",
                    ]
                ];
                $pagepath = "pages/goods_cate/synchronouslist?id=".$class['id'];
                $res = $sendSubscribeMessageServices->sendMiniProgramTemplate($openid,$templateId,$data,$pagepath);
            }
        }
        else if($class['startTime'] - $time == 86400){
            $list = $wechatUserDao->getlists();
            $date1 = date('Y-m-d H:i:s', $class['startTime']);
            foreach($list as $vo){
                $openid = $vo['openid'];
                $templateId = "RmZLl7wTknwPgjVh5mTGlr46u65212qO7AfcTjdsZ3M";
                $data = [
                    "thing16" => [
                        "value" => $class['cate_name'],
                    ],
                    "time4" => [
                        "value" => $date1,
                    ],
                    "thing27" => [
                        "value" => "拍卖专场预展中",
                    ]
                ];
                $pagepath = "pages/goods_cate/synchronouslist?id=".$class['id'];
                $res = $sendSubscribeMessageServices->sendMiniProgramTemplate($openid,$templateId,$data,$pagepath);
            }
            
            
            $sms = new Sms();
            $user = new UserDao();
            $userList = $user->allUser();
            $date1 = date('Y-m-d H:i:s', $class['startTime']);
            foreach($userList as $vo){
                $phone = $vo['phone'];
                $templateId = '1020525020';
                $title = $class['cate_name'];
                $datetime = date('Y年m月d日 H时i分s秒', $class['startTime']);
                $count = $class['count'];
                $data = [
                    $title,
                    $datetime,
                    $count,
                ];//短信模板变量内容
                            
                $res = $sms->send($phone, $templateId, $data);
            }
        }
        else if($class['startTime'] - $time == 7200){
            $list = $wechatUserDao->getlists();
            $date1 = date('Y-m-d H:i:s', $class['startTime']);
            foreach($list as $vo){
                $openid = $vo['openid'];
                $templateId = "RmZLl7wTknwPgjVh5mTGlr46u65212qO7AfcTjdsZ3M";
                $data = [
                    "thing16" => [
                        "value" => $class['cate_name'],
                    ],
                    "time4" => [
                        "value" => $date1,
                    ],
                    "thing27" => [
                        "value" => "拍卖专场预展中",
                    ]
                ];
                $pagepath = "pages/goods_cate/synchronouslist?id=".$class['id'];
                $res = $sendSubscribeMessageServices->sendMiniProgramTemplate($openid,$templateId,$data,$pagepath);
            }
        }
        else if($class['startTime'] - $time == 3600){
            $list = $wechatUserDao->getlists();
            $date1 = date('Y-m-d H:i:s', $class['startTime']);
            foreach($list as $vo){
                $openid = $vo['openid'];
                $templateId = "RmZLl7wTknwPgjVh5mTGlr46u65212qO7AfcTjdsZ3M";
                $data = [
                    "thing16" => [
                        "value" => $class['cate_name'],
                    ],
                    "time4" => [
                        "value" => $date1,
                    ],
                    "thing27" => [
                        "value" => "拍卖专场预展中",
                    ]
                ];
                $pagepath = "pages/goods_cate/synchronouslist?id=".$class['id'];
                $res = $sendSubscribeMessageServices->sendMiniProgramTemplate($openid,$templateId,$data,$pagepath);
            }
        }
        else if($class['startTime'] - $time == 600){
            $list = $wechatUserDao->getlists();
            $date1 = date('Y-m-d H:i:s', $class['startTime']);
            foreach($list as $vo){
                $openid = $vo['openid'];
                $templateId = "RmZLl7wTknwPgjVh5mTGlr46u65212qO7AfcTjdsZ3M";
                $data = [
                    "thing16" => [
                        "value" => $class['cate_name'],
                    ],
                    "time4" => [
                        "value" => $date1,
                    ],
                    "thing27" => [
                        "value" => "拍卖专场即将开始",
                    ]
                ];
                $pagepath = "pages/goods_cate/synchronouslist?id=".$class['id'];
                $res = $sendSubscribeMessageServices->sendMiniProgramTemplate($openid,$templateId,$data,$pagepath);
            }
            
            $sms = new Sms();
            $user = new UserDao();
            $userList = $user->allUser();
            $date1 = date('Y-m-d H:i:s', $class['startTime']);
            foreach($userList as $vo){
                $phone = $vo['phone'];
                $templateId = '1020525020';
                $title = $class['cate_name'];
                $datetime = date('Y年m月d日 H时i分s秒', $class['startTime']);
                $count = $class['count'];
                $data = [
                    $title,
                    $datetime,
                    $count,
                ];//短信模板变量内容
                            
                $res = $sms->send($phone, $templateId, $data);
            }
            
            
            
            $scribeMessageDao = new ScribeMessageDao();
            $list = $scribeMessageDao->getList(['cate_id' => $class['id'],'status'=>0]);
            //$wechatUserDao = new WechatUserDao();
            $subscriptionInformation = app()->make(SubscriptionInformation::class);
            foreach($list as $vo){
                $openid = $wechatUserDao->getWechatOpenid($vo['uid'],'routine');
                $date1 = date('Y-m-d H:i:s', $class['startTime']);
                $data =[
                    'thing14' => ['value' => $class['cate_name']],
                    'date2' => ['value' => $date1],
                    'number6' => ['value' => $class['count']],
                ];
                $res = $subscriptionInformation->send($openid,$data,$vo["template_id"]);
                $scribeMessageDao->editscribe(['id' => $vo['id']],['status'=>1]);
            }
        }
       
        //return $res;
    }
    
}
