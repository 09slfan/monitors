<?php
// +----------------------------------------------------------------------
// | zhiliang weight
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019.
// +----------------------------------------------------------------------
// | Author: 小艾.pino
// +----------------------------------------------------------------------
namespace app\admin\validate;

use think\Validate;

class UserValidate extends Validate
{
    protected $rule = [
        'user_login' => 'require',
        'user_pass'  => 'require',
        'user_email' => 'require|email|unique:user,user_email',
    ];
    protected $message = [
        'user_login.require' => '用户不能为空',
        'user_pass.require'  => '密码不能为空',
        'user_email.require' => '邮箱不能为空',
        'user_email.email'   => '邮箱不正确',
        'user_email.unique'  => '邮箱已经存在',
    ];

    protected $scene = [
        'add'  => ['user_login', 'user_pass', 'user_email'],
        'edit' => ['user_login', 'user_email'],
    ];
}