<?php
namespace app\card\controller;

use cmf\controller\CardBaseController;
use app\organization\service\PostService;
use app\organization\model\CanteenModel;

use app\mission\model\MissionModel;
use app\mission\model\MissionFoodModel;
use app\mission\model\MissionFinishedModel;
use app\mission\model\MissionCheckModel;
use app\mission\model\MissionOtherModel;
use app\mission\model\MissionRecordModel;

use think\Db;

class MissionController extends CardBaseController {

    // 存放食物、留样记录、成品的地方
    public function index() {
        $school_id = $this->schoolId;
        $canteen_id = $this->canteenId;
        $user_id = $this->userId;
        if (empty($school_id) or empty($canteen_id)) {
            $this->error('参数错误');
        }
        $user_info = $this->userInfo;
        $this->assign('user_info', $user_info);
        
        $post = [];
        $time = get_datetime();
        $year = $time['year'];$month = $time['month'];$day = $time['day'];$today = $time['today'];
        $count = db::name('mission')->where('school_id',$school_id)->where('canteen_id',$canteen_id)->where('year',$year)->where('month',$month)->where('day',$day)->count();
        if (empty($count)) {
            $name = $today.'上传清单';
            $add = ['school_id'=>$school_id,'canteen_id'=>$canteen_id,'user_id'=>$user_id,'name'=>$name,'year'=>$year,'month'=>$month,'day'=>$day,'create_time'=>$time['str'],'status'=>0 ];  //待确认提交
            db::name('mission')->insert($add);
        }
        $post['mission'] = db::name('mission')->where('school_id',$school_id)->where('canteen_id',$canteen_id)->where('year',$year)->where('month',$month)->where('day',$day)->find();
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) {
            $id = $post['mission']['id'];
        }
        $status = get_status(31);
        $post['status'] = $status[$post['mission']['status']];

        $post['date'] = $today;
        $post['count_food'] = db::name('mission_food')->where('create_time','gt',$time['t_begin'])->where('create_time','lt',$time['t_end'])->where('mission_id',$id)->count();
        $post['count_finished'] = db::name('mission_finished')->where('create_time','gt',$time['t_begin'])->where('create_time','lt',$time['t_end'])->where('mission_id',$id)->count();
        $post['count_record'] = db::name('mission_record')->where('create_time','gt',$time['t_begin'])->where('create_time','lt',$time['t_end'])->where('mission_id',$id)->count();
        $post['count_check'] = db::name('mission_check')->where('create_time','gt',$time['t_begin'])->where('create_time','lt',$time['t_end'])->where('mission_id',$id)->count();
        $post['count_other'] = db::name('mission_other')->where('create_time','gt',$time['t_begin'])->where('create_time','lt',$time['t_end'])->where('mission_id',$id)->count();
        $this->assign('post', $post);
        return $this->fetch();
    }

    // 每日提交审核
    public function postVerify() {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) {
            $canteen_id = $this->canteenId;
            //$userId = $this->userId;
            if (empty($canteen_id)) {
                $this->error('参数错误');
            }
        }
        $post = $this->request->param();  //提交的信息
        $time = get_datetime();

        $count_food = db::name('mission_food')->where('create_time','gt',$time['t_begin'])->where('create_time','lt',$time['t_end'])->where('mission_id',$id)->count();
        if (empty($count_food)) {
            $this->error('请录入食材信息');
        }
        $count_finished = db::name('mission_finished')->where('create_time','gt',$time['t_begin'])->where('create_time','lt',$time['t_end'])->where('mission_id',$id)->count();
        if (empty($count_finished)) {
            $this->error('请填写饭菜成品');
        }
        $count_record = db::name('mission_record')->where('create_time','gt',$time['t_begin'])->where('create_time','lt',$time['t_end'])->where('mission_id',$id)->count();
        if (empty($count_record)) {
            $this->error('请填写菜品留样记录');
        }
        $post['status'] = 1;
        $post['id'] = $id;
        $model = new MissionModel();
        $res = $model->adminVerify($post);
        if (!empty($res)) {
            $this->success("提交成功",url('index/index') );
        } else {
            $this->error('提交失败');
        }
    }

    // 整体列表
    public function postFood() {
        $canteen_id = $this->canteenId;
        if (empty($canteen_id)) {
            $this->error('参数错误');
        }
        $mission_id = $this->request->param('mission_id', 0, 'intval');
        //->join('__FOOD__ f','f.id=mf.food')->join('__FOOD_UNIT__ u','u.id=mf.unit')
        //f.name as f_name,u.name as u_name
        $field = 'mf.*';
        $list = db::name('mission_food')->alias('mf')->where('mf.mission_id', $mission_id)->field($field)->select();
        $this->assign('list', $list);
        $this->assign('mission_id', $mission_id);
        return $this->fetch();
    }

    // 明细页面
    public function editFood() {
        $canteen_id = $this->canteenId;
        if (empty($canteen_id)) {
            $this->error('参数错误');
        } else {
            $where['cs.canteen_id'] = $canteen_id;
        }
        $mission_id = $this->request->param('mission_id', 0, 'intval');
        if (empty($canteen_id)) {
            $this->error('参数错误');
        }
        $this->assign('mission_id', $mission_id);

        $id = $this->request->param('id', 0, 'intval');
        $model = new MissionFoodModel();
        if (empty($id)) {
            $post = [];
        } else {
            $post = $model->alias('cs')->where('cs.id', $id)->where('cs.canteen_id', $canteen_id)->field('cs.*')->find();
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
            if (empty($post['cover'])) {
                $this->error('请上传原食材图片');
            }
            if (empty($post['list'])) {
                $this->error('请上传进货清单图片');
            }
            if (empty($post['check'])) {
                $this->error('请上传检验检疫证明图片');
            }
            $model = new MissionFoodModel();
            if (isset($post['id']) and !empty($post['id'])) {  //编辑
                $post['name'] = date('Y-m-d').'食材图片';
                $res = $model->allowField(true)->isUpdate(true)->data($post, true)->save();
            } else {   //添加
                $post['school_id'] = $school_id;
                $post['user_id'] = $user_id;
                $post['canteen_id'] = $canteen_id;
                $post['create_time'] = time();
                $post['name'] = date('Y-m-d').'食材图片';
                $res = $model->allowField(true)->data($post, true)->isUpdate(false)->save();
            }
            if (!empty($res)) {
                $this->success("提交成功",url('mission/postFood',['mission_id'=>$post['mission_id']]) );
            } else {
                $this->error('提交成功');
            }
        }
    }
    // public function editFood() {
    //     $canteen_id = $this->canteenId;
    //     if (empty($canteen_id)) {
    //         $this->error('参数错误');
    //     } else {
    //         $where['cs.canteen_id'] = $canteen_id;
    //     }
    //     $mission_id = $this->request->param('mission_id', 0, 'intval');
    //     if (empty($canteen_id)) {
    //         $this->error('参数错误');
    //     }
    //     $this->assign('mission_id', $mission_id);

    //     $id = $this->request->param('id', 0, 'intval');
    //     if (empty($id)) {
    //         $post = [];
    //     } else {
    //         $post = db::name('missionFood')->alias('cs')->where('cs.id', $id)->where('cs.canteen_id', $canteen_id)->field('cs.*')->find();
    //     }
    //     $food = db::name('food')->alias('f')->where('f.canteen_id', $canteen_id)->where('f.status', 1)->select()->toArray();
    //     if (empty($food)) {
    //         $this->error('请先添加食材',url('canteen/more'));
    //     }
    //     $unit = db::name('food_unit')->alias('u')->where('u.status', 1)->select();
    //     $supplier = db::name('canteenSupplier')->alias('cs')->where('cs.canteen_id', $canteen_id)->where('cs.status', 1)->select()->toArray();
    //     if (empty($supplier)) {
    //         $this->error('请先添加供应商信息',url('canteen/more'));
    //     }
        
    //     $this->assign('food', $food);
    //     $this->assign('unit', $unit);
    //     $this->assign('supplier', $supplier);
    //     $this->assign('post', $post);
    //     return $this->fetch();
    // }

    // public function editFoodPost() {
    //     if ($this->request->isPost()) {
    //         $post = $this->request->param();  //提交的信息
    //         $user_id = $this->getUserId();  //用户id
    //         $school_id = $this->schoolId;  //学校id
    //         $canteen_id = $this->canteenId;  //食堂id
    //         if (empty($school_id) or empty($canteen_id)) {
    //             $this->error('参数错误');
    //         }
            
    //         if (empty($post['mission_id'])) {
    //             $this->error('食材参数错误');
    //         }
    //         if (empty($post['food'])) {
    //             $this->error('请选择食材');
    //         }
    //         if (empty($post['num'])) {
    //             $this->error('请输入食材数量');
    //         }
    //         if (empty($post['cover'])) {
    //             $this->error('请上传食材图片');
    //         }
    //         if (empty($post['other'])) {
    //             $this->error('请上传相关检验证明');
    //         }
    //         $model = db::name('missionFood');
    //         if (isset($post['id']) and !empty($post['id'])) {  //编辑
    //             $res = $model->where('id',$post['id'])->update($post);
    //         } else {   //添加
    //             $post['school_id'] = $school_id;
    //             $post['user_id'] = $user_id;
    //             $post['canteen_id'] = $canteen_id;
    //             $post['create_time'] = time();
    //             $res = $model->insert($post);
    //         }
    //         if (!empty($res)) {
    //             $this->success("提交成功",url('mission/postFood',['mission_id'=>$post['mission_id']]) );
    //         } else {
    //             $this->error('提交成功');
    //         }
    //     }
    // }

    // 整体列表
    public function postFinished() {
        $canteen_id = $this->canteenId;
        if (empty($canteen_id)) {
            $this->error('参数错误');
        }
        $mission_id = $this->request->param('mission_id', 0, 'intval');
        $field = 'mf.*';
        $model = new MissionFinishedModel();
        $list = $model->alias('mf')->where('mf.mission_id', $mission_id)->field($field)->select();
        $this->assign('list', $list);
        $this->assign('mission_id', $mission_id);
        return $this->fetch();
    }

    // 明细页面
    public function editFinished() {
        $canteen_id = $this->canteenId;
        if (empty($canteen_id)) {
            $this->error('参数错误');
        } else {
            $where['cs.canteen_id'] = $canteen_id;
        }
        $mission_id = $this->request->param('mission_id', 0, 'intval');
        if (empty($canteen_id)) {
            $this->error('参数错误');
        }
        $this->assign('mission_id', $mission_id);

        $id = $this->request->param('id', 0, 'intval');
        $model = new MissionFinishedModel();
        if (empty($id)) {
            $post = [];
        } else {
            $post = $model->alias('cs')->where('cs.id', $id)->where('cs.canteen_id', $canteen_id)->field('cs.*')->find();
        }
        $this->assign('post', $post);
        return $this->fetch();
    }

    public function editFinishedPost() {
        if ($this->request->isPost()) {
            $post = $this->request->param();  //提交的信息
            $user_id = $this->getUserId();  //用户id
            $school_id = $this->schoolId;  //学校id
            $canteen_id = $this->canteenId;  //食堂id
            if (empty($school_id) or empty($canteen_id)) {
                $this->error('参数错误');
            }
            
            if (empty($post['cover'])) {
                $this->error('请上传饭菜图片');
            }
            $model = new MissionFinishedModel();
            if (isset($post['id']) and !empty($post['id'])) {  //编辑
                $post['name'] = date('Y-m-d').'饭菜图片';
                $res = $model->allowField(true)->isUpdate(true)->data($post, true)->save();
            } else {   //添加
                $post['school_id'] = $school_id;
                $post['user_id'] = $user_id;
                $post['canteen_id'] = $canteen_id;
                $post['create_time'] = time();
                $post['name'] = date('Y-m-d').'饭菜图片';
                $res = $model->allowField(true)->data($post, true)->isUpdate(false)->save();
            }
            if (!empty($res)) {
                $this->success("提交成功",url('mission/postFinished',['mission_id'=>$post['mission_id']]) );
            } else {
                $this->error('提交成功');
            }
        }
    }

    // 整体列表
    public function postRecord() {
        $canteen_id = $this->canteenId;
        if (empty($canteen_id)) {
            $this->error('参数错误');
        }
        $mission_id = $this->request->param('mission_id', 0, 'intval');
        $field = 'mf.*';
        $model = new MissionRecordModel();
        $list = $model->alias('mf')->where('mf.mission_id', $mission_id)->field($field)->select();
        $this->assign('list', $list);
        $this->assign('mission_id', $mission_id);
        return $this->fetch();
    }

    // 明细页面
    public function editRecord() {
        $canteen_id = $this->canteenId;
        if (empty($canteen_id)) {
            $this->error('参数错误');
        } else {
            $where['cs.canteen_id'] = $canteen_id;
        }
        $mission_id = $this->request->param('mission_id', 0, 'intval');
        if (empty($canteen_id)) {
            $this->error('参数错误');
        }
        $this->assign('mission_id', $mission_id);

        $id = $this->request->param('id', 0, 'intval');
        $model = new MissionRecordModel();
        if (empty($id)) {
            $post = [];
        } else {
            $post = $model->alias('cs')->where('cs.id', $id)->where('cs.canteen_id', $canteen_id)->field('cs.*')->find();
        }
        $this->assign('post', $post);
        return $this->fetch();
    }

    public function editRecordPost() {
        if ($this->request->isPost()) {
            $post = $this->request->param();  //提交的信息
            $user_id = $this->getUserId();  //用户id
            $school_id = $this->schoolId;  //学校id
            $canteen_id = $this->canteenId;  //食堂id
            if (empty($school_id) or empty($canteen_id)) {
                $this->error('参数错误');
            }
            
            if (empty($post['cover'])) {
                $this->error('请上传留样图片');
            }
            $model = new MissionRecordModel();
            if (isset($post['id']) and !empty($post['id'])) {  //编辑
                $post['name'] = date('Y-m-d').'留样图片';
                $res = $model->allowField(true)->isUpdate(true)->data($post, true)->save();
            } else {   //添加
                $post['school_id'] = $school_id;
                $post['user_id'] = $user_id;
                $post['canteen_id'] = $canteen_id;
                $post['create_time'] = time();
                $post['name'] = date('Y-m-d').'留样图片';
                $res = $model->allowField(true)->data($post, true)->isUpdate(false)->save();
            }
            if (!empty($res)) {
                $this->success("提交成功",url('mission/postRecord',['mission_id'=>$post['mission_id']]) );
            } else {
                $this->error('提交成功');
            }
        }
    }

    // 整体列表
    public function postCheck() {
        $canteen_id = $this->canteenId;
        if (empty($canteen_id)) {
            $this->error('参数错误');
        }
        $mission_id = $this->request->param('mission_id', 0, 'intval');
        $field = 'mf.*';
        $model = new MissionCheckModel();
        $list = $model->alias('mf')->where('mf.mission_id', $mission_id)->field($field)->select();
        $this->assign('list', $list);
        $this->assign('mission_id', $mission_id);
        return $this->fetch();
    }

    // 明细页面
    public function editCheck() {
        $canteen_id = $this->canteenId;
        if (empty($canteen_id)) {
            $this->error('参数错误');
        } else {
            $where['cs.canteen_id'] = $canteen_id;
        }
        $mission_id = $this->request->param('mission_id', 0, 'intval');
        if (empty($canteen_id)) {
            $this->error('参数错误');
        }
        $this->assign('mission_id', $mission_id);

        $id = $this->request->param('id', 0, 'intval');
        $model = new MissionCheckModel();
        if (empty($id)) {
            $post = [];
        } else {
            $post = $model->alias('cs')->where('cs.id', $id)->where('cs.canteen_id', $canteen_id)->field('cs.*')->find();
        }
        $this->assign('post', $post);
        return $this->fetch();
    }

    public function editCheckPost() {
        if ($this->request->isPost()) {
            $post = $this->request->param();  //提交的信息
            $user_id = $this->getUserId();  //用户id
            $school_id = $this->schoolId;  //学校id
            $canteen_id = $this->canteenId;  //食堂id
            if (empty($school_id) or empty($canteen_id)) {
                $this->error('参数错误');
            }
            
            if (empty($post['cover'])) {
                $this->error('请上传卫生检查记录图片');
            }
            $model = new MissionCheckModel();
            if (isset($post['id']) and !empty($post['id'])) {  //编辑
                $post['name'] = date('Y-m-d').'卫生检查记录';
                $res = $model->allowField(true)->isUpdate(true)->data($post, true)->save();
            } else {   //添加
                $post['school_id'] = $school_id;
                $post['user_id'] = $user_id;
                $post['canteen_id'] = $canteen_id;
                $post['create_time'] = time();
                $post['name'] = date('Y-m-d').'卫生检查记录';
                $res = $model->allowField(true)->data($post, true)->isUpdate(false)->save();
            }
            if (!empty($res)) {
                $this->success("提交成功",url('mission/postCheck',['mission_id'=>$post['mission_id']]) );
            } else {
                $this->error('提交成功');
            }
        }
    }

    public function msgCheck() {
        $mission_id = $this->request->param('mission_id', 0, 'intval');
        $field = 'mf.*';
        $list = db::name('missionCheck')->alias('mf')->where('mf.mission_id', $mission_id)->field($field)->select();
        $this->assign('list', $list);
        $this->assign('mission_id', $mission_id);
        return $this->fetch();
    }

    // 明细页面
    public function infoCheck() {
        $mission_id = $this->request->param('mission_id', 0, 'intval');
        $this->assign('mission_id', $mission_id);

        $id = $this->request->param('id', 0, 'intval');
        $model = new MissionCheckModel();
        $post = $model->alias('cs')->where('cs.id', $id)->field('cs.*')->find();
        $this->assign('post', $post);
        return $this->fetch();
    }

    // 整体列表
    public function postOther() {
        $canteen_id = $this->canteenId;
        if (empty($canteen_id)) {
            $this->error('参数错误');
        }
        $mission_id = $this->request->param('mission_id', 0, 'intval');
        $field = 'mf.*';
        $model = new MissionOtherModel();
        $list = $model->alias('mf')->where('mf.mission_id', $mission_id)->field($field)->select();
        $this->assign('list', $list);
        $this->assign('mission_id', $mission_id);
        return $this->fetch();
    }

    // 明细页面
    public function editOther() {
        $canteen_id = $this->canteenId;
        if (empty($canteen_id)) {
            $this->error('参数错误');
        } else {
            $where['cs.canteen_id'] = $canteen_id;
        }
        $mission_id = $this->request->param('mission_id', 0, 'intval');
        if (empty($canteen_id)) {
            $this->error('参数错误');
        }
        $this->assign('mission_id', $mission_id);

        $id = $this->request->param('id', 0, 'intval');
        $model = new MissionOtherModel();
        if (empty($id)) {
            $post = [];
        } else {
            $post = $model->alias('cs')->where('cs.id', $id)->where('cs.canteen_id', $canteen_id)->field('cs.*')->find();
        }
        $this->assign('post', $post);
        return $this->fetch();
    }

    public function editOtherPost() {
        if ($this->request->isPost()) {
            $post = $this->request->param();  //提交的信息
            $user_id = $this->getUserId();  //用户id
            $school_id = $this->schoolId;  //学校id
            $canteen_id = $this->canteenId;  //食堂id
            if (empty($school_id) or empty($canteen_id)) {
                $this->error('参数错误');
            }
            
            if (empty($post['cover'])) {
                $this->error('请上传其他记录图片');
            }
            $model = new MissionOtherModel();
            if (isset($post['id']) and !empty($post['id'])) {  //编辑
                $post['name'] = date('Y-m-d').'其他记录';
                $res = $model->allowField(true)->isUpdate(true)->data($post, true)->save();
            } else {   //添加
                $post['school_id'] = $school_id;
                $post['user_id'] = $user_id;
                $post['canteen_id'] = $canteen_id;
                $post['create_time'] = time();
                $post['name'] = date('Y-m-d').'其他记录';
                $res = $model->allowField(true)->data($post, true)->isUpdate(false)->save();
            }
            if (!empty($res)) {
                $this->success("提交成功",url('mission/postOther',['mission_id'=>$post['mission_id']]) );
            } else {
                $this->error('提交成功');
            }
        }
    }

    public function msgOther() {
        $mission_id = $this->request->param('mission_id', 0, 'intval');
        $field = 'mf.*';
        $list = db::name('missionOther')->alias('mf')->where('mf.mission_id', $mission_id)->field($field)->select();
        $this->assign('list', $list);
        $this->assign('mission_id', $mission_id);
        return $this->fetch();
    }

    // 明细页面
    public function infoOther() {
        $mission_id = $this->request->param('mission_id', 0, 'intval');
        $this->assign('mission_id', $mission_id);

        $id = $this->request->param('id', 0, 'intval');
        $model = new MissionOtherModel();
        $post = $model->alias('cs')->where('cs.id', $id)->field('cs.*')->find();
        $this->assign('post', $post);
        return $this->fetch();
    }

    public function list() {
        $param = $this->request->param();
        $where = ['status'=>1];
        $model = db::name('mission');
        $school_id = $this->schoolId;
        $canteen_id = $this->canteenId;
        $user_id = $this->userId;

        // if (empty($school_id) or empty($canteen_id)) {
        //     $this->error('参数错误');
        // }
        $userInfo = $this->userInfo;
        if (!empty($school_id)) { $where['school_id'] = $school_id; }
        if (!empty($canteen_id)) { $where['canteen_id'] = $canteen_id; }
        $html = 'list';

        $field = '*';
        $time = get_datetime();
        if (isset($param['date']) and !empty($param['date'])) {
            $date = explode('-', $param['date']);
            $year = (isset($date[0]) and !empty($date[0]))?$date[0]:$time['year'];
            $month = (isset($date[1]) and !empty($date[1]))?$date[1]:$time['month'];
        } else {
            //默认当前月份
            $year = $time['year'];
            $month = $time['month'];
        }
        $list = $model->where('year',$year)->where('month',$month)->where($where)->field($field)->select();
        $this->assign('list', $list);

        $now = [];
        $now['now'] = $year.'-'.$month;
        $now['month'] = $month;
        $now['year'] = $year;
        $this->assign('now', $now);

        return $this->fetch($html);
    }

    public function list4() {
        $param = $this->request->param();
        if (!empty($param)) {
            $is_get = true;
        } else {
            $is_get = false;
        }
        $where = ['m.status'=>1];
        $join = [
            ['__SCHOOL__ s','s.id=m.school_id','left'],
            ['__CANTEEN__ c','c.id=m.canteen_id','left'],
        ];
        $model = db::name('mission')->alias('m')->join($join);
        $school_id = $this->schoolId;
        $canteen_id = $this->canteenId;
        $user_id = $this->userId;
        $userInfo = $this->userInfo;
        if ($userInfo['user_cate']==4) {  //行政人员
            $cate_param = ['province'=>$userInfo['province'],'city'=>$userInfo['city'],'district'=>$userInfo['district'],];
            $param = array_merge($cate_param,$param);
            $this->getStaff($param);
            $s_c = $this->getSchoolAndCanteen($param);
            if (isset($param['school_id']) and !empty($param['school_id'])) { $where['m.school_id'] = $param['school_id']; }
            if (isset($param['canteen_id']) and !empty($param['canteen_id'])) { $where['m.canteen_id'] = $param['canteen_id']; }
            $model = $model->where('m.school_id','in',$s_c['school_ids']);
            $html = 'list4';
        } else {
            if (!empty($school_id)) { $where['m.school_id'] = $school_id; }
            if (!empty($canteen_id)) { $where['m.canteen_id'] = $canteen_id; }
            $html = 'list';
        }
        if (isset($param['keyword']) and !empty($param['keyword'])) {
            $canteens = db::name('canteen')->where('name','like','%'.trim($param['keyword']).'%')->column('id');
            $model = $model->where('m.canteen_id','in',$canteens);
        }
        $this->assign('user', $userInfo);
        $this->assign('param', $param);

        $field = 'm.*,s.name as s_name,c.name as c_name';
        $time = get_datetime();
        if (isset($param['date']) and !empty($param['date'])) {
            $date = explode('-', $param['date']);
            $year = (isset($date[0]) and !empty($date[0]))?$date[0]:$time['year'];
            $month = (isset($date[1]) and !empty($date[1]))?$date[1]:$time['month'];
        } else {
            //默认当前月份
            $year = $time['year'];
            $month = $time['month'];
        }
        $list = [];
        if ($is_get) {
            $list = $model->where('m.year',$year)->where('m.month',$month)->where($where)->field($field)->select();
        }
        $this->assign('list', $list);

        $now = [];
        $now['now'] = $year.'-'.$month;
        $now['month'] = $month;
        $now['year'] = $year;
        $this->assign('now', $now);

        return $this->fetch($html);
    }


    public function info() {
        $school_id = $this->schoolId;
        $canteen_id = $this->canteenId;
        $user_id = $this->userId;
        // if (empty($school_id) or empty($canteen_id)) {
        //     $this->error('参数错误');
        // }
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) {
            $this->error('参数错误');
        }
        $post = [];
        $time = get_datetime();
        $post = db::name('mission')->where('id',$id)->find();
        
        $this->assign('post', $post);
        return $this->fetch();
    }

    // 整体列表
    public function msgFood() {
        $mission_id = $this->request->param('mission_id', 0, 'intval');
        $field = 'mf.*';
        $list = db::name('mission_food')->alias('mf')->where('mf.mission_id', $mission_id)->field($field)->select();
        $this->assign('list', $list);
        $this->assign('mission_id', $mission_id);
        return $this->fetch();
    }

    // 整体列表
    public function infoFood() {
        $mission_id = $this->request->param('mission_id', 0, 'intval');

        $this->assign('mission_id', $mission_id);

        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) {
            $post = [];
        }
        $field = 'cs.*';
        $model = new MissionFoodModel();
        $post = $model->alias('cs')->where('cs.id', $id)->field($field)->find();
        $this->assign('post', $post);
        return $this->fetch();
    }

    public function msgFinished() {
        $mission_id = $this->request->param('mission_id', 0, 'intval');
        $field = 'mf.*';
        $list = db::name('missionFinished')->alias('mf')->where('mf.mission_id', $mission_id)->field($field)->select();
        $this->assign('list', $list);
        $this->assign('mission_id', $mission_id);
        return $this->fetch();
    }

    // 明细页面
    public function infoFinished() {
        $mission_id = $this->request->param('mission_id', 0, 'intval');
        $this->assign('mission_id', $mission_id);

        $id = $this->request->param('id', 0, 'intval');
        $model = new MissionFinishedModel();
        $post = $model->alias('cs')->where('cs.id', $id)->field('cs.*')->find();
        $this->assign('post', $post);
        return $this->fetch();
    }

    public function msgRecord() {
        $mission_id = $this->request->param('mission_id', 0, 'intval');
        $field = 'mf.*';
        $list = db::name('missionRecord')->alias('mf')->where('mf.mission_id', $mission_id)->field($field)->select();
        $this->assign('list', $list);
        $this->assign('mission_id', $mission_id);
        return $this->fetch();
    }

    public function infoRecord() {

        $mission_id = $this->request->param('mission_id', 0, 'intval');
        $this->assign('mission_id', $mission_id);

        $id = $this->request->param('id', 0, 'intval');
        $model = new MissionRecordModel();
        $post = $model->alias('cs')->where('cs.id', $id)->field('cs.*')->find();
        $this->assign('post', $post);
        return $this->fetch();
    }

}
