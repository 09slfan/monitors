<?php
// +----------------------------------------------------------------------
// | zhiliang weight
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019.
// +----------------------------------------------------------------------
// | Author: 小艾.pino
// +----------------------------------------------------------------------
namespace app\portal\model;

use app\admin\model\RouteModel;
use think\db\Query;
use think\Model;
use tree\Tree;

class PortalCategoryModel extends Model
{

    protected $type = [
        'more' => 'array',
    ];

    public function adminCategoryTree($selectId = 0, $currentCid = 0) {
        $categories = $this->order("list_order ASC")
            ->where('delete_time', 0)
            ->where(function (Query $query) use ($currentCid) {
                if (!empty($currentCid)) {
                    $query->where('id', 'neq', $currentCid);
                }
            })
            ->select()->toArray();

        $tree       = new Tree();
        $tree->icon = ['&nbsp;&nbsp;│', '&nbsp;&nbsp;├─', '&nbsp;&nbsp;└─'];
        $tree->nbsp = '&nbsp;&nbsp;';

        $newCategories = [];
        foreach ($categories as $item) {
            $item['selected'] = $selectId == $item['id'] ? "selected" : "";

            array_push($newCategories, $item);
        }

        $tree->init($newCategories);
        $str     = '<option value=\"{$id}\" {$selected}>{$spacer}{$name}</option>';
        $treeStr = $tree->getTree(0, $str);

        return $treeStr;
    }


    public function adminCategoryTableTree($currentIds = 0, $tpl = '') {
        // if (!empty($currentCid)) {
        //     $where['id'] = ['neq', $currentCid];
        // }
        $categories = $this->order("list_order ASC")->where('delete_time', 0)->select()->toArray();

        $tree       = new Tree();
        $tree->icon = ['&nbsp;&nbsp;│', '&nbsp;&nbsp;├─', '&nbsp;&nbsp;└─'];
        $tree->nbsp = '&nbsp;&nbsp;';

        if (!is_array($currentIds)) {
            $currentIds = [$currentIds];
        }

        $newCategories = [];
        foreach ($categories as $item) {
            $item['parent_id_node'] = ($item['parent_id']) ? ' class="child-of-node-' . $item['parent_id'] . '"' : '';
            $item['style']          = empty($item['parent_id']) ? '' : 'display:none;';
            $item['status_text']    = empty($item['status']) ? '<span class="label label-warning">隐藏</span>' : '<span class="label label-success">显示</span>';
            $item['checked']        = in_array($item['id'], $currentIds) ? "checked" : "";
            $item['selected']        = in_array($item['id'], $currentIds) ? "selected" : "";
            $item['url']            = cmf_url('portal/List/index', ['id' => $item['id']]);
            $item['str_action']     = '<a class="btn btn-xs btn-primary" href="' . url("AdminCategory/add", ["parent" => $item['id']]) . '">添加子分类</a>  <a class="btn btn-xs btn-primary" href="' . url("AdminCategory/edit", ["id" => $item['id']]) . '">' . lang('EDIT') . '</a>  <a class="btn btn-xs btn-danger js-ajax-delete" href="' . url("AdminCategory/delete", ["id" => $item['id']]) . '">' . lang('DELETE') . '</a> ';
            if ($item['status']) {
                $item['str_action'] .= '<a class="btn btn-xs btn-warning js-ajax-dialog-btn" data-msg="您确定隐藏此分类吗" href="' . url('AdminCategory/toggle', ['ids' => $item['id'], 'hide' => 1]) . '">隐藏</a>';
            } else {
                $item['str_action'] .= '<a class="btn btn-xs btn-success js-ajax-dialog-btn" data-msg="您确定显示此分类吗" href="' . url('AdminCategory/toggle', ['ids' => $item['id'], 'display' => 1]) . '">显示</a>';
            }
            array_push($newCategories, $item);
        }

        $tree->init($newCategories);

        if (empty($tpl)) {
            $tpl = " <td>\$id</td>
                        <td>\$spacer \$name</td>
                        <td>\$description</td>
                        <td>\$status_text</td>
                        <td>\$str_action</td>
                    </tr>";
        }
        $treeStr = $tree->getTree(0, $tpl);

        return $treeStr;
    }

    /**
     * 添加文章分类
     * @param $data
     * @return bool
     */
    public function addCategory($data)
    {
        $result = true;
        self::startTrans();
        try {
            if (!empty($data['more']['thumbnail'])) {
                $data['more']['thumbnail'] = cmf_asset_relative_url($data['more']['thumbnail']);
            }
            $this->allowField(true)->save($data);
            $id = $this->id;
            if (empty($data['parent_id'])) {

                $this->where('id', $id)->update(['path' => '0-' . $id]);
            } else {
                $parentPath = $this->where('id', intval($data['parent_id']))->value('path');
                $this->where('id', $id)->update(['path' => "$parentPath-$id"]);

            }
            self::commit();
        } catch (\Exception $e) {
            self::rollback();
            $result = false;
        }

        // if ($result != false) {
        //     //设置别名
        //     $routeModel = new RouteModel();
        //     if (!empty($data['alias']) && !empty($id)) {
        //         $routeModel->setRoute($data['alias'], 'portal/List/index', ['id' => $id], 2, 5000);
        //         $routeModel->setRoute($data['alias'] . '/:id', 'portal/Article/index', ['cid' => $id], 2, 4999);
        //     }
        //     $routeModel->getRoutes(true);
        // }

        return $result;
    }

    public function editCategory($data)
    {
        $result = true;

        $id          = intval($data['id']);
        $parentId    = intval($data['parent_id']);
        $oldCategory = $this->where('id', $id)->find();

        if (empty($parentId)) {
            $newPath = '0-' . $id;
        } else {
            $parentPath = $this->where('id', intval($data['parent_id']))->value('path');
            if ($parentPath === false) {
                $newPath = false;
            } else {
                $newPath = "$parentPath-$id";
            }
        }

        if (empty($oldCategory) || empty($newPath)) {
            $result = false;
        } else {
            $data['path'] = $newPath;
            if (!empty($data['more']['thumbnail'])) {
                $data['more']['thumbnail'] = cmf_asset_relative_url($data['more']['thumbnail']);
            }
            $this->isUpdate(true)->allowField(true)->save($data, ['id' => $id]);

            $children = $this->field('id,path')->where('path', 'like', $oldCategory['path'] . "-%")->select();
            if (!$children->isEmpty()) {
                foreach ($children as $child) {
                    $childPath = str_replace($oldCategory['path'] . '-', $newPath . '-', $child['path']);
                    $this->where('id', $child['id'])->update(['path' => $childPath], ['id' => $child['id']]);
                }
            }

            // $routeModel = new RouteModel();
            // if (!empty($data['alias'])) {
            //     $routeModel->setRoute($data['alias'], 'portal/List/index', ['id' => $data['id']], 2, 5000);
            //     $routeModel->setRoute($data['alias'] . '/:id', 'portal/Article/index', ['cid' => $data['id']], 2, 4999);
            // } else {
            //     $routeModel->deleteRoute('portal/List/index', ['id' => $data['id']]);
            //     $routeModel->deleteRoute('portal/Article/index', ['cid' => $data['id']]);
            // }

            // $routeModel->getRoutes(true);
        }


        return $result;
    }


}