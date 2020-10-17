<?php
// +----------------------------------------------------------------------
// | zhiliang weight
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019.
// +----------------------------------------------------------------------
// | Author: 小艾.pino
// +----------------------------------------------------------------------
namespace plugins\wxapp\validate;

use think\Validate;

class AdminWxappValidate extends Validate
{
    protected $rule = [
        // 用|分开
        'name'       => 'require',
        'app_id'     => 'require',
        'app_secret' => 'require'
    ];

    protected $message = [
        'name.require'       => "小程序名称不能为空！",
        'app_id.require'     => "小程序App Id不能为空!",
        'app_secret.require' => '小程序App Secret不能为空!'
    ];


}