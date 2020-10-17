<?php
namespace app\card\controller;

use cmf\controller\CardBaseController;
use app\user\model\CommentModel;


use think\Db;

class CommentController extends CardBaseController {

    public function index() {
        $param = $this->request->param();
        $userId = $this->userId;
        $this->assign('userId', $userId);
        $html = 'index';

        $userInfo = $this->userInfo;
        if ($userInfo['user_cate']==4) {  //行政人员
            $cate_param = ['province'=>$userInfo['province'],'city'=>$userInfo['city'],'district'=>$userInfo['district'],];
            $param = array_merge($cate_param,$param);
            $this->getSchoolAndCanteen($param);
            $html = 'index4';
        }
        
        return $this->fetch($html);
    }

    public function ajaxlist() {
        $param = $this->request->param();
        $model = new CommentModel();
        $school_id = $this->schoolId;
        $canteen_id = $this->canteenId;
        $user_id = $this->userId;
        $param['user_id'] = !empty($user_id)?$user_id:0;

        $userInfo = $this->userInfo;
        $s_c = [];
        if ($userInfo['user_cate']==4) {  //行政人员
            $cate_param = ['province'=>$userInfo['province'],'city'=>$userInfo['city'],'district'=>$userInfo['district'],];
            $param = array_merge($cate_param,$param);
            $s_c = $this->getSchoolAndCanteen($param);
        }
        
        if (isset($param['keyword']) and !empty($param['keyword'])) { 
            $model = $model->where('c.title', 'like', '%'.$param['keyword'].'%');
        }
        if (isset($param['school_id']) and !empty($param['school_id'])) { 
            $model = $model->where('c.school_id', 'eq', $param['school_id']);
        } else {
            if (!empty($school_id)) {
                $model = $model->where('c.school_id', 'eq', $school_id);
            }
            if (!empty($s_c['school_ids'])) {
                $school_id = $s_c['school_ids'];
                $model = $model->where('c.school_id', 'in', $school_id);
            }
        }
        if (isset($param['canteen_id']) and !empty($param['canteen_id'])) { 
            $model = $model->where('c.canteen_id', 'eq', $param['canteen_id']);
        } else {
            // if (!empty($canteen_id)) {
            //     $model = $model->where('c.canteen_id', 'eq', $canteen_id);
            // }
        }
        $join = [
            ['__USER__ u','u.id = c.user_id'],
            ['__SCHOOL__ s','s.id = c.school_id'],
            ['__CANTEEN__ cn','cn.id = c.canteen_id','left'],
        ];
        $field = 'c.*,u.user_nickname as user_name,s.name as s_name';
        $data = $model->alias('c')->join($join)->where('c.status',1)->where('c.object_id',0)->field($field)->paginate(10);
        $data ->appends($param);
        $list = $data->toArray()['data'];
        if (!empty($list)) {
            $join = [ 
                ['__USER__ u', 'c.user_id = u.id'],  
                ['__USER__ tu', 'c.to_user_id = tu.id','left'], 
            ];
            $c_where = ['c.status'=>['eq',1] ];
            foreach ($list as $k => $v) {
                $list[$k]['c_count'] = 0; //初始化 
                $list[$k]['c_list'] = [];
                $c_where['c.object_id'] = $v['id'];
                $c_list = db::name('comment')->alias('c')->field('c.*,u.user_nickname,u.avatar,tu.user_nickname as to_user_nickname')->join($join)->where($c_where)->select()->toArray();
                if (!empty($c_list)) {  //人员
                    $list[$k]['c_count'] = count($c_list);
                    $list[$k]['c_list'] = $c_list;
                }
            }
            $this->assign('list', $list);
            return $this->fetch();
        }
         
    }

    public function add() {
        return $this->fetch();
    }

    public function addPost() {
        if ($this->request->isPost()) {
            $post = $this->request->param();  //提交的信息
            $post['user_id'] = $this->getUserId();  //用户id
            $post['school_id'] = $this->schoolId;  //学校id
            $post['canteen_id'] = $this->canteenId;  //食堂id
            $post['create_time'] = time();
            $post['msg'] = (isset($post['msg']) and !empty($post['msg']))?trim($post['msg']):'';
            if (empty($post['msg'])) {
                $this->error('请输入留言内容');
            }

            $model = new CommentModel();
            $res = $model->allowField(true)->data($post, true)->isUpdate(false)->save();
            if ($res !== false) {
                $this->success("新增成功",cmf_url('comment/index') );
            } else {
                $this->error('新增无效');
            }
        }
    }

    public function commentPost() {
        if ($this->request->isPost()) {
            $post = $this->request->param();  //提交的信息
            $post['user_id'] = $this->getUserId();  //用户id
            $post['school_id'] = $this->schoolId;  //学校id
            $post['create_time'] = time();
            $post['status'] = 1;
            $msg = (isset($post['msg']) and !empty($post['msg']))?trim($post['msg']):'';
            if (empty($msg)) {
                $this->error('请输入评论内容');
            }

            $model = new CommentModel();
            $res = $model->allowField(true)->data($post, true)->isUpdate(false)->save();

            if ($res !== false) {
                $this->success("评论成功",'',['user_nickname'=>$this->userInfo['user_nickname'],'msg'=>$msg,'parent_id'=>$post['user_id'],'id'=>$res] );
            } else {
                $this->error('评论无效');
            }
        }
    }

    public function commentDelete() {
        if ($this->request->isPost()) {
            $post = $this->request->param();  //提交的信息
            $post['user_id'] = $this->getUserId();  //用户id
            $post['school_id'] = $this->schoolId;  //学校id

            $comment = new CommentModel();
            $res = $comment->where($post)->delete();

            if ($res !== false) {
                $this->success("删除成功",'',['id'=>$post['id'] ]  );
            } else {
                $this->error('删除无效');
            }
        }
    }

}
