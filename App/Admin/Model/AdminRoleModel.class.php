<?php
namespace Admin\Model;
use Think\Model;

class AdminRoleModel extends Model{
	protected $tableName = 'admin_role';
	protected $pk        = 'roleid';
	public    $error;
	
	/**
	 * 获取角色中文名称
	 * @param int $roleid 角色ID
	 */
	public function getRoleName($roleid) {
		$roleid = intval($roleid);
		$rolename = $this->where(array('roleid'=>$roleid))->getField('rolename');
		return $rolename;
	}
}