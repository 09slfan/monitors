<?php

namespace app\video\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use think\db\Query;
use app\video\model\VideoModel;

/**
 * 视频管理
 * Class ProductController
 */
class adminVideoController extends AdminBaseController {
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index() {
        $param = $this->request->param();
        if (empty($param['school_id'])) { $param['school_id'] = '0'; }
        if (empty($param['canteen_id'])) { $param['canteen_id'] = '0'; }
        if (empty($param['keyword'])) { $param['keyword'] = ''; }
        if (empty($param['province'])) { $param['province'] = '0'; }
        if (empty($param['city'])) { $param['city'] = '0'; }
        if (empty($param['district'])) { $param['district'] = '0'; }
        if (empty($param['order'])) { $order = 's.id desc'; }
        $this->assign('param',$param);

        //产品列表
        $model = new VideoModel();
        $join = [
            ['__CANTEEN__ c','c.id=v.canteen_id'],
            ['__SCHOOL__ s','s.id=c.school_id'],
        ];
        $field = 'v.*,c.name as c_name,s.name as s_name';
        $data = $model->alias('v')->join($join)
            ->where(function (Query $query) use ($param) {
                $query->where('s.status', 'eq', 1);
                $query->where('c.status', 'eq', 1);
                //$query->where('v.status', 'eq', 1);
                if (!empty($param['keyword'])) {
                    $keyword = $param['keyword'];
                    $query->where('v.name', 'like', "%$keyword%");
                }
                if (!empty($param['school_id'])) {
                    $province = $param['school_id'];
                    $query->where('c.school_id', 'eq', $province);
                }
                if (!empty($param['canteen_id'])) {
                    $province = $param['canteen_id'];
                    $query->where('v.canteen_id', 'eq', $province);
                }
                if (!empty($param['province'])) {
                    $province = $param['province'];
                    $query->where('s.province', 'eq', $province);
                }
                if (!empty($param['city'])) {
                    $city = $param['city'];
                    $query->where('s.city', 'eq', $city);
                }
                if (!empty($param['district'])) {
                    $district = $param['district'];
                    $query->where('s.district', 'eq', $district);
                }

            })
            ->field($field)->order($order)->paginate(10);  //->where('v.status','eq',1)
        $data->appends($param);

        $status = get_status(101);
        $this->assign('status',$status);

        $this->assign('data',$data);
        $this->assign('page', $data->render());

        $school = db::name('school')->where('status',1)->select();
        $this->assign('school', $school);

        $this->getStaff($param);

        return $this->fetch();
    }

    public function add() {
        // $type = db::name('schoolType')->where('status',1)->field('id,name')->select();
        // $this->assign('type',$type);
        return $this->fetch();
    }

    public function addPost() {
        if ($this->request->isPost()) {
            $data = $this->request->param();

            $data['post']['create_time'] = time();
            $data['post']['status'] = 1;
            $post = $data['post'];

            $result = $this->validate($post, 'Video');
            if ($result !== true) {
                $this->error($result);
            }
            if (isset($post['begin_time']) and isset($post['end_time']) and !empty($post['begin_time']) and !empty($post['end_time']) ) {
                $begin_time = strtotime($post['begin_time']);
                $end_time = strtotime($post['end_time']);
                if ($end_time<$begin_time) {
                    $this->error('结束时间须大于开始时间');
                }
            }

            $videoModel = new VideoModel();
            $videoModel->adminAdd($data['post']);

            $this->success('添加成功!', url('adminVideo/index') );
        }

    }

    public function check() {
        $id = $this->request->param('id', 0, 'intval');
        $join = [
            ['__SCHOOL__ s','s.id=v.school_id'],
            ['__CANTEEN__ c','c.id=v.canteen_id'],
        ];
        $field = 'v.*,s.name as s_name,c.name as c_name';

        $videoModel = new VideoModel();
        $post = $videoModel->alias('v')
            ->join($join)->field($field)
            ->where('v.id', $id)->find();

        $this->assign('post', $post);
        return $this->fetch();
    }

    public function edit() {
        $id = $this->request->param('id', 0, 'intval');
        $videoModel = new VideoModel();
        $post = $videoModel->alias('s')
            //->join($foin)->field($field)
            ->where('s.id', $id)->find();
        $school = db::name('school')->where('id','in',$post['school_id'])->column('name');
        $school_name = implode(',', $school);
        $this->assign('school_name', $school_name);
        $canteen = db::name('canteen')->where('id','in',$post['canteen_id'])->column('name');
        $canteen_name = implode(',', $canteen);
        $this->assign('canteen_name', $canteen_name);
        // if (!empty($post['begin_time'])) {
        //     $post['begin_time'] = date('Y-m-d H:i',strtotime($post['begin_time']));
        // }
        // if (!empty($post['end_time'])) {
        //     $post['end_time'] = date('Y-m-d H:i',strtotime($post['end_time']));
        // }

        $this->assign('post', $post);

        return $this->fetch();
    }

    public function editPost() {
        if ($this->request->isPost()) {
            $data = $this->request->param();

            $post   = $data['post'];
            $result = $this->validate($post, 'Video');
            if ($result !== true) {
                $this->error($result);
            }
            if (isset($post['begin_time']) and isset($post['end_time']) and !empty($post['begin_time']) and !empty($post['end_time']) ) {
                $begin_time = strtotime($post['begin_time']);
                $end_time = strtotime($post['end_time']);
                if ($end_time<$begin_time) {
                    $this->error('结束时间须大于开始时间');
                }
            }

            $model = new VideoModel();
            $model->adminEdit($post);

            $this->success('保存成功!', url('adminVideo/index') );

        }
    }

    public function delete() {
        $param          = $this->request->param();
        $videoModel     = new VideoModel();

        if (isset($param['id'])) {
            $id         = $this->request->param('id', 0, 'intval');
            $resultFood = $videoModel->where('id', $id)->delete();
            $this->success("删除成功！", '');

        }
        if (isset($param['ids'])) {
            $ids        = $this->request->param('ids/a');
            $result     = $videoModel->where('id', 'in', $ids)->delete();
            if ($result) {
                $this->success("删除成功！", '');
            }
        }
    }

    public function cancel() {
        $param          = $this->request->param();
        $videoModel     = new VideoModel();

        if (isset($param['id'])) {
            $id         = $this->request->param('id', 0, 'intval');
            $resultFood = $videoModel
                ->where('id', $id)
                ->update(['status' =>-1]);
            $this->success("关闭成功！", '');

        }
        if (isset($param['ids'])) {
            $ids        = $this->request->param('ids/a');
            $result     = $videoModel->where('id', 'in', $ids)->update(['status' => -1]);
            if ($result) {
                $this->success("关闭成功！", '');
            }
        }
    }

    public function open() {
        $param          = $this->request->param();
        $videoModel     = new VideoModel();

        if (isset($param['id'])) {
            $id         = $this->request->param('id', 0, 'intval');
            $resultFood = $videoModel
                ->where('id', $id)
                ->update(['status' =>1]);
            $this->success("开启成功！", '');

        }
        if (isset($param['ids'])) {
            $ids        = $this->request->param('ids/a');
            $result     = $videoModel->where('id', 'in', $ids)->update(['status' => 1]);
            if ($result) {
                $this->success("开启成功！", '');
            }
        }
    }


}
