<?php
namespace Home\Controller;
use Think\Controller;

/**
 * 公共控制器
 */
class CommonController extends Controller {

	/**
	 * 空操作，用于输出404页面
	 */
	public function _empty(){
		header("HTTP/1.0 404 Not Found");
		$this->show('<b>404 Not Found</b>');
		exit;
	}
	
}
