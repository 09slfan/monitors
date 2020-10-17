<?php
// +----------------------------------------------------------------------
// | zhiliang weight
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019.
// +----------------------------------------------------------------------
// | Author: 小艾.pino
// +----------------------------------------------------------------------

namespace app\user\controller;

use cmf\controller\AdminBaseController;
use app\user\model\UserPackageModel;
use app\user\model\UserPackageDetailModel;
use think\Db;
use think\db\Query;

/**
 * Class AdminPackageController
 * @package app\user\controller
 *
 * @adminMenuRoot(
 *     'name'   =>'用户购买套餐管理',
 *     'action' =>'default',
 *     'parent' =>'',
 *     'display'=> true,
 *     'order'  => 10,
 *     'icon'   =>'group',
 *     'remark' =>'用户购买套餐管理'
 * )
 *
 * @adminMenuRoot(
 *     'name'   =>'用户组',
 *     'action' =>'default1',
 *     'parent' =>'user/AdminPackage/default',
 *     'display'=> true,
 *     'order'  => 10000,
 *     'icon'   =>'',
 *     'remark' =>'用户组'
 * )
 */
class AdminPackageController extends AdminBaseController {

    /**
     * 后台用户购买套餐列表
     * @adminMenu(
     *     'name'   => '用户购买套餐',
     *     'parent' => 'default1',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '本站用户',
     *     'param'  => ''
     * )
     */
    public function index() {
        $join = [
            ['__USER__ u','u.id = up.user_id'],
            ['__USER__ u2','u2.id = up.doctor_id','left'],
            ['__PACKAGE_POST__ p','p.id = up.package_id'],
        ];
        $where = ['u.user_type'=>2];
        $field = 'up.*,u.mobile,u.user_nickname,u2.user_nickname as doctor_name,p.post_title,p.money,p.period';
        $model = new UserPackageModel();

        $list = $model->alias('up')->join($join)->field($field)
            ->where(function (Query $query) {
                $param = $this->request->param();
                $startTime = empty($param['start_time']) ? 0 : strtotime($param['start_time']);
                $endTime   = empty($param['end_time']) ? 0 : strtotime($param['end_time']);
                if (!empty($param['uid'])) {
                    $query->where('u.id', intval($param['uid']));
                }
                if (!empty($startTime)) {
                    $query->where('up.begin_time', '>=', $startTime);
                }
                if (!empty($endTime)) {
                    $query->where('up.end_time', '<=', $endTime);
                }

                if (!empty($param['keyword'])) {
                    $keyword = $param['keyword'];
                    $query->where('u.user_login|u.user_nickname|u.mobile', 'like', "%$keyword%");
                }
            })
            ->where($where)
            ->order("up.create_time DESC")
            ->paginate(10);
        // 获取分页显示
        $page = $list->render();

        $package_status = get_status(11);
        $this->assign('package_status',$package_status);

        $this->assign('list', $list);
        $this->assign('page', $page);
        // 渲染模板输出
        return $this->fetch();
    }

    public function info() {
        $param = $this->request->param();
        $where = [];
        if (empty($param['id'])) {
            $this->error('传参有误');
        }
        $where['up.id'] = $param['id'];
        if (!isset($param['month']) or empty($param['month'])) {
            $param['month'] = date("Y-n");
        }
        $model = new UserPackageModel();  
        $join = [
            ['__USER__ u','u.id = up.user_id'],
            ['__USER__ u2','u2.id = up.doctor_id','left'],
            ['__PACKAGE_POST__ p','p.id = up.package_id'],
        ];
        $field = 'up.*,u.mobile,u.user_nickname,u2.user_nickname as doctor_name,p.post_title,p.money,p.period,up.package_id';
        //使用套餐详情
        $post = $model->alias('up')->where($where)->field($field)->join($join)->find();
        $this->assign('post', $post);

        $user_id = $post['user_id'];  //用户id

        $package_status = get_status(11);  //分类状态
        $this->assign('package_status',$package_status);

        //每日打卡详情
        $d_field = 'upd.*,u.user_nickname,pt.name as type_name,upds.sum_pic';
        $detail = db::name('user_package_detail')->alias('upd')->join('__USER__ u','u.id = upd.user_id')->join('__PACKAGE_TYPE__ pt','pt.id = upd.type')->join('__USER_PACKAGE_DETAIL_SUM__ upds','upds.year = upd.year and upds.month = upd.month and upds.day = upd.day','left')->where('upd.user_package_id',$param['id'])->field($d_field)->paginate(10);
        $page = $detail->render();
        $this->assign('detail', $detail);
        $this->assign('page', $page);
        $this->assign('param', $param);

        // 渲染模板输出
        return $this->fetch();

    }

}
