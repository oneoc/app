<?php
namespace Admin\Controller;
use Admin\Controller\CommonController;
use Think\Upload;
use Common\Plugin\ImageMagick;

/**
 * 上传相关模块
 * @author wangdong
 */
class UploadController extends CommonController {
	/**
	 * 文件导入，不支持多文件上传
	 */
	public function import(){
		if(!IS_POST) return false;
		$uniqid = uniqid();
		$config = array(
			'exts'       => array('data'),
			'autoSub'    => false,       //自动子目录保存文件
			'rootPath'   => UPLOAD_PATH,  //保存根路径
			'savePath'   => 'import/',    //保存路径
			'saveName'   => $uniqid,      //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
			'replace'    => true,        //存在同名是否覆盖
		);
		$res = $this->_upload($_FILES, $config);

		if($res['status']){
			$data = array('status'=>1, 'info'=>'上传成功', 'filename'=>$uniqid);
		}else{
			$data = array('status'=>0, 'info'=>$res['info']);
		}
		$this->ajaxReturn(json_encode($data), 'eval');
	}

	/**
	 * 上传所有类型的文件
	 */
	public function file(){
		if(!IS_POST) return false;
		$res = self::_upload($_FILES);
		exit(json_encode($res));
	}

	/**
	 * 附件上传
	 */
	public function link($CKEditorFuncNum = 0){
		if(!IS_POST) return false;
		$config = C('FILE_UPLOAD_LINK_CONFIG');
		$res = self::_upload($_FILES, $config);

		if($res['status']){
			$config = C('TMPL_PARSE_STRING');
			$res['info'] = $config[UPLOAD_PATH] . $res['info'];
			printf('<script type="text/javascript">window.parent.CKEDITOR.tools.callFunction(%d, "%s", "");</script>', $CKEditorFuncNum, $res['info']);
		}else{
			printf('<font color="red"size="2">*上传失败（%s）</font>', $res['info']);
		}
	}

	/**
	 * 图片上传
	 */
	public function image($CKEditorFuncNum = 0){
		if(!IS_POST) return false;
		$config = C('FILE_UPLOAD_IMG_CONFIG');
		$res = self::_upload($_FILES, $config);

		if($res['status']){
			$config = C('TMPL_PARSE_STRING');
			$res['info'] = $config[UPLOAD_PATH] . $res['info'];
			printf('<script type="text/javascript">window.parent.CKEDITOR.tools.callFunction(%d, "%s", "");</script>', $CKEditorFuncNum, $res['info']);
		}else{
			printf('<font color="red"size="2">*上传失败（%s）</font>', $res['info']);
		}
	}

	/**
	 * flash上传
	 */
	public function flash($CKEditorFuncNum = 0){
		if(!IS_POST) return false;
		$config = C('FILE_UPLOAD_FLASH_CONFIG');
		$res = self::_upload($_FILES, $config);

		if($res['status']){
			$config = C('TMPL_PARSE_STRING');
			$res['info'] = $config[UPLOAD_PATH] . $res['info'];
			printf('<script type="text/javascript">window.parent.CKEDITOR.tools.callFunction(%d, "%s", "");</script>', $CKEditorFuncNum, $res['info']);
		}else{
			printf('<font color="red"size="2">*上传失败（%s）</font>', $res['info']);
		}
	}
	

	/**
	 * 缩略图上传
	 * TODO 为了兼容不同平台，裁剪功能将在后续开发
	 */
	public function thumb(){
		if(!IS_POST) return false;
		$config = C('FILE_UPLOAD_IMG_CONFIG');
		$res = self::_upload($_FILES, $config);

		if($res['status']){
			$data = array('err'=>null, 'msg'=>array('url'=>$res['info'], 'localname'=>$res['result']['name']));
		}else{
			$data = array('err'=>$res['info'], 'msg'=>null);
		}

		$this->ajaxReturn(json_encode($data), 'eval');
	}

	/**
	 * 图片裁剪
	 */
	public function crop($subfix = ''){
		$imgUrl = $_POST['imgUrl'];
		$imgW   = ceil($_POST['imgW']);
		$imgH   = ceil($_POST['imgH']);
		$imgY1  = ceil($_POST['imgY1']);
		$imgX1  = ceil($_POST['imgX1']);
		$cropW  = ceil($_POST['cropW']);
		$cropH  = ceil($_POST['cropH']);

		$response = array(
			'status' => 'error',
			'url'    => 'Can`t write cropped File'
		);

		$config  = C('TMPL_PARSE_STRING');
		$imgPath = str_replace($config[UPLOAD_PATH], UPLOAD_PATH, $imgUrl);
		$image   = file_read($imgPath);
		$output  = file_subfix($imgPath, $subfix);

		if($image){
			$imagick = new ImageMagick();
			$imagick->read($image);
			$imagick->thumbnail($imgW, $imgH);
			$imagick->crop($imgX1, $imgY1, $cropW, $cropH);
			$res = file_write($output, $imagick->get_content());

			if($res){
				$response = array(
					'status' => 'success',
					'url'    => $config[UPLOAD_PATH] . file_path_parse($output)
				);
			}
		}
		echo json_encode($response);
	}

	/**
	 * 远程图片上传到本地
	 */
	public function remoteImage($url = ''){
		if (!IS_POST) return false;
		@set_time_limit(600);
		$reExt = '('.implode('|', C('FILE_UPLOAD_IMG_CONFIG.exts')) . ')';

		$config = C('TMPL_PARSE_STRING');

		//base64图片内容上传
		if(substr($url, 0, 10) == 'data:image'){
			if(!preg_match('/^data:image\/'.$reExt.'/i', $url, $sExt)){
				$this->error('不支持的格式');
			}
			$sExt = $sExt[1];
			$imgContent = base64_decode(substr($url,strpos($url,'base64,') + 7));

			//图片地址上传
		}else{
			preg_match("/\.{$reExt}$/i", $url, $sExt);
			if(!$sExt) continue;
			$sExt = $sExt[1];
			//先验证链接地址是否正确
			if(!file_exist_remote($url)){
				$this->error('无效的链接');
			}
			$imgContent = file_read_remote($url);
		}

		$filename = date('Y/m/d/') . uniqid() . '.' . strtolower($sExt);
		file_write(UPLOAD_PATH . $filename, $imgContent);

		$this->water($filename);
		$this->success($config[UPLOAD_PATH] . $filename);
	}

	/**
	 * 文件上传
	 * @param $files
	 * @param array $config
	 * @param string $field
	 * @return array
	 */
	private function _upload($files, $config = array(), $field = 'upload'){
		//上传配置
		if(!isset($config['own']) || !$config['own']){
			$config = array_merge(C('FILE_UPLOAD_CONFIG'), $config);
		}

		$upload = new Upload($config);
		$res = $upload->upload($files);
		if($res){
			$filename = $res[$field]['savepath'] . $res[$field]['savename'];

			//图片加水印
			if(strpos($res[$field]['type'], 'image') !== false ) $this->water($filename);

			return array('status'=>1, 'info'=>$filename, 'result'=>$res[$field]);
		}else{
			return array('status'=>0, 'info'=>$upload->getError(), 'result'=>null);
		}
	}

	/**
	 * 添加水印
	 * @param string $image
	 * @return bool
	 */
	private function water($image){
		if(!C('IMAGE_WATER_CONFIG.status')) return false;
		if(!C('IMAGE_WATER_CONFIG.image')) return false;

		$image  = UPLOAD_PATH . $image;
		$config = C('TMPL_PARSE_STRING');
		$water  = str_replace($config[UPLOAD_PATH], UPLOAD_PATH, C('IMAGE_WATER_CONFIG.image'));

		if(!file_exist($image)) return false;
		if(!file_exist($water)) return false;

		$imagick = new ImageMagick();
		$imagick->read(file_read($image));
		$imagick->add_watermark(file_read($water), 10, 10, false);
		return file_write($image, $imagick->get_content());

	}
}