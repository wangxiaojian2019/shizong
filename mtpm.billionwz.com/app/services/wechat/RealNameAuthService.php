<?php

namespace app\services\wechat;
use app\services\BaseServices;

class RealNameAuthService extends BaseServices
{
    

    public function sign($key, $msg) {
        return hash_hmac("sha256", $msg, $key, true);
    }


}
