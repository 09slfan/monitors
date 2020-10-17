<?php

namespace app\user\service;

use think\Db;

class ImportService {

	public $arr = [];

	/**
	 * 导入Service 
	 * @param $params
	 * @return array
	 * @throws \PHPExcel_Exception
	 */
	public function import($params) {
		$dataFile = $params["datafile"];
		$ext = $params["ext"];
		$message = "";
		$success = true;
		$result = array("msg" => $message,"success" => $success);
		if (! $dataFile || ! $ext) { return $result; }
		
		$inputFileType = 'Excel5';
		if ($ext == 'xlsx') { $inputFileType = 'Excel2007'; }
			
		// 设置php服务器可用内存，上传较大文件时可能会用到
		ini_set('memory_limit', '1024M');
		ini_set('max_execution_time', 300); // 300 seconds = 5 minutes
		$objReader = \PHPExcel_IOFactory::createReader($inputFileType);
		// 设置只读，可取消类似"3.08E-05"之类自动转换的数据格式，避免写库失败
		$objReader->setReadDataOnly(true);

		$db = Db::name('');
		Db::startTrans();  //启动事务
		try {
			// 载入文件
			$objPHPExcel = $objReader->load($dataFile);
			// 获取表中的第一个工作表
			$currentSheet = $objPHPExcel->getSheet(0);
			// 获取总行数
			$allRow = $currentSheet->getHighestRow();
			
			// 如果没有数据行，直接返回
			if ($allRow < 1) { return $result; }
			// 从第2行获取数据
			for($currentRow = 2; $currentRow <= $allRow; $currentRow ++) {
				// 1 数据坐标
				$school      = 'A' . $currentRow;    //学校
				$name        = 'B' . $currentRow;    //账号 
				$code        = 'C' . $currentRow;    //密码 
				$realname    = 'D' . $currentRow;    //姓名 
				$mobile      = 'E' . $currentRow;    //手机号

				// 2 读取数据
				$school      = $currentSheet->getCell($school)->getValue();
				$name        = $currentSheet->getCell($name)->getValue();
				$code        = $currentSheet->getCell($code)->getValue();
				$realname    = $currentSheet->getCell($realname)->getValue();
				$mobile      = $currentSheet->getCell($mobile)->getValue();

				// 3 数据规范化
				$school      = (!empty($school)) ? trim($school) : '';
				$name        = (!empty($name)) ? trim($name) : '';
				$code        = (!empty($code)) ? trim($code) : '';
				$realname    = (!empty($realname)) ? trim($realname) : '';
				$mobile      = (!empty($mobile)) ? trim($mobile) : '';

				if (empty($school) or empty($name) or empty($code) ) { continue; }

				// 密码处理
				if(!preg_match("/^[_0-9a-zA-Z]{6,}$/i",$code)){ continue; }
				//if(!preg_match("/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,}$/",$code)){ continue; }
				$code = cmf_password($code);

				// 0 获取学校id ->where('status',1)
				$school_id = db::name('school')->where('name',$school)->value('id');
				if (empty($school_id)) { continue; }

				// 1 user基本表
				$is_exist = db::name('user')->where('user_login',$name)->where('user_pass',$code)->count();
				if (!empty($is_exist)) { continue; }

				$arr = [];
				$arr['user_login'] = $name;
				$arr['user_nickname'] = $realname;
				$arr['mobile'] = $mobile;
				$arr['user_pass'] = $code;
				$arr['school_id'] = $school_id;
				$arr['user_type'] = 2;
				$arr['user_cate'] = 1;
				$arr['user_status'] = 1;
				$arr['create_time'] = time();
				db::name('user')->insert($arr);
				
			}
		} catch ( Exception $e ) {
			$success = false;
			$message = $e;
		}
		if(false !== $success)
    	    $db->commit();  // 提交事务
    	else
    	    $db->rollback();// 事务回滚

		$message .= '操作完成！';
		
		$result = array(
				"msg" => $message,
				"success" => $success
		);
		return $result;
	}

	public function importParent($params) {
		$dataFile = $params["datafile"];
		$ext = $params["ext"];
		$message = "";
		$success = true;
		$result = array("msg" => $message,"success" => $success);
		if (! $dataFile || ! $ext) { return $result; }
		
		$inputFileType = 'Excel5';
		if ($ext == 'xlsx') { $inputFileType = 'Excel2007'; }
			
		// 设置php服务器可用内存，上传较大文件时可能会用到
		ini_set('memory_limit', '1024M');
		ini_set('max_execution_time', 300); // 300 seconds = 5 minutes
		$objReader = \PHPExcel_IOFactory::createReader($inputFileType);
		// 设置只读，可取消类似"3.08E-05"之类自动转换的数据格式，避免写库失败
		$objReader->setReadDataOnly(true);

		$db = Db::name('');
		Db::startTrans();  //启动事务
		try {
			// 载入文件
			$objPHPExcel = $objReader->load($dataFile);
			// 获取表中的第一个工作表
			$currentSheet = $objPHPExcel->getSheet(0);
			// 获取总行数
			$allRow = $currentSheet->getHighestRow();
			
			// 如果没有数据行，直接返回
			if ($allRow < 1) { return $result; }
			// 从第2行获取数据
			for($currentRow = 2; $currentRow <= $allRow; $currentRow ++) {
				// 1 数据坐标
				$school      = 'A' . $currentRow;    //学校
				$name        = 'B' . $currentRow;    //账号 
				$code        = 'C' . $currentRow;    //密码 
				$realname    = 'D' . $currentRow;    //姓名 
				$mobile      = 'E' . $currentRow;    //手机号

				// 2 读取数据
				$school      = $currentSheet->getCell($school)->getValue();
				$name        = $currentSheet->getCell($name)->getValue();
				$code        = $currentSheet->getCell($code)->getValue();
				$realname    = $currentSheet->getCell($realname)->getValue();
				$mobile      = $currentSheet->getCell($mobile)->getValue();

				// 3 数据规范化
				$school      = (!empty($school)) ? trim($school) : '';
				$name        = (!empty($name)) ? trim($name) : '';
				$code        = (!empty($code)) ? trim($code) : '';
				$realname    = (!empty($realname)) ? trim($realname) : '';
				$mobile      = (!empty($mobile)) ? trim($mobile) : '';

				if (empty($school) or empty($name) or empty($code) ) { continue; }

				// 密码处理
				if(!preg_match("/^[_0-9a-zA-Z]{6,}$/i",$code)){ continue; }
				//if(!preg_match("/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,}$/",$code)){ continue; }
				$code = cmf_password($code);

				// 0 获取学校id ->where('status',1)
				$school_id = db::name('school')->where('name',$school)->value('id');
				if (empty($school_id)) { continue; }

				// 1 user基本表
				$is_exist = db::name('user')->where('user_login',$name)->where('user_pass',$code)->count();
				if (!empty($is_exist)) { continue; }

				$arr = [];
				$arr['user_login'] = $name;
				$arr['user_nickname'] = $realname;
				$arr['mobile'] = $mobile;
				$arr['user_pass'] = $code;
				$arr['school_id'] = $school_id;
				$arr['user_type'] = 2;
				$arr['user_cate'] = 3;
				$arr['user_status'] = 1;
				$arr['create_time'] = time();
				db::name('user')->insert($arr);
				
			}
		} catch ( Exception $e ) {
			$success = false;
			$message = $e;
		}
		if(false !== $success)
    	    $db->commit();  // 提交事务
    	else
    	    $db->rollback();// 事务回滚

		$message .= '操作完成！';
		
		$result = array(
				"msg" => $message,
				"success" => $success
		);
		return $result;
	}

	public function importMonitor($params) {
		$dataFile = $params["datafile"];
		$ext = $params["ext"];
		$message = "";
		$success = true;
		$result = array("msg" => $message,"success" => $success);
		if (! $dataFile || ! $ext) { return $result; }
		
		$inputFileType = 'Excel5';
		if ($ext == 'xlsx') { $inputFileType = 'Excel2007'; }
			
		// 设置php服务器可用内存，上传较大文件时可能会用到
		ini_set('memory_limit', '1024M');
		ini_set('max_execution_time', 300); // 300 seconds = 5 minutes
		$objReader = \PHPExcel_IOFactory::createReader($inputFileType);
		// 设置只读，可取消类似"3.08E-05"之类自动转换的数据格式，避免写库失败
		$objReader->setReadDataOnly(true);

		$db = Db::name('');
		Db::startTrans();  //启动事务
		try {
			// 载入文件
			$objPHPExcel = $objReader->load($dataFile);
			// 获取表中的第一个工作表
			$currentSheet = $objPHPExcel->getSheet(0);
			// 获取总行数
			$allRow = $currentSheet->getHighestRow();
			
			// 如果没有数据行，直接返回
			if ($allRow < 1) { return $result; }
			// 从第2行获取数据
			for($currentRow = 2; $currentRow <= $allRow; $currentRow ++) {
				// 1 数据坐标
				$area        = 'A' . $currentRow;    //区域
				$name        = 'B' . $currentRow;    //账号 
				$code        = 'C' . $currentRow;    //密码 
				$realname    = 'D' . $currentRow;    //姓名 
				$mobile      = 'E' . $currentRow;    //手机号

				// 2 读取数据
				$area        = $currentSheet->getCell($area)->getValue();
				$name        = $currentSheet->getCell($name)->getValue();
				$code        = $currentSheet->getCell($code)->getValue();
				$realname    = $currentSheet->getCell($realname)->getValue();
				$mobile      = $currentSheet->getCell($mobile)->getValue();

				// 3 数据规范化
				$area        = (!empty($area)) ? trim($area) : '';
				$name        = (!empty($name)) ? trim($name) : '';
				$code        = (!empty($code)) ? trim($code) : '';
				$realname    = (!empty($realname)) ? trim($realname) : '';
				$mobile      = (!empty($mobile)) ? trim($mobile) : '';

				if (empty($area) or empty($name) or empty($code) ) { continue; }

				// 密码处理
				if(!preg_match("/^[_0-9a-zA-Z]{6,}$/i",$code)){ continue; }
				//if(!preg_match("/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,}$/",$code)){ continue; }
				$code = cmf_password($code);

				// 0 获取学校id
				$areas = explode('-', $area);
				if (count($areas)<3) { continue; }
				$province = db::name('region')->where('name',$areas[0])->where('level',1)->value('id');
				$city = db::name('region')->where('name',$areas[1])->where('parent_id',$province)->where('level',2)->value('id');
				$district = db::name('region')->where('name',$areas[2])->where('parent_id',$city)->where('level',3)->value('id');
				if (empty($province) or empty($city) or empty($district)) { continue; }

				// 1 user基本表
				$is_exist = db::name('user')->where('user_login',$name)->where('user_pass',$code)->count();
				if (!empty($is_exist)) { continue; }

				$arr = [];
				$arr['user_login'] = $name;
				$arr['user_nickname'] = $realname;
				$arr['mobile'] = $mobile;
				$arr['user_pass'] = $code;
				$arr['province'] = $province;
				$arr['city'] = $city;
				$arr['district'] = $district;
				$arr['user_type'] = 2;
				$arr['user_cate'] = 4;
				$arr['user_status'] = 1;
				$arr['create_time'] = time();
				db::name('user')->insert($arr);
				
			}
		} catch ( Exception $e ) {
			$success = false;
			$message = $e;
		}
		if(false !== $success)
    	    $db->commit();  // 提交事务
    	else
    	    $db->rollback();// 事务回滚

		$message .= '操作完成！';
		
		$result = array(
				"msg" => $message,
				"success" => $success
		);
		return $result;
	}

	public function importCanteen($params) {
		$dataFile = $params["datafile"];
		$ext = $params["ext"];
		$message = "";
		$success = true;
		$result = array("msg" => $message,"success" => $success);
		if (! $dataFile || ! $ext) { return $result; }
		
		$inputFileType = 'Excel5';
		if ($ext == 'xlsx') { $inputFileType = 'Excel2007'; }
			
		// 设置php服务器可用内存，上传较大文件时可能会用到
		ini_set('memory_limit', '1024M');
		ini_set('max_execution_time', 300); // 300 seconds = 5 minutes
		$objReader = \PHPExcel_IOFactory::createReader($inputFileType);
		// 设置只读，可取消类似"3.08E-05"之类自动转换的数据格式，避免写库失败
		$objReader->setReadDataOnly(true);

		$db = Db::name('');
		Db::startTrans();  //启动事务
		try {
			// 载入文件
			$objPHPExcel = $objReader->load($dataFile);
			// 获取表中的第一个工作表
			$currentSheet = $objPHPExcel->getSheet(0);
			// 获取总行数
			$allRow = $currentSheet->getHighestRow();
			
			// 如果没有数据行，直接返回
			if ($allRow < 1) { return $result; }
			// 从第2行获取数据
			for($currentRow = 2; $currentRow <= $allRow; $currentRow ++) {
				// 1 数据坐标
				$school      = 'A' . $currentRow;    //学校
				$canteen     = 'B' . $currentRow;    //食堂
				$name        = 'C' . $currentRow;    //账号 
				$code        = 'D' . $currentRow;    //密码 
				$realname    = 'E' . $currentRow;    //姓名 
				$mobile      = 'F' . $currentRow;    //手机号

				// 2 读取数据
				$school      = $currentSheet->getCell($school)->getValue();
				$canteen     = $currentSheet->getCell($canteen)->getValue();
				$name        = $currentSheet->getCell($name)->getValue();
				$code        = $currentSheet->getCell($code)->getValue();
				$realname    = $currentSheet->getCell($realname)->getValue();
				$mobile      = $currentSheet->getCell($mobile)->getValue();

				// 3 数据规范化
				$school      = (!empty($school)) ? trim($school) : '';
				$canteen     = (!empty($canteen)) ? trim($canteen) : '';
				$name        = (!empty($name)) ? trim($name) : '';
				$code        = (!empty($code)) ? trim($code) : '';
				$realname    = (!empty($realname)) ? trim($realname) : '';
				$mobile      = (!empty($mobile)) ? trim($mobile) : '';

				if (empty($school) or empty($canteen) or empty($name) or empty($code) ) { continue; }

				// 密码处理
				if(!preg_match("/^[_0-9a-zA-Z]{6,}$/i",$code)){ continue; }
				//if(!preg_match("/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,}$/",$code)){ continue; }
				$code = cmf_password($code);

				// 0 获取学校id ->where('status',1)
				$school_id = db::name('school')->where('name',$school)->value('id');
				if (empty($school_id)) { continue; }
				$canteen_id = db::name('canteen')->where('school_id',$school_id)->where('name',$canteen)->value('id');
				if (empty($canteen_id)) { continue; }

				// 1 user基本表
				$is_exist = db::name('user')->where('user_login',$name)->where('user_pass',$code)->count();
				if (!empty($is_exist)) { continue; }

				$arr = [];
				$arr['user_login'] = $name;
				$arr['user_nickname'] = $realname;
				$arr['mobile'] = $mobile;
				$arr['user_pass'] = $code;
				$arr['school_id'] = $school_id;
				$arr['canteen_id'] = $canteen_id;
				$arr['user_type'] = 2;
				$arr['user_cate'] = 2;
				$arr['user_status'] = 1;
				$arr['create_time'] = time();
				db::name('user')->insert($arr);
				
			}
		} catch ( Exception $e ) {
			$success = false;
			$message = $e;
		}
		if(false !== $success)
    	    $db->commit();  // 提交事务
    	else
    	    $db->rollback();// 事务回滚

		$message .= '操作完成！';
		
		$result = array(
				"msg" => $message,
				"success" => $success
		);
		return $result;
	}

	public function setSex($data){
		switch ($data) {
			case '男': $res = 1; break;
			case '女': $res = 2; break;
			default:   $res = 0; break;
		}
		return $res;
	}

	public function setRegion($data,$pid=0,$level=1){
		if (empty($data)) {
			return 0;
		} else {
			$where = ['name'=>$data,'parent_id'=>$pid];
			$rid = Db::name('Region')->where($where)->value('id');
			if (empty($rid)) {
				$rid = Db::name('Region')->insertGetId(['parent_id'=>$pid,'name'=>$data,'level'=>$level]);
			}
			return $rid;
		}
	}

	public function setMsg($data,$table='',$name='name',$field='id'){
		$school_id = cmf_get_current_school_id();
		$where = [$name=>$data,'school_id'=>$school_id];
		$id = Db::name($table)->where($where)->value($field);
		if (empty($id)) {
			$id = Db::name($table)->insertGetId($where);
		}
		return $id;
	}

	public function setInMsg($data,$table='',$name='name',$field='id'){
		$school_id = cmf_get_current_school_id();
		$where = array_merge($data,['school_id'=>$school_id]);
		// $where = [$name=>$data,'school_id'=>$school_id];
		$id = Db::name($table)->where($where)->value($field);
		if (empty($id)) {
			$id = Db::name($table)->insertGetId($where);
		}
		return $id;
	}


}
