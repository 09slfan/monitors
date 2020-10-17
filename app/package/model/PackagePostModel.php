<?php
// +----------------------------------------------------------------------
// | zhiliang weight
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019.
// +----------------------------------------------------------------------
// | Author: 小艾.pino
// +----------------------------------------------------------------------
namespace app\package\model;

use app\admin\model\RouteModel;
use think\Model;
use think\Db;
use tree\Tree;

/**
 * @property mixed id
 */
class PackagePostModel extends Model {
    protected $type = [
        'more' => 'array',
    ];

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    /**
     * 关联 user表
     * @return \think\model\relation\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('UserModel', 'user_id')->setEagerlyType(1);
    }

    /**
     * 关联分类表
     * @return \think\model\relation\BelongsToMany
     */
    public function categories()
    {
        return $this->belongsToMany('PackageCategoryModel', 'package_category_post', 'category_id', 'post_id');
    }

    /**
     * post_content 自动转化
     * @param $value
     * @return string
     */
    public function getPostContentAttr($value) {
        return cmf_replace_content_file_url(htmlspecialchars_decode($value));
    }

    /**
     * post_content 自动转化
     * @param $value
     * @return string
     */
    public function setPostContentAttr($value)
    {
        return htmlspecialchars(cmf_replace_content_file_url(htmlspecialchars_decode($value), true));
    }

    /**
     * published_time 自动完成
     * @param $value
     * @return false|int
     */
    public function setPublishedTimeAttr($value)
    {
        return strtotime($value);
    }

    /**
     * 后台管理添加食品库
     * @param array        $data       食品库数据
     * @param array|string $categories 食品库分类 id
     * @return $this
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function adminAddArticle($data, $categories)
    {
        $data['user_id'] = cmf_get_current_admin_id();

        if (!empty($data['more']['thumbnail'])) {
            $data['more']['thumbnail'] = cmf_asset_relative_url($data['more']['thumbnail']);
            $data['thumbnail']         = $data['more']['thumbnail'];
        }

        $this->allowField(true)->data($data, true)->isUpdate(false)->save();

        if (is_string($categories)) {
            $categories = explode(',', $categories);
        }

        $this->categories()->save($categories);

        return $this;

    }

    /**
     * 后台管理编辑食品库
     * @param array        $data       食品库数据
     * @param array|string $categories 食品库分类 id
     * @return $this
     * @throws \think\Exception
     */
    public function adminEditArticle($data, $categories) {
        unset($data['user_id']);
        if (!empty($data['more']['thumbnail'])) {
            $data['more']['thumbnail'] = cmf_asset_relative_url($data['more']['thumbnail']);
            $data['thumbnail']         = $data['more']['thumbnail'];
        }
        $this->allowField(true)->isUpdate(true)->data($data, true)->save();

        if (is_string($categories)) {
            $categories = explode(',', $categories);
        }

        $oldCategoryIds        = $this->categories()->column('category_id');
        $sameCategoryIds       = array_intersect($categories, $oldCategoryIds);
        $needDeleteCategoryIds = array_diff($oldCategoryIds, $sameCategoryIds);
        $newCategoryIds        = array_diff($categories, $sameCategoryIds);

        if (!empty($needDeleteCategoryIds)) {
            $this->categories()->detach($needDeleteCategoryIds);
        }

        if (!empty($newCategoryIds)) {
            $this->categories()->attach(array_values($newCategoryIds));
        }

        return $this;

    }

    public function adminTableTree($currentIds = 0, $tpl = '')
    {
//        if (!empty($currentCid)) {
//            $where['id'] = ['neq', $currentCid];
//        }
        $categories = $this->order("id DESC")->where('delete_time', 0)->select()->toArray();

        $tree       = new Tree();
        $tree->icon = ['&nbsp;&nbsp;│', '&nbsp;&nbsp;├─', '&nbsp;&nbsp;└─'];
        $tree->nbsp = '&nbsp;&nbsp;';

        if (!is_array($currentIds)) {
            $currentIds = [$currentIds];
        }

        $newCategories = [];
        foreach ($categories as $item) {
            $item['parent_id'] = 0;
            $item['parent_id_node'] = ($item['parent_id']) ? ' class="child-of-node-' . $item['parent_id'] . '"' : '';
            $item['style']          = empty($item['parent_id']) ? '' : 'display:none;';
            $item['checked']        = in_array($item['id'], $currentIds) ? "checked" : "";
            array_push($newCategories, $item);
        }

        $tree->init($newCategories);

        if (empty($tpl)) {
            $tpl = <<<tpl
<tr class='data-item-tr'>
    <td>
        <input type='radio' class='js-check' data-yid='js-check-y' data-xid='js-check-x' name='ids[]' value='\$id' data-name='\$post_title' \$checked>
    </td>
    <td>\$id</td>
    <td>\$spacer \$post_title</td>
</tr>
tpl;
        }
        $treeStr = $tree->getTree(0, $tpl);

        return $treeStr;
    }
}
