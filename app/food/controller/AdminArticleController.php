<?php
// +----------------------------------------------------------------------
// | zhiliang weight
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019.
// +----------------------------------------------------------------------
// | Author: 小艾.pino
// +----------------------------------------------------------------------
namespace app\food\controller;

use cmf\controller\AdminBaseController;
use app\food\model\FoodPostModel;
use app\food\service\PostService;
use app\food\service\ImportService;
use app\food\model\FoodCategoryModel;
use think\Db;

class AdminArticleController extends AdminBaseController {
    /**
     * 食品库列表
     * @adminMenu(
     *     'name'   => '食品库管理',
     *     'parent' => 'food/AdminIndex/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '食品库列表',
     *     'param'  => ''
     * )
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index() {
        $content = hook_one('food_admin_article_index_view');

        if (!empty($content)) {
            return $content;
        }

        $param = $this->request->param();

        $categoryId = $this->request->param('category', 0, 'intval');

        $postService = new PostService();
        $data        = $postService->adminArticleList($param);

        $data->appends($param);

        $foodCategoryModel = new FoodCategoryModel();
        $categoryTree        = $foodCategoryModel->adminCategoryTree($categoryId);

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
     * 添加食品库
     * @adminMenu(
     *     'name'   => '添加食品库',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '添加食品库',
     *     'param'  => ''
     * )
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add() {
        $content = hook_one('food_admin_article_add_view');

        if (!empty($content)) {
            return $content;
        }
        $units = db::name('foodUnit')->where(['status'=>1,'delete_time'=>0 ])->field('id,name')->order('id desc')->select();
        $this->assign('units', $units);
        return $this->fetch();
    }

    /**
     * 添加食品库提交
     * @adminMenu(
     *     'name'   => '添加食品库提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '添加食品库提交',
     *     'param'  => ''
     * )
     */
    public function addPost() {
        if ($this->request->isPost()) {
            $data = $this->request->param();

            //状态只能设置默认值。未发布、未置顶、未推荐
            $data['post']['post_status'] = 0;
            $data['post']['is_top']      = 0;
            $data['post']['recommended'] = 0;

            $post = $data['post'];

            $result = $this->validate($post, 'AdminArticle');
            if ($result !== true) {
                $this->error($result);
            }

            $foodPostModel = new FoodPostModel();

            if (!empty($data['photo_names']) && !empty($data['photo_urls'])) {
                $data['post']['more']['photos'] = [];
                foreach ($data['photo_urls'] as $key => $url) {
                    $photoUrl = cmf_asset_relative_url($url);
                    array_push($data['post']['more']['photos'], ["url" => $photoUrl, "name" => $data['photo_names'][$key]]);
                }
            }

            if (!empty($data['file_names']) && !empty($data['file_urls'])) {
                $data['post']['more']['files'] = [];
                foreach ($data['file_urls'] as $key => $url) {
                    $fileUrl = cmf_asset_relative_url($url);
                    array_push($data['post']['more']['files'], ["url" => $fileUrl, "name" => $data['file_names'][$key]]);
                }
            }


            $foodPostModel->adminAddArticle($data['post'], $data['post']['categories']);

            $data['post']['id'] = $foodPostModel->id;
            $hookParam          = [
                'is_add'  => true,
                'article' => $data['post']
            ];
            hook('food_admin_after_save_article', $hookParam);


            $this->success('添加成功!', url('AdminArticle/edit', ['id' => $foodPostModel->id]));
        }

    }

    /**
     * 编辑食品库
     * @adminMenu(
     *     'name'   => '编辑食品库',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '编辑食品库',
     *     'param'  => ''
     * )
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit() {
        $content = hook_one('food_admin_article_edit_view');

        if (!empty($content)) {
            return $content;
        }

        $id = $this->request->param('id', 0, 'intval');

        $foodPostModel = new FoodPostModel();
        $post            = $foodPostModel->where('id', $id)->find();
        $postCategories  = $post->categories()->alias('a')->column('a.name', 'a.id');
        $postCategoryIds = implode(',', array_keys($postCategories));

        $units = db::name('foodUnit')->where(['status'=>1,'delete_time'=>0 ])->field('id,name')->order('id desc')->select();
        $this->assign('units', $units);

        $this->assign('post', $post);
        $this->assign('post_categories', $postCategories);
        $this->assign('post_category_ids', $postCategoryIds);

        return $this->fetch();
    }

    /**
     * 编辑食品库提交
     * @adminMenu(
     *     'name'   => '编辑食品库提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '编辑食品库提交',
     *     'param'  => ''
     * )
     * @throws \think\Exception
     */
    public function editPost() {
        if ($this->request->isPost()) {
            $data = $this->request->param();

            //需要抹除发布、置顶、推荐的修改。
            unset($data['post']['post_status']);
            unset($data['post']['is_top']);
            unset($data['post']['recommended']);

            $post   = $data['post'];
            $result = $this->validate($post, 'AdminArticle');
            if ($result !== true) {
                $this->error($result);
            }

            $foodPostModel = new FoodPostModel();

            if (!empty($data['photo_names']) && !empty($data['photo_urls'])) {
                $data['post']['more']['photos'] = [];
                foreach ($data['photo_urls'] as $key => $url) {
                    $photoUrl = cmf_asset_relative_url($url);
                    array_push($data['post']['more']['photos'], ["url" => $photoUrl, "name" => $data['photo_names'][$key]]);
                }
            }

            if (!empty($data['file_names']) && !empty($data['file_urls'])) {
                $data['post']['more']['files'] = [];
                foreach ($data['file_urls'] as $key => $url) {
                    $fileUrl = cmf_asset_relative_url($url);
                    array_push($data['post']['more']['files'], ["url" => $fileUrl, "name" => $data['file_names'][$key]]);
                }
            }

            $foodPostModel->adminEditArticle($data['post'], $data['post']['categories']);

            $hookParam = [
                'is_add'  => false,
                'article' => $data['post']
            ];
            hook('food_admin_after_save_article', $hookParam);

            $this->success('保存成功!');

        }
    }

    /**
     * 食品库删除
     * @adminMenu(
     *     'name'   => '食品库删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '食品库删除',
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
        $foodPostModel = new FoodPostModel();

        if (isset($param['id'])) {
            $id           = $this->request->param('id', 0, 'intval');
            $result       = $foodPostModel->where('id', $id)->find();
            $data         = [
                'object_id'   => $result['id'],
                'create_time' => time(),
                'table_name'  => 'food_post',
                'name'        => $result['post_title'],
                'user_id'     => cmf_get_current_admin_id()
            ];
            $resultFood = $foodPostModel
                ->where('id', $id)
                ->update(['delete_time' => time()]);
            if ($resultFood) {
                Db::name('food_category_post')->where('post_id', $id)->update(['status' => 0]);
                Db::name('food_tag_post')->where('post_id', $id)->update(['status' => 0]);

                Db::name('recycleBin')->insert($data);
            }
            $this->success("删除成功！", '');

        }

        if (isset($param['ids'])) {
            $ids     = $this->request->param('ids/a');
            $recycle = $foodPostModel->where('id', 'in', $ids)->select();
            $result  = $foodPostModel->where('id', 'in', $ids)->update(['delete_time' => time()]);
            if ($result) {
                Db::name('food_category_post')->where('post_id', 'in', $ids)->update(['status' => 0]);
                Db::name('food_tag_post')->where('post_id', 'in', $ids)->update(['status' => 0]);
                foreach ($recycle as $value) {
                    $data = [
                        'object_id'   => $value['id'],
                        'create_time' => time(),
                        'table_name'  => 'food_post',
                        'name'        => $value['post_title'],
                        'user_id'     => cmf_get_current_admin_id()
                    ];
                    Db::name('recycleBin')->insert($data);
                }
                $this->success("删除成功！", '');
            }
        }
    }

    /**
     * 食品库发布
     * @adminMenu(
     *     'name'   => '食品库发布',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '食品库发布',
     *     'param'  => ''
     * )
     */
    public function publish() {
        $param           = $this->request->param();
        $foodPostModel = new FoodPostModel();

        if (isset($param['ids']) && isset($param["yes"])) {
            $ids = $this->request->param('ids/a');
            $foodPostModel->where('id', 'in', $ids)->update(['post_status' => 1, 'published_time' => time()]);
            $this->success("发布成功！", '');
        }

        if (isset($param['ids']) && isset($param["no"])) {
            $ids = $this->request->param('ids/a');
            $foodPostModel->where('id', 'in', $ids)->update(['post_status' => 0]);
            $this->success("取消发布成功！", '');
        }

    }

    /**
     * 食品库推荐
     * @adminMenu(
     *     'name'   => '食品库推荐',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '食品库推荐',
     *     'param'  => ''
     * )
     */
    public function recommend() {
        $param           = $this->request->param();
        $foodPostModel = new FoodPostModel();

        if (isset($param['ids']) && isset($param["yes"])) {
            $ids = $this->request->param('ids/a');

            $foodPostModel->where('id', 'in', $ids)->update(['recommended' => 1]);

            $this->success("推荐成功！", '');

        }
        if (isset($param['ids']) && isset($param["no"])) {
            $ids = $this->request->param('ids/a');

            $foodPostModel->where('id', 'in', $ids)->update(['recommended' => 0]);

            $this->success("取消推荐成功！", '');

        }
    }

    /**
     * 食品库排序
     * @adminMenu(
     *     'name'   => '食品库排序',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '食品库排序',
     *     'param'  => ''
     * )
     */
    public function listOrder() {
        parent::listOrders(Db::name('food_category_post'));
        $this->success("排序更新成功！", '');
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
    public function importFood(){
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
    public function importFoodPost(){
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $path = 'upload/'.$post['files']; // 获取上传到服务器文件路径
            $ext = pathinfo($post['files'], PATHINFO_EXTENSION);

            $params = array(
                "datafile" => $path,
                "ext" => $ext,
            );
            $objImport = new ImportService();
            $result = $objImport->importFood($params);
            if ($result !== false) {
                $this->success("导入成功！", url("AdminArticle/index"));
            } else {
                $this->error("导入失败！");
            }
        }
    }
}
