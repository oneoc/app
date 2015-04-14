<?php
namespace Common\Plugin;
// FTP写入存储类
class Ftp{
	private $error  = ''; //错误信息
	private $link;        //FTP连接
	private $config = array(
		'host'     => '', //服务器
		'port'     => 21, //端口
		'timeout'  => 90, //超时时间
		'username' => '', //用户名
		'password' => '', //密码
	);

	/**
	 * 架构函数
	 * @access public
	 */
	public function __construct($config = null) {
		// FTP配置
		if(C('UPLOAD_TYPE_CONFIG')) $this->config = array_merge($this->config, C('UPLOAD_TYPE_CONFIG'));
		if(is_array($config)) $this->config = array_merge($this->config, $config);

		if(!$this->login()) E($this->error);  //登录FTP服务器
	}

	/**
	 * 文件内容读取
	 * @access public
	 * @param string $filename  文件名
	 * @return string     
	 */
	public function read($filename){
		return $this->get($filename, 'content');
	}

	/**
	 * 文件写入
	 * @access public
	 * @param string $filename  文件名
	 * @param string $content  文件内容
	 * @return boolean         
	 */
	public function put($filename, $content){
		$srcFile = tempnam(sys_get_temp_dir(), 'FTP_STOR_UPLOAD');
		file_put_contents($srcFile, $content);

		$this->mkdir(dirname($filename));
		if (!ftp_put($this->link, basename($filename), $srcFile, FTP_BINARY)) {
			unlink($srcFile);
			$this->error = '文件写入保存错误！';
			return false;
		}
		unlink($srcFile);
		return true;
	}

	/**
	 * 文件追加写入
	 * @access public
	 * @param string $filename  文件名
	 * @param string $content  追加的文件内容
	 * @return boolean        
	 */
	public function append($filename, $content){
		if($old_content = $this->read($filename)){
			$content =  $old_content.$content;
		}
		return $this->put($filename, $content);
	}

	/**
	 * 加载文件
	 * @access public
	 * @param string $filename  文件名
	 * @param array $vars  传入变量
	 * @return void
	 */
	public function load($filename,$vars=null){
		if(!is_null($vars)) extract($vars, EXTR_OVERWRITE);
		eval('?>'.$this->read($filename));
	}

	/**
	 * 文件是否存在
	 * @access public
	 * @param string $filename  文件名
	 * @return boolean     
	 */
	public function has($filename){
		if($this->read($filename)){
			return true;
		}else{
			return false;
		}
	}

	/**
	 * 文件删除
	 * @access public
	 * @param string $filename  文件名
	 * @return boolean     
	 */
	public function unlink($filename){
		if (!ftp_delete($this->link, $filename)){
			$this->error = '文件删除错误！';
			return false;
		}
		return true;
	}

	/**
	 * 读取文件信息
	 * @access public
	 * @param string $filename  文件名
	 * @param string $name  信息名 mtime或者content
	 * @return boolean
	 */
	public function get($filename, $name = null){
		$srcFile = tempnam(sys_get_temp_dir(), 'FTP_STOR_DOWNLOAD');
		if (!ftp_get($this->link, $srcFile, $filename, FTP_BINARY)) {
			$this->error = '读取文件保存错误！';
			return false;
		}
		$info  =  array(
			'mtime'   =>  filemtime($srcFile),
			'content' =>  file_get_contents($srcFile)
		);
		unlink($srcFile);
		return $name ? $info[$name] : $info;
	}

	/**
	 * 保存文件
	 * @access public
	 * @param string $local     本地文件名
	 * @param string $filename  文件名
	 * @return boolean
	 */
	public function save($local, $filename){
		$this->mkdir(dirname($filename));

		if (!ftp_put($this->link, $local, $filename, FTP_BINARY)) {
			$this->error = '文件上传错误！';
			return false;
		}
		return true;
	}

	/**
	 * 创建目录
	 * @param  string $dir 要创建的穆里
	 * @return boolean
	 */
	public function mkdir($dir){
		if(ftp_chdir($this->link, $dir)) return true;

		if(ftp_mkdir($this->link, $dir)){
			return true;
		} elseif($this->mkdir(dirname($dir)) && ftp_mkdir($this->link, $dir)) {
			return true;
		} else {
			$this->error = "目录 {$dir} 创建失败！";
			return false;
		}
	}

	public function ls($path = ''){
		$list = ftp_nlist($this->link, $path);
		$result = array();
		foreach($list as $filename){
			$size = ftp_size($this->link, $filename);
			$time = ftp_mdtm($this->link, $filename);
			array_push($result, array(
				'type'  => ($size != -1 ? 'file' : 'dir'),
				'name'  => basename($filename),
				'path'  => $filename,
				'size'  => ($size != -1 ? format_bytes($size, ' ') : '-'),
				'mtime' =>($time != -1 ? date('Y-m-d H:i:s', $time) : '-'),
			));
		}
		return $result;
	}

	/**
	 * 登录到FTP服务器
	 * @return boolean true-登录成功，false-登录失败
	 */
	private function login(){
		extract($this->config);
		$this->link = ftp_connect($host, $port, $timeout);
		if($this->link) {
			if (ftp_login($this->link, $username, $password)) {
				return true;
			} else {
				$this->error = "无法登录到FTP服务器：username - {$username}";
			}
		} else {
			$this->error = "无法连接到FTP服务器：{$host}";
		}
		return false;
	}

	/**
	 * 析构方法，用于断开当前FTP连接
	 */
	public function __destruct() {
		ftp_close($this->link);
	}
}
