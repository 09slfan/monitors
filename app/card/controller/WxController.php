<?php

namespace app\card\controller;
use cmf\controller\WxBaseController;

use think\Db;

class wxController extends WxBaseController {

    public function authorize() {
        $param = $this->request->get();  //获取get参数
        if (session('?user_id')) {
            $this->redirect('card/index/index');
        }
        // cmf_log($param,RUNTIME_PATH."log/log.txt");

        // // 1 getWxOpenId() 获取微信openid ,session('open_id')
        // // 2 getWxUser() 获取微信基本信息 ,session('wxData')
        // if (!session('?wxData')) {   //微信信息不存在
        //     $this->getWxUser();
        // }
        // // 用于去掉退出后还会触发微信自动登录
        //if (!session('?stop_auto')) { $this->checkWxLogin(); }
        
        $cate = db::name('userCate')->where('status',1)->select();
        $this->assign('cate',$cate);

        return $this->fetch();
    }

    // 登录处理
    public function authorizePost() {
        if ( $this->request->isPost() ) {
            $param = $this->request->param(); 
            if (isset($param['cate']) and !empty($param['cate'])) {
                $cate = trim($param['cate']);
            } else {
                $this->error('请选择账号角色');
            }
            if (isset($param['name']) and !empty($param['name'])) {
                $name = trim($param['name']);
            } else {
                $this->error('请输入账号');
            }
            if (isset($param['code']) and !empty($param['code'])) {
                $code = cmf_password( trim($param['code']) );
            } else {
                $this->error('请输入账号密码');
            }
            $join = [];
            // $join = [
            //     ['__SCHOOL__ s', 's.id = u.school_id','left'],
            // ];
            $where = ['u.user_login'=>$name,'u.user_status'=>1,'u.user_type'=>2,'u.user_cate'=>$cate];
            $where['u.user_pass'] = $code;
            $field = 'u.id,u.user_status,count,u.avatar,u.school_id,u.canteen_id';
            $info = Db::name('user')->alias('u')->join($join)->where($where)->field($field)->find();
            if (!empty($info)) {  //1 存在
                if ($info['user_status'] == 1) {   //正常状态
                    if (isset($info['school_id']) and !empty($info['school_id'])) {
                        $count = Db::name('school')->where('id',$info['school_id'])->where('status','gt',0)->count();
                        if (empty($count)) { $this->error("平台学校已停用"); }
                    }
                    if (isset($info['canteen_id']) and !empty($info['canteen_id'])) {
                        $count = Db::name('canteen')->where('id',$info['canteen_id'])->where('status','gt',0)->count();
                        if (empty($count)) { $this->error("平台学校食堂已停用"); }
                    }
                    $this->goLogin($info);
                } elseif($info['user_status'] == 2) {   //待后台验证，提示
                    $this->error("请等待管理员验证");
                } else {    //被禁用
                    $this->error("当前用户被禁用，请联系管理员");
                }
            } else { //不存在
                $this->error("当前账号信息错误");
            }
            
        } else {
            $this->error("接口错误");
        }
    }

    // 登录处理-游客身份
    public function authorizePost5() {
        if ( $this->request->isPost() ) {
            $param = $this->request->param(); 
            if (isset($param['type']) and !empty($param['type'])) {
                $type = trim($param['type']);
            } else {
                $this->error('请选择账号角色');
            }
            $join = [];
            $where = ['u.id'=>79];
            $field = 'u.id,u.user_status,count,u.avatar,u.school_id,u.canteen_id';
            $info = Db::name('user')->alias('u')->join($join)->where($where)->field($field)->find();
            if (!empty($info)) {  //1 存在
                if ($info['user_status'] == 1) {   //正常状态
                    if (isset($info['school_id']) and !empty($info['school_id'])) {
                        $count = Db::name('school')->where('id',$info['school_id'])->where('status','gt',0)->count();
                        if (empty($count)) { $this->error("平台学校已停用"); }
                    }
                    if (isset($info['canteen_id']) and !empty($info['canteen_id'])) {
                        $count = Db::name('canteen')->where('id',$info['canteen_id'])->where('status','gt',0)->count();
                        if (empty($count)) { $this->error("平台学校食堂已停用"); }
                    }
                    $this->goLogin($info);
                } elseif($info['user_status'] == 2) {   //待后台验证，提示
                    $this->error("请等待管理员验证");
                } else {    //被禁用
                    $this->error("当前用户被禁用，请联系管理员");
                }
            } else { //不存在
                $this->error("当前账号信息错误");
            }
            
        } else {
            $this->error("接口错误");
        }
    }

    private function checkWxLogin(){
        if (session('?wxData')) {   //微信信息存在
            $wx_data = session('wxData');
            //进行登录预判
            $user_id = $this->getUserId();
            if (empty($user_id)) {
                $field = 'u.id,u.user_status,count,u.avatar';
                $info = db::name('user')->alias('u')->where('u.open_id',$wx_data['openid'])->where('user_status',1)->where('user_type',2)->field($field)->find();
                if (!empty($info)) {
                    $this->goLogin($info,2);
                }
            }
        }
    }

    private function goLogin($info=[],$type=1){
        //参数存在 用户id缓存不存在
        if (!empty($info) and !session('?user_id') ) {
            //增加登录次数
            $count = $info['count']+1;
            $last_login_time = time();
            $last_login_ip = $this->request->ip(0, true);
            $update = ['count'=>$count,'last_login_ip'=>$last_login_ip,'last_login_time'=>$last_login_time];
            if (session('?wxData')) {   //微信信息存在
                $wx_data = session('wxData');
                $update['open_id'] = $wx_data['openid'];
                if (empty($info['avatar'])) {
                    $update['avatar'] = $wx_data['head_pic'];
                }
                if (empty($info['user_nickname'])) {
                    $update['user_nickname'] = !empty($wx_data['nickname'])?$wx_data['nickname']:'游客';
                }
            }
            Db::name('user')->where('id',$info['id'])->update($update);

            session('user_id',$info['id']);   //记录id
            session('check_status',0);  //用来监测学校或食堂状态
            session('stop_auto',null);  //用于去掉退出后还会触发微信自动登录
            if ($type==1) {
                $this->success("登录成功，正在为您跳转",cmf_url('card/index/index'));
            } else {
                $this->redirect('card/index/index');
            }
        }
        
    }

    // 快捷sql访问方法
    public function setMsg($data,$table='',$name='name',$field='id',$wheres=[]){
        if (empty($data)) {
            return '';
        }
        $where = [$name=>$data];
        if (!empty($wheres)) {
            array_merge($where,$wheres);
        }
        $id = Db::name($table)->where($where)->value($field);
        if (empty($id)) {
            $id = Db::name($table)->insertGetId($where);
        }
        return $id;
    }

}
