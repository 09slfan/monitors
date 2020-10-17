<?php
// +----------------------------------------------------------------------
// | zhiliang weight
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019.
// +----------------------------------------------------------------------
// | Author: 小艾.pino
// +----------------------------------------------------------------------
namespace app\organization\validate;

use app\admin\model\RouteModel;
use think\Validate;

class SchoolValidate extends Validate
{
    protected $rule = [
        'name'  => 'require|unique:school,name',
        'school_type'  => 'require',
        'province'  => 'require',
        'city'  => 'require',
        'district'  => 'require',
        //'mobile' => 'mobile',
    ];
    protected $message = [
        'name.require' => '名称不能为空',
        'name.unique'  => '该学校名称已存在',
        'school_type.require' => '请选择学校类型',
        'province.require' => '所在省份不能为空',
        'city.require' => '所在城市不能为空',
        'district.require' => '所在区县不能为空',
        //'mobile.mobile' => '手机号码格式不正确',
    ];

    protected $scene = [
//        'add'  => ['user_login,user_pass,user_email'],
//        'edit' => ['user_login,user_email'],
    ];

}