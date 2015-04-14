<?php
namespace Admin\Model;
use Think\Model;

class AdminModel extends Model{
	protected $tableName = 'admin';
	protected $pk             = 'userid';
	public      $error;
	
	/**
	 * 登录验证
	 */
	public function login($username, $password){
		$times_db = M('times');

		//查询帐号
		$r = $this->where(array('username'=>$username))->find();
		if(!$r){
			$this->error = '管理员不存在';
			return false;
		}
		
		//密码错误剩余重试次数
		$rtime = $times_db->where(array('username'=>$username, 'isadmin'=>'1'))->find();
		if($rtime['times'] >= C('MAX_LOGIN_TIMES')) {
			$minute = C('LOGIN_WAIT_TIME') - floor((time()-$rtime['logintime'])/60);
			if ($minute > 0) {
				$this->error = "密码重试次数太多，请过{$minute}分钟后重新登录！";
				return false;
			}else {
				$times_db->where(array('username'=>$username))->delete();
			}
		}

		$password = md5(md5($password).$r['encrypt']);
		$ip             = get_client_ip(0, true);

		if($r['password'] != $password) {
			if($rtime && $rtime['times'] < C('MAX_LOGIN_TIMES')) {
				$times = C('MAX_LOGIN_TIMES') - intval($rtime['times']);
				$times_db->where(array('username'=>$username))->save(array('ip'=>$ip,'isadmin'=>1));
				$times_db->where(array('username'=>$username))->setInc('times');
			} else {
				$times_db->where(array('username'=>$username,'isadmin'=>1))->delete();
				$times_db->add(array('username'=>$username,'ip'=>$ip,'isadmin'=>1,'logintime'=>time(),'times'=>1));
				$times = C('MAX_LOGIN_TIMES');
			}
			$this->error = "密码错误，您还有{$times}次尝试机会！";
			return false;
		}
		
		$times_db->where(array('username'=>$username))->delete();
		$this->where(array('userid'=>$r['userid']))->save(array('lastloginip'=>$ip,'lastlogintime'=>time()));
		
		//登录日志
		$admin_log_db = M('admin_log');
		$admin_log_db->add(array(
			'userid'             => $r['userid'],
			'username'       => $username,
			'httpuseragent' => $_SERVER['HTTP_USER_AGENT'],
			'ip'                   => $ip,
			'time'               => date('Y-m-d H:i:s'),
			'type'               => 'login',
			'sessionid'        => session_id(),
		));
		
		session('userid', $r['userid']);
		session('roleid', $r['roleid']);
		cookie('username', $username);
		cookie('userid', $r['userid']);
		S('SESSION_ID_' . $r['userid'] , session_id());  //单点登录用
		return true;
	}
	
	/**
	 * 获取用户信息
	 */
	public function getUserInfo($userid){
		$admin_role_db = D('AdminRole');
		$info = $this->field('password, encrypt', true)->where(array('userid'=>$userid))->find();
		if($info) $info['rolename'] = $admin_role_db->getRoleName($info['roleid']);    //获取角色名称
		return $info;
	}
	
	/**
	 * 修改密码
	 */
	public function editPassword($userid, $password){
		$userid = intval($userid);
		if($userid < 1) return false;
		$passwordinfo = password($password);
		return $this->where(array('userid'=>$userid))->save($passwordinfo);
	}
}