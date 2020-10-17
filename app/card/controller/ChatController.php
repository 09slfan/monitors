<?php
namespace app\card\controller;

use cmf\controller\CardBaseController;
use app\chat\model\SchoolObjectModel;
use app\chat\model\SchoolChatModel;
use app\user\model\CommentModel;

use app\chat\service\ChatService;

use think\Db;

class ChatController extends CardBaseController {

    public function index() {
        $param = $this->request->param();
        $userId = $this->userId;
        $this->assign('userId', $userId);

        $userInfo = $this->userInfo;
        if ($userInfo['user_cate']==4) {  //行政人员
            $cate_param = ['province'=>$userInfo['province'],'city'=>$userInfo['city'],'district'=>$userInfo['district'],];
            $param = array_merge($cate_param,$param);
            $this->getSchoolAndCanteen($param);
        }
        
        return $this->fetch();
    }

    public function ajaxlist() {
        $param = $this->request->param();
        $service = new ChatService();
        $school_id = $this->schoolId;
        $user_id = $this->userId;
        $param['user_id'] = !empty($user_id)?$user_id:0;

        $userInfo = $this->userInfo;
        if ($userInfo['user_cate']==4) {  //行政人员
            $cate_param = ['province'=>$userInfo['province'],'city'=>$userInfo['city'],'district'=>$userInfo['district'],];
            $param = array_merge($cate_param,$param);
            $this->getSchoolAndCanteen($param);
        } else {
            $param['school_id'] = !empty($school_id)?$school_id:0;
        }
        $data = $service->postList($param);
        $data ->appends($param);
        $list = $data->toArray()['data'];
        if (!empty($list)) {
            //$list = $service->getComments($list);  //评论
            $this->assign('list', $list);
            return $this->fetch();
        }
         
    }

    public function info() {
        $id = $this->request->param('id', 0, 'intval');
        // session('userId',$this->userId);
        $service = new ChatService();
        $res = $service->published($id);
        $res = $service->getComments($res);
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

            $service = new ChatService();
            $res = $service->postComment($post);

            if ($res !== false) {
                $this->success("评论成功",'',['user_nickname'=>$this->userInfo['user_nickname'],'msg'=>$post['msg'],'parent_id'=>$post['user_id'],'id'=>$res] );
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

            $service = new ChatService();
            $res = $service->delComment($post);

            if ($res !== false) {
                $this->success("删除成功",'',['id'=>$post['id'] ]  );
            } else {
                $this->error('删除无效');
            }
        }
    }

}
