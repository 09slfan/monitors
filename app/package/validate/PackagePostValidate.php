<?php
// +----------------------------------------------------------------------
// | zhiliang weight
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019.
// +----------------------------------------------------------------------
// | Author: 小艾.pino
// +----------------------------------------------------------------------
namespace app\package\validate;

use think\Validate;

class PackagePostValidate extends Validate
{
    protected $rule = [
        'categories' => 'require',
        'post_title' => 'require',
        'money' => 'require',
        'period' => 'require',
    ];
    protected $message = [
        'categories.require' => '请指定分类！',
        'post_title.require' => '标题不能为空！',
        'money.require' => '请输入套餐金额',
        'period.require' => '请输入套餐时间',
    ];

    protected $scene = [
//        'add'  => ['user_login,user_pass,user_email'],
//        'edit' => ['user_login,user_email'],
    ];
}