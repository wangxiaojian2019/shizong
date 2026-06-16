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


use app\services\product\product\StoreNumberPlateServices;
use think\facade\App;
use think\Request;
use app\adminapi\controller\AuthController;


/**
 * Class CategoryController
 * @package app\api\controller\v1\store
 */
class StoreNumberPlateController extends AuthController
{
    protected $services;

    public function __construct(App $app, StoreNumberPlateServices $services)
    {
        parent::__construct($app);
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
            ['cate_id', 0],
            ['paystatus', -1],
        ]);
        $filterCateId = (int)$where['cate_id'];
        $paystatus = $this->parseAdminPaystatus($where['paystatus'] ?? -1);
        $list = $this->services->getAdminList($filterCateId, $paystatus);
        return app('json')->success($list);
    }

    /** paystatus：-1 全部，0/1/2 为具体状态；空字符串不误判为 0 */
    protected function parseAdminPaystatus($value): int
    {
        if ($value === '' || $value === null || $value === false) {
            return -1;
        }
        $paystatus = (int)$value;
        return ($paystatus >= 0 && $paystatus <= 2) ? $paystatus : -1;
    }

    public function editPlate(Request $request){
        $data= $request->getMore([
            ['cate_id', 0],
            ['user_id', 0],
            ['remaining',0],
            ['state', 0],
        ]);
        $userId = (int)$data['user_id'];
        $cateId = (int)$data['cate_id'];
        if ($userId <= 0 || $cateId <= 0) {
            return app('json')->fail('参数错误');
        }
        $state = ($data['state'] === true || $data['state'] === 1 || $data['state'] === '1') ? 1 : 0;
        $remaining = is_numeric($data['remaining']) ? (int)$data['remaining'] : 0;
        if ($remaining < 0) {
            $remaining = 0;
        }
        try {
            $ok = $this->services->updateAdminPlateMeta($userId, $cateId, $state, $remaining);
            return $ok ? app('json')->success('保存成功') : app('json')->fail('保存失败');
        } catch (\RuntimeException $e) {
            return app('json')->fail($e->getMessage());
        } catch (\Throwable $e) {
            return app('json')->fail('保存失败，请稍后重试');
        }
    }
    
 
}
