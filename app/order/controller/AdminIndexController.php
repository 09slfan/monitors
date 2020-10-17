<?php

namespace app\order\controller;

use cmf\controller\AdminBaseController;
use app\order\model\OrderModel;

use kuaidi\kuaidi100;
use think\Db;

/**
 * 订单管理
 * Class OrderController
 */
class AdminIndexController extends AdminBaseController {
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index() {
        $param = $this->request->param();
        $where = [];
        // 订单状态（-1 : 申请退款 -2 : 退货成功 0：待发货；1：待收货；2：已收货；3：待评价；-1：已退款）
        if (!empty($param['status'])) { 
            $where['status'] = $param['status']; 
        }
        if (!empty($param['order'])) { 
            $order = $param['order']; 
        } else {
            $order = 'o.id desc'; 
        }
        if (empty($param['type'])) { 
            $param['type'] = 0;
        } elseif (!empty($param['type'])) { 
            $where['status'] = [$param['type']]; 
        }
        $model = new OrderModel();
        $model = $model->alias('o');
        $startTime = empty($param['start_time']) ? 0 : strtotime($param['start_time']);
        $endTime   = empty($param['end_time']) ? 0 : strtotime($param['end_time']);
        if (!empty($startTime)) {
            $model = $model->where('o.create_time', '>=', $startTime);
        }
        if (!empty($endTime)) {
            $model = $model->where('o.create_time', '<=', $endTime);
        }
        if(isset($where['keyword']) && $where['keyword']!=''){
            $model = $model->where('o.order_sn','LIKE',"%".$where['keyword']."%");
        }
        $this->assign('param',$param);

        //订单列表
        $data = $model->where($where)->field('o.*')->paginate(10);
        //$aa = $model->getlastsql();
        //var_dump($aa);
        $data->appends($param);

        $this->getStatus();

        $this->assign('data',$data);
        $this->assign('page', $data->render());
        return $this->fetch();
    }

    public function getStatus() {
        $status = get_status(1);
        $this->assign('status',$status);

        $pay_status = get_status(2);
        $this->assign('pay_status',$pay_status);

        // $refund_status = get_status(3);
        // $this->assign('refund_status',$refund_status);
    }

    public function info() {
        $param = $this->request->param();
        if (empty($param['id'])) { 
            $this->error('无效的订单id');
        }
        $model = db::name('order_info')->where('order_id',$param['id']);
        if (!empty($param['keyword'])) { 
            $where['p_name'] = $param['keyword']; 
            $model = $model->where('p_name','like','%'.$param['keyword'].'%');
        }
        $data = $model->select();
        $this->assign('param',$param);
        $this->assign('data',$data);
        return $this->fetch();
    }

    /**
     * 编辑订单
     * @adminMenu(
     *     'name'   => '编辑订单',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '编辑订单',
     *     'param'  => ''
     * )
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit() {

        $id = $this->request->param('id', 0, 'intval');

        $orderModel = new OrderModel();
        $post = $orderModel->where('id', $id)->find();

        $this->assign('post', $post);
        return $this->fetch();
    }

    /**
     * 编辑订单提交
     * @adminMenu(
     *     'name'   => '编辑订单提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '编辑订单提交',
     *     'param'  => ''
     * )
     * @throws \think\Exception
     */
    public function editPost() {
        if ($this->request->isPost()) {
            $data = $this->request->param();

            $post = $data['post'];
            if (isset($post['refund_reason']) and !empty(isset($post['refund_reason']))) {
                $post['refund_status'] = 2;
                $post['refund_reason_time'] = time();
            }
            $result = $this->validate($post, 'Order');
            if ($result !== true) {
                $this->error($result);
            }
            $orderModel = new OrderModel();
            $orderModel->adminEdit($post);

            $this->success('保存成功!', url('AdminIndex/index') );

        }
    }

    public function check() {

        $id = $this->request->param('id', 0, 'intval');

        $this->getStatus();

        $orderModel = new OrderModel();
        $post = $orderModel->where('id', $id)->find();

        $this->assign('post', $post);
        return $this->fetch();
    }

    public function delivery() {
        $params = $this->request->param();
        $id = $params['id'];
        if (empty($id)) {
            $this->error('无效的订单id');
        }
        $res = $code = [];
        $kd = new kuaidi100();
        $data = Db::name('order')->where(['id'=>$id ])->field('delivery_sn,delivery_code')->find();
        if (!empty($data['delivery_sn']) and !empty($data['delivery_code'])) {
            $delivery = $kd->track(['com'=>$data['delivery_code'], 'num'=>$data['delivery_sn'] ]);
            //var_dump($delivery);exit; 
            $res['sn'] = $data['delivery_sn'];
            $res['code'] = $data['delivery_code'];
            if ($delivery['message'] == 'ok') {
                $res['data'] = $delivery['data'];
            } else {
                $res['data'] = $delivery['message'];
            }
        } else {
            $code = Db::name('delivery_code')->where('status',1)->select();
        }
        
        //var_dump($res);exit;
        $this->assign('res', $res);
        $this->assign('id', $id);
        $this->assign('code', $code);
        return $this->fetch();
    }

    // 编辑快递信息
    public function deliveryEditPost() {
        $param = $this->request->param();

        $orderModel = new OrderModel();
        $post = $orderModel->where('id', $param['id'])->find();
        if (!empty($post)) {
            $data = ['id'=>$param['id'],'delivery_sn'=>$param['delivery_sn'],'delivery_code'=>$param['delivery_code']];
            $orderModel->allowField(true)->isUpdate(true)->data($data, true)->save();
        }

        $this->success('操作成功!' );
    }

}
