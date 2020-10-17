<?php
namespace app\card\controller;

use cmf\controller\CardBaseController;
use app\organization\service\PostService;
use app\organization\model\CanteenModel;
use think\Db;

class CanteenController extends CardBaseController {

    public function index() {
        $param = $this->request->param();
        $html = 'index';
        
        $userInfo = $this->userInfo;
        if ($userInfo['user_cate']==4) {  //行政人员
            $cate_param = ['province_id'=>$userInfo['province'],'city_id'=>$userInfo['city'],'district_id'=>$userInfo['district'],'province'=>$userInfo['province'],'city'=>$userInfo['city'],'district'=>$userInfo['district'],];
            $param = array_merge($cate_param,$param);
            $this->getSchoolAndCanteen($param);
            $this->getStaff($param);
            $html = 'index4';
        }
        $this->assign('user', $userInfo);
        $this->assign('param', $param);

        return $this->fetch($html);
    }

    public function ajaxlist() {
        $param = $this->request->param();
        $service = new PostService();
        // $param['type'] = 2;
        $school_id = $this->schoolId;
        if (empty($param['school_id'])) {
            $param['school_id'] = $school_id;
        }

        $userInfo = $this->userInfo;
        if ($userInfo['user_cate']==4) {  //行政人员
            $cate_param = ['province'=>$userInfo['province'],'city'=>$userInfo['city'],'district'=>$userInfo['district'],];
            $param = array_merge($cate_param,$param);
            $s_c = $this->getSchoolAndCanteen($param);
        }

        $list = $service->canteenList($param);
        $list ->appends($param);
        $this->assign('list', $list);
        return $this->fetch();
    }

    public function info() {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) {
            $canteen_id = $this->canteenId;
            //$userId = $this->userId;
            if (empty($canteen_id)) {
                $this->error('参数错误');
            } else {
                $id = $canteen_id;
            }
        }
        $service = new PostService();
        $res = $service->canteenInfo($id);
        
        $status = get_status(1);
        $userInfo = $this->userInfo;
        $res['role'] = $userInfo['user_cate'];
        $res['status_name'] = $status[$res['status']];
        $this->assign('res', $res);

        $user_id = $this->userId;
        $this->assign('user_id', $user_id);  //自身id
        return $this->fetch();
    }

    public function add() {
        //$id = $this->request->param('id', 0, 'intval');
        //$user_id = $this->getUserId();  //用户id
        return $this->fetch();
    }

    public function editPost() {
        if ($this->request->isPost()) {
            $post = $this->request->param();  //提交的信息
            $user_id = $this->getUserId();  //用户id
            $canteen_id = $this->canteenId;  //食堂id
            if (empty($canteen_id)) {
                $this->error('参数错误');
            }
            $type = (isset($post['type']) and !empty($post['type']))?$post['type']:'normal';
            unset($post['type']);
            $service = new PostService();
            $res = $service->canteenInfo($canteen_id);
            $more = $res['more'];
            switch ($type) {
        		case 'permit':
        		case 'licence':
        		case 'admin':
        			$more[$type] = $post['more'][$type];
        			break;
        		case 'kitchen':
        		    $more['wash'] = $post['more']['wash'];
                	$more['cut'] = $post['more']['cut'];
                	$more['fly'] = $post['more']['fly'];
                	$more['combine'] = $post['more']['combine'];
                    $more['poison'] = $post['more']['poison'];
        			break;
        		case 'staff':
        			break;
        		case 'normal':
                    $more['content'] = $post['more']['content'];
                    $more['image'] = $post['more']['image'];
                    $more['pics'] = $post['more']['pics'];
                    break;
                case 'post':
                    break;
        		default:
        			break;
        	}
            
            $post['more'] = $more;
            $post['id'] = $canteen_id;

            $model = new CanteenModel();
            $res = $model->adminEdit($post);
            if ($res !== false) {
                $this->success("提交成功",url('canteen/info') );
            } else {
                $this->error('提交成功');
            }
        }
    }

    public function edit() {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) {
            $canteen_id = $this->canteenId;
            //$userId = $this->userId;
            if (empty($canteen_id)) {
                $this->error('参数错误');
            } else {
                $id = $canteen_id;
            }
        }
        $model = new CanteenModel();
        $post = $model->alias('s')->where('s.id', $id)->find();
        $post['pics_count'] = 0;
        if (isset($post['more']['pics']) and !empty($post['more']['pics'])) {
            $post['pics'] = $post['more']['pics'];
            $post['pics_count'] = count($post['pics']);
        }
        $this->assign('post', $post);
        return $this->fetch();
    }

    public function editMore() {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) {
            $canteen_id = $this->canteenId;
            //$userId = $this->userId;
            if (empty($canteen_id)) {
                $this->error('参数错误');
            } else {
                $id = $canteen_id;
            }
        }
        $type = $this->request->param('type','');
        if (empty($type)) {
            $type = 'permit';
            //$this->error('类型参数错误');
        }
        $type_name = '';
        switch ($type) {
        	case 'permit':
                $type_name = '食堂营业执照';
                $html = 'edit_more';
                break;
        	case 'licence':
                $type_name = '食品经营许可证';
                $html = 'edit_more';
                break;
        	case 'admin':
        		$type_name = '食品安全管理员证';
                $html = 'edit_more';
                break;
        	case 'kitchen':
        		$html = 'edit_kitchen';
        		break;
        	case 'staff':
        		$html = 'edit_staff';
        		break;
        	default:
        		$html = 'edit_more';
        		break;
        }
        $this->assign('type', $type);
        $this->assign('type_name', $type_name);

        $model = new CanteenModel();
        $post = $model->alias('s')->where('s.id', $id)->field('id,name,more')->find();
        $post['pics_count'] = 0;
        $value = (isset($post['more'][$type]) and !empty($post['more'][$type]))?$post['more'][$type]:'';
        $this->assign('value', $value);
        $this->assign('post', $post);
        return $this->fetch($html);
    }

    public function post() {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) {
            $canteen_id = $this->canteenId;
            //$userId = $this->userId;
            if (empty($canteen_id)) {
                $this->error('参数错误');
            } else {
                $id = $canteen_id;
            }
        }
        $model = new CanteenModel();
        $post = $model->alias('s')->where('s.id', $id)->find();
        $post['pics_count'] = 0;
        if (isset($post['more']['pics']) and !empty($post['more']['pics'])) {
            $post['pics'] = $post['more']['pics'];
            $post['pics_count'] = count($post['pics']);
        }
        $post['count_staff'] = db::name('canteenStaff')->where('canteen_id',$id)->where('status',1)->count();
        $post['count_job'] = db::name('canteenJob')->where('canteen_id',$id)->where('status',1)->count();
        $level = db::name('canteenLevel')->where('status',1)->select();
        $this->assign('level', $level);
        $this->assign('post', $post);
        return $this->fetch();
    }

    // 食堂提交审核
    public function postVerify() {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) {
            $canteen_id = $this->canteenId;
            //$userId = $this->userId;
            if (empty($canteen_id)) {
                $this->error('参数错误');
            } else {
                $id = $canteen_id;
            }
        }
        $post = $this->request->param();  //提交的信息
        $model = new CanteenModel();
        $data = $model->alias('s')->where('s.id', $id)->find();
        if (!isset($data['more']['content']) or empty($data['more']['content'])) {
            $this->error('请填写食堂简介');
        }
        if (!isset($data['more']['permit']) or empty($data['more']['permit'])) {
            $this->error('请填写食堂营业执照');
        }
        if (!isset($data['more']['licence']) or empty($data['more']['licence'])) {
            $this->error('请填写食堂经营许可证');
        }
        if (!isset($data['more']['admin']) or empty($data['more']['admin'])) {
            $this->error('请填写食品安全管理员证');
        }
        if (!isset($data['more']['cut']) or empty($data['more']['cut'])) {
            $this->error('请填写后厨环境图片');
        }
        $data['count_staff'] = db::name('canteenStaff')->where('canteen_id',$id)->where('status',1)->count();
        if (empty($data['count_staff'])) {
            $this->error('请填写工作人员');
        }
        if (empty($post['level'])) {
            $this->error('请选择餐饮量化分级');
        }
        if (empty($post['contact'])) {
            $this->error('请填写联系人姓名');
        }
        if (empty($post['mobile'])) {
            $this->error('请填写联系方式');
        }
        $post['status'] = 1;
        $post['id'] = $id;
        $model = new CanteenModel();
        $res = $model->adminEdit($post);
        if (!empty($res)) {
            $this->success("提交审核成功",url('index/index') );
        } else {
            $this->error('提交失败');
        }
    }

    // 工作人员整体列表
    public function postStaff() {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) {
            $canteen_id = $this->canteenId;
            //$userId = $this->userId;
            if (empty($canteen_id)) {
                $this->error('参数错误');
            } else {
                $id = $canteen_id;
            }
        }
        $field = 'cs.*,j.name as j_name';
        $list = db::name('canteenStaff')->alias('cs')->join('__CANTEEN_JOB__ j','cs.job=j.id')->where('cs.canteen_id', $id)->field($field)->select();
        $this->assign('list', $list);
        return $this->fetch();
    }

    // 工作人员明细页面
    public function editStaff() {
        $canteen_id = $this->canteenId;
        $where = ['cs.status'=>1];
        if (empty($canteen_id)) {
            $this->error('参数错误');
        } else {
            $where['cs.canteen_id'] = $canteen_id;
        }
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) {
            $post = [];
        } else {
            $post = db::name('canteenStaff')->alias('cs')->where('cs.id', $id)->field('cs.*')->find();
        }
        
        $job = db::name('canteenJob')->where('canteen_id',$canteen_id)->where('status',1)->select();
        $this->assign('job', $job);
        $this->assign('post', $post);
        return $this->fetch();
    }

    public function editStaffPost() {
        if ($this->request->isPost()) {
            $post = $this->request->param();  //提交的信息
            $user_id = $this->getUserId();  //用户id
            $school_id = $this->schoolId;  //学校id
            $canteen_id = $this->canteenId;  //食堂id
            if (empty($school_id) or empty($canteen_id)) {
                $this->error('参数错误');
            }
            
            if (empty($post['name'])) {
                $this->error('请输入人员姓名');
            } else {
                $post['name'] = trim($post['name']);
            }
            if (empty($post['job'])) {
                $this->error('请选择职务');
            }
            if (empty($post['cover'])) {
                $this->error('请上传健康证');
            }
            $model = db::name('canteenStaff');
            if (isset($post['id']) and !empty($post['id'])) {  //编辑
                $res = $model->where('id',$post['id'])->update($post);
            } else {   //添加
                $post['school_id'] = $school_id;
                $post['canteen_id'] = $canteen_id;
                $post['create_time'] = time();
                $res = $model->insert($post);
            }
            if (!empty($res)) {
                $this->success("提交成功",url('canteen/postStaff') );
            } else {
                $this->error('提交成功');
            }
        }
    }

    // 存放工作人员、食物、供应商信息的地方
    public function more() {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) {
            $canteen_id = $this->canteenId;
            //$userId = $this->userId;
            if (empty($canteen_id)) {
                $this->error('参数错误');
            } else {
                $id = $canteen_id;
            }
        }
        $model = new CanteenModel();
        $post = $model->alias('s')->where('s.id', $id)->find();
        //$post['count_food'] = db::name('food')->where('canteen_id',$id)->where('status',1)->count();
        $post['count_supplier'] = db::name('canteen_supplier')->where('canteen_id',$id)->where('status',1)->count();
        $post['count_staff'] = db::name('canteenStaff')->where('canteen_id',$id)->where('status',1)->count();
        $post['count_job'] = db::name('canteenJob')->where('canteen_id',$id)->where('status',1)->count();
        $this->assign('post', $post);
        return $this->fetch();
    }

    // 食品整体列表
    public function postFood() {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) {
            $canteen_id = $this->canteenId;
            //$userId = $this->userId;
            if (empty($canteen_id)) {
                $this->error('参数错误');
            } else {
                $id = $canteen_id;
            }
        }
        $field = 'f.*';
        $list = db::name('food')->alias('f')->where('f.canteen_id', $id)->where('f.status', 1)->field($field)->select();
        $this->assign('list', $list);
        return $this->fetch();
    }

    // 食品明细页面
    public function editFood() {
        $canteen_id = $this->canteenId;
        $where = ['cs.status'=>1];
        if (empty($canteen_id)) {
            $this->error('参数错误');
        } else {
            $where['cs.canteen_id'] = $canteen_id;
        }
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) {
            $post = [];
        } else {
            $post = db::name('Food')->alias('cs')->where('cs.id', $id)->where('cs.id', $id)->field('cs.*')->find();
        }
        
        $this->assign('post', $post);
        return $this->fetch();
    }

    public function editFoodPost() {
        if ($this->request->isPost()) {
            $post = $this->request->param();  //提交的信息
            $user_id = $this->getUserId();  //用户id
            $school_id = $this->schoolId;  //学校id
            $canteen_id = $this->canteenId;  //食堂id
            if (empty($school_id) or empty($canteen_id)) {
                $this->error('参数错误');
            }
            
            if (empty($post['name'])) {
                $this->error('请输入人员姓名');
            } else {
                $post['name'] = trim($post['name']);
            }
            $model = db::name('Food');
            if (isset($post['id']) and !empty($post['id'])) {  //编辑
                $res = $model->where('id',$post['id'])->update($post);
            } else {   //添加
                $post['school_id'] = $school_id;
                $post['canteen_id'] = $canteen_id;
                $post['create_time'] = time();
                $res = $model->insert($post);
            }
            if (!empty($res)) {
                $this->success("提交成功",url('canteen/postFood') );
            } else {
                $this->error('提交成功');
            }
        }
    }

    // 食品整体列表
    public function postSupplier() {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) {
            $canteen_id = $this->canteenId;
            //$userId = $this->userId;
            if (empty($canteen_id)) {
                $this->error('参数错误');
            } else {
                $id = $canteen_id;
            }
        }
        $field = 'cs.*';
        $list = db::name('canteenSupplier')->alias('cs')->where('cs.canteen_id', $id)->field($field)->select();
        $this->assign('list', $list);
        return $this->fetch();
    }

    // 食品明细页面
    public function editSupplier() {
        $canteen_id = $this->canteenId;
        $where = ['cs.status'=>1];
        if (empty($canteen_id)) {
            $this->error('参数错误');
        } else {
            $where['cs.canteen_id'] = $canteen_id;
        }
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) {
            $post = [];
        } else {
            $post = db::name('canteenSupplier')->alias('cs')->where('cs.id', $id)->field('cs.*')->find();
        }
        
        $this->assign('post', $post);
        return $this->fetch();
    }

    public function editSupplierPost() {
        if ($this->request->isPost()) {
            $post = $this->request->param();  //提交的信息
            $user_id = $this->getUserId();  //用户id
            $school_id = $this->schoolId;  //学校id
            $canteen_id = $this->canteenId;  //食堂id
            if (empty($school_id) or empty($canteen_id)) {
                $this->error('参数错误');
            }
            
            if (empty($post['name'])) {
                $this->error('请输入供应商名称');
            } else {
                $post['name'] = trim($post['name']);
            }
            if (empty($post['contact'])) {
                $this->error('请输入供应商联系人姓名');
            }
            if (empty($post['cover'])) {
                $this->error('请上传相关证明');
            }
            $model = db::name('canteenSupplier');
            if (isset($post['id']) and !empty($post['id'])) {  //编辑
                $res = $model->where('id',$post['id'])->update($post);
            } else {   //添加
                $post['school_id'] = $school_id;
                $post['canteen_id'] = $canteen_id;
                $post['create_time'] = time();
                $res = $model->insert($post);
            }
            if (!empty($res)) {
                $this->success("提交成功",url('canteen/postSupplier') );
            } else {
                $this->error('提交成功');
            }
        }
    }

    // 职位列表
    public function postJob() {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) {
            $canteen_id = $this->canteenId;
            //$userId = $this->userId;
            if (empty($canteen_id)) {
                $this->error('参数错误');
            } else {
                $id = $canteen_id;
            }
        }
        $field = 'cj.*';
        $list = db::name('canteenJob')->alias('cj')->where('cj.canteen_id', $id)->field($field)->select();
        $this->assign('list', $list);
        return $this->fetch();
    }

    // 职位明细页面
    public function editJob() {
        $canteen_id = $this->canteenId;
        $where = ['cj.status'=>1];
        if (empty($canteen_id)) {
            $this->error('参数错误');
        } else {
            $where['cj.canteen_id'] = $canteen_id;
        }
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) {
            $post = [];
        } else {
            $post = db::name('canteenJob')->alias('cj')->where('cj.id', $id)->field('cj.*')->find();
        }
        
        $this->assign('post', $post);
        return $this->fetch();
    }

    public function editJobPost() {
        if ($this->request->isPost()) {
            $post = $this->request->param();  //提交的信息
            $user_id = $this->getUserId();  //用户id
            $school_id = $this->schoolId;  //学校id
            $canteen_id = $this->canteenId;  //食堂id
            if (empty($school_id) or empty($canteen_id)) {
                $this->error('参数错误');
            }
            
            if (empty($post['name'])) {
                $this->error('请输入职务名称');
            } else {
                $post['name'] = trim($post['name']);
            }
            $model = db::name('canteenJob');
            if (isset($post['id']) and !empty($post['id'])) {  //编辑
                $res = $model->where('id',$post['id'])->update($post);
            } else {   //添加
                $post['school_id'] = $school_id;
                $post['canteen_id'] = $canteen_id;
                $post['create_time'] = time();
                $res = $model->insert($post);
            }
            if (!empty($res)) {
                $this->success("提交成功",url('canteen/postJob') );
            } else {
                $this->error('提交成功');
            }
        }
    }

}
