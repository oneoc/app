<?php
namespace Home\Controller;
use Home\Controller\CommonController;
class IndexController extends CommonController {
	public function index(){
		$this->redirect('Admin/Index/index');
	}
}