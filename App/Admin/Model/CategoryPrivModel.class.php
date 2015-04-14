<?php
namespace Admin\Model;
use Think\Model;

class CategoryPrivModel extends Model{
	protected $tableName = 'category_priv';
	protected $pk        = '';
	
	/**
	 * 获取栏目权限列表
	 * @param int $id          角色id
	 * @param array $data      默认不需要传值
	 * @param array $privList  默认不需要传值
	 * @return array
	 */
	public function getTreeGrid($id, $data = 0, $privList = 0){
		//当前选中权限列表
		if($privList === 0){
			$privList = array();
			$list = $this->where(array('roleid'=>$id))->select();
			foreach ($list as $info){
				$privList[$info['catid']][] = $info['action'];
			}
			unset($list);
		}
		
		//权限字段列表
		if($data === 0){
			if(S('category_categorylist')){
				$data = S('category_categorylist');
			}else{
				$category_db = D('Category');
				$data = $category_db->getTree();
				S('category_categorylist', $data);
			}
		}
		
		//默认选中赋值
		foreach ($data as &$info){
			if (is_array($info)){
				if(is_array($privList[$info['catid']]) && !empty($privList[$info['catid']])){
					foreach($privList[$info['catid']] as $action){
						$info['checked_' . $action] = true;
					}
				}
				if(!$info['children']) continue;
				$info['children'] = $this->getTreeGrid($id, $info['children'], $privList);
			}
		}
		
		return $data;
	}
}