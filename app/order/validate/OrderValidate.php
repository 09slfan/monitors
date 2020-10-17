<?php
// +----------------------------------------------------------------------
// | zhiliang weight
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019.
// +----------------------------------------------------------------------
// | Author: 小艾.pino
// +----------------------------------------------------------------------
namespace app\order\validate;

use think\Validate;

class OrderValidate extends Validate {
    protected $rule = [
        'order_sn' => 'unique:order,order_sn',
    ];
    protected $message = [
        'order_sn.unique' => '订单号不能重复！',
    ];
}