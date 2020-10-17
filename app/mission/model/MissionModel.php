<?php

namespace app\mission\model;

use app\admin\model\RouteModel;
use think\Model;
use think\Db;

/**
 * 资询管理 model
 */
class MissionModel extends Model {

    // protected $type = [
    //     'more' => 'array',
    // ];

    public function adminAdd($data)  {
        $this->allowField(true)->data($data, true)->isUpdate(false)->save();
        return $this;

    }

    public function adminEdit($data) {
        $this->allowField(true)->isUpdate(true)->data($data, true)->save();
        return $this;

    }

    public function adminVerify($data) {
        $this->allowField(true)->isUpdate(true)->data($data, true)->save();
        return $this;
    }

}