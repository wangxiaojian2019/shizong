<?php

namespace app\model\product\product;

use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;

class StoreTimedDeposit extends BaseModel
{
    use ModelTrait;

    protected $pk = 'id';
    protected $name = 'store_timed_deposit';

    /** update_time 为 Unix 时间戳（int），勿按 datetime 字符串解析 */
    protected $autoWriteTimestamp = 'int';
    protected $updateTime = 'update_time';
    protected $createTime = false;

    /** 默认保证金金额（元），实际以后台配置 timed_auction_deposit_amount 为准 */
    public const DEFAULT_DEPOSIT_AMOUNT = 1;
}
