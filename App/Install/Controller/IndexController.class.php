<?php
namespace Install\Controller;
use Think\Controller;

class IndexController extends Controller {
	/**
	 * 安装验证
	 */
	public function _initialize(){
		$this->lock_file = './Public/tmp/install.lock';
		if(!APP_DEBUG && file_exist($this->lock_file)){
		    $this->show('对您已经安装过，请不要重复安装！', 'utf-8');
		    exit;
		}
	}
	/**
	 * 后台首页
	 */
	public function index($step = 1){
		switch($step){
			 case 1:	//环境检测
				$this->display('step'.$step);
				break;
				
			 case 2:	//配置帐号
			 	$this->display('step'.$step);
			 	break;
			 	
			 case 3:	//安装详细过程
			 	unset($_POST['step']);
			 	$this->assign('data',json_encode($_POST));
			 	$this->display('step'.$step);
			 	break;
			 	
			 case 4:
			 	file_write($this->lock_file, time());
			 	$this->display('step'.$step);
			 	break;
		}
	}
	
	public function check($item = ''){
		switch ($item){
			case 'uname':
				//'操作系统'
				$this->success('当前【'.php_uname().'】 通过');
				break;
			case 'server':
				//'WEB 服务器'
				$this->success('当前【'.$_SERVER['SERVER_SOFTWARE'].'】 通过');
				break;
			case 'phpversion':
				phpversion() < '5.3.0' ? $this->error('当前php版本小于5.3，无法安装') : $this->success('通过');
				break;
				//'PHP 版本'
			case 'mysql':
				//'MYSQL 扩展'
				extension_loaded('mysql') ? $this->success('通过') : $this->error('未开启mysql扩展');
				break;
			case 'gd':
				//'GD 扩展'
				extension_loaded('gd') ? $this->success('通过') : $this->error('未开启GD扩展');
				break;
			case 'imagick':
				class_exists('Imagick') ? $this->success('通过') : $this->error('未开启Imagick扩展');
				break;
			case 'json':
				//'JSON 扩展'
				extension_loaded('json') ? $this->success('通过') : $this->error('未开启JSON扩展');
				break;
			case 'curl':
				//'CURL 扩展'
				function_exists('curl_init') ? $this->success('通过') : $this->error('未开启CURL扩展');
				break;
			case 'config':
				//'配置文件写入权限'
				$configFile = CONF_PATH.'config.php';
				is_writable($configFile) ? $this->success('通过') : $this->error($configFile.'文件不可写');
				break;
			case 'upload':
				//'上传目录写入权限'
				$uploadDir = SITE_DIR. DS .'Public' . DS . 'upload';
				is_writable($uploadDir) ? $this->success('通过') : $this->error($uploadDir.'目录不可写');
				break;
			case 'tmp':
				//'临时目录写入权限'
				$tmpDir = SITE_DIR. DS .'Public' . DS . 'tmp';
				is_writable($tmpDir) ? $this->success('通过') : $this->error($tmpDir.'目录不可写');
				break;
			default:
				$this->error('未知选项');
		}
	}
	
	public function dbTest($dbhost = '', $dbport = '3306', $dbname = '', $tablepre = '', $dbuser = '', $dbpw = ''){
		$dbhosts = explode(',', $dbhost);
		$dbhost = $dbhosts[0];
		@mysql_connect($dbhost.':'.$dbport, $dbuser, $dbpw) or die('2');
		
		$server_info = mysql_get_server_info();
		if($server_info < '4.0') exit('6');
		
		if(!mysql_select_db($dbname)) {
			if(!@mysql_query("CREATE DATABASE `{$dbname}` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci")) exit('3');
			mysql_select_db($dbname);
		}
		$query = mysql_query("SHOW TABLES FROM `{$dbname}`");
		while($r = mysql_fetch_row($query)) {
			if($r[0] == $tablepre.'admin') exit('0');
		}
		exit('1');
	}
	
	function install($dbhost = '', $dbport = '3306', $dbname = '', $tablepre = '', $dbuser = '', $dbpw = '', $username = '', $password = '', $email = ''){
		$dbhosts = explode(',', $dbhost);
		$dbhost = $dbhosts[0];
		$lnk = @mysql_connect($dbhost.':'.$dbport, $dbuser, $dbpw) or die('Not connected : ' . mysql_error());
		
		$version = mysql_get_server_info();
		if($version > '5.0') {
			mysql_query("SET sql_mode=''");
		}
												
		if(!@mysql_select_db($dbname)){
			@mysql_query("CREATE DATABASE `{$dbname}` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci");
			if(@mysql_error()) {
				exit('1');
			} else {
				mysql_select_db($dbname);
			}
		}
		mysql_query("SET names 'utf8'");
		$dbfile =  MODULE_PATH . 'Data'. DS . 'sql.sql';
		if(file_exists($dbfile)) {
			$sql = file_get_contents($dbfile);
			$sql = str_replace('CREATE DATABASE `app` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;', '', $sql);
			$sql = str_replace('USE `app`;', '', $sql);
			$sql = str_replace('app_', $tablepre, $sql);
			$this->_sql_execute($sql);
			//创建网站创始人
			$password_arr = password($password);
			$password = $password_arr['password'];
			$encrypt = $password_arr['encrypt'];
			$email = trim($email);
			$this->_sql_execute("update ".$tablepre."admin set `username`='{$username}',`password`='{$password}',`roleid`='1',`encrypt`='{$encrypt}',`email`='{$email}' where `userid`='1'");
			
			//同步配置文件
			if(IS_WRITE){
				$configFile = CONF_PATH.'config.php';
				if(is_writable($configFile)){
					$data =  file_get_contents($configFile);
					$data = preg_replace("/('DB_HOST'\s*=>\s*)'(.*)',/Us", "\\1'{$dbhost}',", $data);
					$data = preg_replace("/('DB_NAME'\s*=>\s*)'(.*)',/Us", "\\1'{$dbname}',", $data);
					$data = preg_replace("/('DB_USER'\s*=>\s*)'(.*)',/Us", "\\1'{$dbuser}',", $data);
					$data = preg_replace("/('DB_PWD'\s*=>\s*)'(.*)',/Us", "\\1'{$dbpw}',", $data);
					$data = preg_replace("/('DB_PORT'\s*=>\s*)'(.*)',/Us", "\\1'{$dbport}',", $data);
					$data = preg_replace("/('DB_PREFIX'\s*=>\s*)'(.*)',/Us", "\\1'{$tablepre}',", $data);
					file_put_contents($configFile, $data);
				}else{
					exit('3');
				}
			}
			exit('4');
		} else {
			exit('2');
		}
	}
	
	function _sql_execute($sql) {
		$sqls = sql_split($sql);
		if(is_array($sqls)){
			foreach($sqls as $sql){
				if(trim($sql) != ''){
					mysql_query($sql);
				}
			}
		}else{
			mysql_query($sqls);
		}
		return true;
	}
}