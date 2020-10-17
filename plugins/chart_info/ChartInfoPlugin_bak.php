<?php

namespace plugins\chart_info;

use cmf\lib\Plugin;
use think\Db;

class ChartInfoPlugin extends Plugin {

    public $info = [
        'name'        => 'ChartInfo',
        'title'       => '后台首页面信息',
        'description' => '用图表展示一些常用的信息',
        'status'      => 1,
        'author'      => '小艾',
        'version'     => '1.0'
    ];

    public $hasAdmin = 0;//插件是否有后台管理界面

    // 插件安装
    public function install() {
        return true;//安装成功返回true，失败false
    }

    // 插件卸载
    public function uninstall() {
        return true;//安装成功返回true，失败false
    }

    public function adminDashboard() {
        $time = get_datetime();
        if($time['hour']<11) {$time['moment'] = '早上好';}
        elseif($time['hour']<13) {$time['moment'] = '中午好';}
        elseif($time['hour']<17) {$time['moment'] = '下午好';}
        elseif($time['hour']<19) {$time['moment'] = '傍晚好';}
        else {$time['moment'] = '晚上好';}
        $time['week'] = get_week($time['week']);
        $time['warm'] = get_warm_msg();
        $this->assign('time', $time);
        
        // $u_table = Db::name('user');
        // $su_table = Db::name('schoolUser');
        // $school_id = cmf_get_current_school_id();

        // //处理时间戳获取当月月份作为筛选条件
        // $month = date('Y-m',time());
        // $this->assign('month',$month);
        // $this->assign('title','校友注册统计');

        // $prefix = config('database.prefix');
        // $res = $u_table->where(["FROM_UNIXTIME(create_time,'%Y-%m')"=>$month,'user_status'=>['gt',0] ])->field("FROM_UNIXTIME(create_time,'%d') days,count(id) count")->group('days')->select();
        // // x 轴数据，作为 x 轴标注
        // $j = date("t"); //获取当前月份天数
        // $start_time = strtotime(date('01'));  //获取本月第一天时间戳
        // $xdata = array();
        // for($i=0;$i<$j;$i++) {
        //     $xdata[] = date('d',$start_time+$i*86400); //每隔一天赋值给数组
        // }
        // //处理获取到的数据
        // //[{"name":"1","y":8},{"name":"2","y":0},{"name":"3","y":0},{"name":"4","y":0},{"name":"5","y":0},{"name":"6","y":9},{"name":"7","y":11},{"name":"8","y":12}]    }]
        // $ydata = array();

        // if(!empty($res)) {
        //     foreach ($xdata as $k=>&$v) {
        //         $ydata[$k]['name'] = $v;
        //         foreach ($res as $kk=>$vv) {
        //             if($v == $vv['days']) {
        //                 $ydata[$k]['y'] = $vv['count'];
        //                 break;
        //             }else{
        //                 $ydata[$k]['y'] = 0;
        //                 continue;
        //             }
        //         }
        //         $v = substr($v,-2);
        //     }
        // }else{
        //     foreach ($xdata as $k=>$v) {
        //         $ydata[$k]['name'] = $v;
        //         $ydata[$k]['y'] = 0;
        //     }
        // }
        // $ydata = json_encode($ydata,JSON_NUMERIC_CHECK);
        // $this->assign('ydata',$ydata);

        // //统计
        // $data = [];
        // //用户总数
        // $join = [
        //     ['__SCHOOL_USER__ su','su.user_id = u.id','left'],
        // ];
        // $where = ['u.user_status'=>['gt',0],'su.school_id'=>$school_id ];
        // $data['total'] = $u_table->alias('u')->join($join)->where($where)->count();
        // $today = strtotime(date('Y-m-d'));
        // // var_dump($today);
        // // var_dump(time());exit;
        // $where['u.create_time'] = ['gt',$today];
        // $data['today'] = $u_table->alias('u')->join($join)->where($where)->count();
        // //var_dump($u_table->getlastsql());exit;
        // $where['u.create_time'] = ['gt',$today-86400*6];
        // $data['seven'] = $u_table->alias('u')->join($join)->where($where)->count();
        // $where['u.user_status'] = ['eq',2];
        // $data['wait'] = $u_table->alias('u')->join($join)->where($where)->count();
        // if ($data['total']>0) {
        //     $data['today_rate'] = round($data['today']*100/$data['total']);
        // } else {
        //     $data['today_rate'] = 0;
        // }
        // if ($data['seven']>0) {
        //     $data['seven_rate'] = round($data['seven']*100/$data['total']);
        // } else {
        //     $data['seven_rate'] = 0;
        // }
        // $this->assign('data',$data);

        // //活动排行
        // $join = [ ['__USER__ u','ua.user_id = u.id','left'],
        //           ['__ACTIVITY_POST__ ap','ap.id = ua.activity_id','left'], 
        //         ];
        // $where = ['ua.school_id'=>$school_id,'status'=>1];
        // $ua_table = Db::name('userActivity');
        // $a_data = $ua_table->alias('ua')->join($join)->where($where)->field('u.id,u.avatar,ap.post_title,ua.enroll_time,ua.type')->order('ua.enroll_time desc')->limit(5)->select();

        // $this->assign('a_data',$a_data);

        // //捐赠排行
        // $join = [ ['__USER__ u','d.user_id = u.id','left'], ];
        // $where = ['d.school_id'=>$school_id,'status'=>1];
        // $d_table = Db::name('donateDetail');
        // $d_data = $d_table->alias('d')->join($join)->where($where)->field('u.id,u.avatar,d.money,d.createtime')->order('d.createtime desc')->limit(5)->select();

        // $this->assign('d_data',$d_data);
        
        return [
            'width'  => 12,
            'view'   => $this->fetch('widget'),
            'plugin' => 'chartInfo'
        ];
    }
}
