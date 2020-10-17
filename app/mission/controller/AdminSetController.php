<?php

namespace app\mission\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use think\db\Query;
use app\mission\model\MissionModel;
use app\organization\model\CanteenModel;

/**
 * 视频管理
 * Class ProductController
 */
class adminSetController extends AdminBaseController {
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index() {
        $param = $this->request->param();
        if (empty($param['school_id'])) { $param['school_id'] = '0'; }
        if (empty($param['keyword'])) { $param['keyword'] = ''; }
        if (empty($param['province'])) { $param['province'] = '0'; }
        if (empty($param['city'])) { $param['city'] = '0'; }
        if (empty($param['district'])) { $param['district'] = '0'; }
        if (empty($param['order'])) { $order = 'c.id desc'; }
        $this->assign('param',$param);

        //产品列表
        $model = new CanteenModel();
        $join = [
            ['__SCHOOL__ s','s.id=c.school_id'],
        ];
        $field = 'c.*,s.name as s_name';
        $data = $model->alias('c')->join($join)
            ->where(function (Query $query) use ($param) {
                $query->where('s.status', 'eq', 1);
                $query->where('c.status', 'eq', 1);
                if (!empty($param['keyword'])) {
                    $keyword = $param['keyword'];
                    $query->where('c.name', 'like', "%$keyword%");
                }
                if (!empty($param['school_id'])) {
                    $province = $param['school_id'];
                    $query->where('c.school_id', 'eq', $province);
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

    public function check() {
        $id = $this->request->param('id', 0, 'intval');
        $join = [
            ['__SCHOOL__ s','s.id=v.school_id'],
            ['__CANTEEN__ c','c.id=v.canteen_id'],
        ];
        $field = 'v.*,s.name as s_name,c.name as c_name';

        $missionModel = new MissionModel();
        $post = $missionModel->alias('v')
            ->join($join)->field($field)
            ->where('v.id', $id)->find();

        $this->assign('post', $post);
        return $this->fetch();
    }

    public function edit() {
        $id = $this->request->param('id', 0, 'intval');
        $organizationModel = new CanteenModel();
        $post = $organizationModel->alias('s')
            ->where('s.id', $id)->find();
        $school = db::name('school')->where('id','in',$post['school_id'])->column('name');
        $school_name = implode(',', $school);
        $this->assign('school_name', $school_name);
        $this->assign('post', $post);
        return $this->fetch();
    }

    public function editPost() {
        if ($this->request->isPost()) {
            $data = $this->request->param();

            $post   = $data['post'];
            $model = new CanteenModel();
            $model->adminEdit($post);

            $this->success('保存成功!', url('adminSet/index') );

        }
    }

}
