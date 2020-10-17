<?php
namespace app\card\controller;

use cmf\controller\CardBaseController;
use app\portal\service\PostService;

use app\portal\model\PortalCategoryModel;

use think\Db;

class TopController extends CardBaseController {

    public function index() {
        $param = $this->request->param();
        $model = new PortalCategoryModel();
        $cid = 0;
        $selected = [];
        if (isset($param['cid']) and !empty($param['cid']) ) {
            $cid = $param['cid'];
            $selected[] = $param['cid'];
        }
        $tpl = "<option \$selected value='\$id'>\$spacer \$name</option>";
        $cate_tree = $model->adminCategoryTableTree($selected,$tpl);

        $this->assign('cid', $cid);
        $this->assign('cate_tree', $cate_tree);
        return $this->fetch();
    }

    public function ajaxlist() {
        $param = $this->request->param();
        $param['post_status'] = 1;
        $postService = new PostService();
        // $param['type'] = 2;
        $school_id = $this->schoolId;
        $param['school_id'] = !empty($school_id)?$school_id:0;
        $data = $postService->postList($param);
        $data ->appends($param);
        $list = $data->toArray()['data'];
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $list[$k]['published_time'] = tranTime($v['published_time']);
                $list[$k]['image'] = cmf_get_image_url($v['more']['thumbnail']);
                unset($list[$k]['more']);
            }
            $this->assign('list', $list);
            return $this->fetch();
        }
         
    }

    public function info() {
        $id = $this->request->param('id', 0, 'intval');
        // session('userId',$this->userId);
        $postService = new PostService();
        $res = $postService->publishedArticle($id);
        $this->assign('res', $res);
        // var_dump($res);exit;
        return $this->fetch();
    }

    public function commentPost() {
        if ($this->request->isPost()) {
            $post = $this->request->param();  //提交的信息
            $post['user_id'] = $this->getUserId();  //用户id
            $post['school_id'] = $this->schoolId;  //学校id
            $post['create_time'] = time();
            $post['status'] = 1;

            $postService = new PostService();
            $res = $postService->postComment($post);

            if ($res !== false) {
                $this->success("评论成功",cmf_url('top/info',['id'=>$post['object_id']]) );
            } else {
                $this->error('评论无效');
            }
        }
    }

}
