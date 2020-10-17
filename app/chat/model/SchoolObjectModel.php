<?php

namespace app\chat\model;

use app\admin\model\RouteModel;
use think\Model;
use think\Db;

/**
 * 资询管理 model
 */
class SchoolObjectModel extends Model {

    // protected $type = [
    //     'more' => 'array',
    // ];

    /**
     * post_content 自动转化
     * @param $value
     * @return string
     */
    public function getMsgAttr($value) {
        return cmf_replace_content_file_url(htmlspecialchars_decode($value));
    }

    /**
     * post_content 自动转化
     * @param $value
     * @return string
     */
    public function setMsgAttr($value) {
        return htmlspecialchars(cmf_replace_content_file_url(htmlspecialchars_decode($value), true));
    }

    public function adminAdd($data) {
        $data['user_id'] = cmf_get_current_admin_id();
        $this->allowField(true)->data($data, true)->isUpdate(false)->save();

        return $this;

    }

    public function adminEdit($data) {
        $this->allowField(true)->isUpdate(true)->data($data, true)->save();

        return $this;

    }

}