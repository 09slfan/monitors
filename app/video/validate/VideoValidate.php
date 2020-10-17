<?php
// +----------------------------------------------------------------------
// | zhiliang weight
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019.
// +----------------------------------------------------------------------
// | Author: 小艾.pino
// +----------------------------------------------------------------------
namespace app\video\validate;

use app\admin\model\RouteModel;
use think\Validate;

class VideoValidate extends Validate
{
    protected $rule = [
        'name'    => 'require|unique:video,name',
        'stream'  => 'require|unique:video,stream',
    ];
    protected $message = [
        'name.require'   => '名称不能为空',
        'name.unique'    => '该名称已存在',
        'stream.require' => '设备号不能为空',
        'stream.unique'  => '该设备号已存在',
    ];

    protected $scene = [
//        'add'  => ['user_login,user_pass,user_email'],
//        'edit' => ['user_login,user_email'],
    ];

}