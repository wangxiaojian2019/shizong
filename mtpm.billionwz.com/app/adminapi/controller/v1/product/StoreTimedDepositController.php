<?php



namespace app\adminapi\controller\v1\product;



use app\adminapi\controller\AuthController;

use app\services\product\product\StoreTimedDepositServices;

use think\Request;



/**

 * 限时拍用户保证金（退款审核、金额配置）

 */

class StoreTimedDepositController extends AuthController

{

    public function getConfig()

    {

        return app('json')->success([

            'deposit_amount' => StoreTimedDepositServices::getDepositAmount(),

        ]);

    }



    public function saveConfig(Request $request)

    {

        $data = $request->postMore([

            ['deposit_amount', 0],

        ]);

        $amount = (float)$data['deposit_amount'];

        if ($amount <= 0) {

            return app('json')->fail('保证金金额须大于0');

        }

        $services = app()->make(StoreTimedDepositServices::class);

        try {

            $services->setDepositAmount($amount);

        } catch (\InvalidArgumentException $e) {

            return app('json')->fail($e->getMessage());

        }

        return app('json')->success('保存成功');

    }



    public function pendingList(Request $request)

    {

        $data = $request->getMore([

            ['page', 1],

            ['limit', 20],

        ]);

        $services = app()->make(StoreTimedDepositServices::class);

        return app('json')->success($services->listPendingRefund((int)$data['page'], (int)$data['limit']));

    }



    /**

     * 审核通过并退款（微信原路退限时拍保证金订单）

     */

    public function approveRefund(Request $request)

    {

        $data = $request->postMore([

            ['uid', 0],

        ]);

        $uid = (int)$data['uid'];

        if ($uid <= 0) {

            return app('json')->fail('请指定用户');

        }

        $depositServices = app()->make(StoreTimedDepositServices::class);

        $status = $depositServices->getStatus($uid);

        if ((int)($status['application'] ?? 0) !== 1) {

            return app('json')->fail('该用户无待审核的退款申请');

        }

        if (!$status['deposit_sufficient']) {

            return app('json')->fail('保证金未处于可退状态');

        }

        $cateId = (int)$depositServices->getRefundContext($uid)['refundable_cate_id'];
        $result = $depositServices->processWechatRefund($uid, $cateId);

        if (!$result['ok']) {

            return app('json')->fail($result['msg']);

        }

        return app('json')->success($result['msg']);

    }



    /**

     * 违约扣减保证金

     */

    public function deduct(Request $request)

    {

        $data = $request->postMore([

            ['uid', 0],

            ['amount', 0],

        ]);

        $services = app()->make(StoreTimedDepositServices::class);

        $res = $services->deductForBreach((int)$data['uid'], (float)$data['amount']);

        if (!$res['ok']) {

            return app('json')->fail($res['msg']);

        }

        return app('json')->success($res);

    }

}

