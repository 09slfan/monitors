<?php

namespace app\card\controller;

use cmf\controller\CardBaseController;

use app\package\service\PostService;
use app\order\model\OrderModel;
use think\Db;
use think\Image;

class PayController extends CardBaseController {

    public function index() {
        $school_id = $this->schoolId;  //用户id getUserId()
        $user_id = $this->getUserId();
        $this->getUserInfo(true);
        $res = $this->userInfo;
        $this->assign('res', $res);
        $vip = db::name('userVip')->where('user_id',$user_id)->find();
        if (!empty($vip)) {
            $vip['begin_date'] = date('Y-m-d',$vip['begin_time']);
            $vip['end_date'] = date('Y-m-d',$vip['end_time']);
        }
        $this->assign('vip', $vip);

        // $s_msg = Db::name('school')->where(['id'=>$school_id])->find();
        // $this->assign('s_msg', $s_msg);
        return $this->fetch();
    }

    // 支付信息页
    public function info() {
        $param = $this->request->param();

        $this->getUserInfo(true);
        $res = $this->userInfo;
        $this->assign('res', $res);

        $service = new PostService();
        $pack = $service->adminPostList($param,99);
        $this->assign('pack', $pack);
        return $this->fetch();
    }

    public function packAjaxinfo() {
        $pid = $this->request->param('pid');
        $service = new PostService();
        $info = $service->publishedArticle($pid);
        
        if (!empty($info)) {
            $time = time();
            $period = $info['period'];
            $expire_time = $time+$period*86400;
            $info['expire_date'] = date('Y-m-d',$expire_time);
            $this->success('获取成功','',$info);
        } else {
            $this->error('服务器错误');
        }
    }

    public function toPay() {
        $post = $this->request->param();
        if (!empty($post)) {
            $user_id = $this->getUserId();  //用户id
            $school_id = $this->schoolId;
            $pid = $post['pid'];
            if (empty($pid)) {
                $this->error('pid参数错误');
            }
            $service = new PostService();
            $info = $service->publishedArticle($pid);
            //生成order
            $sn = cmf_get_order_sn();
            $oid = db::name('order')->where('user_id',$user_id)->where('status','neq',1)->value('id');
            if (!empty($oid)) {    //已有旧订单，更新订单
                $order_update = ['out_trade_no'=>$sn,'school_id'=>$school_id,'status'=>0,'package_id'=>$pid,'p_name'=>$info['post_title'],'p_money'=>$info['money'],'p_period'=>$info['period'],'pay_type'=>'wxpay','create_time'=>time()];
                db::name('order')->where('id',$oid)->update($order_update);
            } else {
                $order_add = ['out_trade_no'=>$sn,'school_id'=>$school_id,'status'=>0,'user_id'=>$user_id,'package_id'=>$pid,'p_name'=>$info['post_title'],'p_money'=>$info['money'],'p_period'=>$info['period'],'pay_type'=>'wxpay','create_time'=>time()];
                db::name('order')->insert($order_add);
            }

            //启动支付
            $params = [
                'body' => $info['post_title'],
                'out_trade_no' => $sn,
                'total_fee' => $info['money']*100,  //单位为分
            ];
            $open_id = db::name('user')->where('id',$user_id)->value('open_id');
            if (empty($open_id)) {
                $open_id = $this->getWxOpenId();
                db::name('user')->where('id',$user_id)->update(['open_id'=>$open_id]);
            }
            // \wxpay\JsapiPay::getPayParams($params)
            // \wxpay\WapPay::getPayUrl($params);
            $result = \wxpay\JsapiPay::getParams($params,$open_id);  //halt($result);
            return json_decode($result);
        }
    }

    // 支付历史页
    public function history() {
        $param = $this->request->param();
        $userId = $this->userId;
        $this->assign('userId', $userId);
        
        return $this->fetch();
    }

    public function ajaxhistory() {
        $param = $this->request->param();
        $school_id = $this->schoolId;
        $user_id = $this->userId;
        $join = [
            ['__USER__ u', 'a.user_id = u.id']
        ];
        $field = 'a.*,u.user_login,u.user_nickname';
        $model = new OrderModel();
        $data = $model->alias('a')->field($field)
            ->join($join)
            ->where('a.create_time', '>=', 0)
            ->where('a.user_id', $user_id)
            ->where('a.status', 1)
            ->order('id', 'DESC')
            ->paginate(10);

        $data ->appends($param);
        $list = $data->toArray()['data'];
        if (!empty($list)) {
            $this->assign('list', $list);
            return $this->fetch();
        }
         
    }

    public function orderInfo() {
        $id = $this->request->param('id');
        $model = new OrderModel();
        $field = 'o.*,p.post_content as content';
        $post = $model->alias('o')->join('__PACKAGE_POST__ p','p.id=o.package_id')->where('o.id',$id)->field($field)->find();
        
        if (!empty($post)) {
            $time = $post['create_time'];
            $period = $post['p_period'];
            $expire_time = $time+$period*86400;
            $post['pay_date'] = date('Y-m-d',$expire_time);
            $post['content'] = cmf_replace_content_file_url(htmlspecialchars_decode($post['content']));
            $this->assign('post', $post);
            return $this->fetch();
        } else {
            $this->error('服务器错误');
        }
    }
}
