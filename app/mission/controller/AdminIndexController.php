<?php

namespace app\mission\controller;

use cmf\controller\AdminBaseController;

use app\organization\model\SchoolModel;
use app\organization\model\CanteenModel;

use app\mission\model\MissionModel;
use app\mission\model\MissionFinishedModel;
use app\mission\model\MissionFoodModel;
use app\mission\model\MissionCheckModel;
use app\mission\model\MissionOtherModel;
use app\mission\model\MissionRecordModel;

use think\Db;
use think\db\Query;

/**
 * 任务管理
 * Class OrderController
 */
class AdminIndexController extends AdminBaseController {

    /**
     * 管理列表
     * @return \think\Response
     */
    public function index() {
        $param = $this->request->param();

        $model = new CanteenModel();
        $model = $model->alias('c');
        $where = ['c.status'=>1,'s.status'=>1];

        if (isset($param['order']) and !empty($param['order'])) { 
            $order = $param['order'];
        } else {
            $order = 'c.id desc'; 
        }
        if (isset($param['school_id']) and !empty($param['school_id'])) {
            $where['c.school_id'] = $param['school_id'];
        }
        
        //上传条件
        $m_model = db::name('mission');
        $m_field = 'id,name,create_time,status';
        $param['start_time'] = empty($param['start_time']) ? date("Y-m-d") : $param['start_time'];
        $m_where = [];
        if (!empty($param['start_time'])) {
            $days = explode('-', $param['start_time']);
            $m_where['year'] = $days[0];
            $m_where['month'] = $days[1];
            $m_where['day'] = $days[2];
        }
        $join = [
            ['__SCHOOL__ s','s.id = c.school_id'],
        ];
        $field = 'c.*,s.name as s_name';
        $data = $model->join($join)->where($where)->field($field)->order($order)->paginate(10);
        $data->appends($param);
        $this->assign('page', $data->render());
        $data = $data->toArray()['data'];
        $status = get_status(31);
        foreach ($data as $k => $v) {
            $data[$k]['m_exist'] = false;
            $data[$k]['m_time'] = '暂无信息';
            $data[$k]['m_status'] = '0';
            $data[$k]['m_status_name'] = '未上传';
            $mission = db::name('mission')->where($m_where)->where('canteen_id',$v['id'])->where('school_id',$v['school_id'])->field( $m_field )->find();
            if (!empty($mission)) {
                $data[$k]['m_id'] = $mission['id'];
                $data[$k]['m_exist'] = true;
                $data[$k]['m_time'] = tranTime($mission['create_time']);
                $data[$k]['m_status'] = $mission['status'];
                $data[$k]['m_status_name'] = $status[$mission['status']];
            }
        }

        $this->assign('data',$data);
        
        $school = db::name('school')->where('status',1)->select();
        $this->assign('school', $school);

        $this->assign('param',$param);
        return $this->fetch();
    }

    //发送公众号提醒
    public function sendMsg() {
        $param = $this->request->param(); //获取post参数
        if (!empty($param)) {
            $canteen_id = $param['id'];
             
            if (!empty($canteen_id)) {
                $arr = ['canteen_id'=>$canteen_id];
                $date = date('Y-m-d');
                $arr['arr'] = [
                    'first'=>['value'=>'您好，您收到一条今日上传提醒！'],
                    'keyword1'=>['value'=>'今日进货上传'],
                    'keyword2'=>['value'=>'请记得上传'.$date.'的进货信息'],
                    'keyword3'=>['value'=>'点击进入食安监管平台，进行今日进货上传信息的录入'],
                    'keyword4'=>['value'=>'来自食安监管平台的今日上传提醒']
                ];
                $res = send_msgs($arr);
                if (!empty($res)) {
                    $this->success("发送成功！");
                } else {
                    $this->error("食堂信息返回有误");
                }
                
            } else {
                $this->error('执行失败');
            }
        } else {
            $this->error('操作失败');
        }
    }

    public function check() {
        $param = $this->request->param();
        if (isset($param['id']) and !empty($param['id'])) {
            $model     = new MissionModel();
            $food      = new MissionFoodModel();
            $finished  = new MissionFinishedModel();
            $record    = new MissionRecordModel();
            $check     = new MissionCheckModel();
            $other     = new MissionOtherModel();

            $model = $model->alias('m');  //'m.status'=>1,
            $where = ['m.id'=>$param['id']];
            $join = [
                ['__CANTEEN__ c','c.id = m.canteen_id'],
                ['__SCHOOL__ s','s.id = m.school_id'],
                ['__USER__ u','u.id = m.user_id'],
            ];
            $field = 'm.*,u.user_nickname as user_name,c.name as c_name,s.name as s_name';
            $post = $model->join($join)->where($where)->field($field)->find();
            // $f_join = [
            //     ['__FOOD__ f','f.id = mf.food'],
            //     ['__FOOD_UNIT__ fu','fu.id = mf.unit'],
            //     ['__CANTEEN_SUPPLIER__ s','s.id = mf.supplier'],
            // ];
            // $f_field = 'mf.*,f.name as f_name,fu.name as fu_name,s.name as s_name';
            // $post['food'] = $food->alias('mf')->join($f_join)->field($f_field)->where('mf.mission_id',$post['id'])->select()->toArray();
            $post['food'] = $food->where('mission_id',$post['id'])->select()->toArray();
            $post['check'] = $check->where('mission_id',$post['id'])->select()->toArray();
            $post['other'] = $other->where('mission_id',$post['id'])->select()->toArray();
            $post['finished'] = $finished->where('mission_id',$post['id'])->select()->toArray();
            $post['record'] = $record->where('mission_id',$post['id'])->select()->toArray();

            $this->assign('post',$post);
            return $this->fetch();
        }
    }

    public function chart() {
        $param = $this->request->param();

        $model = new MissionFoodModel();
        $model = $model->alias('mf');
        $where = [];

        if (isset($param['order']) and !empty($param['order'])) { 
            $order = $param['order'];
        } else {
            $order = 'mf.id desc'; 
        }
        if (isset($param['school_id']) and !empty($param['school_id'])) {
            $where['mf.school_id'] = $param['school_id'];
        }
        //默认一周
        $now = date('Y-m-d',time());
        $time = strtotime($now);
        if (isset($param['start_time']) and !empty($param['start_time'])) {
            $startTime = strtotime($param['start_time']);
        } else {
            $startTime = $time-86400*7;
            $param['start_time'] = date('Y-m-d',$startTime);
        }
        if (isset($param['end_time']) and !empty($param['end_time'])) {
            $endTime = strtotime($param['end_time']);
        } else {
            $endTime = $time+86399;
            $param['end_time'] = $now;
        }
        $this->assign('param',$param);

        if (!empty($startTime)) {
            $model->where('mf.create_time', '>=', $startTime);
        }
        if (!empty($endTime)) {
            $model->where('mf.create_time', '<=', $endTime);
        }
        $join = [
            ['__SCHOOL__ s','s.id = mf.school_id'],
            ['__CANTEEN__ c','c.id = mf.canteen_id'],
            ['__FOOD__ f','f.id = mf.food'],
            ['__FOOD_UNIT__ fu','fu.id = mf.unit'],
        ];
        $field = 'mf.id,mf.create_time,sum(mf.num) as num,mf.food,f.name as f_name,mf.unit,fu.name as fu_name,c.name as c_name,s.name as s_name';
        $data = $model->join($join)->where($where)->field($field)->order($order)->group('mf.food,mf.unit')->paginate(10);
        $data->appends($param);
        $this->assign('page', $data->render());
        $this->assign('data',$data);
        
        $school = db::name('school')->where('status',1)->select();
        $this->assign('school', $school);

        return $this->fetch();
    }
 
}
