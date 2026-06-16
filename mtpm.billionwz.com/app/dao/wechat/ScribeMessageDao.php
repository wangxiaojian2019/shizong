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
declare (strict_types=1);

namespace app\dao\wechat;

use think\model;
use app\dao\BaseDao;
use app\model\wechat\ScribeMessage;

class ScribeMessageDao extends BaseDao
{
    protected function setModel(): string
    {
        return ScribeMessage::class;
    }
    
    public function insertData1(array $data){
        
        $this->getModel()->insert($data);
    }
    
    public function getList(array $where){
        return $this->search($where)->order('id desc')->select()->toArray();
    }
    
    public function getScribeMessage(array $where){
        if($this->search($where)->find()){
            return "true";
        }else{
            return "false";
        }
    }
    
     public function editscribe(array $where = [], array $data = []){
        return $this->search($where)->update($data); 
    }
}