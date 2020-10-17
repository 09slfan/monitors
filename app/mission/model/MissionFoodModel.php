<?php

namespace app\mission\model;

use app\admin\model\RouteModel;
use think\Model;
use think\Db;

/**
 * 资询管理 model
 */
class MissionFoodModel extends Model {

    protected $type = [
        'check' => 'array',
        'cover' => 'array',
        'list' => 'array',
    ];

}