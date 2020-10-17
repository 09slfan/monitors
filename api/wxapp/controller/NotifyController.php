<?php
// +----------------------------------------------------------------------
// | 涉及到需要回调的文件返回
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019.
// +----------------------------------------------------------------------
// | Author: 小艾.pino
// +----------------------------------------------------------------------
namespace api\wxapp\controller;

use think\Db;
use cmf\controller\RestBaseController;
use wxpay\Notify;

class NotifyController extends RestBaseController {
    //支付回调
    public function paySucceed() {
        $pay = new Notify();
        $pay->Handle();
    }

    public function text() {
    	if (isset($GLOBALS['HTTP_RAW_POST_DATA'])) {
			$xml = $GLOBALS['HTTP_RAW_POST_DATA'];
			$results = json_encode($xml);
            cmf_log($results,"data/runtime/log/111result_".time().".txt");
		}
    }
}
