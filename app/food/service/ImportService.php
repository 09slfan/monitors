<?php

namespace app\food\service;

use think\Db;

class ImportService {

	public $arr = [];

    public function getInit($arr = []) {
        $this->arr = $arr;
        return is_array($arr);
    }

    public function getTreeArray($myId, $maxLevel = 0, $level = 1) {
        $returnArray = [];

        //一级数组
        $children = $this->getChild($myId);

        if (is_array($children)) {
            foreach ($children as $child) {
                $returnArray[$child['name']] = $child;
                if ($maxLevel === 0 || ($maxLevel !== 0 && $maxLevel > $level)) {
                    $mLevel                                = $level + 1;
                    if (!empty($this->getTreeArray($child['name'], $maxLevel, $mLevel) )) {
                        $returnArray[$child['name']]["children"] = $this->getTreeArray($child['name'], $maxLevel, $mLevel);
                    }
                    
                }

            }
        }

        return  array_values($returnArray);
    }

    /**
     * 得到子级数组
     * @param int
     * @return array
     */
    public function getChild($myId) {
        $newArr = [];
        if (is_array($this->arr)) {
            foreach ($this->arr as $id => $a) {
                if ($a['parent_id'] == $myId) {
                    unset($a['parent_id']);
                    $newArr[$id] = $a;
                }
            }
        }
        return $newArr ? $newArr : false;
    }

	/**
	 * 导入Service 
	 * @param $params
	 * @return array
	 * @throws \PHPExcel_Exception
	 */
	public function importFood($params) {
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
		$f = Db::name('foodPost');
		$fc = Db::name('foodCategoryPost');
		Db::startTrans();  //启动事务
		try {
			// 载入文件
			$objPHPExcel = $objReader->load($dataFile);
			// 获取表中的第一个工作表
			$currentSheet = $objPHPExcel->getSheet(0);
			// 获取总行数
			$allRow = $currentSheet->getHighestRow();
			$now = 837;
			$category_id = 13;
			
			// 如果没有数据行，直接返回
			if ($allRow < 1) { return $result; }
			// 从第2行获取数据
			for($currentRow = 1; $currentRow <= $allRow; $currentRow ++) {
				// 1 数据坐标
				$title       = 'A' . $currentRow;    //名称
				$nums        = 'B' . $currentRow;    //热量 

				// 2 读取数据
				$title       = $currentSheet->getCell($title)->getValue();
				$nums        = $currentSheet->getCell($nums)->getValue();

				// 3 数据规范化
				$title       = (!empty($title)) ? trim($title) : '';
				$nums        = (!empty($nums)) ? trim($nums) : '0';
				$gross       = 100;
				$nums        = explode("/", $nums);
				$calorie     = $nums[0];
				$value       = $nums[1];

				if (empty($title) or !is_numeric($calorie) or !is_numeric($value) ) { continue; }
				// 1 food基本表
				$arr = [];
				$arr['id'] = $currentRow+$now;
				$arr['user_id'] = 1;
				$arr['post_status'] = 1;
				$arr['post_title'] = $title;
				$arr['post_unit'] = 1;
				$arr['post_calorie'] = $calorie;
				$arr['post_gross'] = $gross;
				$arr['post_value'] = $value;
				$arr['create_time'] = $arr['update_time'] = $arr['published_time'] = time();
				$f->insert($arr);

				// 2 user_more表，用户扩展表
				$fc_arr = [];
				$fc_arr['id'] = $currentRow+$now;
				$fc_arr['post_id'] = $arr['id'];
				$fc_arr['category_id'] = $category_id;
				$fc->insert($fc_arr);
				
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
