<?php

namespace app\video\model;

use app\admin\model\RouteModel;
use think\Model;
use think\Db;
use tree\Tree;

/**
 * 视频管理 model
 */
class VideoModel extends Model {

    protected $type = [
        'more' => 'array',
    ];

    public function adminAdd($data)  {
        // $data['user_id'] = cmf_get_current_admin_id();
        // if (!empty($data['thumbnail'])) {
        //     $data['thumbnail'] = cmf_asset_relative_url($data['thumbnail']);
        // }

        $this->allowField(true)->data($data, true)->isUpdate(false)->save();
        return $this;

    }

    public function adminEdit($data) {
        // unset($data['user_id']);
        // if (!empty($data['thumbnail'])) {
        //     $data['thumbnail'] = cmf_asset_relative_url($data['thumbnail']);
        // }

        $this->allowField(true)->isUpdate(true)->data($data, true)->save();
        return $this;

    }

    public function adminCategoryTableTree($currentIds = 0, $tpl = '') {
        $categories = $this->order("id DESC")->where('status', 1)->select()->toArray();

        $tree       = new Tree();
        $tree->icon = ['&nbsp;&nbsp;│', '&nbsp;&nbsp;├─', '&nbsp;&nbsp;└─'];
        $tree->nbsp = '&nbsp;&nbsp;';

        if (!is_array($currentIds)) {
            $currentIds = [$currentIds];
        }

        $newCategories = [];
        foreach ($categories as $item) {
            $item['parent_id_node'] = '';
            $item['parent_id']      = 0;
            $item['style']          = '';
            $item['checked']        = in_array($item['id'], $currentIds) ? "checked" : "";
            array_push($newCategories, $item);
        }

        $tree->init($newCategories);

        if (empty($tpl)) {
            $tpl = " <tr class='data-item-tr'>
                        <td>
                            <input type='radio' class='js-check' data-yid='js-check-y' data-xid='js-check-x' name='ids[]' value='\$id' data-name='\$name' \$checked>
                        </td>
                        <td>\$id</td>
                        <td>\$spacer \$name</td>
                    </tr>";
        }
        $treeStr = $tree->getTree(0, $tpl);

        return $treeStr;
    }

}