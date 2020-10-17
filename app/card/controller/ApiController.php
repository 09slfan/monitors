<?php

namespace app\card\controller;

use cmf\controller\WxBaseController;
use think\Db;
use cmf\lib\Upload;

class ApiController extends WxBaseController {

    /**
     * 获取省
     */
    public function getProvince() {
        $type = $this->request->param('type','2');
        if ($type==1) {
            $table = 'region';
        } else {
            $table = 'regionGlobal';
        }
        $level = $this->request->param('level','1');
        $province = Db::name($table)->field('id,name')->where(array('level' => $level))->select();
        $res = array('status' => 1, 'msg' => '获取成功', 'result' => $province);
        exit(json_encode($res));
    }

    /**
     * 获取市或者区
     */
    public function getRegionByParentId() {
        $type = $this->request->param('type','2');
        $table = $type==1?'region':'regionGlobal';
        $parent_id = $this->request->param('parent_id','');
        $res = array('status' => 0, 'msg' => '获取失败，参数错误', 'result' => '');
        if($parent_id){
            $region_list = Db::name($table)->field('id,name')->where(['parent_id'=>$parent_id])->select();
            $res = array('status' => 1, 'msg' => '获取成功', 'result' => $region_list);
        }
        exit(json_encode($res));
    }

    /**
     * 获取专业
     */
    public function getMajor() {
        $i_id = $this->request->param('i_id','0');
        $q_id = $this->request->param('q_id','0');
        $major = Db::name('schoolMajor')->field('id,name')->where(array('q_id' => $q_id,'i_id' => $i_id))->cache(true)->select();
        $res = array('status' => 1, 'msg' => '获取成功', 'result' => $major);
        exit(json_encode($res));
    }

    public function uploadFile(){
        $file = $this->request->param('file','');
        $random = $this->request->param('random','0');
        // 获取图片
        list($type, $data) = explode(',', $file);
 
        // 判断类型
        if(strstr($type,'image/jpeg')!=''){
            $ext = '.jpg';
        }elseif(strstr($type,'image/gif')!=''){
            $ext = '.gif';
        }elseif(strstr($type,'image/png')!=''){
            $ext = '.png';
        }
        $path = 'card/';
        $dir = "./upload/".$path;
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        $dir_id = session('?user_id')?session('user_id'):'0';
        $dir .= $dir_id.'/';
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        $filepath = $path.$dir_id.'/'.$random.time().$ext;
        $photo = $dir.$random.time().$ext;
        file_put_contents($photo, base64_decode($data), true);
        // 返回
        header('content-type:application/json;charset=utf-8');
        $res = array('img'=>$filepath);
        exit(json_encode($res));
    }

    // 通用方法
    public function upload(){
        $user_id  = $this->getUserId();
        if($this->request->isPost()){
            $file = $this->request->file('file');
            $info = $file->move(WEB_ROOT . 'upload/default');
            $file_info = $info->getInfo();
            $size = $info->getSize();
            //$size = set_number_format($size/1024);
            $url = '/upload/default/'.$info->getSaveName();
            $url = input('server.REQUEST_SCHEME'). '://' .input('server.SERVER_NAME').str_replace("\\","/",$url);   //更新链接 DS
            $data = ['file_size'=>$size,'file_path'=>$url];
            $data['user_id'] = $user_id?$user_id:0;
            $data['create_time'] = time();
            $data['filename'] = $file_info['name'];
            $data['suffix'] = $file_info['type'];
            $id = Db::name('asset')->insertGetId($data);
            $res = array('status' => 1, 'msg' => '获取成功', 'result' => ['id'=>$id,'url'=>$url,'data'=>['src'=>$url,'title'=>$data['filename'] ] ]);
            exit(json_encode($res));
        }
    }

    public function one() {
        if ($this->request->isPost()) {
            $uploader = new Upload();

            $result = $uploader->upload();

            if ($result === false) {
                $this->error($uploader->getError());
            } else {
                $result['preview_url'] = cmf_get_image_preview_url($result["filepath"]);
                $result['url']         = cmf_get_image_url($result["filepath"]);
                $result['filename']    = $result["name"];
                $res = array('status' => 1, 'msg' => '获取成功', 'result' => $result);
                exit(json_encode($res));

            }
        }
    }


}
