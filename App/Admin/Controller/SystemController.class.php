<?php
namespace Admin\Controller;
use Admin\Controller\CommonController;

/**
 * 后台管理模块
 * @author wangdong
 */
class SystemController extends CommonController{
	private $fileBathPath = RUNTIME_PATH;   //文件管理根目录
	
	/**
	 * 站点设置
	 */
	public function setting(){
		if(IS_POST){
			$setting_db = D('Setting');
			if(I('get.dosubmit')){
				$state = $setting_db->dosave($_POST['data']);
				$state ? $this->success('操作成功') : $this->error('操作失败');
			}else{
				if(S('system_setting')){
					$data = S('system_setting');
				}else{
					$data = $setting_db->getSetting();
					S('system_setting', $data);
				}
				$this->ajaxReturn($data);
			}
		}else {
			$menu_db = D('Menu');
			$currentpos = $menu_db->currentPos(I('get.menuid'));  //栏目位置
			$this->assign('currentpos', $currentpos);
			$this->display();
		}
	}
	
	/**
	 * 恢复出厂设置
	 */
	public function settingDefault(){
		if(IS_POST){
			$setting_db = D('Setting');
			if($setting_db->where('1')->count()){
				$state = $setting_db->where('1')->delete();
				if($state){
					$setting_db->clearCatche();
					$this->success('操作成功');
				}else{
					$this->error('操作失败');
				}
			}
			$this->success('操作成功');
		}
	}
	
	/**
	 * 导出
	 */
	public function settingExport($filename = ''){
		if(IS_POST) {
			$setting_db = M('setting');
			$data       = array('type'=>'setting');
			$data['data']   = $setting_db->select();
			$data['verify'] = md5(var_export($data['data'], true) . $data['type']);
	
			//数据进行多次加密，防止数据泄露
			$data = base64_encode(gzdeflate(json_encode($data)));
	
			$uniqid = uniqid();
			$filename = UPLOAD_PATH . 'export/' . $uniqid . '.data';
			if(file_write($filename, $data)){
				$this->success('导出成功', U('System/settingExport', array('filename'=>$uniqid)));
			}
			$this->error('导出失败，请重试！');
		}else{
			//过滤特殊字符，防止非法下载文件
			$filename = str_replace(array('.', '/', '\\'), '', $filename);
			$filename = UPLOAD_PATH . 'export/' . $filename . '.data';
			if(!file_exist($filename)) $this->error('非法访问');
	
			header('Content-type: application/octet-stream');
			header('Content-Disposition: attachment; filename="站点设置.data"');
			echo file_read($filename);
	
			file_delete($filename);
		}
	}
	
	/**
	 * 导入
	 */
	public function settingImport($filename = ''){
		if(IS_POST) {
			//过滤特殊字符，防止非法下载文件
			$filename = str_replace(array('.', '/', '\\'), '', $filename);
			$filename = UPLOAD_PATH . 'import/' . $filename . '.data';
			if(!file_exist($filename)) $this->error('导入失败');
	
			$content = file_read($filename);
	
			//解密
			try {
				$data  = gzinflate(base64_decode($content));
			}catch (\Exception $e){};
			if(!isset($data)){
				file_delete($filename);
				$this->error('非法数据');
			}
	
			//防止非法数据
			try {
				$data = json_decode($data, true);
			}catch (\Exception $e){};
			if(!is_array($data) || !isset($data['type']) || $data['type'] != 'setting' || !isset($data['verify']) || !isset($data['data'])){
				file_delete($filename);
				$this->error('非法数据');
			}
	
			if($data['verify'] != md5(var_export($data['data'], true) . $data['type'])){
				file_delete($filename);
				$this->error('非法数据');
			}
	
			$setting_db = D('Setting');
	
			//先清空数据再导入
			$setting_db->where("`key` <> ''")->delete();
			$setting_db->clearCatche();
	
			//开始导入
			asort($data['data']);
			foreach ($data['data'] as $add){
				$setting_db->add($add);
			}
	
			file_delete($filename);
			$this->success('导入成功');
		}else{
			$this->error('非法访问');
		}
	}
	
	
	/**
	 * 操作日志列表
	 */
	public function logList($page=1, $rows=10, $search = array(), $sort = 'time', $order = 'desc'){
		if(IS_POST){
			$log_db = M('log');
			
			//搜索
			$where = array();
			foreach ($search as $k=>$v){
				if(!$v) continue;
				switch ($k){
					case 'username':
					case 'controller':
						$where[] = "`{$k}` = '{$v}'";
						break;
					case 'begin':
						if(!preg_match("/^\d{4}(-\d{2}){2}$/", $v)){
							unset($search[$k]);
							continue;
						}
						if($search['end'] && $search['end'] < $v) $v = $search['end'];
						$where[] = "`time` >= '{$v}'";
						break;
					case 'end':
						if(!preg_match("/^\d{4}(-\d{2}){2}$/", $v)){
							unset($search[$k]);
							continue;
						}
						if($search['begin'] && $search['begin'] > $v) $v = $search['begin'];
						$where[] = "`time` <= '{$v}'";
						break;
				}
			}
			$where = implode(' and ', $where);
			
			$limit=($page - 1) * $rows . "," . $rows;
			$total = $log_db->where($where)->count();
			$order = $sort.' '.$order;
			$list = $total ? $log_db->where($where)->order($order)->limit($limit)->select() : array();
			foreach($list as &$info){
				//过滤html标签，以免显示不正常
				$info['querystring'] = htmlentities($info['querystring']);
			}
			$data = array('total'=>$total, 'rows'=>$list);
			$this->ajaxReturn($data);
		}else{
			$menu_db = D('Menu');
			$admin_db = D('Admin');
			
			$currentpos = $menu_db->currentPos(I('get.menuid'));  //栏目位置
			$list = array();
			$list['admin'] = $admin_db->order('lastlogintime DESC')->getField('username', true);
			$list['module'] = $menu_db->order('c')->group('c')->getField('c', true);

			$datagrid = array(
				'options'     => array(
					'title'   => $currentpos,
					'url'     => U('System/logList', array('grid'=>'datagrid')),
					'toolbar' => '#system-loglist-datagrid-toolbar',
				),
				'fields' => array(
					'用户名' => array('field'=>'username','width'=>20,'sortable'=>true),
					'模块'   => array('field'=>'controller','width'=>15,'sortable'=>true),
					'方法'   => array('field'=>'action','width'=>15,'sortable'=>true),
					'参数'   => array('field'=>'querystring','width'=>100,'formatter'=>'systemLogModule.querystring'),
					'时间'   => array('field'=>'time','width'=>30,'sortable'=>true),
					'IP'    => array('field'=>'ip','width'=>25,'sortable'=>true),
				)
			);
			$this->assign('datagrid', $datagrid);
			$this->assign('list', $list);
			$this->display('log_list');
		}
	}
	
	/**
	 * 操作日志删除
	 */
	public function logDelete($week = 4) {
		$log_db = M('log');
		$start = time() - $week*7*24*3600;
		$d = date('Y-m-d', $start); 
		$where = "left(`time`, 10) <= '$d'";
		$result = $log_db->where($where)->delete();
		$result ? $this->success('删除成功') : $this->error('没有数据或已删除过了，请稍后再试');
	}
	
	
	/**
	 * 菜单列表
	 */
	public function menuList(){
		$menu_db = D('Menu');
		if(IS_POST){
			if(S('system_menulist')){
				$data = S('system_menulist');
			}else{
				$data = $menu_db->getTree();
				S('system_menulist', $data);
			}
			$this->ajaxReturn($data);
		}else{
			$currentpos = $menu_db->currentPos(I('get.menuid'));  //栏目位置
			$treegrid = array(
				'options'       => array(
					'title'        => $currentpos,
					'url'          => U('System/menuList', array('grid'=>'treegrid')),
					'idField'    => 'id',
					'treeField' => 'name',
					'toolbar'   => 'systemMenuModule.toolbar',
				),
				'fields' => array(
					'排序'        => array('field'=>'listorder','width'=>20,'align'=>'center','formatter'=>'systemMenuModule.sort'),
					'菜单ID'    => array('field'=>'id','width'=>20,'align'=>'center'),
					'菜单名称' => array('field'=>'name','width'=>200),
					'管理操作' => array('field'=>'operateid','width'=>80,'align'=>'center','formatter'=>'systemMenuModule.operate'),
				)
			);
			$this->assign('treegrid', $treegrid);
			$this->display('menu_list');
		}
	}
	
	/**
	 * 添加菜单
	 */
	public function menuAdd($parentid = 0){
		if(IS_POST){
			$menu_db = D('Menu');
			$data = I('post.info');
			$data['display'] = $data['display'] ? '1' : '0';
			$id = $menu_db->add($data);
			if($id){
				$menu_db->clearCatche();
				$this->success('添加成功');
			}else {
				$this->error('添加失败');
			}
		}else{
			$this->assign('parentid', $parentid);
			$this->display('menu_add');
		}
	}
	
	/**
	 * 编辑菜单
	 */
	public function menuEdit($id = 0){
		if(!$id) $this->error('未选择菜单');
		$menu_db = D('Menu');
		if(IS_POST){
			$data = I('post.info');
			if(!$menu_db->checkParentId($id, $data['parentid'])){
				$this->error('上级菜单设置失败');
			}

			$data['display'] = $data['display'] ? '1' : '0';
			$result = $menu_db->where(array('id'=>$id))->save($data);
			if($result){
				$menu_db->clearCatche();
				$this->success('修改成功');
			}else {
				$this->error('修改失败');
			}
		}else{
			$data = $menu_db->where(array('id'=>$id))->find();
			$this->assign('data', $data);
			$this->display('menu_edit');
		}
	}
	
	/**
	 * 删除菜单
	 */
	public function menuDelete($id = 0){
		if($id && IS_POST){
			$menu_db = D('Menu');
			$result = $menu_db->where(array('id'=>$id))->delete();
			if($result){
				$menu_db->clearCatche();
				$this->success('删除成功');
			}else {
				$this->error('删除失败');
			}
		}else{
			$this->error('删除失败');
		}
	}

	/**
	 * 菜单排序
	 */
	public function menuOrder(){
		if(IS_POST) {
			$menu_db = D('Menu');
			foreach(I('post.order') as $id => $listorder) {
				$menu_db->where(array('id'=>$id))->save(array('listorder'=>$listorder));
			}
			$menu_db->clearCatche();
			$this->success('操作成功');
		} else {
			$this->error('操作失败');
		}
	}
	
	/**
	 * 菜单导出
	 */
	public function menuExport($filename = ''){
		if(IS_POST) {
			$menu_db = D('Menu');
			$data    = array('type'=>'menu');
			$data['data']   = $menu_db->order('id asc')->getField('id,name,parentid,c,a,data,listorder,display', true);
			$data['verify'] = md5(var_export($data['data'], true) . $data['type']);
			
			//数据进行多次加密，防止数据泄露
			$data = base64_encode(gzdeflate(json_encode($data)));
			
			$uniqid = uniqid();
			$filename = UPLOAD_PATH . 'export/' . $uniqid . '.data';
			if(file_write($filename, $data)){
				$this->success('导出成功', U('System/menuExport', array('filename'=>$uniqid)));
			}
			$this->error('导出失败，请重试！');
		}else{
			//过滤特殊字符，防止非法下载文件
			$filename = str_replace(array('.', '/', '\\'), '', $filename);
			$filename = UPLOAD_PATH . 'export/' . $filename . '.data';
			if(!file_exist($filename)) $this->error('非法访问');
			
			header('Content-type: application/octet-stream');
			header('Content-Disposition: attachment; filename="菜单管理.data"');
			echo file_read($filename);
			
			file_delete($filename);
		}
	}
	
	/**
	 * 菜单导入
	 */
	public function menuImport($filename = ''){
		if(IS_POST) {
			//过滤特殊字符，防止非法下载文件
			$filename = str_replace(array('.', '/', '\\'), '', $filename);
			$filename = UPLOAD_PATH . 'import/' . $filename . '.data';
			if(!file_exist($filename)) $this->error('导入失败');
			
			$content = file_read($filename);
			
			//解密
			try {
				$data  = gzinflate(base64_decode($content));
			}catch (\Exception $e){};
			if(!isset($data)){
				file_delete($filename);
				$this->error('非法数据');
			}
			
			//防止非法数据
			try {
				$data = json_decode($data, true);
			}catch (\Exception $e){};
			if(!is_array($data) || !isset($data['type']) || $data['type'] != 'menu' || !isset($data['verify']) || !isset($data['data'])){
				file_delete($filename);
				$this->error('非法数据');
			}
			
			if($data['verify'] != md5(var_export($data['data'], true) . $data['type'])){
				file_delete($filename);
				$this->error('非法数据');
			}
			
			$menu_db = D('Menu');
			
			//先清空数据再导入
			$menu_db->where('id > 0')->delete();
			$menu_db->clearCatche();
			
			//开始导入
			asort($data['data']);
			foreach ($data['data'] as $add){
				$menu_db->add($add);
			}
			
			file_delete($filename);
			$this->success('导入成功');
		}else{
			$this->error('非法访问');
		}
	}

	/**
	 * 菜单下拉框
	 */
	public function public_menuSelectTree(){
		if(S('system_public_menuselecttree')){
			$data = S('system_public_menuselecttree');
		}else {
			$menu_db = D('Menu');
			$data = $menu_db->getSelectTree();
			$data = array(0=>array('id'=>0,'text'=>'作为一级菜单','children'=>$data));
			S('system_public_menuselecttree', $data);
		}
		$this->ajaxReturn($data);
	}
	
	/**
	 * 验证菜单名称是否已存在
	 */
	public function public_menuNameCheck($name){
		if(I('get.default') == $name) {
			exit('true');
		}
		
		$menu_db = D('Menu');
		$exists = $menu_db->checkName($name);
		if ($exists) {
			exit('false');
		}else{
			exit('true');
		}
	}
	
	
	
	/**
	 * 文件列表
	 */
	public function fileList($path = '/'){
		if(IS_POST){
			$path = preg_replace("/\.+/", '.', $path);  //屏蔽非法路径
			$path = str_replace(array('\\', '/', '\\/', '/\\'), DS, $this->fileBathPath . $path);
			$data = scan_dir($path, $this->fileBathPath);
			$this->ajaxReturn($data);
		}else{
			$menu_db = D('Menu');
			$currentpos = $menu_db->currentPos(I('get.menuid'));  //栏目位置
			$this->assign('currentpos', $currentpos);
			$this->display('file_list');
		}
	}
	
	/**
	 * 文件查看
	 */
	public function fileView($filename){
		$filename = urldecode($filename);
		$filename = $this->fileBathPath . $filename;
		if(\Think\Storage::has($filename)){
			echo str_replace(array("\n", "\t"), array('<br />', '&nbsp;&nbsp;&nbsp;&nbsp;'), htmlspecialchars(\Think\Storage::read($filename)));
		}
	}
	
	/**
	 * 删除文件
	 */
	public function fileDelete($filename){
		$filename = urldecode($filename);
		$filename = preg_replace("/\.+/", '.', $filename);  //屏蔽非法路径
		$filename = $this->fileBathPath . $filename;
		if(\Think\Storage::has($filename)){
			if(\Think\Storage::unlink($filename)){
				$this->success('操作成功');
			}
		}
		$this->error('操作失败');
	}

	/**
	 * 邮件模板管理
	 */
	public function email($page=1, $rows=10, $search = array(), $sort = 'id', $order = 'desc'){
		if(IS_POST){
			$email_db = M('email');

			//搜索
			$where = array();
			foreach ($search as $k=>$v){
				if(!$v) continue;
				switch ($k){
					case 'code':
					case 'subject':
						$where[] = "`{$k}` like '%{$v}%'";
						break;
					case 'begin':
						if(!preg_match("/^\d{4}(-\d{2}){2}$/", $v)){
							unset($search[$k]);
							continue;
						}
						if($search['end'] && $search['end'] < $v) $v = $search['end'];
						$v = strtotime($v);
						$where[] = "`addtime` >= '{$v}'";
						break;
					case 'end':
						if(!preg_match("/^\d{4}(-\d{2}){2}$/", $v)){
							unset($search[$k]);
							continue;
						}
						if($search['begin'] && $search['begin'] > $v) $v = $search['begin'];
						$v = strtotime($v);
						$where[] = "`addtime` <= '{$v}'";
						break;
				}
			}
			$where = implode(' and ', $where);

			$limit=($page - 1) * $rows . "," . $rows;
			$total = $email_db->where($where)->count();
			$order = $sort.' '.$order;
			$list = $total ? $email_db->where($where)->order($order)->limit($limit)->select() : array();
			foreach($list as &$info){
				$info['addtime']    = $info['addtime'] ? date('Y-m-d H:i:s', $info['addtime']) : '-';
				$info['edittime'] = $info['edittime'] ? date('Y-m-d H:i:s', $info['edittime']) : '-';
			}
			$data = array('total'=>$total, 'rows'=>$list);
			$this->ajaxReturn($data);
		}else{
			$menu_db = D('Menu');
			$currentpos = $menu_db->currentPos(I('get.menuid'));  //栏目位置

			$datagrid = array(
				'options'     => array(
					'title'        => $currentpos,
					'url'          => U('System/email', array('grid'=>'datagrid')),
					'toolbar'      => '#system-email-datagrid-toolbar',
					'singleSelect' => false,
				),
				'fields' => array(
					'选中'     => array('field' => 'ck', 'checkbox' => true),
					'ID'       => array('field'=>'id','width'=>10,'sortable'=>true),
					'编号'     => array('field'=>'code','width'=>15,'sortable'=>true),
					'主题'     => array('field'=>'subject','width'=>30,'sortable'=>true),
					'添加时间' => array('field'=>'addtime','width'=>15,'sortable'=>true),
					'修改时间' => array('field'=>'edittime','width'=>15,'sortable'=>true),
					'管理操作' => array('field'=>'operate','width'=>20,'formatter'=>'systemEmailModule.operate'),
				)
			);
			$this->assign('datagrid', $datagrid);
			$this->assign('list', $list);
			$this->display('email_list');
		}
	}

	/**
	 * 邮件模板添加
	 */
	public function emailAdd(){
		if(IS_POST){
			$email_db = M('email');
			$data = I('post.info');
			$data['addtime'] = time();
			$res = $email_db->add($data);
			$res ? $this->success('操作成功') : $this->error('操作失败');
		}else{
			$this->display('email_add');
		}
	}

	/**
	 * 邮件模板编辑
	 */
	public function emailEdit($id){
		$email_db = M('email');
		if(IS_POST){
			$data = I('post.info');
			$data['edittime'] = time();
			$res = $email_db->where(array('id'=>$id))->save($data);
			$res ? $this->success('操作成功') : $this->error('操作失败');
		}else{
			$info = $email_db->where(array('id'=>$id))->find();
			$this->assign('info', $info);
			$this->display('email_edit');
		}
	}

	/**
	 * 邮件模板删除
	 */
	public function emailDelete() {
		if (IS_POST) {
			$email_db = M('email');
			$ids = I('post.ids', array());
			foreach ($ids as $id) {
				$email_db->where(array('id' => $id))->delete();
			}
			$this->success('操作成功');
		} else {
			$this->error('操作失败');
		}
	}

	/**
	 * 验证模版编号是否已存在
	 */
	public function public_emailCodeCheck($code){
		if(I('get.default') == $code) {
			exit('true');
		}

		$email_db = M('email');
		$exists = $email_db->where(array('code' => $code))->count();
		if ($exists) {
			exit('false');
		}else{
			exit('true');
		}
	}

}
