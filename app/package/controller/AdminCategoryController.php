<?php
// +----------------------------------------------------------------------
// | zhiliang weight
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019.
// +----------------------------------------------------------------------
// | Author: 小艾.pino
// +----------------------------------------------------------------------
namespace app\package\controller;

use app\admin\model\RouteModel;
use cmf\controller\AdminBaseController;
use app\package\model\PackageCategoryModel;
use think\Db;
use app\admin\model\ThemeModel;


class AdminCategoryController extends AdminBaseController
{
    /**
     * 食品库分类列表
     * @adminMenu(
     *     'name'   => '分类管理',
     *     'parent' => 'package/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '食品库分类列表',
     *     'param'  => ''
     * )
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()  {
        $packageCategoryModel = new PackageCategoryModel();
        $keyword             = $this->request->param('keyword');

        if (empty($keyword)) {
            $categoryTree = $packageCategoryModel->adminCategoryTableTree();
            $this->assign('category_tree', $categoryTree);
        } else {
            $categories = $packageCategoryModel->where('name', 'like', "%{$keyword}%")
                ->where('delete_time', 0)->select();
            $this->assign('categories', $categories);
        }

        $this->assign('keyword', $keyword);

        return $this->fetch();
    }

    /**
     * 添加食品库分类
     * @adminMenu(
     *     'name'   => '添加食品库分类',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '添加食品库分类',
     *     'param'  => ''
     * )
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add() {

        $parentId            = $this->request->param('parent', 0, 'intval');
        $packageCategoryModel = new PackageCategoryModel();
        $categoriesTree      = $packageCategoryModel->adminCategoryTree($parentId);
        $this->assign('categories_tree', $categoriesTree);
        return $this->fetch();
    }

    /**
     * 添加食品库分类提交
     * @adminMenu(
     *     'name'   => '添加食品库分类提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '添加食品库分类提交',
     *     'param'  => ''
     * )
     */
    public function addPost() {
        $packageCategoryModel = new PackageCategoryModel();

        $data = $this->request->param();

        $result = $this->validate($data, 'PackageCategory');

        if ($result !== true) {
            $this->error($result);
        }

        $result = $packageCategoryModel->addCategory($data);

        if ($result === false) {
            $this->error('添加失败!');
        }

        $this->success('添加成功!', url('AdminCategory/index'));
    }

    /**
     * 编辑食品库分类
     * @adminMenu(
     *     'name'   => '编辑食品库分类',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '编辑食品库分类',
     *     'param'  => ''
     * )
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit() {

        $id = $this->request->param('id', 0, 'intval');
        if ($id > 0) {
            $packageCategoryModel = new PackageCategoryModel();
            $category            = $packageCategoryModel->get($id)->toArray();

            $categoriesTree = $packageCategoryModel->adminCategoryTree($category['parent_id'], $id);

            $routeModel = new RouteModel();
            $alias      = $routeModel->getUrl('package/List/index', ['id' => $id]);

            $category['alias'] = $alias;
            $this->assign($category);
            $this->assign('categories_tree', $categoriesTree);
            return $this->fetch();
        } else {
            $this->error('操作错误!');
        }

    }

    /**
     * 编辑食品库分类提交
     * @adminMenu(
     *     'name'   => '编辑食品库分类提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '编辑食品库分类提交',
     *     'param'  => ''
     * )
     */
    public function editPost() {
        $data = $this->request->param();

        $result = $this->validate($data, 'PackageCategory');

        if ($result !== true) {
            $this->error($result);
        }

        $packageCategoryModel = new PackageCategoryModel();

        $result = $packageCategoryModel->editCategory($data);

        if ($result === false) {
            $this->error('保存失败!');
        }

        $this->success('保存成功!');
    }

    /**
     * 食品库分类选择对话框
     * @adminMenu(
     *     'name'   => '食品库分类选择对话框',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '食品库分类选择对话框',
     *     'param'  => ''
     * )
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function select()
    {
        $ids                 = $this->request->param('ids');
        $selectedIds         = explode(',', $ids);
        $packageCategoryModel = new PackageCategoryModel();

        $tpl = <<<tpl
<tr class='data-item-tr'>
    <td>
        <input type='radio' class='js-check' data-yid='js-check-y' data-xid='js-check-x' name='ids[]' value='\$id' data-name='\$name' \$checked>
    </td>
    <td>\$id</td>
    <td>\$spacer \$name</td>
</tr>
tpl;

        $categoryTree = $packageCategoryModel->adminCategoryTableTree($selectedIds, $tpl);

        $categories = $packageCategoryModel->where('delete_time', 0)->select();

        $this->assign('categories', $categories);
        $this->assign('selectedIds', $selectedIds);
        $this->assign('categories_tree', $categoryTree);
        return $this->fetch();
    }

    /**
     * 食品库分类显示隐藏
     * @adminMenu(
     *     'name'   => '食品库分类显示隐藏',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '食品库分类显示隐藏',
     *     'param'  => ''
     * )
     */
    public function toggle() {
        $data                = $this->request->param();
        $packageCategoryModel = new PackageCategoryModel();
        $ids                 = $this->request->param('ids/a');

        if (isset($data['ids']) && !empty($data["display"])) {
            $packageCategoryModel->where('id', 'in', $ids)->update(['status' => 1]);
            $this->success("更新成功！");
        }

        if (isset($data['ids']) && !empty($data["hide"])) {
            $packageCategoryModel->where('id', 'in', $ids)->update(['status' => 0]);
            $this->success("更新成功！");
        }

    }

    /**
     * 删除食品库分类
     * @adminMenu(
     *     'name'   => '删除食品库分类',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '删除食品库分类',
     *     'param'  => ''
     * )
     */
    public function delete() {
        $packageCategoryModel = new PackageCategoryModel();
        $id                  = $this->request->param('id');
        //获取删除的内容
        $findCategory = $packageCategoryModel->where('id', $id)->find();

        if (empty($findCategory)) {
            $this->error('分类不存在!');
        }
        //判断此分类有无子分类（不算被删除的子分类）
        $categoryChildrenCount = $packageCategoryModel->where(['parent_id' => $id, 'delete_time' => 0])->count();
        if ($categoryChildrenCount > 0) {
            $this->error('此分类有子类无法删除!');
        }

        $categoryPostCount = Db::name('package_category_post')->where('category_id', $id)->count();

        if ($categoryPostCount > 0) {
            $this->error('此分类有食品库无法删除!');
        }
        $result = $packageCategoryModel
            ->where('id', $id)
            ->update(['delete_time' => time()]);
        if ($result) {
            $this->success('删除成功!');
        } else {
            $this->error('删除失败');
        }
    }
}
