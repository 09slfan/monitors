<?php
namespace app\card\controller;

use cmf\controller\CardBaseController;
use app\organization\service\PostService;
use app\organization\model\SchoolModel;
use app\organization\model\SchoolTypeModel;
use think\Db;

class SchoolController extends CardBaseController {

    public function index() {
        $param = $this->request->param();
        $userInfo = $this->userInfo;
        $html = 'index';
        if ($userInfo['user_cate']==4) {  //行政人员
            $cate_param = ['province_id'=>$userInfo['province'],'city_id'=>$userInfo['city'],'district_id'=>$userInfo['district'],'province'=>$userInfo['province'],'city'=>$userInfo['city'],'district'=>$userInfo['district'],];
            $param = array_merge($cate_param,$param);
            $this->getSchoolAndCanteen($param);
            $this->getStaff($param);

            $html = 'index4';
        }
        $this->assign('user', $userInfo);
        $this->assign('param', $param);

        return $this->fetch($html);
    }

    public function ajaxlist() {
        $param = $this->request->param();
        $param['status'] = 1;
        $service = new PostService();

        $userInfo = $this->userInfo;
        if ($userInfo['user_cate']==4) {  //行政人员
            $cate_param = ['province'=>$userInfo['province'],'city'=>$userInfo['city'],'district'=>$userInfo['district'],];
            $param = array_merge($cate_param,$param);
            $s_c = $this->getSchoolAndCanteen($param);
        }

        $list = $service->postList($param);
        $list ->appends($param);
        $this->assign('list', $list);
        return $this->fetch();
    }

    public function info() {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) {
            $school_id = $this->schoolId;
            //$userId = $this->userId;
            if (empty($school_id)) {
                $this->error('参数错误');
            } else {
                $id = $school_id;
            }
        }
        $service = new PostService();
        $res = $service->published($id);
        $status = get_status(1);
        $userInfo = $this->userInfo;
        $res['role'] = $userInfo['user_cate'];
        $res['status_name'] = $status[$res['status']];
        $this->assign('res', $res);

        $user_id = $this->userId;
        $this->assign('user_id', $user_id);  //自身id
        return $this->fetch();
    }

    public function add() {
        //$id = $this->request->param('id', 0, 'intval');
        //$user_id = $this->getUserId();  //用户id
        return $this->fetch();
    }

    public function editPost() {
        if ($this->request->isPost()) {
            $post = $this->request->param();  //提交的信息
            $user_id = $this->getUserId();  //用户id
            $school_id = $this->schoolId;  //学校id
            if (empty($school_id)) {
                $this->error('参数错误');
            }
            if (empty($post['content'])) {
                $this->error('请填写学校简介内容');
            }
            if (empty($post['pics'])) {
                $this->error('请上传学校图片');
            }
            $post['image'] = isset($post['image'])?$post['image']:'';
            $post['video'] = isset($post['video'])?$post['video']:'';
            //if (empty($post['video'])) {
                //$this->error('请上传学校视频文件');
            //}
            $update = [ 'id'=>$school_id, 'more'=> ['image'=>$post['image'],'content'=>$post['content'],'pics'=>$post['pics'],'video'=>$post['video'], ] ];
            $status = db::name('school')->where('id',$school_id)->value('status');
            if ($status == 3) {
                $update['status'] = 1;
            }

            $model = new SchoolModel();
            $res = $model->adminEdit($update);
            if ($res !== false) {
                $this->success("提交成功", url("card/school/info") );
            } else {
                $this->error('提交成功');
            }
        }
    }

    public function edit() {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) {
            $school_id = $this->schoolId;
            //$userId = $this->userId;
            if (empty($school_id)) {
                $this->error('参数错误');
            } else {
                $id = $school_id;
            }
        }
        $model = new SchoolModel();
        $post = $model->alias('s')->where('s.id', $id)->find();
        $post['pics_count'] = 0;
        if (isset($post['more']['pics']) and !empty($post['more']['pics'])) {
            $post['pics'] = $post['more']['pics'];
            $post['pics_count'] = count($post['pics']);
        }
        $this->assign('post', $post);
        // $types = db::name('schoolType')->where('id','in',$post['school_type'])->where('status',1)->field('name')->select();
        // $this->assign('types', $types);

        //$this->getStaff($post);
        return $this->fetch();
    }

}
