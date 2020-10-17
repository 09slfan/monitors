<?php
// +----------------------------------------------------------------------
// | zhiliang weight
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019.
// +----------------------------------------------------------------------
// | Author: 小艾.pino
// +----------------------------------------------------------------------

namespace app\user\controller;

use cmf\controller\AdminBaseController;
use app\user\service\ImportService;
use think\Db;
use think\db\Query;

class AdminIndexController extends AdminBaseController {

    public function index() {
        $param = $this->request->param();
        if (empty($param['keyword'])) { $param['keyword'] = ''; }
        if (empty($param['province'])) { $param['province'] = '0'; }
        if (empty($param['city'])) { $param['city'] = '0'; }
        if (empty($param['district'])) { $param['district'] = '0'; }
        $this->assign('param',$param);

        // 1 学校  2 食堂  3 家长  4 监管 
        $where = ['u.user_type'=>2, "u.user_cate" => 1,'u.user_status'=>1];
        $field = 'u.*,s.name as s_name,s.address,r1.name as p_name,r2.name as c_name,r3.name as d_name';  
        $join = [
            ['__SCHOOL__ s','s.id=u.school_id'],
            ['__REGION__ r1','r1.id=s.province'],
            ['__REGION__ r2','r2.id=s.city'],
            ['__REGION__ r3','r3.id=s.district'],
        ];

        $list = Db::name('user')->alias('u')->join($join)->field($field)
            ->where(function (Query $query) use ($param) {
                $query->where('s.status', 'gt', 0);
                if (!empty($param['keyword'])) {
                    $keyword = $param['keyword'];
                    $query->where('u.user_login|u.user_nickname|u.mobile', 'like', "%$keyword%");
                }
                if (!empty($param['school_id'])) {
                    $school_id = $param['school_id'];
                    $query->where('u.school_id', 'eq', $school_id);
                }
                if (!empty($param['province'])) {
                    $province = $param['province'];
                    $query->where('s.province', 'eq', $province);
                }
                if (!empty($param['city'])) {
                    $city = $param['city'];
                    $query->where('s.city', 'eq', $city);
                }
                if (!empty($param['district'])) {
                    $district = $param['district'];
                    $query->where('s.district', 'eq', $district);
                }

            })
            ->where($where)
            ->order("u.id DESC")
            ->paginate(10);
        // 获取分页显示
        $page = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $page);

        $school = db::name('school')->where('status',1)->select();
        $this->assign('school', $school);

        $this->getStaff($param);

        // 渲染模板输出
        return $this->fetch();
    }

    public function add() {
        return $this->fetch();
    }

    public function addPost() {
        $params = $this->request->param();
        $param = $params['post'];
        if (!isset($param['name']) or empty($param['name'])) {
            $this->error('请输入账户名！');
        }
        if (!isset($param['code']) or empty($param['code'])) {
            $this->error('请输入账号对应的密码！');
        }
        if(!preg_match("/^[_0-9a-zA-Z]{6,}$/i",$param['code'])){
            $this->error('密码长度要大于等于6位');
        }
        if (!isset($param['school_id']) or empty($param['school_id'])) {
            $this->error('请选择所属学校！');
        }
        if (isset($param['mobile']) and !empty($param['mobile'])) {
            if (!cmf_check_mobile($param['mobile'])) {
                $this->error('手机号码格式不正确！');
            }
        }
        $post = [];
        $post['user_login'] = trim($param['name']);
        $post['user_nickname'] = trim($param['user_nickname']);
        $post['mobile'] = trim($param['mobile']);
        $post['user_pass'] = cmf_password(trim($param['code']));
        $post['school_id'] = $param['school_id'];
        $post['user_type'] = 2;
        $post['user_status'] = 1;
        $post['user_cate'] = 1;
        $post['create_time'] = time();
        $res = db::name('user')->insert($post);
        if (!empty($res)) {
            $this->success("会员添加成功！", "adminIndex/index");
        } else {
            $this->error('数据传入失败！');
        }
    }

    public function edit() {
        $id = $this->request->param('id', 0, 'intval');
        $model = db::name('user');
        $post = $model->alias('u')
            //->join($join)->field($field)
            ->where('u.id', $id)->find();
        $school = db::name('school')->where('id','in',$post['school_id'])->column('name');
        $school_name = implode(',', $school);
        $this->assign('school_name', $school_name);
        $this->assign('post', $post);

        return $this->fetch();
    }

    public function editPost() {
        if ($this->request->isPost()) {
            $update = [];
            $data = $this->request->param();

            $post   = $data['post'];
            if (!isset($post['id']) or empty($post['id'])) {
                $this->error('参数错误');
            }
            if (isset($post['code']) and !empty($post['code'])) {
                if(!preg_match("/^[_0-9a-zA-Z]{6,}$/i",$post['code'])){
                    $this->error('密码长度要大于等于6位');
                }
                $update['user_pass'] = cmf_password(trim($post['code']));
            }
            
            if (!isset($post['school_id']) or empty($post['school_id'])) {
                $this->error('请选择所属学校！');
            }

            if (isset($post['mobile']) and !empty($post['mobile'])) {
                if (!cmf_check_mobile($post['mobile'])) {
                    $this->error('手机号码格式不正确！');
                }
            }
            
            $update['user_login'] = trim($post['name']);
            $update['user_nickname'] = trim($post['user_nickname']);
            $update['mobile'] = trim($post['mobile']);
            $update['school_id'] = $post['school_id'];
            db::name('user')->where('id',$post['id'])->update($update);

            $this->success('保存成功!', url('AdminIndex/index') );

        }
    }

    public function check() {
        $id = $this->request->param('id', 0, 'intval');
        // $join = [
        //     ['__REGION__ r1','r1.id=s.province'],
        //     ['__REGION__ r2','r2.id=s.city'],
        //     ['__REGION__ r3','r3.id=s.district'],
        // ];
        // $field = 's.*,r1.name as p_name,r2.name as c_name,r3.name as d_name';

        $model = db::name('user');
        $post = $model->alias('u')
            //->join($join)->field($field)
            ->where('u.id', $id)->find();
        $school = db::name('school')->where('id','in',$post['school_id'])->column('name');
        $school_name = implode(',', $school);
        $this->assign('school_name', $school_name);
        $this->assign('post', $post);

        return $this->fetch();
    }

    /**
     * 本站用户禁用
     */
    public function ban() {
        $id = input('param.id', 0, 'intval');
        if ($id) {
            $result = Db::name("user")->where(["id" => $id, "user_type" => 2, "user_cate" => 1])->setField('user_status', 0);
            if ($result) {
                $this->success("会员删除成功！", "adminIndex/index");
            } else {
                $this->error('会员删除失败,会员不存在,或者是管理员！');
            }
        } else {
            $this->error('数据传入失败！');
        }
    }

    public function import(){
        return $this->fetch();
    }

    public function importPost(){
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $path = 'upload/'.$post['files']; // 获取上传到服务器文件路径
            $ext = pathinfo($post['files'], PATHINFO_EXTENSION);

            $params = array(
                "datafile" => $path,
                "ext" => $ext,
            );
            $objImport = new ImportService();
            $result = $objImport->import($params);
            if ($result !== false) {
                $this->success("导入成功！", url("AdminIndex/index"));
            } else {
                $this->error("导入失败！");
            }
        }
    }

    public function export() {
        $param = $this->request->param();
        if (empty($param['keyword'])) { $param['keyword'] = ''; }
        if (empty($param['school_id'])) { $param['school_id'] = '0'; }
        if (empty($param['province'])) { $param['province'] = '0'; }
        if (empty($param['city'])) { $param['city'] = '0'; }
        if (empty($param['district'])) { $param['district'] = '0'; }
        // 1 学校  2 食堂  3 家长  4 监管 
        $where = ['u.user_type'=>2, "u.user_cate" => 1,'u.user_status'=>1];
        //$field = 'u.*,s.name as s_name,s.address,r1.name as p_name,r2.name as c_name,r3.name as d_name';
        $field = 's.name as s_name,u.user_login,u.user_nickname,u.mobile';  
        $join = [
            ['__SCHOOL__ s','s.id=u.school_id'],
            ['__REGION__ r1','r1.id=s.province'],
            ['__REGION__ r2','r2.id=s.city'],
            ['__REGION__ r3','r3.id=s.district'],
        ];

        $list = Db::name('user')->alias('u')->join($join)->field($field)
            ->where(function (Query $query) use ($param) {
                if (!empty($param['keyword'])) {
                    $keyword = $param['keyword'];
                    $query->where('u.user_login|u.user_nickname|u.mobile', 'like', "%$keyword%");
                }
                if (!empty($param['school_id'])) {
                    $school_id = $param['school_id'];
                    $query->where('u.school_id', 'eq', $school_id);
                }
                if (!empty($param['province'])) {
                    $province = $param['province'];
                    $query->where('s.province', 'eq', $province);
                }
                if (!empty($param['city'])) {
                    $city = $param['city'];
                    $query->where('s.city', 'eq', $city);
                }
                if (!empty($param['district'])) {
                    $district = $param['district'];
                    $query->where('s.district', 'eq', $district);
                }

            })
            ->where($where)
            ->order("u.id DESC")
            ->select();
        $headArr = ['学校名称','账号','账号姓名','手机号码'];
        $fileName = date('YmdJHis').'-'.'学校账号导出文件.xls';
        cmf_export_excel($fileName,$headArr,$list);
        exit;

    }

    public function monitor() {
        $param = $this->request->param();
        if (empty($param['keyword'])) { $param['keyword'] = ''; }
        if (empty($param['province'])) { $param['province'] = '0'; }
        if (empty($param['city'])) { $param['city'] = '0'; }
        if (empty($param['district'])) { $param['district'] = '0'; }
        $this->assign('param',$param);

        // 1 学校  2 食堂  3 家长  4 监管 
        $where = ['u.user_type'=>2, "u.user_cate" => 4,'u.user_status'=>1];
        $field = 'u.*,r1.name as p_name,r2.name as c_name,r3.name as d_name';  
        $join = [
            ['__REGION__ r1','r1.id=u.province'],
            ['__REGION__ r2','r2.id=u.city','left'],
            ['__REGION__ r3','r3.id=u.district','left'],
        ];

        $list = Db::name('user')->alias('u')->join($join)->field($field)
            ->where(function (Query $query) use ($param) {
                if (!empty($param['keyword'])) {
                    $keyword = $param['keyword'];
                    $query->where('u.user_login|u.user_nickname|u.mobile', 'like', "%$keyword%");
                }
                if (!empty($param['province'])) {
                    $province = $param['province'];
                    $query->where('u.province', 'eq', $province);
                }
                if (!empty($param['city'])) {
                    $city = $param['city'];
                    $query->where('u.city', 'eq', $city);
                }
                if (!empty($param['district'])) {
                    $district = $param['district'];
                    $query->where('u.district', 'eq', $district);
                }

            })
            ->where($where)
            ->order("u.id DESC")
            ->paginate(10);
        // 获取分页显示
        $page = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $page);

        $this->getStaff($param);

        // 渲染模板输出
        return $this->fetch();
    }

    public function addMonitor() {
        return $this->fetch();
    }

    public function addMonitorPost() {
        $params = $this->request->param();
        $param = $params['post'];
        if (!isset($param['name']) or empty($param['name'])) {
            $this->error('请输入账户名！');
        }
        if (!isset($param['code']) or empty($param['code'])) {
            $this->error('请输入账号对应的密码！');
        }
        if (!isset($param['province']) or empty($param['province'])) {
            $this->error('请输入账号对应的监管省份！');
        }
        // if (!isset($param['city']) or empty($param['city'])) {
        //     $this->error('请输入账号对应的监管城市！');
        // }
        // if (!isset($param['district']) or empty($param['district'])) {
        //     $this->error('请输入账号对应的监管区域！');
        // }
        if(!preg_match("/^[_0-9a-zA-Z]{6,}$/i",$param['code'])){
            $this->error('密码长度要大于等于6位');
        }
        if (isset($param['mobile']) and !empty($param['mobile'])) {
            if (!cmf_check_mobile($param['mobile'])) {
                $this->error('手机号码格式不正确！');
            }
        }
        $post = [];
        $post['user_login'] = trim($param['name']);
        $post['user_nickname'] = trim($param['user_nickname']);
        $post['mobile'] = trim($param['mobile']);
        $post['user_pass'] = cmf_password(trim($param['code']));
        $post['province'] = $param['province'];
        $post['city'] = $param['city'];
        $post['district'] = $param['district'];
        $post['user_type'] = 2;
        $post['user_cate'] = 4;
        $post['user_status'] = 1;
        $post['create_time'] = time();
        $res = db::name('user')->insert($post);
        if (!empty($res)) {
            $this->success("会员添加成功！", "adminIndex/monitor");
        } else {
            $this->error('数据传入失败！');
        }
    }

    public function editMonitor() {
        $id = $this->request->param('id', 0, 'intval');
        $model = db::name('user');
        $post = $model->alias('u')
            //->join($join)->field($field)
            ->where('u.id', $id)->find();

        $this->assign('post', $post);

        $this->getStaff($post);

        return $this->fetch();
    }

    public function editMonitorPost() {
        if ($this->request->isPost()) {
            $update = [];
            $data = $this->request->param();

            $post   = $data['post'];
            if (!isset($post['id']) or empty($post['id'])) {
                $this->error('参数错误');
            }
            if (isset($post['code']) and !empty($post['code'])) {
                if(!preg_match("/^[_0-9a-zA-Z]{6,}$/i",$post['code'])){
                    $this->error('密码长度要大于等于6位');
                }
                $update['user_pass'] = cmf_password(trim($post['code']));
            }

            if (isset($post['mobile']) and !empty($post['mobile'])) {
                if (!cmf_check_mobile($post['mobile'])) {
                    $this->error('手机号码格式不正确！');
                }
            }
            if (!isset($post['province']) or empty($post['province'])) {
                $this->error('请输入账号对应的监管省份！');
            }
            // if (!isset($post['city']) or empty($post['city'])) {
            //     $this->error('请输入账号对应的监管城市！');
            // }
            // if (!isset($post['district']) or empty($post['district'])) {
            //     $this->error('请输入账号对应的监管区域！');
            // }
            
            $update['user_login'] = trim($post['name']);
            $update['user_nickname'] = trim($post['user_nickname']);
            $update['mobile'] = trim($post['mobile']);
            $update['province'] = $post['province'];
            $update['city'] = $post['city'];
            $update['district'] = $post['district'];
            db::name('user')->where('id',$post['id'])->update($update);

            $this->success('保存成功!', url('AdminIndex/monitor') );

        }
    }

    public function checkMonitor() {
        $id = $this->request->param('id', 0, 'intval');
        $field = 'u.*,r1.name as p_name,r2.name as c_name,r3.name as d_name';  
        $join = [
            ['__REGION__ r1','r1.id=u.province'],
            ['__REGION__ r2','r2.id=u.city','left'],
            ['__REGION__ r3','r3.id=u.district','left'],
        ];
        $model = db::name('user');
        $post = $model->alias('u')
            ->join($join)->field($field)
            ->where('u.id', $id)->find();
        $this->assign('post', $post);

        return $this->fetch();
    }

    /**
     * 本站用户禁用
     */
    public function banMonitor() {
        $id = input('param.id', 0, 'intval');
        if ($id) {
            $result = Db::name("user")->where(["id" => $id, "user_type" => 2, "user_cate" => 4])->setField('user_status', 0);
            if ($result) {
                $this->success("会员删除成功！", "adminIndex/monitor");
            } else {
                $this->error('会员删除失败,会员不存在,或者是管理员！');
            }
        } else {
            $this->error('数据传入失败！');
        }
    }

    public function importMonitor(){
        return $this->fetch();
    }

    public function importMonitorPost(){
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $path = 'upload/'.$post['files']; // 获取上传到服务器文件路径
            $ext = pathinfo($post['files'], PATHINFO_EXTENSION);

            $params = array(
                "datafile" => $path,
                "ext" => $ext,
            );
            $objImport = new ImportService();
            $result = $objImport->importMonitor($params);
            if ($result !== false) {
                $this->success("导入成功！", url("AdminIndex/monitor"));
            } else {
                $this->error("导入失败！");
            }
        }
    }

    public function exportMonitor() {
        $param = $this->request->param();
        if (empty($param['keyword'])) { $param['keyword'] = ''; }
        if (empty($param['province'])) { $param['province'] = '0'; }
        if (empty($param['city'])) { $param['city'] = '0'; }
        if (empty($param['district'])) { $param['district'] = '0'; }
        // 1 学校  2 食堂  3 家长  4 监管 
        $where = ['u.user_type'=>2, "u.user_cate" => 4,'u.user_status'=>1];
        //$field = 'u.*,s.name as s_name,s.address,r1.name as p_name,r2.name as c_name,r3.name as d_name';
        $field = 'concat(r1.name,"-",r2.name,"-",r3.name) as area,u.user_login,u.user_nickname,u.mobile';  
        $join = [
            ['__REGION__ r1','r1.id=u.province','left'],
            ['__REGION__ r2','r2.id=u.city','left'],
            ['__REGION__ r3','r3.id=u.district','left'],
        ];

        $list = Db::name('user')->alias('u')->join($join)->field($field)
            ->where(function (Query $query) use ($param) {
                if (!empty($param['keyword'])) {
                    $keyword = $param['keyword'];
                    $query->where('u.user_login|u.user_nickname|u.mobile', 'like', "%$keyword%");
                }
                if (!empty($param['province'])) {
                    $province = $param['province'];
                    $query->where('u.province', 'eq', $province);
                }
                if (!empty($param['city'])) {
                    $city = $param['city'];
                    $query->where('u.city', 'eq', $city);
                }
                if (!empty($param['district'])) {
                    $district = $param['district'];
                    $query->where('u.district', 'eq', $district);
                }

            })
            ->where($where)
            ->order("u.id DESC")
            ->select();
        $headArr = ['监管区域','账号','账号姓名','手机号码'];
        $fileName = date('YmdJHis').'-'.'行政监管账号导出文件.xls';
        cmf_export_excel($fileName,$headArr,$list);
        exit;

    }

    public function parent() {
        $param = $this->request->param();
        if (empty($param['keyword'])) { $param['keyword'] = ''; }
        if (empty($param['province'])) { $param['province'] = '0'; }
        if (empty($param['city'])) { $param['city'] = '0'; }
        if (empty($param['district'])) { $param['district'] = '0'; }
        $this->assign('param',$param);

        // 1 学校  2 食堂  3 家长  4 监管 
        $where = ['u.user_type'=>2, "u.user_cate" => 3,'u.user_status'=>1];
        $field = 'u.*,s.name as s_name,s.address,r1.name as p_name,r2.name as c_name,r3.name as d_name';  
        $join = [
            ['__SCHOOL__ s','s.id=u.school_id','left'],
            ['__REGION__ r1','r1.id=s.province','left'],
            ['__REGION__ r2','r2.id=s.city','left'],
            ['__REGION__ r3','r3.id=s.district','left'],
        ];

        $list = Db::name('user')->alias('u')->join($join)->field($field)
            ->where(function (Query $query) use ($param) {
                $query->where('s.status', 'gt', 0);
                if (!empty($param['keyword'])) {
                    $keyword = $param['keyword'];
                    $query->where('u.user_login|u.user_nickname|u.mobile', 'like', "%$keyword%");
                }
                if (!empty($param['school_id'])) {
                    $school_id = $param['school_id'];
                    $query->where('u.school_id', 'eq', $school_id);
                }
                if (!empty($param['province'])) {
                    $province = $param['province'];
                    $query->where('s.province', 'eq', $province);
                }
                if (!empty($param['city'])) {
                    $city = $param['city'];
                    $query->where('s.city', 'eq', $city);
                }
                if (!empty($param['district'])) {
                    $district = $param['district'];
                    $query->where('s.district', 'eq', $district);
                }

            })
            ->where($where)
            ->order("u.id DESC")
            ->paginate(10);
        // 获取分页显示
        $page = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $page);

        $school = db::name('school')->where('status',1)->select();
        $this->assign('school', $school);

        $this->getStaff($param);

        // 渲染模板输出
        return $this->fetch();
    }

    public function addParent() {
        return $this->fetch();
    }

    public function addParentPost() {
        $params = $this->request->param();
        $param = $params['post'];
        if (!isset($param['name']) or empty($param['name'])) {
            $this->error('请输入账户名！');
        }
        if (!isset($param['code']) or empty($param['code'])) {
            $this->error('请输入账号对应的密码！');
        }
        if(!preg_match("/^[_0-9a-zA-Z]{6,}$/i",$param['code'])){
            $this->error('密码长度要大于等于6位');
        }
        if (!isset($param['school_id']) or empty($param['school_id'])) {
            $this->error('请选择所属学校！');
        }
        if (isset($param['mobile']) and !empty($param['mobile'])) {
            if (!cmf_check_mobile($param['mobile'])) {
                $this->error('手机号码格式不正确！');
            }
        }
        $post = [];
        $post['user_login'] = trim($param['name']);
        $post['user_nickname'] = trim($param['user_nickname']);
        $post['mobile'] = trim($param['mobile']);
        $post['user_pass'] = cmf_password(trim($param['code']));
        $post['school_id'] = $param['school_id'];
        $post['user_type'] = 2;
        $post['user_cate'] = 3;
        $post['user_status'] = 1;
        $post['is_vip'] = trim($param['is_vip']);
        $post['create_time'] = time();
        $res = db::name('user')->insert($post);
        if (!empty($res)) {
            $this->success("会员添加成功！", "adminIndex/parent");
        } else {
            $this->error('数据传入失败！');
        }
    }

    public function editParent() {
        $id = $this->request->param('id', 0, 'intval');
        $model = db::name('user');
        $post = $model->alias('u')
            //->join($join)->field($field)
            ->where('u.id', $id)->find();
        $school = db::name('school')->where('id','in',$post['school_id'])->column('name');
        $school_name = implode(',', $school);
        $this->assign('school_name', $school_name);
        $this->assign('post', $post);

        return $this->fetch();
    }

    public function editParentPost() {
        if ($this->request->isPost()) {
            $update = [];
            $data = $this->request->param();

            $post   = $data['post'];
            if (!isset($post['id']) or empty($post['id'])) {
                $this->error('参数错误');
            }
            if (isset($post['code']) and !empty($post['code'])) {
                if(!preg_match("/^[_0-9a-zA-Z]{6,}$/i",$post['code'])){
                    $this->error('密码长度要大于等于6位');
                }
                $update['user_pass'] = cmf_password(trim($post['code']));
            }
            
            if (!isset($post['school_id']) or empty($post['school_id'])) {
                $this->error('请选择所属学校！');
            }

            if (isset($post['mobile']) and !empty($post['mobile'])) {
                if (!cmf_check_mobile($post['mobile'])) {
                    $this->error('手机号码格式不正确！');
                }
            }
            
            $update['user_login'] = trim($post['name']);
            $update['user_nickname'] = trim($post['user_nickname']);
            $update['mobile'] = trim($post['mobile']);
            $update['is_vip'] = trim($post['is_vip']);
            $update['school_id'] = $post['school_id'];
            db::name('user')->where('id',$post['id'])->update($update);

            $this->success('保存成功!', url('AdminIndex/parent') );

        }
    }

    public function checkParent() {
        $id = $this->request->param('id', 0, 'intval');
        $model = db::name('user');
        $post = $model->alias('u')
            //->join($join)->field($field)
            ->where('u.id', $id)->find();
        $school = db::name('school')->where('id','in',$post['school_id'])->column('name');
        $school_name = implode(',', $school);
        $this->assign('school_name', $school_name);
        $this->assign('post', $post);

        return $this->fetch();
    }

    /**
     * 本站用户禁用
     */
    public function banParent() {
        $id = input('param.id', 0, 'intval');
        if ($id) {
            $result = Db::name("user")->where(["id" => $id, "user_type" => 2, "user_cate" => 3])->setField('user_status', 0);
            if ($result) {
                $this->success("会员删除成功！", "adminIndex/parent");
            } else {
                $this->error('会员删除失败,会员不存在,或者是管理员！');
            }
        } else {
            $this->error('数据传入失败！');
        }
    }

    public function importParent(){
        return $this->fetch();
    }

    public function importParentPost(){
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $path = 'upload/'.$post['files']; // 获取上传到服务器文件路径
            $ext = pathinfo($post['files'], PATHINFO_EXTENSION);

            $params = array(
                "datafile" => $path,
                "ext" => $ext,
            );
            $objImport = new ImportService();
            $result = $objImport->importParent($params);
            if ($result !== false) {
                $this->success("导入成功！", url("AdminIndex/parent"));
            } else {
                $this->error("导入失败！");
            }
        }
    }

    public function exportParent() {
        $param = $this->request->param();
        if (empty($param['keyword'])) { $param['keyword'] = ''; }
        if (empty($param['school_id'])) { $param['school_id'] = '0'; }
        if (empty($param['province'])) { $param['province'] = '0'; }
        if (empty($param['city'])) { $param['city'] = '0'; }
        if (empty($param['district'])) { $param['district'] = '0'; }
        // 1 学校  2 食堂  3 家长  4 监管 
        $where = ['u.user_type'=>2, "u.user_cate" => 3,'u.user_status'=>1];
        //$field = 'u.*,s.name as s_name,s.address,r1.name as p_name,r2.name as c_name,r3.name as d_name';
        $field = 's.name as s_name,u.user_login,u.user_nickname,u.mobile';  
        $join = [
            ['__SCHOOL__ s','s.id=u.school_id'],
            ['__REGION__ r1','r1.id=s.province'],
            ['__REGION__ r2','r2.id=s.city'],
            ['__REGION__ r3','r3.id=s.district'],
        ];

        $list = Db::name('user')->alias('u')->join($join)->field($field)
            ->where(function (Query $query) use ($param) {
                if (!empty($param['keyword'])) {
                    $keyword = $param['keyword'];
                    $query->where('u.user_login|u.user_nickname|u.mobile', 'like', "%$keyword%");
                }
                if (!empty($param['school_id'])) {
                    $school_id = $param['school_id'];
                    $query->where('u.school_id', 'eq', $school_id);
                }
                if (!empty($param['province'])) {
                    $province = $param['province'];
                    $query->where('s.province', 'eq', $province);
                }
                if (!empty($param['city'])) {
                    $city = $param['city'];
                    $query->where('s.city', 'eq', $city);
                }
                if (!empty($param['district'])) {
                    $district = $param['district'];
                    $query->where('s.district', 'eq', $district);
                }

            })
            ->where($where)
            ->order("u.id DESC")
            ->select();
        $headArr = ['学校名称','账号','账号姓名','手机号码'];
        $fileName = date('YmdJHis').'-'.'家长账号导出文件.xls';
        cmf_export_excel($fileName,$headArr,$list);
        exit;

    }

    /**
     * 食堂用户列表
     */
    public function canteen() {
        $param = $this->request->param();
        if (empty($param['keyword'])) { $param['keyword'] = ''; }
        if (empty($param['school_id'])) { $param['school_id'] = '0'; }
        if (empty($param['canteen_id'])) { $param['canteen_id'] = '0'; }
        if (empty($param['province'])) { $param['province'] = '0'; }
        if (empty($param['city'])) { $param['city'] = '0'; }
        if (empty($param['district'])) { $param['district'] = '0'; }
        $this->assign('param',$param);

        // user_cate 1 学校  2 食堂  3 家长  4 监管 
        $where = ['u.user_type'=>2, "u.user_cate" => 2,'u.user_status'=>1];
        $field = 'u.*,c.name as c_name,s.name as s_name,s.address,r1.name as p_name,r2.name as c_name,r3.name as d_name';  
        $join = [
            ['__CANTEEN__ c','c.id=u.canteen_id'],
            ['__SCHOOL__ s','s.id=u.school_id'],
            ['__REGION__ r1','r1.id=s.province'],
            ['__REGION__ r2','r2.id=s.city'],
            ['__REGION__ r3','r3.id=s.district'],
        ];

        $list = Db::name('user')->alias('u')->join($join)->field($field)
            ->where(function (Query $query) use ($param) {
                $query->where('s.status', 'gt', 0);
                $query->where('c.status', 'gt', 0);
                if (!empty($param['keyword'])) {
                    $keyword = $param['keyword'];
                    $query->where('u.user_login|u.user_nickname|u.mobile', 'like', "%$keyword%");
                }
                if (!empty($param['school_id'])) {
                    $school_id = $param['school_id'];
                    $query->where('u.school_id', 'eq', $school_id);
                }
                if (!empty($param['canteen_id'])) {
                    $canteen_id = $param['canteen_id'];
                    $query->where('u.canteen_id', 'eq', $canteen_id);
                }
                if (!empty($param['province'])) {
                    $province = $param['province'];
                    $query->where('s.province', 'eq', $province);
                }
                if (!empty($param['city'])) {
                    $city = $param['city'];
                    $query->where('s.city', 'eq', $city);
                }
                if (!empty($param['district'])) {
                    $district = $param['district'];
                    $query->where('s.district', 'eq', $district);
                }

            })
            ->where($where)
            ->order("u.id DESC")
            ->paginate(10);
        // 获取分页显示
        $page = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $page);

        $school = db::name('school')->where('status',1)->select();
        $this->assign('school', $school);

        $this->getStaff($param);

        // 渲染模板输出
        return $this->fetch();
    }

    public function addCanteen() {
        return $this->fetch();
    }

    public function addCanteenPost() {
        $params = $this->request->param();
        $param = $params['post'];
        if (!isset($param['name']) or empty($param['name'])) {
            $this->error('请输入账户名！');
        }
        if (!isset($param['code']) or empty($param['code'])) {
            $this->error('请输入账号对应的密码！');
        }
        if(!preg_match("/^[_0-9a-zA-Z]{6,}$/i",$param['code'])){
            $this->error('密码长度要大于等于6位');
        }
        if (!isset($param['school_id']) or empty($param['school_id'])) {
            $this->error('请选择所属学校！');
        }
        if (!isset($param['canteen_id']) or empty($param['canteen_id'])) {
            $this->error('请选择所属食堂！');
        }
        if (isset($param['mobile']) and !empty($param['mobile'])) {
            if (!cmf_check_mobile($param['mobile'])) {
                $this->error('手机号码格式不正确！');
            }
        }

        $post = [];
        $post['user_login'] = trim($param['name']);
        $post['user_nickname'] = trim($param['user_nickname']);
        $post['mobile'] = trim($param['mobile']);
        $post['user_pass'] = cmf_password(trim($param['code']));
        $post['school_id'] = $param['school_id'];
        $post['canteen_id'] = $param['canteen_id'];
        $post['user_type'] = 2;
        $post['user_cate'] = 2;
        $post['user_status'] = 1;
        $post['create_time'] = time();
        $res = db::name('user')->insert($post);
        if (!empty($res)) {
            $this->success("会员添加成功！", "adminIndex/canteen");
        } else {
            $this->error('数据传入失败！');
        }
    }

    public function editCanteen() {
        $id = $this->request->param('id', 0, 'intval');
        $model = db::name('user');
        $post = $model->alias('u')
            //->join($join)->field($field)
            ->where('u.id', $id)->find();
        $school = db::name('school')->where('id','in',$post['school_id'])->column('name');
        $school_name = implode(',', $school);
        $this->assign('school_name', $school_name);
        $canteen = db::name('canteen')->where('id','in',$post['canteen_id'])->column('name');
        $canteen_name = implode(',', $canteen);
        $this->assign('canteen_name', $canteen_name);
        $this->assign('post', $post);

        return $this->fetch();
    }

    public function editCanteenPost() {
        if ($this->request->isPost()) {
            $update = [];
            $data = $this->request->param();

            $post   = $data['post'];
            if (!isset($post['id']) or empty($post['id'])) {
                $this->error('参数错误');
            }
            if (isset($post['code']) and !empty($post['code'])) {
                if(!preg_match("/^[_0-9a-zA-Z]{6,}$/i",$post['code'])){
                    $this->error('密码长度要大于等于6位');
                }
                // if(!preg_match("/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,}$/",$post['code'])){
                //     $this->error('密码要求数字和字母组合');
                // }
                $update['user_pass'] = cmf_password(trim($post['code']));
            }
            
            if (!isset($post['school_id']) or empty($post['school_id'])) {
                $this->error('请选择所属学校！');
            }
            if (!isset($post['canteen_id']) or empty($post['canteen_id'])) {
                $this->error('请选择所属学校食学堂！');
            }

            if (isset($post['mobile']) and !empty($post['mobile'])) {
                if (!cmf_check_mobile($post['mobile'])) {
                    $this->error('手机号码格式不正确！');
                }
            }
            
            $update['user_login'] = trim($post['name']);
            $update['user_nickname'] = trim($post['user_nickname']);
            $update['mobile'] = trim($post['mobile']);
            $update['school_id'] = $post['school_id'];
            $update['canteen_id'] = $post['canteen_id'];
            db::name('user')->where('id',$post['id'])->update($update);
            $this->success('保存成功!', url('AdminIndex/canteen') );

        }
    }

    public function checkCanteen() {
        $id = $this->request->param('id', 0, 'intval');
        $model = db::name('user');
        $post = $model->alias('u')
            //->join($join)->field($field)
            ->where('u.id', $id)->find();
        $school = db::name('school')->where('id','in',$post['school_id'])->column('name');
        $school_name = implode(',', $school);
        $this->assign('school_name', $school_name);
        $canteen = db::name('canteen')->where('id','in',$post['canteen_id'])->column('name');
        $canteen_name = implode(',', $canteen);
        $this->assign('canteen_name', $canteen_name);
        $this->assign('post', $post);

        return $this->fetch();
    }

    /**
     * 本站食堂用户禁用
     */
    public function banCanteen() {
        $id = input('param.id', 0, 'intval');
        if ($id) {
            $result = Db::name("user")->where(["id" => $id, "user_type" => 2, "user_cate" => 2])->setField('user_status', 0);
            if ($result) {
                $this->success("会员删除成功！", "adminIndex/canteen");
            } else {
                $this->error('会员删除失败,会员不存在,或者是管理员！');
            }
        } else {
            $this->error('数据传入失败！');
        }
    }

    public function importCanteen(){
        return $this->fetch();
    }

    public function importCanteenPost(){
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $path = 'upload/'.$post['files']; // 获取上传到服务器文件路径
            $ext = pathinfo($post['files'], PATHINFO_EXTENSION);

            $params = array(
                "datafile" => $path,
                "ext" => $ext,
            );
            $objImport = new ImportService();
            $result = $objImport->importCanteen($params);
            if ($result !== false) {
                $this->success("导入成功！", url("AdminIndex/canteen"));
            } else {
                $this->error("导入失败！");
            }
        }
    }

    public function exportCanteen() {
        $param = $this->request->param();
        if (empty($param['keyword'])) { $param['keyword'] = ''; }
        if (empty($param['school_id'])) { $param['school_id'] = '0'; }
        if (empty($param['canteen_id'])) { $param['canteen_id'] = '0'; }
        if (empty($param['province'])) { $param['province'] = '0'; }
        if (empty($param['city'])) { $param['city'] = '0'; }
        if (empty($param['district'])) { $param['district'] = '0'; }
        // 1 学校  2 食堂  3 家长  4 监管 
        $where = ['u.user_type'=>2, "u.user_cate" => 2,'u.user_status'=>1];
        //$field = 'u.*,s.name as s_name,s.address,r1.name as p_name,r2.name as c_name,r3.name as d_name';
        $field = 's.name as s_name,c.name as c_name,u.user_login,u.user_nickname,u.mobile';  
        $join = [
            ['__SCHOOL__ s','s.id=u.school_id'],
            ['__CANTEEN__ c','c.id=u.canteen_id'],
            ['__REGION__ r1','r1.id=s.province'],
            ['__REGION__ r2','r2.id=s.city'],
            ['__REGION__ r3','r3.id=s.district'],
        ];

        $list = Db::name('user')->alias('u')->join($join)->field($field)
            ->where(function (Query $query) use ($param) {
                if (!empty($param['keyword'])) {
                    $keyword = $param['keyword'];
                    $query->where('u.user_login|u.user_nickname|u.mobile', 'like', "%$keyword%");
                }
                if (!empty($param['school_id'])) {
                    $school_id = $param['school_id'];
                    $query->where('u.school_id', 'eq', $school_id);
                }
                if (!empty($param['canteen_id'])) {
                    $canteen_id = $param['canteen_id'];
                    $query->where('u.canteen_id', 'eq', $canteen_id);
                }
                if (!empty($param['province'])) {
                    $province = $param['province'];
                    $query->where('s.province', 'eq', $province);
                }
                if (!empty($param['city'])) {
                    $city = $param['city'];
                    $query->where('s.city', 'eq', $city);
                }
                if (!empty($param['district'])) {
                    $district = $param['district'];
                    $query->where('s.district', 'eq', $district);
                }

            })
            ->where($where)
            ->order("u.id DESC")
            ->select();
        $headArr = ['学校名称','学校食堂名称','账号','账号姓名','手机号码'];
        $fileName = date('YmdJHis').'-'.'学校食堂账号导出文件.xls';
        cmf_export_excel($fileName,$headArr,$list);
        exit;

    }

}
