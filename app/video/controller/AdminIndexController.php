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
class AdminIndexController extends AdminBaseController {
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
            ->where('v.status','eq',1)->field($field)->order($order)->paginate(10);
        $data->appends($param);

        $status = get_status(101);
        $this->assign('status',$status);

        $this->assign('data',$data);
        $this->assign('page', $data->render());

        $school = db::name('school')->where('status',1)->select();
        $this->assign('school', $school);
        $c_model = db::name('canteen')->where('status',1);
        if (!empty($param['school_id'])) {
            $c_model = $c_model->where('school_id',$param['school_id']);
        }
        $canteen = $c_model->select();
        $this->assign('canteen', $canteen);

        $this->getStaff($param);

        return $this->fetch();
    }

}
