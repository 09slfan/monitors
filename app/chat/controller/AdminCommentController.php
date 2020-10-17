<?php

namespace app\chat\controller;

use cmf\controller\AdminBaseController;
use app\user\model\CommentModel;

use think\Db;

/**
 * 留言板管理
 * Class OrderController
 */
class AdminCommentController extends AdminBaseController {

    /**
     * 显示资源列表
     * @return \think\Response
     */
    public function index() {
        $param = $this->request->param();
        $model = new CommentModel();
        $model = $model->alias('c');
        $where = ['s.status'=>1];

        if (!empty($param['status'])) { 
            $where['c.status'] = $param['status']; 
        }
        if (!empty($param['order'])) { 
            $order = $param['order'];
        } else {
            $order = 'c.id desc'; 
        }
        if (!empty($param['user_id']) and !empty($param['to_user_id'])) { 
            $where['c.user_id|c.to_user_id'] = $param['user_id'];
        } elseif (!empty($param['user_id'])) {
            $where['c.user_id'] = $param['user_id'];
        } elseif (!empty($param['to_user_id'])) { 
            $where['c.to_user_id'] = $param['to_user_id']; 
        }
        if (isset($param['keyword']) and !empty($param['keyword'])) { 
            $model = $model->where('c.msg|u.user_nickname|u2.user_nickname', 'like', '%'.$param['keyword'].'%');
        }
        $this->assign('param',$param);

        //留言列表
        $startTime = empty($param['start_time']) ? 0 : strtotime($param['start_time']);
        $endTime   = empty($param['end_time']) ? 0 : strtotime($param['end_time']);
        if (!empty($startTime)) {
            $model->where('c.create_time', '>=', $startTime);
        }
        if (!empty($endTime)) {
            $model->where('c.create_time', '<=', $endTime);
        }

        $join = [
            ['__USER__ u','u.id = c.user_id','left'],
            ['__USER__ u2','u2.id = c.to_user_id','left'],
            ['__SCHOOL__ s','s.id = c.school_id'],
        ];
        $field = 'c.*,s.name as s_name,u.user_nickname as user_name1,u2.user_nickname as user_name2';
        $data = $model->join($join)->where($where)->where('c.status','gt',0)->field($field)->order('c.id desc')->paginate(10);
        $data->appends($param);

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
        $model = new CommentModel();
        $join = [
            ['__USER__ u','u.id = c.user_id'],
            ['__USER__ u2','u2.id = c.to_user_id'],
            ['__SCHOOL__ s','s.id = c.school_id'],
        ];
        $field = 'c.*,s.name as s_name,u.user_nickname as user_name1,u2.user_nickname as user_name2';
        $model = $model->alias('c')->join($join)->field($field)->where('c.id',$param['id']);
        $post = $model->find();
        $this->assign('param',$param);
        $this->assign('post',$post);
        return $this->fetch();
    }

    public function infoDel() {
        $param = $this->request->param();  //获取参数
        if (!empty($param)) {
            $id = $param['id'];
            if (!empty($id)) {
                Db::name('comment')->where('id',$id)->update(['status'=>0,'delete_time'=>time()]);
                $this->success("删除成功！", url("adminComment/index"));
            } else {
                $this->error('操作失败');
            }
        } else {
            $this->error('操作失败');
        }
    }

 
}
