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

class UserArticlesValidate extends Validate
{
    protected $rule = [
        'post_title' => 'require',
    ];
    protected $message = [
        'post_title.require' => '文章标题不能为空',
    ];

    protected $scene = [
        'add'  => ['post_title'],
        'edit' => ['post_title'],
    ];
}