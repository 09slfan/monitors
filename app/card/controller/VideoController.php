<?php
namespace app\card\controller;

use cmf\controller\CardBaseController;
use app\video\model\VideoModel;
use app\organization\model\SchoolModel;
use app\organization\model\CanteenModel;
use think\Db;

class VideoController extends CardBaseController {

    public function index() {
        $param = $this->request->param();
        $html = 'index';
        $s_c = [];
        $userInfo = $this->userInfo;
        if ($userInfo['user_cate']==4) {  //行政人员
            $cate_param = ['province'=>$userInfo['province'],'city'=>$userInfo['city'],'district'=>$userInfo['district'],];
            $param = array_merge($cate_param,$param);
            $s_c = $this->getSchoolAndCanteen($param);
            $this->getStaff($param);
            $html = 'index4';
        }
        $this->assign('user', $userInfo);
        $this->assign('param', $param);

        return $this->fetch($html);
    }

    public function ajaxlist() {
        $param = $this->request->param();
        $model = new VideoModel();
        // $param['type'] = 2;
        $school_id = $this->schoolId;
        $user_id = $this->userId;
        $join = [
            ['__CANTEEN__ c','c.id=v.canteen_id'],
            ['__SCHOOL__ s','s.id=c.school_id'],
        ];
        if (isset($param['cid']) and !empty($param['cid'])) {
            $model = $model->where('v.canteen_id',$param['cid']);
        }
        $field = 'v.*,c.name as c_name,s.name as s_name';
        $list = $model->alias('v')->join($join)->field($field)
            ->where('v.school_id',$school_id)->where('v.status',1)
            ->order('v.id desc')->paginate(10);
        $list ->appends($param);
        $this->assign('list', $list);
        return $this->fetch();
    }

    public function cIndex() {
        $param = $this->request->param();

        $user_id = $this->userId;
        $userInfo = $this->getUserInfo();  // 获取用户信息
        if ($userInfo['user_cate'] == 3 && $userInfo['is_vip'] == 0) {  //家长
            $time = time();
            $count = db::name('userVip')->where('user_id',$user_id)->where('begin_time','lt',$time)->where('end_time','gt',$time)->where('status',1)->count();
            if(empty($count)) {
                $this->error('请先购买会员视频套餐！',url('card/pay/index'));
            }
        }

        $s_c = [];
        if ($userInfo['user_cate']==4) {  //行政人员
            $cate_param = ['province'=>$userInfo['province'],'city'=>$userInfo['city'],'district'=>$userInfo['district'],];
            $param = array_merge($cate_param,$param);
            $s_c = $this->getSchoolAndCanteen($param);
        }

        $school_id = $this->schoolId;
        if (!empty($s_c)) {
            if (!empty($s_c['school_id'])) {
                $school_id = $s_c['school_id'];
            } else {
                $school_id = $s_c['school_ids'];
            }
        }

        $model = new CanteenModel();
        $cid = 0;
        $selected = [];
        if (isset($param['cid']) and !empty($param['cid']) ) {
            $cid = $param['cid'];
            $selected[] = $param['cid'];
        }
        $tpl = "<option \$selected value='\$id'>\$spacer \$name</option>";
        $canteen_tree = $model->adminCategoryTableTree($selected,$school_id,$tpl);

        $this->assign('cid', $cid);
        $this->assign('canteen_tree', $canteen_tree);
        return $this->fetch();
    }

    public function cIndex4() {
        $param = $this->request->param();
        $html = 'c_index';
        
        $userInfo = $this->userInfo;
        if ($userInfo['user_cate']==4) {  //行政人员
            $cate_param = ['province_id'=>$userInfo['province'],'city_id'=>$userInfo['city'],'district_id'=>$userInfo['district'],'province'=>$userInfo['province'],'city'=>$userInfo['city'],'district'=>$userInfo['district'],];
            $param = array_merge($cate_param,$param);
            $this->getSchoolAndCanteen($param);
            $this->getStaff($param);
            $html = 'c_index4';
        }
        $this->assign('user', $userInfo);
        $this->assign('param', $param);

        return $this->fetch($html);
    }

    public function cAjaxlist() {
        $param = $this->request->param();
        $model = new VideoModel();
        $where = ['v.status'=>1];
        $user_id = $this->userId;
        $userInfo = $this->getUserInfo();  // 获取用户信息
        $s_c = [];
        if ($userInfo['user_cate']==4) {  //行政人员
            $cate_param = ['province'=>$userInfo['province'],'city'=>$userInfo['city'],'district'=>$userInfo['district'],];
            $param = array_merge($cate_param,$param);
            $s_c = $this->getSchoolAndCanteen($param);
            $is_monitor = true;
        } else {
            $is_monitor = false;
        }

        $school_id = $this->schoolId;
        if (!empty($s_c)) {
            if (!empty($s_c['school_id'])) {
                $school_id = $s_c['school_id'];
            } else {
                $school_id = $s_c['school_ids'];
            }
        }
        if (isset($param['school_id']) and !empty($param['school_id'])) {
            $where['v.school_id'] = $param['school_id'];
        }
        if (isset($param['cid']) and !empty($param['cid'])) {
            $where['v.canteen_id'] = $param['cid'];
        }
        if (isset($param['province']) and !empty($param['province'])) {
            $where['s.province'] = $param['province'];
        }
        if (isset($param['city']) and !empty($param['city'])) {
            $where['s.city'] = $param['city'];
        }
        if (isset($param['district']) and !empty($param['district'])) {
            $where['s.district'] = $param['district'];
        }
        
        $join = [
            ['__CANTEEN__ c','c.id=v.canteen_id'],
            ['__SCHOOL__ s','s.id=c.school_id'],
        ];
        $today = date('Y-m-d');
        $now = time();
        $field = 'v.*,c.name as c_name,s.name as s_name';
        $model = $model->alias('v')->join($join)->field($field)->where($where)->where('v.school_id','in',$school_id)->where('c.status','1')->where('s.status','1');
        if (isset($param['keyword']) and !empty($param['keyword'])) {
            $keyword = trim($param['keyword']);
            $model = $model->where('c.name', 'like', "%$keyword%");
        }
        $list = $model->order('v.id desc')->paginate(10);
        $list ->appends($param);

        $list = $list->toArray()['data'];
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                if (!empty($v['begin_time'])) {
                    $time = $today. ' '. $v['begin_time'];
                    $b_time = strtotime($time);
                    if ($now<$b_time) {
                        unset($list[$k]);
                    }
                }
                
                if (!empty($v['end_time'])) {
                    $time = $today. ' '. $v['end_time'];
                    $e_time = strtotime($time);
                    if ($now>$e_time) {
                        unset($list[$k]);
                    }
                }
                $list[$k]['play'] = '回放';
            }
        }

        $this->assign('is_monitor', $is_monitor);
        $this->assign('list', $list);
        return $this->fetch();
    }

    public function record() {
        $vid = $this->request->param('vid',0);
        if (empty($vid)) {
            return '';
        }
        $user_id = $this->userId;
        $userInfo = $this->getUserInfo();  // 获取用户信息
        $s_c = [];
        if ($userInfo['user_cate']==4) {  //行政人员
            $post = db::name('video')->where('id',$vid)->find();
            $this->assign('post', $post);
            return $this->fetch();
        } else {   //非行政人员不可使用
            return '';
        }
        
    }

    // 据说会有只留三天的说法
    public function recordList() {
        //$record_url = config('record_url');
        $param = $this->request->param();
        if (!isset($param['stream'])) {
            return '';
        }
        $model = db::name('videoRecord');
        $time = time();
        $y=date("Y",$time );
        $m=date("m",$time );
        $d=date("d",$time );
        $today=mktime(0,0,0,$m,$d,$y);        // 创建本天开始时间
        $begin = $today-86400*3;
        $where = ['v.status'=>1,'v.stream'=>$param['stream'] ];
        $field = 'v.*';
        $list = $model->alias('v')->field($field)->where($where)->where('start_time','gt',$begin)
            ->order('v.id desc')->paginate(16);
        $list ->appends($param);
        $list = $list->toArray()['data'];
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $list[$k]['title'] = '视频 '.date('y-m-d H:i',$v['start_time']).' 至 '.date('y-m-d H:i',$v['stop_time']);
                //$url = '';
            }
        }

        $this->assign('list', $list);
        return $this->fetch();
    }

    public function rInfo() {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) {
            $this->error('参数错误');
        }
        $model = db::name('videoRecord');
        $field = 'v.*';
        $res = $model->alias('v')->field($field)->where('v.id',$id)->find();
        if (!empty($res)) {
            $record_url = config('record_url');
            $res['url'] = $record_url.$res['uri'];
            $res['title'] = date('y-m-d H:i',$res['start_time']).' 至 '.date('y-m-d H:i',$res['stop_time']);
        }
        $this->assign('res', $res);

        $user_id = $this->userId;
        $this->assign('user_id', $user_id);  //自身id
        return $this->fetch();
    }

    public function info() {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) {
            $school_id = $this->schoolId;
            //$userId = $this->userId;
            if (empty($school_id)) {
                $this->error('参数错误');
            }
        }
        $model = new VideoModel();
        $join = [
            ['__CANTEEN__ c','c.id=v.canteen_id'],
            ['__SCHOOL__ s','s.id=c.school_id'],
        ];
        $field = 'v.*,c.name as c_name,s.name as s_name';
        $res = $model->alias('v')->join($join)->field($field)->where('v.id',$id)->where('v.school_id',$school_id)->find();
        $this->assign('res', $res);

        $user_id = $this->userId;
        $this->assign('user_id', $user_id);  //自身id
        return $this->fetch();
    }

}
