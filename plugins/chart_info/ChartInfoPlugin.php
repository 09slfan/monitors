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

        $month = date('Y-m',time());
        $this->assign('month',$month);
        $this->assign('title','平台登录使用统计'); 
        $this->assign('bar_title','学校类型登录访问量比例'); 

        $prefix = config('database.prefix');
        $res = db::name('user')->where('last_login_time','gt',$time['month_begin'])->where('last_login_time','lt',$time['month_end'])->where('user_type','eq',2)->where('user_status','gt',0)->field("FROM_UNIXTIME(last_login_time,'%d') days,count(id) count")->group('days')->select();
        // x 轴数据，作为 x 轴标注
        $j = date("t"); //获取当前月份天数
        $start_time = strtotime(date('01'));  //获取本月第一天时间戳
        $xdata = array();
        for($i=0;$i<$j;$i++) {
            $xdata[] = date('d',$start_time+$i*86400); //每隔一天赋值给数组
        }
        //处理获取到的数据
        //[{"name":"1","y":8},{"name":"2","y":0},{"name":"3","y":0},{"name":"4","y":0},{"name":"5","y":0},{"name":"6","y":9},{"name":"7","y":11},{"name":"8","y":12}]    }]
        $ydata = array();

        if(!empty($res)) {
            foreach ($xdata as $k=>&$v) {
                $ydata[$k]['name'] = $v;
                foreach ($res as $kk=>$vv) {
                    if($v == $vv['days']) {
                        $ydata[$k]['y'] = $vv['count'];
                        break;
                    }else{
                        $ydata[$k]['y'] = 0;
                        continue;
                    }
                }
                $v = substr($v,-2);
            }
        }else{
            foreach ($xdata as $k=>$v) {
                $ydata[$k]['name'] = $v;
                $ydata[$k]['y'] = 0;
            }
        }
        $ydata = json_encode($ydata,JSON_NUMERIC_CHECK);
        $this->assign('ydata',$ydata);

        //----- 学校类型 饼图 开始 
        $types = Db::name('school_type')->where('status',1)->field('id,name')->cache(true)->select();
        foreach ($types as $k => $v) {
            $in[$k]['name'] = $v['name'];
            $school_ids = Db::name('school')->where('school_type',$v['id'])->column('id');
            $in[$k]['y'] = Db::name('user')->where('last_login_time','gt',$time['month_begin'])->where('last_login_time','lt',$time['month_end'])->where('user_type','eq',2)->where('user_status','gt',0)->where('school_id','in',$school_ids)->count();
        }
        $in = json_encode($in,JSON_NUMERIC_CHECK);
        $this->assign('in',$in);
        unset($income);
        //-----饼图 结束

        $current = time();

        $data = [];
        $data['school'] = db::name('school')->where('status','gt',0)->count();
        $data['canteen'] = db::name('canteen')->where('status','gt',0)->count();
        $data['parent'] = db::name('user')->where('user_cate','eq',3)->where('user_type','eq',2)->where('user_status','eq',1)->count();
        $data['vip'] = db::name('user')->alias('u')->join('__USER_VIP__ uv','uv.user_id=u.id')->where('u.user_cate','eq',3)->where('u.user_type','eq',2)->where('u.user_status','eq',1)->where('uv.end_time','lt',$current)->count();

        $this->assign('data', $data);
        
        return [
            'width'  => 12,
            'view'   => $this->fetch('widget'),
            'plugin' => 'chartInfo'
        ];
    }
}
