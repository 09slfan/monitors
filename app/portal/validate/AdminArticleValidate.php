<?php
// +----------------------------------------------------------------------
// | zhiliang weight
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019.
// +----------------------------------------------------------------------
// | Author: 小艾.pino
// +----------------------------------------------------------------------
namespace app\portal\validate;

use think\Validate;

class AdminArticleValidate extends Validate
{
    protected $rule = [
        'categories' => 'require',
        'post_title' => 'require',
    ];
    protected $message = [
        'categories.require' => '请指定文章分类！',
        'post_title.require' => '文章标题不能为空！',
    ];

    protected $scene = [
//        'add'  => ['user_login,user_pass,user_email'],
//        'edit' => ['user_login,user_email'],
    ];
}