<?php

namespace app\organization\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use think\db\Query;
use app\organization\service\PostService;
use app\organization\model\SchoolModel;
use app\organization\model\CanteenModel;
use app\organization\model\SchoolTypeModel;

/**
 * 产品管理
 * Class ProductController
 */
class AdminIndexController extends AdminBaseController {
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index() {
        $param = $this->request->param();
        if (empty($param['keyword'])) { $param['keyword'] = ''; }
        if (empty($param['province'])) { $param['province'] = '0'; }
        if (empty($param['city'])) { $param['city'] = '0'; }
        if (empty($param['district'])) { $param['district'] = '0'; }
        $this->assign('param',$param);

        $service = new PostService();
        $data = $service->postList($param);
        $data->appends($param);

        $status = get_status(1);
        $this->assign('status',$status);

        $this->assign('data',$data);
        $this->assign('page', $data->render());

        $this->getStaff($param);

        return $this->fetch();
    }

    public function add() {
        // $type = db::name('schoolType')->where('status',1)->field('id,name')->select();
        // $this->assign('type',$type);
        return $this->fetch();
    }

    public function addPost() {
        if ($this->request->isPost()) {
            $data = $this->request->param();

            $data['post']['create_time'] = time();
            $data['post']['status'] = 3;
            $post = $data['post'];

            $result = $this->validate($post, 'School');
            if ($result !== true) {
                $this->error($result);
            }
            // if (isset($post['mobile']) and !empty($post['mobile'])) {
            //     if (!cmf_check_mobile($post['mobile']) ) {
            //         $this->error('手机号码格式不正确!');
            //     }
            // }

            $organizationModel = new SchoolModel();
            $organizationModel->adminAdd($data['post']);

            $this->success('添加成功!', url('AdminIndex/index') );
        }

    }

    public function edit() {

        $id = $this->request->param('id', 0, 'intval');
        // $join = [
        //     ['__REGION__ r1','r1.id=s.province'],
        //     ['__REGION__ r2','r2.id=s.city'],
        //     ['__REGION__ r3','r3.id=s.district'],
        // ];
        // $field = 's.*,r1.name as p_name,r2.name as c_name,r3.name as d_name';

        $organizationModel = new SchoolModel();
        $post = $organizationModel->alias('s')
            //->join($foin)->field($field)
            ->where('s.id', $id)->find();
        $type = db::name('schoolType')->where('id','in',$post['school_type'])->column('name');
        $type_name = implode(',', $type);
        $this->assign('type_name', $type_name);
        $this->assign('post', $post);

        $this->getStaff($post);
        return $this->fetch();
    }

    public function check() {
        $id = $this->request->param('id', 0, 'intval');
        $service = new PostService();
        $post = $service->published($id);

        $type = db::name('schoolType')->where('id','in',$post['school_type'])->column('name');
        $type_name = implode(',', $type);
        $this->assign('type_name', $type_name);

        $this->assign('post', $post);
        return $this->fetch();
    }

    public function verify() {
        $id = $this->request->param('id', 0, 'intval');
        $service = new PostService();
        $post = $service->published($id);

        $type = db::name('schoolType')->where('id','in',$post['school_type'])->column('name');
        $type_name = implode(',', $type);
        $this->assign('type_name', $type_name);

        $this->assign('post', $post);
        return $this->fetch();
    }

    public function verifyPost() {
        if ($this->request->isPost()) {
            $data = $this->request->param();

            $post   = $data['post'];
            if (!isset($post['id']) or empty($post['id'])) {
                $this->success('内容有误!');
            }
            if (!isset($post['status']) or empty($post['status'])) {
                $this->success('操作有误!');
            }
            if (!in_array($post['status'], ['-1',1])) {
                $this->success('类型信息操作有误!');
            }
            $model = new SchoolModel();
            $model->adminVerify($post);

            $this->success('审核成功!', url('AdminIndex/index') );

        }
    }

    public function editPost() {
        if ($this->request->isPost()) {
            $data = $this->request->param();

            $post   = $data['post'];
            $result = $this->validate($post, 'School');
            if ($result !== true) {
                $this->error($result);
            }
            // if (isset($post['mobile']) and !empty($post['mobile'])) {
            //     if (!cmf_check_mobile($post['mobile']) ) {
            //         $this->error('手机号码格式不正确!');
            //     }
            // }

            $model = new SchoolModel();
            $model->adminEdit($post);

            $this->success('保存成功!', url('AdminIndex/index') );

        }
    }

    public function delete() {
        $param           = $this->request->param();
        $organizationModel = new SchoolModel();

        if (isset($param['id'])) {
            $id           = $this->request->param('id', 0, 'intval');
            $resultFood = $organizationModel
                ->where('id', $id)
                ->update(['status' =>0]);
            $this->success("删除成功！", '');

        }
        if (isset($param['ids'])) {
            $ids     = $this->request->param('ids/a');
            $result  = $organizationModel->where('id', 'in', $ids)->update(['status' => 0]);
            if ($result) {
                $this->success("删除成功！", '');
            }
        }
    }

    public function canteen() {
        $param = $this->request->param();
        if (empty($param['keyword'])) { $param['keyword'] = ''; }
        if (empty($param['province'])) { $param['province'] = '0'; }
        if (empty($param['city'])) { $param['city'] = '0'; }
        if (empty($param['district'])) { $param['district'] = '0'; }
        $this->assign('param',$param);
        $service = new PostService();
        $data = $service->canteenList($param);
        $data->appends($param);

        $status = get_status(1);
        $this->assign('status',$status);

        $this->assign('data',$data);
        $this->assign('page', $data->render());

        $this->getStaff($param);

        return $this->fetch();
    }

    public function addCanteen() {
        // $type = db::name('schoolType')->where('status',1)->field('id,name')->select();
        // $this->assign('type',$type);
        return $this->fetch();
    }

    public function addCanteenPost() {
        if ($this->request->isPost()) {
            $data = $this->request->param();

            $data['post']['create_time'] = time();
            $data['post']['status'] = 3;
            $post = $data['post'];
            // if (isset($post['mobile']) and !empty($post['mobile'])) {
            //     if (!cmf_check_mobile($post['mobile']) ) {
            //         $this->error('手机号码格式不正确!');
            //     }
            // }

            $result = $this->validate($post, 'Canteen');
            if ($result !== true) {
                $this->error($result);
            }

            $organizationModel = new CanteenModel();
            $organizationModel->adminAdd($data['post']);

            $this->success('添加成功!', url('AdminIndex/canteen') );
        }

    }

    public function editCanteen() {
        $id = $this->request->param('id', 0, 'intval');
        // $join = [
        //     ['__REGION__ r1','r1.id=s.province'],
        //     ['__REGION__ r2','r2.id=s.city'],
        //     ['__REGION__ r3','r3.id=s.district'],
        // ];
        // $field = 's.*,r1.name as p_name,r2.name as c_name,r3.name as d_name';

        $organizationModel = new CanteenModel();
        $post = $organizationModel->alias('s')
            //->join($foin)->field($field)
            ->where('s.id', $id)->find();
        $school = db::name('school')->where('id','in',$post['school_id'])->column('name');
        $school_name = implode(',', $school);
        $this->assign('school_name', $school_name);
        $this->assign('post', $post);

        return $this->fetch();
    }

    public function checkCanteen() {
        $id = $this->request->param('id', 0, 'intval');
        $service = new PostService();
        $post = $service->canteenInfo($id);
        
        $school = db::name('school')->where('id','in',$post['school_id'])->column('name');
        $school_name = implode(',', $school);
        $this->assign('school_name', $school_name);

        $this->assign('post', $post);
        return $this->fetch();
    }

    public function verifyCanteen() {
        $id = $this->request->param('id', 0, 'intval');
        $service = new PostService(); 
        $post= $service->canteenInfo($id);

        $school = db::name('school')->where('id','in',$post['school_id'])->column('name');
        $school_name = implode(',', $school);
        $this->assign('school_name', $school_name);

        $this->assign('post', $post);
        return $this->fetch();
    }

    public function verifyCanteenPost() {
        if ($this->request->isPost()) {
            $data = $this->request->param();

            $post   = $data['post'];
            if (!isset($post['id']) or empty($post['id'])) {
                $this->success('内容有误!');
            }
            if (!isset($post['status']) or empty($post['status'])) {
                $this->success('操作有误!');
            }
            if (!in_array($post['status'], ['-1',1])) {
                $this->success('类型信息操作有误!');
            }
            $model = new CanteenModel();
            $model->adminVerify($post);

            $this->success('审核成功!', url('AdminIndex/canteen') );

        }
    }

    public function editCanteenPost() {
        if ($this->request->isPost()) {
            $data = $this->request->param();

            $post   = $data['post'];
            $result = $this->validate($post, 'Canteen');
            if ($result !== true) {
                $this->error($result);
            }
            // if (isset($post['mobile']) and !empty($post['mobile'])) {
            //     if (!cmf_check_mobile($post['mobile']) ) {
            //         $this->error('手机号码格式不正确!');
            //     }
            // }

            $model = new CanteenModel();
            $model->adminEdit($post);

            $this->success('保存成功!', url('AdminIndex/canteen') );

        }
    }

    public function deleteCanteen() {
        $param           = $this->request->param();
        $organizationModel = new CanteenModel();

        if (isset($param['id'])) {
            $id           = $this->request->param('id', 0, 'intval');
            $resultFood = $organizationModel
                ->where('id', $id)
                ->update(['status' =>0]);
            $this->success("删除成功！", '');

        }
        if (isset($param['ids'])) {
            $ids     = $this->request->param('ids/a');
            $result  = $organizationModel->where('id', 'in', $ids)->update(['status' => 0]);
            if ($result) {
                $this->success("删除成功！", '');
            }
        }
    }

    // 选择学校分类
    public function select() {
        $ids                 = $this->request->param('ids');
        $selectedIds         = explode(',', $ids);
        $model = new SchoolTypeModel();

        $tpl = <<<tpl
<tr class='data-item-tr'>
    <td>
        <input type='radio' class='js-check' data-yid='js-check-y' data-xid='js-check-x' name='ids[]'
               value='\$id' data-name='\$name' \$checked>
    </td>
    <td>\$id</td>
    <td>\$spacer \$name</td>
</tr>
tpl;

        $categoryTree = $model->adminCategoryTableTree($selectedIds, $tpl);

        $categories = $model->where('status',1)->select();

        $this->assign('categories', $categories);
        $this->assign('selectedIds', $selectedIds);
        $this->assign('categories_tree', $categoryTree);
        return $this->fetch();
    }

    // 选择学校
    public function selectSchool() {
        $type                = $this->request->param('type',1);
        $status              = $this->request->param('status','');
        $ids                 = $this->request->param('ids');
        $selectedIds         = explode(',', $ids);
        $model = new SchoolModel();
        $categoryTree = $model->adminCategoryTableTree($selectedIds,'',$type,$status);

        $categories = $model->where('status',1)->select();

        $this->assign('categories', $categories);
        $this->assign('selectedIds', $selectedIds);
        $this->assign('categories_tree', $categoryTree);
        $this->assign('type', $type);
        return $this->fetch();
    }

    // 选择学校食堂
    public function selectCanteen() {
        $school_id           = $this->request->param('school_id',0);
        $status              = $this->request->param('status','');
        $ids                 = $this->request->param('ids');
        $selectedIds         = explode(',', $ids);
        $model = new CanteenModel();
        $categoryTree = $model->adminCategoryTableTree($selectedIds,$school_id,'',$status);

        $categories = $model->where('school_id',$school_id)->where('status',1)->select();

        $this->assign('categories', $categories);
        $this->assign('selectedIds', $selectedIds);
        $this->assign('categories_tree', $categoryTree);
        return $this->fetch();
    }


}
