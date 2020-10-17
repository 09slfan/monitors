<?php

namespace app\finance\controller;

use cmf\controller\AdminBaseController;
use app\user\model\UserVipModel;

use think\Db;
use think\db\Query;

/**
 * 会员购买管理
 * Class OrderController
 */
class AdminIndexController extends AdminBaseController {

    public function user() {
        $this->isExpiredVip();  //判断是否有过期会员

        $param = $this->request->param();
        if (empty($param['school_id'])) { $param['school_id'] = '0'; }
        if (empty($param['canteen_id'])) { $param['canteen_id'] = '0'; }
        if (empty($param['keyword'])) { $param['keyword'] = ''; }
        if (empty($param['province'])) { $param['province'] = '0'; }
        if (empty($param['city'])) { $param['city'] = '0'; }
        if (empty($param['district'])) { $param['district'] = '0'; }
        $this->assign('param',$param);

        $where = ['u.user_status'=>1,'u.user_type'=>2,'u.user_cate'=>3,];
        if (isset($param['order']) and !empty($param['order'])) { 
            $order = $param['order']; 
        } else {
            $order = 'uv.status asc,uv.id desc'; 
        }
        $join = [
            ['__USER__ u','u.id=uv.user_id'],
            ['__SCHOOL__ s','s.id=u.school_id'],
        ];
        $field = 'uv.*,u.user_login,u.user_nickname,s.name as s_name';

        //订单列表
        $model = new UserVipModel();
        $data = $model->alias('uv')->join($join)
            ->where(function (Query $query) use ($param) {
                $query->where('s.status', 'gt', 0);
                if (!empty($param['keyword'])) {
                    $keyword = $param['keyword'];
                    $query->where('u.user_login|u.user_nickname', 'like', "%$keyword%");
                }
                if (!empty($param['school_id'])) {
                    $province = $param['school_id'];
                    $query->where('u.school_id', 'eq', $province);
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
            ->where($where)->field($field)->order($order)->paginate(10);
        $data->appends($param);

        $status = get_status(13);
        $this->assign('status',$status);

        $this->assign('data',$data);
        $this->assign('page',$data->render());

        $school = db::name('school')->where('status',1)->select();
        $this->assign('school', $school);

        $this->getStaff($param);

        return $this->fetch();
    }

    public function order() {
        $param = $this->request->param();
        $where = ['u.user_status'=>1,'u.user_type'=>2,'u.user_cate'=>3];
        if (isset($param['user_id']) and !empty($param['user_id'])) {
            $where['o.user_id'] = $param['user_id'];
        }
        if (isset($param['school_id']) and !empty($param['school_id'])) {
            $where['u.school_id'] = $param['school_id'];
        }
        if (isset($param['order']) and !empty($param['order'])) { 
            $order = $param['order']; 
        } else {
            $order = 'o.id desc'; 
        }
        $join = [
            ['__USER__ u','u.id=o.user_id'],
            //['__PACKAGE_POST__ p','p.id=o.package_id'],
        ];
        $field = 'o.*,u.user_nickname,u.user_login';

        //订单列表
        $model = db::name('order');
        $data = $model->alias('o')->join($join)->where($where)->field($field)->order($order)->select();

        $status = get_status(12);
        $this->assign('status',$status);

        $this->assign('data',$data);

        return $this->fetch();
    }

    // 对会员状态进行判断
    public function isExpiredVip() {
        $time = time();
        $data = db::name('userVip')->where('status',1)->where('end_time','lt',$time)->select();
        if (!empty($data)) {
            db::name('userVip')->where('status',1)->where('end_time','lt',$time)->update(['status'=>2]);
        }
        return true;
    }

    
}
