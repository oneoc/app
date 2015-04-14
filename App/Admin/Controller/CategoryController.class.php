<?php
namespace Admin\Controller;
use Admin\Controller\CommonController;

/**
 * 栏目相关模块
 * @author wangdong
 */
class CategoryController extends CommonController {
	/**
	 * 栏目管理
	 */
	public function categoryList(){
		if(IS_POST){
			if(S('category_categorylist')){
			$data = S('category_categorylist');
			}else{
				$category_db = D('Category');
				$data = $category_db->getTree();
				S('category_categorylist', $data);
			}
			$this->ajaxReturn($data);
		}else{
			$menu_db = D('Menu');
			$currentpos = $menu_db->currentPos(I('get.menuid'));  //栏目位置
			$treegrid = array(
				'options' => array(
					'title'     => $currentpos,
					'url'       => U('Category/categoryList', array('grid'=>'treegrid')),
					'idField'   => 'catid',
					'treeField' => 'catname',
					'toolbar'   => 'categoryCategoryModule.toolbar',
				),
				'fields' => array(
					'排序'     => array('field'=>'listorder','width'=>15,'align'=>'center','formatter'=>'categoryCategoryModule.sort'),
					'栏目ID'   => array('field'=>'catid','width'=>25,'align'=>'center'),
					'栏目名称' => array('field'=>'catname','width'=>130),
					'栏目类型' => array('field'=>'type','width'=>30,'formatter'=>'categoryCategoryModule.type'),
					'模型'     => array('field'=>'model','width'=>30,'formatter'=>'categoryCategoryModule.model'),
					'描述'     => array('field'=>'description','width'=>80),
					'前台显示' => array('field'=>'ismenu','width'=>20,'formatter'=>'categoryCategoryModule.show'),
					'状态'     => array('field'=>'disabled','width'=>20,'formatter'=>'categoryCategoryModule.state'),
					'管理操作' => array('field'=>'operateid','width'=>50,'align'=>'center','formatter'=>'categoryCategoryModule.operate'),
				)
			);
			$this->assign('treegrid', $treegrid);
			$this->display('category_list');
		}
	}
	
	/**
	 * 添加栏目
	 */
	public function categoryAdd(){
		if(IS_POST){
			$category_db = D('Category');
			$data = I('post.info');
			$data['ismenu'] = $data['ismenu'] ? '1' : '0';
			$id = $category_db->add($data);
			if($id){
				$category_db->clearCatche();
				$this->success('添加成功');
			}else {
				$this->error('添加失败');
			}
		}else{
			$this->assign('typeList', dict('type', 'Category'));
			$this->assign('modelList', dict('model', 'Category'));
			$this->display('category_add');
		}
	}
	
	/**
	 * 编辑栏目
	 */
	public function categoryEdit($id){
		$category_db = D('Category');
		if(IS_POST){
			$data = I('post.info');
			if(!$category_db->checkParentId($id, $data['parentid'])){
				$this->error('上级栏目设置失败');
			}
			
			$data['ismenu'] = $data['ismenu'] ? '1' : '0';
			$res = $category_db->where(array('catid'=>$id))->save($data);
			if($res){
				$category_db->clearCatche();
				$this->success('操作成功');
			}else {
				$this->error('操作失败');
			}
		}else{
			$info = $category_db->where(array('catid'=>$id))->find();
			$this->assign('info', $info);
			$this->assign('typeList', dict('type', 'Category'));
			$this->assign('modelList', dict('model', 'Category'));
			$this->display('category_edit');
		}
	}
	
	/**
	 * 删除栏目
	 */
	public function categoryDelete($id = 0){
		if($id && IS_POST){
			$category_db = D('Category');
			$result = $category_db->where(array('catid'=>$id))->delete();
			if($result){
				$category_db->clearCatche();
				$this->success('删除成功');
			}else {
				$this->error('删除失败');
			}
		}else{
			$this->error('删除失败');
		}
	}
	
	/**
	 * 栏目排序
	 */
	public function categoryOrder(){
		if(IS_POST) {
			$category_db = D('Category');
			foreach(I('post.order') as $id => $listorder) {
				$category_db->where(array('catid'=>$id))->save(array('listorder'=>$listorder));
			}
			$category_db->clearCatche();
			$this->success('操作成功');
		} else {
			$this->error('操作失败');
		}
	}
	
	/**
	 * 栏目导出
	 */
	public function categoryExport($filename = ''){
		if(IS_POST) {
			$category_db = D('Category');
			$data = array('type'=>'category');
			$data['data']   = $category_db->order('catid asc')->getField('catid,type,model,parentid,catname,description,setting,listorder,disabled,ismenu', true);
			$data['verify'] = md5(var_export($data['data'], true) . $data['type']);
				
			//数据进行多次加密，防止数据泄露
			$data = base64_encode(gzdeflate(json_encode($data)));
				
			$uniqid = uniqid();
			$filename = UPLOAD_PATH . 'export/' . $uniqid . '.data';
			if(file_write($filename, $data)){
				$this->success('导出成功', U('Category/categoryExport', array('filename'=>$uniqid)));
			}
			$this->error('导出失败，请重试！');
		}else{
			//过滤特殊字符，防止非法下载文件
			$filename = str_replace(array('.', '/', '\\'), '', $filename);
			$filename = UPLOAD_PATH . 'export/' . $filename . '.data';
			if(!file_exist($filename)) $this->error('非法访问');
				
			header('Content-type: application/octet-stream');
			header('Content-Disposition: attachment; filename="栏目管理.data"');
			echo file_read($filename);
				
			file_delete($filename);
		}
	}
	
	/**
	 * 栏目导入
	 */
	public function categoryImport($filename = ''){
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
			if(!is_array($data) || !isset($data['type']) || $data['type'] != 'category' || !isset($data['verify']) || !isset($data['data'])){
				file_delete($filename);
				$this->error('非法数据');
			}
				
			if($data['verify'] != md5(var_export($data['data'], true) . $data['type'])){
				file_delete($filename);
				$this->error('非法数据');
			}
				
			$category_db = D('Category');
				
			//先清空数据再导入
			$category_db->where('catid > 0')->delete();
			$category_db->clearCatche();
				
			//开始导入
			asort($data['data']);
			foreach ($data['data'] as $add){
				$category_db->add($add);
			}
				
			file_delete($filename);
			$this->success('导入成功');
		}else{
			$this->error('非法访问');
		}
	}
	
	/**
	 * 栏目下拉框
	 */
	public function public_categorySelect(){
		if(S('category_public_categoryselect')){
			$data = S('category_public_categoryselect');
		}else {
			$category_db = D('Category');
			$data = $category_db->getSelectTree();
			$data = array(0=>array('id'=>0,'text'=>'作为一级栏目','children'=>$data));
			S('category_public_categoryselect', $data);
		}
		$this->ajaxReturn($data);
	}
}