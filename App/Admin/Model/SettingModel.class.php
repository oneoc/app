<?php
namespace Admin\Model;
use Think\Model;

class SettingModel extends Model{
	protected $tableName = 'setting';
	protected $pk        = 'key';
	
	//获取全部设置信息
	public function getSetting(){
		$result = array();
		$fields = dict('', 'Setting');			//获取当前配置选项列表
		$settingField = array_keys($fields);
		$where = "`key` in('".implode("','", $settingField)."')";
		$data = $this->where($where)->getField('key,value', true);	//从数据库中获取设置信息
		
		$result['total'] = count($fields);
		foreach ($fields as $key=>&$arr){
			switch ($key){
				case 'SAVE_LOG_OPEN': //后台日志过滤
				case 'LOGIN_ONLY_ONE': //单点登录
				case 'IMAGE_WATER_CONFIG.status': //图片水印开启
					$data[$key] = $data[$key] ? '开启' : (C($key) ? '开启' : '关闭');
					break;
					
				//上传类型过滤
				case 'FILE_UPLOAD_CONFIG.exts':
				case 'FILE_UPLOAD_LINK_CONFIG.exts':
				case 'FILE_UPLOAD_IMG_CONFIG.exts':
				case 'FILE_UPLOAD_FLASH_CONFIG.exts':
				case 'FILE_UPLOAD_MEDIA_CONFIG.exts':
					if(isset($data[$key]) && !is_array($data[$key])) $data[$key] = explode(',', $data[$key]);
					break;
			}
			
			//如果数据库不存在该设置项则从默认值中获取
			$arr['value'] = array_key_exists($key, $data) ? $data[$key] : $arr['default'];
			$arr['key'] = $key;
		}
		$result['rows'] = array_values($fields);
		
		return $result;
	}
	
	//保存设置
	public function dosave($datas = array()){
		$fields = dict('', 'Setting');			//获取当前配置选项列表
		$settingField = array_keys($fields);
		$this->where("`key` not in('".implode("','", $settingField)."')")->delete();	//删除多余属性
		$where = "`key` in('".implode("','", $settingField)."')";
		$list = $this->where($where)->getField('key', true);	//从数据库中获取设置信息
		if(!is_array($list)) $list = array();
		$result = false;
		foreach ($datas as $data){
			switch ($data['key']){
				case 'SAVE_LOG_OPEN': //后台日志过滤
				case 'LOGIN_ONLY_ONE': //单点登录
				case 'IMAGE_WATER_CONFIG.status': //图片水印开启
					$data['value'] = $data['value'] == '开启' ? '1' : '0';
					break;
			}
			
			if(in_array($data['key'], $list)){
				$state = $this->where(array('key'=>$data['key']))->save($data);
			}else {
				$state = $this->add($data);
			}
			if($state) $result = true;
		}
		$this->clearCatche();	//有修改时清空缓存
		return $result;
	}
	
	//清除设置相关缓存
	public function clearCatche(){
		S('system_setting', null);
		S('common_setting_behavior', null);
	}
}