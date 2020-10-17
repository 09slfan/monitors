<?php

namespace app\chat\controller;

use cmf\controller\AdminBaseController;
use app\chat\model\SchoolObjectModel;
use app\chat\model\SchoolChatModel;

use app\chat\service\ChatService;

use think\Db;

/**
 * 留言管理
 * Class OrderController
 */
class AdminIndexController extends AdminBaseController {

    /**
     * 消息管理列表
     * @return \think\Response
     */
    public function index() {
        $param = $this->request->param();
        $this->assign('param',$param);

        $service = new ChatService();
        $data = $service->postList($param);
        $data->appends($param);

        $this->assign('data',$data);
        $this->assign('page', $data->render());

        $school = db::name('school')->where('status',1)->select();
        $this->assign('school', $school);

        return $this->fetch();
    }

    public function add() {
        return $this->fetch();
    }

    public function addPost() {
        if ($this->request->isPost()) {
            $post = $this->request->post();  //获取post参数
            if (!empty($post)) {
                $param = $post['post'];
                if (!isset($param['school_id']) or empty($param['school_id'])) {
                    $this->error('请选择发送学校对象');
                }
                if (!isset($param['title']) or empty($param['title'])) {
                    $this->error('请输入消息标题');
                }

                $data = ['school_id'=>$param['school_id'],'title'=>$param['title'],'create_time'=>time(),'status'=>1 ];
                if (isset($param['msg']) and !empty($param['msg'])) {
                    $data['msg'] = trim($param['msg']);
                }
                $model = new SchoolObjectModel();
                
                $model->adminAdd($data);
                $this->success("发送成功！", url("adminIndex/index"));
            } else {
                $this->error('操作失败');
            }
        } else {
            $this->error('操作失败');
        }
    }

    public function check() {
        $param = $this->request->param();
        if (isset($param['id']) and !empty($param['id'])) {
            $id = $param['id'];
            $service = new ChatService();
            $post = $service->published($id);
            
            $this->assign('post',$post);
            return $this->fetch();
        }
    }

    public function delete() {
        $param = $this->request->param();
        $model = new SchoolObjectModel();

        if (isset($param['id'])) {
            $id = $this->request->param('id', 0, 'intval');
            $resultFood = $model
                ->where('id', $id)
                ->update(['status' =>0]);
            db::name('schoolChat')->where('object_id',$id)->update(['status'=>0]);
            $this->success("删除成功！", '');
        }
        if (isset($param['ids'])) {
            $ids     = $this->request->param('ids/a');
            $result  = $model->where('id', 'in', $ids)->update(['status' => 0]);
            db::name('schoolChat')->where('object_id','in', $ids)->update(['status'=>0]);
            $this->success("删除成功！", '');
        }
    }

    public function chat() {
        $param = $this->request->param();
        $this->assign('param',$param);
        $service = new ChatService();
        $data = $service->chatList($param);

        $data->appends($param);

        $status = get_status(52);
        $this->assign('status',$status);

        $this->assign('data',$data);
        $this->assign('page', $data->render());

        $school = db::name('school')->where('status',1)->select();
        $this->assign('school', $school);

        return $this->fetch();
    }

    public function info() {
        $param = $this->request->param();
        if (empty($param['id'])) { 
            $this->error('无效的留言id');
        }
        $service = new ChatService();
        $post = $service->chatInfo($param['id']);

        //已读
        db::name('schoolChat')->where('id',$param['id'])->update(['is_read'=>1]);

        $this->assign('param',$param);
        $this->assign('post',$post);
        return $this->fetch();
    }

    public function infoPost() {
        if ($this->request->isPost()) {
            $param = $this->request->post();  //获取post参数
            $id = $param['id'];
            if (!empty($id)) {
                $info = Db::name('schoolChat')->where('id',$id)->find();
                $adminId = cmf_get_current_admin_id();
                $data = [ 'school_id'=>$info['school_id'],'object_id'=>$info['object_id'],'user_id'=>$adminId,'to_user_id'=>$info['user_id'],'msg'=>$param['msg'],'create_time'=>time(),'status'=>1 ];
                Db::name('schoolChat')->insert($data);
                $this->success("添加成功！", url("adminIndex/chat"));
            } else {
                $this->error('操作失败');
            }
        } else {
            $this->error('操作失败');
        }
    }

    public function ban() {
        $param = $this->request->param();
        $model = new SchoolChatModel();

        if (isset($param['id'])) {
            $id = $this->request->param('id', 0, 'intval');
            $resultFood = $model
                ->where('id', $id)
                ->update(['status' =>0]);
            $this->success("删除成功！", '');
        }
        if (isset($param['ids'])) {
            $ids     = $this->request->param('ids/a');
            $result  = $model->where('id', 'in', $ids)->update(['status' => 0]);
            if ($result) {
                $this->success("删除成功！", '');
            }
        }
    }

 
}
