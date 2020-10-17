<?php

namespace app\card\controller;

use cmf\controller\CardBaseController;
use think\Db;
use think\Image;

class UserController extends CardBaseController {

    public function index() {
        $school_id = $this->schoolId;  //用户id getUserId()
        // $s_msg = Db::name('school')->where(['id'=>$school_id])->find();
        // $this->assign('s_msg', $s_msg);
        return $this->fetch();
    }

    // 个人信息页
    public function info() {
        $this->getUserInfo(true);
        $res = $this->userInfo;
        $this->assign('res', $res);
        return $this->fetch();
    }

    public function edit() {
        $type = $this->request->param('type', 'user_nickname');
        $this->assign('type', $type);

        $name = 'name';  //正常的输出键名

        $data = $this->userInfo;
        $info = [];
        switch ($type) {
            case 'user_nickname':
                $info['cell'] = '姓名';  //对应名
                $info['form_type'] = 'text';  //输入类
                break;
            case 'mobile':
                $info['cell'] = '联系方式';  //对应名
                $info['form_type'] = 'number';  //输入类
                break;
        }
        $this->assign('name', $name);
        $this->assign('info', $info);
        $this->assign('data', $data);
        return $this->fetch();
    }

    public function editPost() {
        $post = [];
        $captcha = $this->request->param('captcha');
        if (empty($captcha)) {
            $this->error('请输入验证码');
        }
        //验证码
        if (!cmf_captcha_check($captcha)) {
            $this->error('验证码错误');
        }
        if ($this->request->isPost()) {
            $res = [];
            $user_id = $this->getUserId();  //用户id
            $type = $this->request->param('type');  //提交的信息
            $data = $this->request->param($type);
            if ( in_array($type, ['user_nickname','sex','mobile'] ) ) {  //更新user表
                $table = Db::name('User');
                $where = ['id'=>$user_id];
                $save = [$type=>$data];
                $res = $table->where($where)->update($save);
            }
            
            if (!empty($res)) {
                $this->success('修改成功',url('user/info'));
            } else {
                $this->error('服务器错误');
            }
        }
    }

    public function avatar() {
        $data = $this->userInfo;
        $this->assign('data', $data);
        return $this->fetch();
    }

    public function avatarPost() {
        $post = [];
        if ($this->request->isPost()) {
            $user_id = $this->getUserId();  //用户id
            $avatar = $this->request->param('avatar');  //提交的信息
            $table = Db::name('User');
            $where = ['id'=>$user_id];
            $save = ['avatar'=>$avatar];
            $res = $table->where($where)->update($save);
            
            $this->success('修改成功',url('user/info'));
        }
    }

    public function logout() {
        if ($this->request->isPost()) {
            //$post = $this->request->param();  //提交的信息
            $user_id = $this->getUserId();  //用户id
            if (empty($user_id)) {
                $this->error('当前未登录');
            }

            session('user_id',null);
            session('school_id',null);
            session('canteen_id',null);
            session('user',null);
            //session('open_id',null);
            //session('wxData',null);
            session('check_status',null);  //首页用于判断学校或食堂审核状态
            session('stop_auto',1);  //用于去掉退出后还会触发微信自动登录
            $this->success("正在为您退出登录",cmf_url('wx/authorize') );
        }
    }

    public function cancelWx() {
        if ($this->request->isPost()) {
            //$post = $this->request->param();  //提交的信息
            $user_id = $this->getUserId();  //用户id
            if (empty($user_id)) {
                $this->error('当前未登录');
            }
            db::name('user')->where('id',$user_id)->update(['open_id'=>'']);
            $this->success("解绑成功" );
        } else {
            $this->error('操作错误');
        }
    }

    public function msg_lists() {
        //判断请求为POST,修改信息
        return $this->fetch();
    }

    public function msg_ajaxlist() {
        //判断请求为POST,修改信息
        $userId = $this->getUserId();
        $school_id = $this->schoolId;

        $param = [];
        $param['user_id'] = !empty($userId)?$userId:0;
        $param['school_id'] = !empty($school_id)?$school_id:0;
        // if ($userId>0) {
        //     $activityService = new ActivityService();
        //     $list = $activityService->getUserActivity($param);
        //     $this->assign('list', $list);
        // }
        $list = '';
        $this->assign('list', $list);
        return $this->fetch();
    }

    public function invite() {
        $id = $this->request->param('id', 0, 'intval');
        $user_id = $this->getUserId();
        $school_id = $this->schoolId;
        
        $dir = '/upload/user/'.$user_id.'/';
        $path = './upload/user/';
        //生成路径
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        $path .= $user_id.'/';
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        $saveName = 'invite_'.$id.'_'.$user_id.'.png';    //以用户名及捐赠id作为名称，防重复生成
        $img = $dir.$saveName;
        if (!file_exists($img)) {
            $userInfo = $this->userInfo;
            $user_nickname = $userInfo['user_nickname'];
            $come_on = '邀请您加入'.$this->siteInfo['site_name'];
            $text = '食安监管平台';

            $file = $this->siteInfo['invite_bg'];
            $file = WEB_ROOT.'upload/'.$file;
            $image = Image::open($file);

            // 水印
            // $water = WEB_ROOT.'static/images/create.png';
            // // $image->thumb(100,100,Image::THUMB_FIXED );  //将水印制成所需的大小
            // $image->water($water,['500','950'],100);   //位置可自定义 ['10','20'];

            //文字
            $ttf = WEB_ROOT.'static/font-awesome/fonts/text.ttf';
            $image->text($user_nickname,$ttf,32,'#333333',['208','1602']);//位置可自定义 ['10','20'];
            $image->text($come_on,$ttf,32,'#333333',['208','1664']);//位置可自定义 ['10','20'];
            $image->text($text,$ttf,32,'#333333',['208','1726']);
            
            $image->save(WEB_ROOT.$img);
        } 

        $this->assign('img', $img);
        return $this->fetch();
    }

}
