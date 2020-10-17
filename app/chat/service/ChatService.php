<?php
// +----------------------------------------------------------------------
// | zhiliang weight
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019.
// +----------------------------------------------------------------------
// | Author: 小艾.pino
// +----------------------------------------------------------------------
namespace app\chat\service;

use app\chat\model\SchoolObjectModel;
use app\chat\model\SchoolChatModel;
use think\Db;
use think\db\Query;


class ChatService {

    public function postList($param, $isPage = false) {
        $model = new SchoolObjectModel();
        $model = $model->alias('c');
        $where = ['c.status'=>1,'s.status'=>1];

        if (!empty($param['order'])) { 
            $order = $param['order'];
        } else {
            $order = 'c.id desc'; 
        }

        if (isset($param['keyword']) and !empty($param['keyword'])) { 
            $model = $model->where('c.title', 'like', '%'.$param['keyword'].'%');
        }
        if (isset($param['school_id']) and !empty($param['school_id'])) { 
            $model = $model->where('c.school_id', 'eq', $param['school_id']);
        }
        
        //留言列表
        $startTime = empty($param['start_time']) ? 0 : strtotime($param['start_time']);
        $endTime   = empty($param['end_time']) ? 0 : strtotime($param['end_time']);
        if (!empty($startTime)) {
            $model->where('c.create_time', '>=', $startTime);
        }
        if (!empty($endTime)) {
            $model->where('c.create_time', '<=', $endTime);
        }

        $join = [
            ['__USER__ u','u.id = c.user_id'],
            ['__SCHOOL__ s','s.id = c.school_id'],
        ];
        $field = 'c.*,u.user_nickname as user_name,s.name as s_name';
        $data = $model->join($join)->where($where)->field($field)->order('c.id desc')->paginate(10);

        return $data;

    }

    public function published($postId) {
        $model = new SchoolObjectModel();
        $model = $model->alias('c');
        $where = ['c.status'=>1,'c.id'=>$postId];
        $join = [
            ['__USER__ u','u.id = c.user_id'],
            ['__SCHOOL__ s','s.id = c.school_id'],
        ];
        $field = 'c.*,u.user_nickname as user_name,s.name as s_name';
        $post = $model->join($join)->where($where)->field($field)->find();

        return $post;
    }

    //评论列表
    public function getComments($res=[]){
        // ------------------------评论情况------------------------
        if (empty($res)) {
            return '';
        }
        $comment = new SchoolChatModel();
        $join = [ 
            ['__USER__ u', 'c.user_id = u.id'],  
            ['__USER__ tu', 'c.to_user_id = tu.id','left'], 
        ];
        $where = ['c.status'=>['eq',1] ];
        $res['c_count'] = 0; //初始化 
        $res['c_list'] = [];
        $where['c.object_id'] = $res['id'];
        $list = $comment->alias('c')->field('c.*,u.user_nickname,u.avatar,tu.user_nickname as to_user_nickname')->join($join)->where($where)->select()->toArray();
        if (!empty($list)) {  //人员
            $res['c_count'] = count($list);
            $res['c_list'] = $list;
        }
        // ------------------------评论情况------------------------
        return $res;
    }

    //评论
    public function postComment($data){
        $comment = Db::name('schoolChat');
        // if (!isset($data['to_user_id']) or empty($data['to_user_id'])) {
        //     $data['to_user_id'] = Db::name('schoolObject')->where(['id'=>$data['object_id']])->value('user_id');  //发布者
        // }

        $res = $comment->insertGetId($data);
        return $res;
    }

    //评论删除
    public function delComment($data){
        $comment = Db::name('schoolChat');
        if (isset($data['id'])) {
            $res = $comment->where($data)->delete();
        }
        return $res;
    }

    public function chatList($param) {
        $model = new SchoolChatModel();
        $model = $model->alias('c');
        $where = ['s.status'=>1];

        if (!empty($param['status'])) { 
            $where['c.status'] = $param['status']; 
        }
        if (!empty($param['order'])) { 
            $order = $param['order'];
        } else {
            $order = 'c.id desc'; 
        }
        if (!empty($param['user_id']) and !empty($param['to_user_id'])) { 
            $where['c.user_id|c.to_user_id'] = $param['user_id'];
        } elseif (!empty($param['user_id'])) {
            $where['c.user_id'] = $param['user_id'];
        } elseif (!empty($param['to_user_id'])) { 
            $where['c.to_user_id'] = $param['to_user_id']; 
        }
        if (isset($param['keyword']) and !empty($param['keyword'])) { 
            $model = $model->where('c.msg|u.user_nickname|u2.user_nickname', 'like', '%'.$param['keyword'].'%');
        }
        
        //留言列表
        $startTime = empty($param['start_time']) ? 0 : strtotime($param['start_time']);
        $endTime   = empty($param['end_time']) ? 0 : strtotime($param['end_time']);
        if (!empty($startTime)) {
            $model->where('c.create_time', '>=', $startTime);
        }
        if (!empty($endTime)) {
            $model->where('c.create_time', '<=', $endTime);
        }

        $join = [
            ['__USER__ u','u.id = c.user_id','left'],
            ['__USER__ u2','u2.id = c.to_user_id','left'],
            ['__SCHOOL__ s','s.id = c.school_id','left'],
            ['__SCHOOL_OBJECT__ so','so.id = c.object_id'],
        ];
        $field = 'c.*,u.user_nickname as user_name1,u2.user_nickname as user_name2';
        $data = $model->join($join)->where('c.status','gt',0)->where($where)->field($field)->order('c.id desc')->paginate(10);

        return $data;
    }

    public function chatInfo($postId) {
        $model = new SchoolChatModel();
        $join = [
            ['__USER__ u','u.id = c.user_id'],
            ['__USER__ u2','u2.id = c.to_user_id'],
        ];
        $field = 'c.*,u.user_nickname as user_name1,u2.user_nickname as user_name2';
        $post = $model->alias('c')->join($join)->field($field)->where('c.id',$postId)->find();

        return $post;
    }
}