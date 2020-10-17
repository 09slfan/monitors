<?php
// +----------------------------------------------------------------------
// | zhiliang weight
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019.
// +----------------------------------------------------------------------
// | Author: 小艾.pino
// +----------------------------------------------------------------------
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class PublicController extends AdminBaseController
{
    public function initialize() {
    }

    /**
     * 后台登陆界面
     */
    public function login() {
        $loginAllowed = session("__LOGIN_BY_CMF_ADMIN_PW__");
        if (empty($loginAllowed)) {
            //$this->error('非法登录!', cmf_get_root() . '/');
            return redirect(cmf_get_root() . "/");
        }

        $admin_id = session('ADMIN_ID');
        if (!empty($admin_id)) {//已经登录
            return redirect(url("admin/Index/index"));
        } else {
            session("__SP_ADMIN_LOGIN_PAGE_SHOWED_SUCCESS__", true);
            $result = hook_one('admin_login');
            if (!empty($result)) {
                return $result;
            }
            return $this->fetch(":login");
        }
    }

    /**
     * 登录验证
     */
    public function doLogin()
    {
        if (hook_one('admin_custom_login_open')) {
            $this->error('您已经通过插件自定义后台登录！');
        }

        $loginAllowed = session("__LOGIN_BY_CMF_ADMIN_PW__");
        if (empty($loginAllowed)) {
            $this->error('非法登录!', cmf_get_root() . '/');
        }

        $captcha = $this->request->param('captcha');
        if (empty($captcha)) {
            $this->error(lang('CAPTCHA_REQUIRED'));
        }
        //验证码
        if (!cmf_captcha_check($captcha)) {
            $this->error(lang('CAPTCHA_NOT_RIGHT'));
        }

        $name = $this->request->param("username");
        if (empty($name)) {
            $this->error(lang('USERNAME_OR_EMAIL_EMPTY'));
        }
        $pass = $this->request->param("password");
        if (empty($pass)) {
            $this->error(lang('PASSWORD_REQUIRED'));
        }
        if (strpos($name, "@") > 0) {//邮箱登陆
            $where['user_email'] = $name;
        } else {
            $where['user_login'] = $name;
        }

        $result = Db::name('user')->where($where)->find();

        if (!empty($result) && $result['user_type'] == 1) {
            if (cmf_compare_password($pass, $result['user_pass'])) {
                $groups = Db::name('RoleUser')
                    ->alias("a")
                    ->join('__ROLE__ b', 'a.role_id =b.id')
                    ->where(["user_id" => $result["id"], "status" => 1])
                    ->value("role_id");
                if ($result["id"] != 1 && (empty($groups) || empty($result['user_status']))) {
                    $this->error(lang('USE_DISABLED'));
                }
                //登入成功页面跳转
                session('ADMIN_ID', $result["id"]);
                session('name', $result["user_login"]);
                $result['last_login_ip']   = get_client_ip(0, true);
                $result['last_login_time'] = time();
                $token                     = cmf_generate_user_token($result["id"], 'web');
                if (!empty($token)) {
                    session('token', $token);
                }
                Db::name('user')->update($result);
                cookie("admin_username", $name, 3600 * 24 * 30);
                session("__LOGIN_BY_CMF_ADMIN_PW__", null);
                $this->success(lang('LOGIN_SUCCESS'), url("admin/Index/index"));
            } else {
                $this->error(lang('PASSWORD_NOT_RIGHT'));
            }
        } else {
            $this->error(lang('USERNAME_NOT_EXIST'));
        }
    }

    /**
     * 后台管理员退出
     */
    public function logout() {
        session('ADMIN_ID', null);
        return redirect(url('/admin', [], false, true));
    }

    //获取省市区
    public function getArea() {
        $param = $this->request->param();
        if (isset($param['pid']) and !empty($param['pid'])) {
            $parent_id = $param['pid'];
        } else {
            $parent_id = 0;
        }
        if (isset($param['level']) and !empty($param['level'])) {
            $level = $param['level'];
        } else {
            $level = 1;
        }
        $data = db::name('region')->where(['parent_id'=>$parent_id,'level'=>$level])->field('id,name,parent_id')->cache(true)->select();
        if (!empty($data)) {
            $this->success('返回成功', '',$data);
        }
    }

    //获取关联表信息
    public function getSubDate() {
        $table = $this->request->param('table','');
        $where = $this->request->param('where','');
        $type = $this->request->param('type',1);
        $field = $this->request->param('field','*');
        if (empty($table)) { $this->error('表参数错误'); }
        if (empty($where)) { $this->error('条件参数错误'); }

        $data = cmf_quick_sql($table,$where,$type,$field);
        if (!empty($data)) {
            $this->success('返回成功', '',$data);
        } else {
            $this->error('返回失败');
        }
    }

    // 用户头像api
    public function thumbnail() {
        $pic   = $this->request->param("pic",'');
        if (!empty($pic)) {
            $pic = cmf_get_user_avatar_url($pic);
            if (strpos($pic, "/") === 0) {
                $pic = $this->request->domain() . $pic;
            }
        }

        if (empty($pic)) {
            $pic = $this->request->domain() . "/static/images/headicon.png";
        }

        return redirect($pic);
    }
}