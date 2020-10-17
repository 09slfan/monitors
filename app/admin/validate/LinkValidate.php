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

class LinkValidate extends Validate
{
    protected $rule = [
        'name' => 'require',
        'url'  => 'require',
    ];

    protected $message = [
        'name.require' => '名称不能为空',
        'url.require'  => '链接地址不能为空',
    ];

}