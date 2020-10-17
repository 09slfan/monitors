<?php
// +----------------------------------------------------------------------
// | zhiliang weight
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019.
// +----------------------------------------------------------------------
// | Author: 小艾.pino
// +----------------------------------------------------------------------
namespace app\organization\validate;

use think\Validate;

class CanteenValidate extends Validate
{
    protected $rule = [
        'school_id' => 'require',
        'name'  => 'require|unique:canteen,name',
        //'mobile' => 'mobile',
    ];
    protected $message = [
        'school_id.require' => '请指定学校！',
        'name.require' => '名称不能为空！',
        'name.unique'  => '该食堂名称已存在',
        //'mobile.mobile' => '手机号码格式不正确',
    ];

    protected $scene = [
//        'add'  => ['user_login,user_pass,user_email'],
//        'edit' => ['user_login,user_email'],
    ];
}