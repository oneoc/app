<?php
/**
 * 获取数据字典
 * @param string $key      //键值，方便查找数据
 * @param string $fileName //字典文件名 目录Common/Dict/
 * @return mixed
 */
function dict($key = '', $fileName = 'Setting') {
	static $_dictFileCache  =   array();
	$file = APP_PATH . 'Common' . DS . 'Dict' . DS . $fileName . '.php';
	if (!file_exists($file)){
		unset($_dictFileCache);
		return null;
	}
	if(!$key && !empty($_dictFileCache)) return $_dictFileCache;
	if ($key && isset($_dictFileCache[$key])) return $_dictFileCache[$key];
	$data = require_once $file;
	$_dictFileCache = $data;
	return $key ? $data[$key] : $data;
}

/**
 * 字符串截取，支持中文和其他编码
 * @param string $str     需要转换的字符串
 * @param int    $start   开始位置
 * @param string $length  截取长度
 * @param string $charset 编码格式
 * @param bool   $suffix  截断显示字符
 * @return string
 */
function msubstr($str, $start=0, $length, $charset="utf-8", $suffix=false) {
	return Org\Util\String::msubstr($str, $start, $length, $charset, $suffix);
}

/**
 * 检测输入的验证码是否正确
 * @param string $code 为用户输入的验证码字符串
 * @param string $id   其他参数
 * @return bool
 */
function check_verify($code, $id = ''){
	$verify = new \Think\Verify();
	return $verify->check($code, $id);
}

/**
 * 对用户的密码进行加密
 * @param string $password
 * @param string $encrypt //传入加密串，在修改密码时做认证
 * @return array/string
 */
function password($password, $encrypt='') {
	$pwd = array();
	$pwd['encrypt'] =  $encrypt ? $encrypt : Org\Util\String::randString(6);
	$pwd['password'] = md5(md5(trim($password)).$pwd['encrypt']);
	return $encrypt ? $pwd['password'] : $pwd;
}

/**
 * 解析多行sql语句转换成数组
 * @param string $sql
 * @return array
 */
function sql_split($sql) {
	$sql = str_replace("\r", "\n", $sql);
	$ret = array();
	$num = 0;
	$queriesarray = explode(";\n", trim($sql));
	unset($sql);
	foreach($queriesarray as $query) {
		$ret[$num] = '';
		$queries = explode("\n", trim($query));
		$queries = array_filter($queries);
		foreach($queries as $query) {
			$str1 = substr($query, 0, 1);
			if($str1 != '#' && $str1 != '-') $ret[$num] .= $query;
		}
		$num++;
	}
	return($ret);
}

/**
 * 格式化字节大小
 * @param  number $size      字节数
 * @param  string $delimiter 数字和单位分隔符
 * @return string            格式化后的带单位的大小
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function format_bytes($size, $delimiter = '') {
	$units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
	for ($i = 0; $size >= 1024 && $i < 5; $i++) $size /= 1024;
	return round($size, 2) . $delimiter . $units[$i];
}

/**
 * 取得文件扩展
 * @param string $filename 文件名
 * @return string
 */
function file_ext($filename) {
	return strtolower(trim(substr(strrchr($filename, '.'), 1, 10)));
}

/**
 * 文件是否存在
 * @param string $filename  文件名
 * @param string $type     其他参数
 * @return boolean  
 */
function file_exist($filename ,$type=''){
	switch (strtoupper(C('FILE_UPLOAD_TYPE'))){
		case 'SAE':
			$arr = explode('/', ltrim($filename, './'));
			$domain = array_shift($arr);
			$filePath = implode('/', $arr);
			$s = new SaeStorage();
			return $s->fileExists($domain, $filePath);
			break;

		case 'FTP':
			$storage = new \Common\Plugin\Ftp();
			return $storage->has($filename);
			break;

		default:
			return \Think\Storage::has($filename ,$type);
	}
}

/**
 * 文件内容读取
 * @param string $filename  文件名
 * @param string $type     其他参数
 * @return bool
 */
function file_read($filename, $type=''){
	switch (strtoupper(C('FILE_UPLOAD_TYPE'))){
		case 'SAE':
			$arr = explode('/', ltrim($filename, './'));
			$domain = array_shift($arr);
			$filePath = implode('/', $arr);
			$s=new SaeStorage();
			return $s->read($domain, $filePath);
			break;

		case 'FTP':
			$storage = new \Common\Plugin\Ftp();
			return $storage->read($filename);
			break;

		default:
			return \Think\Storage::read($filename, $type);
	}
}

/**
 * 文件写入
 * @param string $filename  文件名
 * @param string $content  文件内容
 * @param string $type     其他参数
 * @return bool
 */
function file_write($filename, $content, $type=''){
	switch (strtoupper(C('FILE_UPLOAD_TYPE'))){
		case 'SAE':
			$s=new SaeStorage();
			$arr = explode('/',ltrim($filename,'./'));
			$domain = array_shift($arr);
			$save_path = implode('/',$arr);
			return $s->write($domain, $save_path, $content);
			break;

		case 'FTP':
			$storage = new \Common\Plugin\Ftp();
			return $storage->put($filename, $content);
			break;

		default:
			return \Think\Storage::put($filename, $content, $type);
	}
}

/**
 * 文件删除
 * @param string $filename 文件名
 * @param string $type     其他参数
 * @return bool
 */
function file_delete($filename ,$type=''){
	switch (strtoupper(C('FILE_UPLOAD_TYPE'))){
		case 'SAE':
			$arr = explode('/', ltrim($filename, './'));
			$domain = array_shift($arr);
			$filePath = implode('/', $arr);
			$s = new SaeStorage();
			return $s->delete($domain, $filePath);
			break;

		case 'FTP':
			$storage = new \Common\Plugin\Ftp();
			return $storage->unlink($filename);
			break;

		default:
			return \Think\Storage::unlink($filename ,$type);
	}
}

/**
 * 获取文件URL
 * @param string $filename  文件名
 * @return string
 */
function file_path_parse($filename){
	$config = C('TMPL_PARSE_STRING');
	return str_ireplace(UPLOAD_PATH, '', $filename);
}

/**
 * 验证远程链接地址是否正确
 * @param string $url
 * @return bool
 */
function file_exist_remote($url){
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_REFERER, $url); //伪造来路
	curl_setopt($curl,CURLOPT_USERAGENT, 'Alexa (IA Archiver)');
	curl_setopt($curl, CURLOPT_NOBODY, true);
	$result = curl_exec($curl);
	$found = false;
	if ($result !== false) {
		$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if ($statusCode == 200) $found = true;
	}
	curl_close($curl);
	return $found;
}

/**
 * 远程文件内容读取
 * @param string $url
 * @return string
 */
function file_read_remote($url){
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_REFERER, $url); //伪造来路
	curl_setopt($curl,CURLOPT_USERAGENT, 'Alexa (IA Archiver)');
	curl_setopt($curl,CURLOPT_HEADER,0);
	curl_setopt($curl, CURLOPT_NOBODY, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
	$result = curl_exec($curl);
	curl_close($curl);
	return $result;
}

/**
 * 文件名加后缀
 * @param string $string
 * @param string $subfix
 * @return string
 */
function file_subfix($string, $subfix = ''){
	return preg_replace("/(\.\w+)$/", "{$subfix}\\1", $string);
}

/**
 * 发送邮件
 * @param string $to      收件人
 * @param string $subject 主题
 * @param string $body    内容
 * @param array $config
 * @return bool
 */
function send_email($to, $subject, $body, $config = array()){
	$email = new \Common\Plugin\Email($config);
	$email->send($to, $subject, $body);
	return $email->result;
}

/**
 * 生成签名
 * @param array $param
 * @return string
 */
function sign($param = array()){
	return md5(base64_encode(hash_hmac('sha1', http_build_query($param), C('API_SIGN'), true)));
}

/**
 * xml转数组
 * @param string $xml
 * @param bool $isFile
 * @return null|array
 */
function xml2array($xml, $isFile = false){
	if($isFile && file_exist($xml)) $xml = file_read($xml);
	$xml = @simplexml_load_string($xml);

	if(is_object($xml)){
		$xml = json_encode($xml);
		$xml = @json_decode($xml, true);
	}
	if(!is_array($xml)) return null;

	return $xml;
}