<?php

namespace app\command\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class auto extends Command {
    protected function configure(){
        $this->setName('auto')->setDescription("每天自动执行");//这里的setName和php文件名一致,setDescription随意
    }

    protected function execute(Input $input, Output $output) {
        $this->pushMsg();
    }

    // 发送上传提醒到公众号
    public function pushMsg() {
        // 获取时间
        $start_time = date("Y-m-d");
        $m_where = [];
        if (!empty($start_time)) {
            $days = explode('-', $start_time);
            $m_where['year'] = $days[0];
            $m_where['month'] = $days[1];
            $m_where['day'] = $days[2];
        }
        $time = time();
        $canteen = db::name('canteen')->where('status',1)->order('id asc')->field('id,school_id,upload_time')->select();
        if (!empty($canteen)) {
            foreach ($canteen as $k => $v) {
                if (!empty($v['upload_time'])) {
                    $upload_date = $start_time.' '.$v['upload_time'];
                    $upload_time = strtotime($upload_date);
                } else {  //未设时间，不提醒
                    continue;
                }
                if ($upload_time>$time) {   //未到时间，不提醒
                    continue;
                }
                $push = db::name('missionPush')->where('canteen_id',$v['id'])->where('school_id',$v['school_id'])->where($m_where)->count();
                if (empty($push)) {
                    $canteen_id = $v['id'];
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
                        $add = ['canteen_id'=>$canteen_id,'school_id'=>$v['school_id'],'create_time'=>time(),'year'=>$days[0],'month'=>$days[1],'day'=>$days[2], ];
                        db::name('missionPush')->insert($add);
                        $res = json_encode($res);
                        cmf_log($res,"data/runtime/log/".$canteen_id."_auto_push_".$start_time.".txt");
                    }
                }
            }
        }
    }
}