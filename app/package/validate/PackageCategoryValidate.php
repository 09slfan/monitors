<?php
// +----------------------------------------------------------------------
// | zhiliang weight
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019.
// +----------------------------------------------------------------------
// | Author: 小艾.pino
// +----------------------------------------------------------------------
namespace app\package\validate;

use app\admin\model\RouteModel;
use think\Validate;

class PackageCategoryValidate extends Validate
{
    protected $rule = [
        'name'  => 'require',
        'alias' => 'checkAlias',
    ];
    protected $message = [
        'name.require' => '分类名称不能为空',
    ];

    protected $scene = [
//        'add'  => ['user_login,user_pass,user_email'],
//        'edit' => ['user_login,user_email'],
    ];

    // 自定义验证规则
    protected function checkAlias($value, $rule, $data)
    {
        if (empty($value)) {
            return true;
        }

        if (preg_match("/^\d+$/", $value)) {
            return "别名不能为纯数字!";
        }

        $routeModel = new RouteModel();
        if (isset($data['id']) && $data['id'] > 0) {
            $fullUrl = $routeModel->buildFullUrl('package/List/index', ['id' => $data['id']]);
        } else {
            $fullUrl = $routeModel->getFullUrlByUrl($data['alias']);
        }
        if (!$routeModel->existsRoute($value, $fullUrl)) {
            return true;
        } else {
            return "别名已经存在!";
        }

    }
}