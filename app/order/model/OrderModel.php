<?php

namespace app\order\model;

use app\admin\model\RouteModel;
use think\Model;
use think\Db;

/**
 * 订单管理 model
 */
class OrderModel extends Model {

    // protected $type = [
    //     'more' => 'array',
    // ];

    public function adminEdit($data) {
        $this->allowField(true)->isUpdate(true)->data($data, true)->save();
        return $this;
    }

}