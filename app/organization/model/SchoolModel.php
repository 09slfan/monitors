<?php

namespace app\organization\model;

use app\admin\model\RouteModel;
use think\Model;
use think\Db;
use tree\Tree;

/**
 * 产品管理 model
 */
class SchoolModel extends Model {

    protected $type = [
        'more' => 'array',
    ];

    /**
     * post_content 自动转化
     * @param $value
     * @return string
     */
    public function getDescriptionAttr($value) {
        return cmf_replace_content_file_url(htmlspecialchars_decode($value));
    }

    /**
     * post_content 自动转化
     * @param $value
     * @return string
     */
    public function setDescriptionAttr($value) {
        return htmlspecialchars(cmf_replace_content_file_url(htmlspecialchars_decode($value), true));
    }

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

        $this->allowField(true)->isUpdate(true)->data($data, true)->save();
        return $this;

    }

    public function adminVerify($data) {

        $this->allowField(true)->isUpdate(true)->data($data, true)->save();
        return $this;
    }

    public function adminCategoryTableTree($currentIds = 0, $tpl = '',$type=1,$status='') {
        $where = [];
        if (!empty($status)) {
            $where['status']=$status;
        }
        //->where('status', 1)
        $categories = $this->order("id DESC")->where($where)->where('status', 'gt',0)->select()->toArray();

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
            $item['selected']        = in_array($item['id'], $currentIds) ? "selected" : "";
            array_push($newCategories, $item);
        }

        $tree->init($newCategories);

        if (empty($tpl)) {
            switch ($type) {
                case '1':
                    $tpl = " <tr class='data-item-tr'>
                        <td>
                            <input type='radio' class='js-check' data-yid='js-check-y' data-xid='js-check-x' name='ids[]' value='\$id' data-name='\$name' \$checked>
                        </td>
                        <td>\$id</td>
                        <td>\$spacer \$name</td>
                    </tr>";
                    break;
                case '2':
                    $tpl = " <tr class='data-item-tr'>
                        <td>
                            <input type='checkbox' class='js-check' data-yid='js-check-y' data-xid='js-check-x' name='ids[]' value='\$id' data-name='\$name' \$checked>
                        </td>
                        <td>\$id</td>
                        <td>\$spacer \$name</td>
                    </tr>";
                    break;
                default:
                    break;
            }
            
        }
        $treeStr = $tree->getTree(0, $tpl);

        return $treeStr;
    }

}