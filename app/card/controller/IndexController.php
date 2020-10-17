<?php

namespace app\card\controller;

use cmf\controller\CardBaseController;
use app\portal\service\PostService;
use think\Db;
use think\db\Query;

class IndexController extends CardBaseController {

    public function index() {
        $param = $this->request->param();
        // 模块
        $cate = $this->userInfo['user_cate'];
        $modules = config('module');
        $module = $modules[$cate];
        $this->assign('module', $module);

        // $param['type'] = 2;
        $school_id = $this->schoolId;
        $param['school_id'] = !empty($school_id)?$school_id:0;
        $param['page_size'] = 8;
        $param['post_status'] = 1;
        $postService = new PostService();
        $list = $postService->postList($param);
        $this->assign('list', $list->items());

        //监测账号所在学校或食堂是否ok
        $check_status = session('check_status');
        $this->assign('check_status', $check_status);

        return $this->fetch(':index');
    }

    //监测账号所在学校或食堂是否ok
    public function checkStatus() {
        $check_status = session('check_status');
        if ($check_status==1) {  //已监测，不再重复
            $this->success();
        }
        $res = $this->getUserInfo();  // 获取用户信息
        if (empty($this->userId) or empty($res)) {
            $this->redirect('card/wx/authorize');
        }
        session('check_status',1);  //放在这个位置，会出现只触发一次，下次返回首页时不会再出来的结果
        if ($res['user_cate'] == 1 and $res['role_status'] != 1) {  //学校
            if($res['role_status'] == 3) {
                $this->error('请先完善学校信息',url('card/school/edit'));
            } elseif($res['role_status'] == 2) {
                $this->error('学校信息审核中，请耐心等待',url('card/school/edit'));
            } elseif($res['role_status'] == 0) {
                $this->error('该学校已删除',url('card/school/edit'));
            } elseif($res['role_status'] == '-1') {
                $this->error('该学校信息审核不通过',url('card/school/edit'));
            }
        }
        if ($res['user_cate'] == 2 and $res['role_status'] != 1) {  //食堂
            if($res['role_status'] == 3) {
                $this->error('请先完善食堂信息',url('card/canteen/post'));
            } elseif($res['role_status'] == 2) {
                $this->error('食堂信息审核中，请耐心等待',url('card/canteen/post'));
            } elseif($res['role_status'] == 0) {
                $this->error('该食堂已删除',url('card/canteen/post'));
            } elseif($res['role_status'] == '-1') {
                $this->error('该食堂信息审核不通过',url('card/canteen/post'));
            }
        }
        if ($res['user_cate'] == 2 and $res['role_status'] == 1) {  //食堂，提示在规定时间内上传清单
            $upload_time = (isset($res['role_upload_time']) and !empty($res['role_upload_time']))?$res['role_upload_time']:'';  //获取需上传时间
            if (!empty($upload_time)) {
                $today = date('Y-m-d').' '.$upload_time;
                $time = strtotime($today);
                $formal_time = get_datetime();
                $year = $formal_time['year'];$month = $formal_time['month'];$day = $formal_time['day'];
                $now= time();
                if ($now>$time) {   //过了设定的时间
                    $count = db::name('mission')->where('canteen_id',$res['canteen_id'])->where('status',1)->where('year',$year)->where('month',$month)->where('day',$day)->count();
                    if (empty($count)) {
                        $this->error('已过设定的上传时间，请尽快上传进货信息',url('card/mission/index'));
                    }
                }
                
            }
        }
        $this->success();  //啥事没有
    }

    public function guest() {
    	return $this->fetch(':guest');
    }

    public function guestPost() {
    	if ($this->request->isPost()) {
    		$data = $this->request->post();
    		$table = Db::name('guestbook');
    		$title = trim($data['title']);
    		$msg = trim($data['msg']);
    		//1 判断是否已有重复
    		$count = $table->where(['title'=>$title])->count();
    		if (empty($count)) {
                $data['user_id'] = $this->userId; 
                $data['school_id'] = $this->school_id; 
    			$data['title'] = $title;
    			$data['msg'] = $msg;
    			$data['createtime'] = time();
    			$table->insert($data);
    			$this ->success("反馈成功",cmf_url('card/index/index') );
    		} else {
    			$this ->error("该反馈已有重复" );
    		}
    	}
    }
}
