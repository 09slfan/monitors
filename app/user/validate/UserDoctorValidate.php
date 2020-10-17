<?php
// +----------------------------------------------------------------------
// | zhiliang weight
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019.
// +----------------------------------------------------------------------
// | Author: 小艾.pino
// +----------------------------------------------------------------------
namespace app\user\validate;

use think\Validate;

class UserDoctorValidate extends Validate
{
    protected $rule = [
        'hospital' => 'require',
    ];
    protected $message = [
        'hospital.require' => '医院信息不能为空',
    ];

    protected $scene = [
        'add'  => ['hospital'],
        'edit' => ['hospital'],
    ];
}