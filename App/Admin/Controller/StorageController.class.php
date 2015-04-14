<?php
namespace Admin\Controller;
use Admin\Controller\CommonController;

/**
 * 附件相关模块
 * @author wangdong
 */
class StorageController extends CommonController {
	public function index($path = '/'){
		if(IS_POST){
			$path = ltrim($path, '/');
			$path = preg_replace("/\.+/", '.', $path);  //屏蔽非法路径
			$path = str_replace(array('\\', '/', '\\/', '/\\'), DS, UPLOAD_PATH . $path);
			$data = file_list_upload($path);
			$this->ajaxReturn($data);
		}else{
			$menu_db = D('Menu');
			$currentpos = $menu_db->currentPos(I('get.menuid'));  //栏目位置
			$this->assign('currentpos', $currentpos);
			$this->display('index');
		}
	}

	/**
	 * 删除文件
	 */
	public function delete($filename){
		$filename = urldecode($filename);
		$filename = ltrim($filename, '/');
		$filename = preg_replace("/\.+/", '.', $filename);  //屏蔽非法路径
		$filename = UPLOAD_PATH . $filename;
		if(file_exist($filename)){
			if(file_delete($filename)){
				$this->success('操作成功');
			}
		}
		$this->error('操作失败');
	}

	public function public_dialog($path = '/', $callback = null, $ext = 'jpg|jpeg|png|gif|txt|xml|doc|ppt|pdf|zip|rar', $sign = ''){
		if(IS_POST){
			$path = ltrim($path, '/');
			$path = preg_replace("/\.+/", '.', $path);  //屏蔽非法路径
			$path = str_replace(array('\\', '/', '\\/', '/\\'), DS, UPLOAD_PATH . $path);
			$data = file_list_upload($path);
			$this->ajaxReturn($data);
		}else{
			//验证签名，防止非法上传
			$key = sign(array(
				'callback' => $callback,
				'ext'      => $ext
			));
			if($sign != $key) exit('<div style="padding:6px">签名错误</div>');

			$ext = preg_replace('/^\W+|\W+$/', '', $ext);
			$ext = preg_split('/\W/', $ext);

			$this->assign('callback', $callback);
			$this->assign('ext', $ext);
			$this->display('dialog');
		}
	}
}