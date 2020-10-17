<?php

namespace api\home\controller;

use cmf\controller\RestBaseController;
use think\db;

class NotifyController extends RestBaseController {

    /**
     * 获取设备视频推送信息
     * {"domain":"ycplay7.yunchuanglive.com","app":"live","stream":"SSSS-175396-BCEEE","uri":"yc-ycc-rec1d\/record\/live\/SSSS-175396-BCEEE\/2019-11-29-15-13-41_2019-11-29-15-28-41.mp4","duration":875.328,"start_time":1575011592,"stop_time":1575012500}
     */
    public function record() {
        $param = $this->request->param();
        $res = json_encode($param)."\r\n";
        cmf_log($res,"data/runtime/log/record_".date('Y_m_d').".txt");
        // $param = json_decode('{"domain":"ycplay7.yunchuanglive.com","app":"live","stream":"SSSS-175396-BCEEE","uri":"yc-ycc-rec1d\/record\/live\/SSSS-175396-BCEEE\/2019-11-29-15-13-41_2019-11-29-15-28-41.mp4","duration":875.328,"start_time":1575011592,"stop_time":1575012500}',true);
        if (!empty($param)) {
        	$domain     = isset($param['domain'])?trim($param['domain']):'';
        	$app        = isset($param['app'])?trim($param['app']):'';
        	$stream     = isset($param['stream'])?trim($param['stream']):'';
        	$uri        = isset($param['uri'])?trim($param['uri']):'';
        	$duration   = isset($param['duration'])?intval(trim($param['duration'])):'';
        	$start_time = isset($param['start_time'])?intval(trim($param['start_time'])):'';
        	$stop_time  = isset($param['stop_time'])?intval(trim($param['stop_time'])):'';
        	if (!empty($domain) and !empty($app) and !empty($stream) and !empty($uri) and !empty($duration) and !empty($start_time) and !empty($stop_time)) {
        		$time = time();
        		$add = ['domain'=>$domain,'app'=>$app,'stream'=>$stream,'uri'=>$uri,'duration'=>$duration,'start_time'=>$start_time,'stop_time'=>$stop_time,'create_time'=>$time];
        		db::name('videoRecord')->insert($add);
        		$this->success("获取成功!", $param);
        	} {
        		$this->error("获取失败");
        	}
        } else {
        	$this->error("获取失败");
        }
    }
}
