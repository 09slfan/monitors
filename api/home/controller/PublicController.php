<?php

namespace api\home\controller;

use cmf\controller\RestBaseController;
use think\db;

class PublicController extends RestBaseController {

    /**
     * 获取省
     */
    public function getProvince() {
        $level = $this->request->param('level',1);
        $province = Db::name('region')->field('id,name')->where(array('level' => $level))->cache(true)->select();
        $this->success("获取成功!", $province);
    }

    /**
     * 获取市或者区
     */
    public function getRegionByParentId() {
        $parent_id = $this->request->param('parent_id','');
        $region_list = '';
        if($parent_id){
            $region_list = Db::name('region')->field('id,name')->where(['parent_id'=>$parent_id])->select();
        }
        $this->success("获取成功!", $region_list);
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
            $this->success('返回成功',$data);
        } else {
            $this->error('返回失败');
        }
    }

    public function updateIsRead(){
    	$join = [
            ['__SCHOOL__ s','s.id = sc.school_id'],
        ];
        $is_read = $this->request->param('is_read',0,'intval');
        $chat = db::name('schoolChat')->alias('sc')->join($join)->where('sc.is_read',$is_read)->where('s.status',1)->where('sc.status',1)->count();
        if (!empty($chat)) {
            $this->success('返回成功',['count'=>$chat]);
        } else {
            $this->error('返回失败');
        }
    }

}
