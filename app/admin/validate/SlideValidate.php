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

class SlideValidate extends Validate
{
    protected $rule = [
        'name' => 'require',
    ];

    protected $message = [
        'name.require' => '分类名称必须',
    ];

    protected $scene = [
        'add'  => ['name'],
        'edit' => ['name'],
    ];
}