<?php
namespace Admin\Controller;
use Think\Controller;

/**
 * 公共控制器
 * @author wangdong
 * 
 * TODO
 * 后缀带_iframe的ACTION是在iframe中加载的，用于统一返回格式
 */
class CommonController extends Controller {
	function _initialize(){
		if(IS_AJAX && IS_GET) C('DEFAULT_AJAX_RETURN', 'html');
		self::check_admin();
		self::check_priv();
		self::manage_log();
		
		//记录上次每页显示数
		if(I('get.grid') && I('post.rows')) cookie('pagesize', I('post.rows', C('DATAGRID_PAGE_SIZE'), 'intVal'));
	}
	
	/**
	 * 判断用户是否已经登陆
	 */
	final public function check_admin() {
		if(CONTROLLER_NAME =='Index' && in_array(ACTION_NAME, array('login', 'code')) ) {
			return true;
		}
		if(!session('userid') || !session('roleid')){
			//针对iframe加载返回
			if(IS_GET && strpos(ACTION_NAME,'_iframe') !== false){
				exit('<style type="text/css">body{margin:0;padding:0}a{color:#08c;text-decoration:none}a:hover,a:focus{color:#005580;text-decoration:underline}a:focus,a:hover,a:active{outline:0}</style><div style="padding:6px;font-size:12px">请先<a target="_parent" href="'.U('Index/login').'">登录</a>后台管理</div>');
			}
			if(IS_AJAX && IS_GET){
				exit('<div style="padding:6px">请先<a href="'.U('Index/login').'">登录</a>后台管理</div>');
			}else {
				$this->error('请先登录后台管理', U('Index/login'));
			}
		}
	}
	
	/**
	 * 权限判断
	 */
	final public function check_priv() {
		if(session('roleid') == 1) return true;
		//过滤不需要权限控制的页面
		switch (CONTROLLER_NAME){
			case 'Index':
				switch (ACTION_NAME){
					case 'index':
					case 'login':
					case 'code':
					case 'logout':
						return true;
						break;
				}
				break;
			case 'Upload':
				return true;
				break;
			case 'Content':
				if (ACTION_NAME != 'index') return true;
				break;
		}
		if(strpos(ACTION_NAME, 'public_')!==false) return true;
				
		$priv_db = M('admin_role_priv');
		$r = $priv_db->where(array('c'=>CONTROLLER_NAME, 'a'=>ACTION_NAME, 'roleid'=>session('roleid')))->find();
		if(!$r){
			//兼容iframe加载
			if(IS_GET && strpos(ACTION_NAME,'_iframe') !== false){
				exit('<style type="text/css">body{margin:0;padding:0}</style><div style="padding:6px;font-size:12px">您没有权限操作该项</div>');
			}
			if(IS_AJAX && IS_GET){
				exit('<div style="padding:6px">您没有权限操作该项</div>');
			}else {
				$this->error('您没有权限操作该项');
			}
		}
	}

	/**
	 * 记录日志 
	 */
	final private function manage_log(){
		//判断是否记录
 		if(C('SAVE_LOG_OPEN')){
 			$action = ACTION_NAME;
 			if($action == '' || strchr($action,'public') || (CONTROLLER_NAME =='Index' && in_array($action, array('login','code'))) ||  CONTROLLER_NAME =='Upload') {
				return false;
			}else {
				$ip        = get_client_ip(0, true);
				$username  = cookie('username');
				$userid    = session('userid');
				$time      = date('Y-m-d H-i-s');
				$data      = array('GET'=>$_GET);
				if(IS_POST) $data['POST'] = $_POST;
				$data_json = json_encode($data);

				$log_db    = M('log');
				$log_db->add(array(
					'username'    => $username,
					'userid'      => $userid,
					'controller'  => CONTROLLER_NAME,
					'action'      => ACTION_NAME,
					'querystring' => $data_json,
					'time'        => $time,
					'ip'          => $ip
				));
			}
	  	}
	}

	/**
	 * 空操作，用于输出404页面
	 */
	public function _empty(){
		//针对后台ajax请求特殊处理
		if(!IS_AJAX) send_http_status(404);
		if (IS_AJAX && IS_POST){
			$data = array('info'=>'请求地址不存在或已经删除', 'status'=>0, 'total'=>0, 'rows'=>array());
			$this->ajaxReturn($data);
		}else{
			$this->display('Common:404');
		}
	}
	
}
