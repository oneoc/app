<?php
namespace Common\Behavior;
use Think\Behavior;

class SettingBehavior extends Behavior{
	public function run(&$params){
		if(MODULE_NAME == 'Install'){
			return true;
		}else {
			//自动安装判断
			$lock_file = './Public/tmp/install.lock';
			if(!file_exist($lock_file)){
				redirect(U('Install/index/index'));
				exit;
			}
		}
		if(!S('common_setting_behavior')){
			$setting_db = M('setting');
			$list = $setting_db->getField('key,value', true);
			S('common_setting_behavior', $list);
		}else{
			$list = S('common_setting_behavior');
		}
		//使用自定义设置
		if(is_array($list) && !empty($list)){
			foreach ($list as $key=>$value){
				switch ($key){
					//上传类型过滤
					case 'FILE_UPLOAD_CONFIG.exts':
					case 'FILE_UPLOAD_LINK_CONFIG.exts':
					case 'FILE_UPLOAD_IMG_CONFIG.exts':
					case 'FILE_UPLOAD_FLASH_CONFIG.exts':
					case 'FILE_UPLOAD_MEDIA_CONFIG.exts':
						if(!is_array($value)) $value = explode(',', $value);
						break;
				}
				C($key, $value);
			}
		}
	}
}