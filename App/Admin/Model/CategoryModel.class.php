<?php
namespace Admin\Model;
use Think\Model;

class CategoryModel extends Model{
	protected $tableName = 'category';
	protected $pk        = 'catid';
	
	//获取栏目列表
	public function getTree($parentid = 0){
		$field = array('catid','`catname`','type','model','description','disabled','ismenu','listorder','`catid` as `operateid`');
		$order = '`listorder` ASC,`catid` DESC';
		$data = $this->field($field)->where(array('parentid'=>$parentid))->order($order)->select();
		if (is_array($data)){
			foreach ($data as &$arr){
				$arr = array_merge($arr, array(
					'field_view'     => 'view',
					'field_add'      => 'add',
					'field_edit'     => 'edit',
					'field_delete'   => 'delete',
				));
				$arr['children'] = $this->getTree($arr['catid']);
			}
		}else{
			$data = array();
		}
		return $data;
	}
	
	//栏目下拉列表
	public function getSelectTree($parentid = 0){
		$field = array('`catid` as `id`','`catname` as `text`');
		$order = '`listorder` ASC,`id` DESC';
		$data = $this->field($field)->where(array('parentid'=>$parentid))->order($order)->select();
		if (is_array($data)){
			foreach ($data as &$arr){
				$arr['children'] = $this->getSelectTree($arr['id']);
			}
		}else{
			$data = array();
		}
		return $data;
	}
	
	//内容管理左侧导航
	public function getCatTree($parentid = 0){
		$field = array('catid as id','`catname` as `text`','type', 'model');
		$order = '`listorder` ASC,`id` DESC';
		$data = $this->field($field)->where(array('parentid'=>$parentid, 'disabled'=>0, 'type'=>array('NEQ',2)))->order($order)->select();
		if (is_array($data)){
			foreach ($data as $k=>&$arr){
				$arr['crumbs'] = $this->currentPos($arr['id']);
				$arr['children'] = $this->getCatTree($arr['id']);
				
				//设置自定义图标
				if(!is_array($arr['children']) || empty($arr['children']) ){
					switch($arr['type']){
						case 0:
							$arr['iconCls'] = 'icons-folder-folder_page_white';
							break;
						case 1:
							switch($arr['model']){
								case 'article':
									$arr['iconCls'] = 'icons-folder-folder_feed';
									break;
								case 'news':
									$arr['iconCls'] = 'icons-folder-folder_star';
									break;
								case 'picture':
									$arr['iconCls'] = 'icons-folder-folder_picture';
									break;
								case 'image':
									$arr['iconCls'] = 'icons-folder-folder_image';
								case 'bug':
									$arr['iconCls'] = 'icons-folder-folder_bug';
									break;
								case 'heart':
									$arr['iconCls'] = 'icons-folder-folder_heart';
									break;
								default:
									$arr['iconCls'] = 'icons-folder-folder_table';
							}
							break;
					}
				}else{
					$arr['iconCls'] = 'icons-folder-folder';
				}
			}
		}else{
			$data = array();
		}
		return $data;
	}
	
	/**
	 * 检查上级菜单设置是否正确
	 */
	public function checkParentId($id, $parentid){
		if($id == $parentid) return false;  //上级菜单不能与本级菜单相同
	
		$data = $this->field(array('catid'))->where(array('parentid'=>$id))->order('`listorder` ASC,`catid` DESC')->select();
		if(is_array($data)){
			foreach ($data as &$arr){
				if($arr['catid'] == $parentid) return false; //上级菜单不能与本级菜单子菜单
	
				return $this->checkParentId($arr['catid'], $parentid);
			}
		}else{
			return true;
		}
		return true;
	}

	/**
	 * 当前位置
	 * @param $id 菜单id
	 */
	public function currentPos($id) {
		$r = $this->where(array('catid'=>$id))->find(array('catidid','catname','parentid'));
		$str = '';
		if($r['parentid']) {
			$str = $this->currentPos($r['parentid']);
		}
		return $str.$r['catname'].' &gt; ';
	}

	/**
	 * 清除栏目相关缓存
	 */
	public function clearCatche(){
		S('category_categorylist', null);
		S('category_public_categoryselect', null);
		S('content_public_category', null);
	}
}