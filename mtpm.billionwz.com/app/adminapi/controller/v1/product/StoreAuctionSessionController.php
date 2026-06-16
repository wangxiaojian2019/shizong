<?php

namespace app\adminapi\controller\v1\product;

use app\adminapi\controller\AuthController;
use app\services\product\product\StoreAuctionSessionServices;
use think\Request;

/**
 * 限时拍拍卖周期（后台）
 */
class StoreAuctionSessionController extends AuthController
{
    protected $services;

    public function __construct(StoreAuctionSessionServices $services)
    {
        $this->services = $services;
    }

    public function index()
    {
        return app('json')->success($this->services->listSessionsWithDetail());
    }

    public function timedCategories(Request $request)
    {
        $sessionId = (int)$request->get('session_id', 0);
        return app('json')->success($this->services->listTimedCategoryOptions($sessionId));
    }

    public function create(Request $request)
    {
        $data = $request->postMore([
            ['name', ''],
        ]);
        if ($data['name'] === '') {
            return app('json')->fail('请输入周期名称');
        }
        $id = $this->services->createSession((string)$data['name']);
        return app('json')->success('创建成功', ['id' => $id]);
    }

    public function bind(Request $request)
    {
        $data = $request->postMore([
            ['session_id', 0],
            ['cate_a_id', 0],
            ['cate_b_id', 0],
            ['cate_ids', []],
        ]);
        $cateA = (int)$data['cate_a_id'];
        $cateB = (int)$data['cate_b_id'];
        $cateIds = $data['cate_ids'];
        if (!is_array($cateIds)) {
            $cateIds = [];
        }
        if (($cateA <= 0 || $cateB <= 0) && count($cateIds) === 2) {
            $cateA = (int)$cateIds[0];
            $cateB = (int)$cateIds[1];
        }
        $res = $this->services->bindTimedCategories((int)$data['session_id'], $cateA, $cateB);
        if (!$res['ok']) {
            return app('json')->fail($res['msg']);
        }
        return app('json')->success($res['msg']);
    }

    public function update(Request $request, $id)
    {
        $data = $request->postMore([
            ['name', ''],
        ]);
        $res = $this->services->updateSession((int)$id, (string)$data['name']);
        if (!$res['ok']) {
            return app('json')->fail($res['msg']);
        }
        return app('json')->success($res['msg']);
    }

    public function delete($id)
    {
        $res = $this->services->deleteSession((int)$id);
        if (!$res['ok']) {
            return app('json')->fail($res['msg']);
        }
        return app('json')->success($res['msg']);
    }
}
