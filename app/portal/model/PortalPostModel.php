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
use think\Model;
use think\Db;

/**
 * @property mixed id
 */
class PortalPostModel extends Model
{

    protected $type = [
        'more' => 'array',
    ];

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    /**
     * 关联 user表
     * @return \think\model\relation\BelongsTo
     */
    public function user() {
        return $this->belongsTo('UserModel', 'user_id')->setEagerlyType(1);
    }

    /**
     * 关联分类表
     * @return \think\model\relation\BelongsToMany
     */
    public function categories() {
        return $this->belongsToMany('PortalCategoryModel', 'portal_category_post', 'category_id', 'post_id');
    }

    public function schools() {
        return $this->belongsToMany('app\organization\model\SchoolModel', 'portal_school_post', 'school_id', 'post_id');
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
    public function setPostContentAttr($value) {
        return htmlspecialchars(cmf_replace_content_file_url(htmlspecialchars_decode($value), true));
    }

    /**
     * published_time 自动完成
     * @param $value
     * @return false|int
     */
    public function setPublishedTimeAttr($value) {
        return strtotime($value);
    }

    /**
     * 后台管理添加文章
     * @param array        $data       文章数据
     * @param array|string $categories 文章分类 id
     * @return $this
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function adminAddArticle($data, $categories,$schools='') {
        $data['user_id'] = cmf_get_current_admin_id();

        if (!empty($data['more']['thumbnail'])) {
            $data['more']['thumbnail'] = cmf_asset_relative_url($data['more']['thumbnail']);
            $data['thumbnail']         = $data['more']['thumbnail'];
        }

        // if (!empty($data['more']['audio'])) {
        //     $data['more']['audio'] = cmf_asset_relative_url($data['more']['audio']);
        // }
        // if (!empty($data['more']['video'])) {
        //     $data['more']['video'] = cmf_asset_relative_url($data['more']['video']);
        // }

        $this->allowField(true)->data($data, true)->isUpdate(false)->save();

        if (is_string($categories)) { $categories = explode(',', $categories); }
        $this->categories()->save($categories);

        if (is_string($schools)) { $schools = explode(',', $schools); }
        $this->schools()->save($schools);

        return $this;

    }

    /**
     * 后台管理编辑文章
     * @param array        $data       文章数据
     * @param array|string $categories 文章分类 id
     * @return $this
     * @throws \think\Exception
     */
    public function adminEditArticle($data, $categories, $schools='') {
        unset($data['user_id']);
        if (!empty($data['more']['thumbnail'])) {
            $data['more']['thumbnail'] = cmf_asset_relative_url($data['more']['thumbnail']);
            $data['thumbnail']         = $data['more']['thumbnail'];
        }

        // if (!empty($data['more']['audio'])) {
        //     $data['more']['audio'] = cmf_asset_relative_url($data['more']['audio']);
        // }
        // if (!empty($data['more']['video'])) {
        //     $data['more']['video'] = cmf_asset_relative_url($data['more']['video']);
        // }

        $this->allowField(true)->isUpdate(true)->data($data, true)->save();

        if (is_string($categories)) { $categories = explode(',', $categories); }
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

        if (is_string($schools)) { $schools = explode(',', $schools); }
        $oldSchools        = $this->schools()->column('school_id');
        $sameSchools       = array_intersect($schools, $oldSchools);
        $needDeleteSchools = array_diff($oldSchools, $sameSchools);
        $newSchools        = array_diff($schools, $sameSchools);
        if (!empty($needDeleteSchools)) {
            $this->schools()->detach($needDeleteSchools);
        }
        if (!empty($newSchools)) {
            $this->schools()->attach(array_values($newSchools));
        }

        return $this;

    }
}
