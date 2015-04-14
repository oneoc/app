<?php
namespace Admin\Model;
use Think\Model;

class MenuModel extends Model{
	protected $tableName = 'menu';
	protected $pk        = 'id';
	public    $error;
	
	/**
	 * 按父ID查找菜单子项
	 * @param integer $parentid   父菜单ID  
	 * @param integer $with_self  是否包括他自己
	 */
	public function getMenu($parentid = 0, $with_self = 0) {
		$parentid = intval($parentid);
		$roleid = session('roleid');
		$result = $this->where(array('parentid'=>$parentid, 'display'=>1))->order('listorder ASC')->limit(1000)->select();
		if (!is_array($result)) $result=array();
		if($with_self) {
			$result2[0] = $this->where(array('id'=>$parentid))->find();
			$result = array_merge($result2,$result);
		}
		//权限检查
		if($roleid == 1) return $result;
		$admin_role_priv_db = M('admin_role_priv');
		$array = array();
		foreach($result as $v) {
			$action = $v['a'];
			if(preg_match('/^public_/',$action)) {
				$array[] = $v;
			} else {
				if(preg_match('/^ajax_(\w+)_/',$action,$_match)) $action = $_match[1];
				$r = $admin_role_priv_db->where(array('c'=>$v['c'],'a'=>$action,'roleid'=>$roleid))->find();
				if($r) $array[] = $v;
			}
		}
		return $array;
	}
	
	/**
	 * 当前位置
	 * @param $id 菜单id
	 */
	public function currentPos($id) {
		$r = $this->where(array('id'=>$id))->find(array('id','name','parentid'));
		$str = '';
		if($r['parentid']) {
			$str = $this->currentPos($r['parentid']);
		}
		return $str.$r['name'].' &gt; ';
	}
	
	/**
	 * 菜单列表
	 */
	public function getTree($parentid = 0) {
		$field = array('id','`name`','listorder','`id` as `operateid`');
		$order = '`listorder` ASC,`id` DESC';
		$data = $this->field($field)->where(array('parentid'=>$parentid))->order($order)->select();
		if (is_array($data)){
			foreach ($data as &$arr){
				$arr['children'] = $this->getTree($arr['id']);
			}
		}else{
			$data = array();
		}
		return $data;
	}
	
	/**
	 * 权限管理列表
	 */
	public function getRoleTree($parentid = 0, $roleid = 0){
		$field = array('id','`name` as `text`','c','a');
		$order = '`listorder` ASC,`id` DESC';
		//过滤我的面板
		$data = $this->field($field)->where("`parentid`='{$parentid}'")->order($order)->select();
		if (is_array($data)){
			$admin_role_priv_db = M('admin_role_priv');
			foreach ($data as $k=>&$arr){
				$arr['attributes']['parent'] = $this->getParentIds($arr['id']);
				$arr['children'] = $this->getRoleTree($arr['id'], $roleid);
				if(is_array($arr['children']) && !empty($arr['children']) ){
					$arr['state'] = 'closed';
				}else{
					//勾选默认菜单
					$r = $admin_role_priv_db->where(array('c'=>$arr['c'],'a'=>$arr['a'],'roleid'=>$roleid))->find();
					if($r) $arr['checked'] = true;
				}
			}
		}else{
			$data = array();
		}
		return $data;
	}
	
	/**
	 * 获取菜单父级id
	 */
	public function getParentIds($id, $result = null){
		$parentid = $this->where(array('id'=>$id))->getField('parentid');
		if($parentid){
			$result .= $result ? ','.$parentid : $parentid;
			$result = $this->getParentIds($parentid, $result);
		}
		return $result;
	}
	
	/**
	 * 检查菜单名称是否存在
	 */
	public function checkName($name){
		$name =  trim($name);
		if ($this->where(array('name'=>$name))->field('id')->find()){
			return true;
		}
		return false;
	}
	
	/**
	 * 菜单下拉列表
	 */
	public function getSelectTree($parentid = 0){
		$field = array('id','`name` as `text`');
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


	/**
	 * 检查上级菜单设置是否正确
	 */
	public function checkParentId($id, $parentid){
		if($id == $parentid) return false;  //上级菜单不能与本级菜单相同

		$data = $this->field(array('id'))->where(array('parentid'=>$id))->order('`listorder` ASC,`id` DESC')->select();
		if(is_array($data)){
			foreach ($data as &$arr){
				if($arr['id'] == $parentid) return false; //上级菜单不能与本级菜单子菜单

				return $this->checkParentId($arr['id'], $parentid);
			}
		}else{
			return true;
		}
		return true;
	}
	
	/**
	 * 清除菜单相关缓存
	 */
	public function clearCatche(){
		S('system_menulist', null);
		S('system_public_menuselecttree', null);
	}
}