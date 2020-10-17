<?php
// +----------------------------------------------------------------------
// | zhiliang weight
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019.
// +----------------------------------------------------------------------
// | Author: 小艾.pino
// +----------------------------------------------------------------------
namespace app\organization\service;

use app\organization\model\PortalPostModel;
use think\Db;
use think\db\Query;
use app\organization\model\SchoolModel;
use app\organization\model\CanteenModel;
use app\organization\model\SchoolTypeModel;

class PostService {

    public function postList($param, $isPage = false) {
        if (empty($param['order'])) { $order = 's.id desc'; }
        //产品列表
        $model = new SchoolModel();
        $join = [
            ['__SCHOOL_TYPE__ st','st.id=s.school_type'],
            ['__REGION__ r1','r1.id=s.province'],
            ['__REGION__ r2','r2.id=s.city'],
            ['__REGION__ r3','r3.id=s.district'],
        ];
        $field = 's.*,st.name as type_name,r1.name as p_name,r2.name as c_name,r3.name as d_name';
        $data = $model->alias('s')->join($join)
            ->where(function (Query $query) use ($param) {
                if (!empty($param['keyword'])) {
                    $keyword = $param['keyword'];
                    $query->where('s.name|s.contact|s.mobile', 'like', "%$keyword%");
                }
                if (!empty($param['school_id'])) {
                    $id = $param['school_id'];
                    $query->where('s.id', 'eq', $id);
                }
                if (!empty($param['status'])) {
                    $status = $param['status'];
                    $query->where('s.status', 'eq', $status);
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
            ->where('s.status','neq',0)->field($field)->order($order)->paginate(10);

        return $data;

    }

    public function published($postId) {
        $join = [
            ['__REGION__ r1','r1.id=s.province'],
            ['__REGION__ r2','r2.id=s.city'],
            ['__REGION__ r3','r3.id=s.district'],
        ];
        $field = 's.*,r1.name as p_name,r2.name as c_name,r3.name as d_name';

        $organizationModel = new SchoolModel();
        $post = $organizationModel->alias('s')
            ->join($join)->field($field)
            ->where('s.id', $postId)->find();

        return $post;
    }

    public function canteenList($param) {
        if (empty($param['order'])) { $order = 'c.id desc'; }
        //产品列表
        $model = new CanteenModel();
        $join = [
            ['__SCHOOL__ s','s.id=c.school_id'],
            ['__CANTEEN_LEVEL__ cl','cl.id=c.level','left'],
            ['__REGION__ r1','r1.id=s.province','left'],
            ['__REGION__ r2','r2.id=s.city','left'],
            ['__REGION__ r3','r3.id=s.district','left'],
        ];
        $field = 'c.*,cl.name as level_name,s.name as s_name,r1.name as p_name,r2.name as c_name,r3.name as d_name';
        $data = $model->alias('c')->join($join)
            ->where(function (Query $query) use ($param) {
                $query->where('s.status', 'gt', 0);
                if (!empty($param['keyword'])) {
                    $keyword = $param['keyword'];
                    $query->where('c.name|c.contact|c.mobile', 'like', "%$keyword%");
                }
                if (!empty($param['school_id'])) {
                    $id = $param['school_id'];
                    $query->where('s.id', 'eq', $id);
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
            ->where('c.status','neq',0)->field($field)->order($order)->paginate(10);

        return $data;
    }

    public function canteenInfo($postId) {
        $join = [
            ['__SCHOOL__ s','s.id=c.school_id'],
            ['__CANTEEN_LEVEL__ cl','cl.id=c.level','left'],
            ['__REGION__ r1','r1.id=s.province','left'],
            ['__REGION__ r2','r2.id=s.city','left'],
            ['__REGION__ r3','r3.id=s.district','left'],
        ];
        $field = 'c.*,cl.name as level_name,s.name as s_name,s.address,r1.name as p_name,r2.name as c_name,r3.name as d_name';

        $organizationModel = new CanteenModel();
        $post = $organizationModel->alias('c')
            ->join($join)->field($field)
            ->where('c.id', $postId)->find();
        $post['supplier'] = $post['staff'] = [];
        if (!empty($post)) {
            $post['staff'] = db::name('canteenStaff')->alias('cs')->join('__CANTEEN_JOB__ cj','cj.id=cs.job','left')->where('cs.canteen_id',$post['id'])->where('cs.school_id',$post['school_id'])->where('cs.status',1)->field('cs.*,cj.name as job_name')->select();
            $post['supplier'] = db::name('canteenSupplier')->alias('cs')->where('cs.canteen_id',$post['id'])->where('cs.school_id',$post['school_id'])->where('cs.status',1)->field('cs.*')->select();
        }

        return $post;
    }
}