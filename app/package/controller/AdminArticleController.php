<?php
// +----------------------------------------------------------------------
// | zhiliang weight
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019.
// +----------------------------------------------------------------------
// | Author: 小艾.pino
// +----------------------------------------------------------------------
namespace app\package\controller;

use cmf\controller\AdminBaseController;
use app\package\model\PackagePostModel;
use app\package\service\PostService;
use app\package\service\ImportService;
use app\package\model\PackageCategoryModel;
use think\Db;

class AdminArticleController extends AdminBaseController {
    /**
     * 套餐列表
     * @adminMenu(
     *     'name'   => '套餐管理',
     *     'parent' => 'package/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '套餐列表',
     *     'param'  => ''
     * )
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index() {
        $param = $this->request->param();

        $categoryId = $this->request->param('category', 0, 'intval');

        $postService = new PostService();
        $data        = $postService->adminArticleList($param);

        $data->appends($param);

        $packageCategoryModel = new PackageCategoryModel();
        $categoryTree         = $packageCategoryModel->adminCategoryTree($categoryId);

        $this->assign('start_time', isset($param['start_time']) ? $param['start_time'] : '');
        $this->assign('end_time', isset($param['end_time']) ? $param['end_time'] : '');
        $this->assign('keyword', isset($param['keyword']) ? $param['keyword'] : '');
        $this->assign('articles', $data->items());
        $this->assign('category_tree', $categoryTree);
        $this->assign('category', $categoryId);
        $this->assign('page', $data->render());


        return $this->fetch();
    }

    /**
     * 添加套餐
     * @adminMenu(
     *     'name'   => '添加套餐',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '添加套餐',
     *     'param'  => ''
     * )
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add() {
        // $types = db::name('packageType')->where(['status'=>1])->field('id,name')->order('id desc')->select();
        // $this->assign('types', $types);
        return $this->fetch();
    }

    /**
     * 添加套餐提交
     * @adminMenu(
     *     'name'   => '添加套餐提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '添加套餐提交',
     *     'param'  => ''
     * )
     */
    public function addPost() {
        if ($this->request->isPost()) {
            $data = $this->request->param();

            //状态只能设置默认值。未发布、未置顶、未推荐
            $data['post']['post_status'] = 1;
            $post = $data['post'];

            $result = $this->validate($post, 'PackagePost');
            if ($result !== true) {
                $this->error($result);
            }
            if (isset($post['money'])) {
                # code...
            }

            $packagePostModel = new PackagePostModel();
            $packagePostModel->adminAddArticle($data['post'], $data['post']['categories']);
            $this->success('添加成功!', url('AdminArticle/index'));
        }

    }

    public function check() {
        $id = $this->request->param('id', 0, 'intval');

        $packagePostModel = new PackagePostModel();
        $post            = $packagePostModel->where('id', $id)->find();
        $postCategories  = $post->categories()->alias('a')->column('a.name', 'a.id');
        $postCategoryIds = implode(',', array_keys($postCategories));

        $this->assign('post', $post);
        $this->assign('post_categories', $postCategories);
        $this->assign('post_category_ids', $postCategoryIds);

        return $this->fetch();
    }

    public function edit() {
        $id = $this->request->param('id', 0, 'intval');

        $packagePostModel = new PackagePostModel();
        $post            = $packagePostModel->where('id', $id)->find();
        $postCategories  = $post->categories()->alias('a')->column('a.name', 'a.id');
        $postCategoryIds = implode(',', array_keys($postCategories));

        // $types = db::name('packageType')->where(['status'=>1])->field('id,name')->order('id desc')->select();
        // $this->assign('types', $types);

        $this->assign('post', $post);
        $this->assign('post_categories', $postCategories);
        $this->assign('post_category_ids', $postCategoryIds);

        return $this->fetch();
    }

    /**
     * 编辑套餐提交
     * @adminMenu(
     *     'name'   => '编辑套餐提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '编辑套餐提交',
     *     'param'  => ''
     * )
     * @throws \think\Exception
     */
    public function editPost() {
        if ($this->request->isPost()) {
            $data = $this->request->param();

            //需要抹除发布、置顶、推荐的修改。
            unset($data['post']['post_status']);

            $post   = $data['post'];
            $result = $this->validate($post, 'PackagePost');
            if ($result !== true) {
                $this->error($result);
            }

            $packagePostModel = new PackagePostModel();
            $packagePostModel->adminEditArticle($data['post'], $data['post']['categories']);
            $this->success('保存成功!', url('AdminArticle/index'));

        }
    }

    public function select() {
        $ids         = $this->request->param('ids');
        $selectedIds = explode(',', $ids);
        $model = new PackagePostModel();

        $tree = $model->adminTableTree($selectedIds);

        // $categories = $model->where('delete_time', 0)->select();
        // $this->assign('categories', $categories);
        $this->assign('selectedIds', $selectedIds);
        $this->assign('post_tree', $tree);
        return $this->fetch();
    }

    /**
     * 套餐删除
     * @adminMenu(
     *     'name'   => '套餐删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '套餐删除',
     *     'param'  => ''
     * )
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function delete() {
        $param           = $this->request->param();
        $packagePostModel = new PackagePostModel();

        if (isset($param['id'])) {
            $id           = $this->request->param('id', 0, 'intval');
            $resultPackage = $packagePostModel
                ->where('id', $id)
                ->update(['delete_time' => time()]);
            if ($resultPackage) {
                Db::name('package_category_post')->where('post_id', $id)->update(['status' => 0]);
            }
            $this->success("删除成功！", '');

        }

        if (isset($param['ids'])) {
            $ids     = $this->request->param('ids/a');
            $result  = $packagePostModel->where('id', 'in', $ids)->update(['delete_time' => time()]);
            if ($result) {
                Db::name('package_category_post')->where('post_id', 'in', $ids)->update(['status' => 0]);
            }
        }
    }

    /**
     * 套餐发布
     * @adminMenu(
     *     'name'   => '套餐发布',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '套餐发布',
     *     'param'  => ''
     * )
     */
    public function publish() {
        $param           = $this->request->param();
        $packagePostModel = new PackagePostModel();

        if (isset($param['ids']) && isset($param["yes"])) {
            $ids = $this->request->param('ids/a');
            $packagePostModel->where('id', 'in', $ids)->update(['post_status' => 1, 'published_time' => time()]);
            $this->success("发布成功！", '');
        }

        if (isset($param['ids']) && isset($param["no"])) {
            $ids = $this->request->param('ids/a');
            $packagePostModel->where('id', 'in', $ids)->update(['post_status' => 0]);
            $this->success("取消发布成功！", '');
        }

    }

    /**
     * 导入用户界面
     * @schoolMenu(
     *     'name'   => '导入用户',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '导入用户',
     *     'param'  => ''
     * )
     */
    public function importPackage(){
        return $this->fetch();
    }

    /**
     * 导入用户
     * @schoolMenu(
     *     'name'   => '导入用户',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '导入用户',
     *     'param'  => ''
     * )
     */
    public function importPackagePost(){
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $path = 'upload/'.$post['files']; // 获取上传到服务器文件路径
            $ext = pathinfo($post['files'], PATHINFO_EXTENSION);

            $params = array(
                "datafile" => $path,
                "ext" => $ext,
            );
            $objImport = new ImportService();
            $result = $objImport->importPackage($params);
            if ($result !== false) {
                $this->success("导入成功！", url("AdminArticle/index"));
            } else {
                $this->error("导入失败！");
            }
        }
    }
}
