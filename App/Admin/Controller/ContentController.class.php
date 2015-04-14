<?php
namespace Admin\Controller;
use Admin\Controller\CommonController;

/**
 * 内容管理相关模块
 * @author wangdong
 * 
 * TODO
 * 带前缀的ACTION为控制权限用的，默认为view_
 * 后缀带_iframe的ACTION是在iframe中加载的，用于统一返回格式
 */
class ContentController extends CommonController {
	/**
	 * 对应权限如下：
	 * view 查看 | add 添加 | edit 编辑 | delete 删除 | order 排序
	 */
	public function _initialize(){
		parent::_initialize();
		
		//权限判断
		if(session('roleid') != 1 && ACTION_NAME != 'index' && strpos(ACTION_NAME, 'public_')===false) {
			
			$category_priv_db = M('category_priv');
			$tmp = explode('_', ACTION_NAME);
			$action = strtolower($tmp[0]);
			unset($tmp);
			if(!in_array($action, array('view', 'add', 'edit', 'delete', 'order', 'export', 'import'))) $action = 'view';
			
			$catid  = I('get.catid', 0, 'intVal');
			$roleid = session('roleid');
			
			$info = $category_priv_db->where(array('catid'=>$catid, 'roleid'=> $roleid, 'is_admin'=>1, 'action'=>$action))->count();
			if(!$info){
				//兼容iframe加载
				if(IS_GET && strpos(ACTION_NAME,'_iframe') !== false){
					exit('<style type="text/css">body{margin:0;padding:0}</style><div style="padding:6px;font-size:12px">您没有权限操作该项</div>');
				}
				//普通返回
				if(IS_AJAX && IS_GET){
					exit('<div style="padding:6px">您没有权限操作该项</div>');
				}else {
					$this->error('您没有权限操作该项');
				}
			}
		}
	}
	
	/**
	 * 内容管理首页
	 */
	public function index(){
		$menu_db = D('Menu');
		$currentpos = $menu_db->currentPos(I('get.menuid'));  //栏目位置
		$this->assign(currentpos, $currentpos);
		$this->display('index');
	}
	
	/**
	 * 介绍
	 */
	public function public_welcome(){
		$this->display('welcome');
	}
	
	/**
	 * 左侧栏目
	 */
	public function public_category(){
		if(IS_POST){
			if(S('content_public_category')){
				$data = S('content_public_category');
			}else{
				$category_db = D('Category');
				$data = $category_db->getCatTree();
				S('content_public_category', $data);
			}
			$this->ajaxReturn($data);
		}else{
			$this->display('category');
		}
	}
	
	/**
	 * 文本编辑器
	 */
	public function editor_iframe($callback = ''){
		//转成js可以直接识别的代码
		if($callback) $callback = 'window.parent.'. $callback .'();';
		
		$this->assign('callback', $callback);
		$this->display('editor');
	}

	/**
	 * 页面
	 */
	public function page($catid){
		$page_db = M('page');
		if(IS_POST){
			if(I('get.dosubmit')){
				$data = I('post.info');
				$data['updatetime'] = time();
				$data['status'] = (isset($data['status']) && $data['status'] == '发布') ? '1' : '0';

				if($page_db->where(array('catid'=>$catid))->find()){
					$res = $page_db->where(array('catid'=>$catid))->save($data);
				}else{
					$data['catid'] = $catid;
					$data['uuid']  = uuid();
					$res = $page_db->add($data);
				}
				$res ? $this->success('操作成功') : $this->error('操作失败');
			}else{
				$data = array();
				$info = $page_db->where(array('catid'=>$catid))->find();
				
				$fieldList = dict('field', 'Category'); //获取当前配置选项列表
				foreach ($fieldList['page'] as $key=>$fieldInfo){
					$fieldInfo['name']  = isset($fieldInfo['required']) && $fieldInfo['required'] ? "*{$fieldInfo['name']}" : $fieldInfo['name'];
					$fieldInfo['value'] = isset($info[$key]) ? $info[$key] : (isset($fieldInfo['default']) ? $fieldInfo['default'] : '');
					$fieldInfo['key']   = $key;
					
					switch($key){
						case 'status':
							$fieldInfo['value'] = $fieldInfo['value'] ? '发布' : '不发布';
							break;
					}
					
					array_push($data, $fieldInfo);
				}
				$this->ajaxReturn($data);
			}
		}else{
			$info = $page_db->field(array('content'))->where(array('catid'=>$catid))->find();
			$info['content'] = htmlspecialchars_decode($info['content']);
			$this->assign('info', $info);
			$this->assign('catid', $catid);
			$this->display('page');
		}
	}
	
	/**
	 * 文章列表
	 */
	public function article($catid, $page=1, $rows=10, $search = array(), $sort = 'id', $order = 'desc'){
		if(IS_POST){
			$mapList = dict('map', 'Category'); //模型映射
			$model   = D('Category')->where(array('catid'=>$catid))->getField('model');
			$db      = M($mapList[$model]);
			
			//搜索
			$where = array("`catid` = {$catid}");
			foreach ($search as $k=>$v){
				if(!$v) continue;
				switch ($k){
					case 'title':
					case 'author':
						$where[] = "`{$k}` like '%{$v}%'";
						break;
					case 'begin':
						if(!preg_match("/^\d{4}(-\d{2}){2}$/", $v)){
							unset($search[$k]);
							continue;
						}
						if($search['end'] && $search['end'] < $v) $v = $search['end'];
						$v = strtotime($v);
						$where[] = "`addtime` >= '{$v}'";
						break;
					case 'end':
						if(!preg_match("/^\d{4}(-\d{2}){2}$/", $v)){
							unset($search[$k]);
							continue;
						}
						if($search['begin'] && $search['begin'] > $v) $v = $search['begin'];
						$v = strtotime($v);
						$where[] = "`addtime` <= '{$v}'";
						break;
				}
			}
			$where = implode(' and ', $where);
			
			$limit=($page - 1) * $rows . "," . $rows;
			$total = $db->where($where)->count();
			$order = $sort.' '.$order;
			$field = array('id', 'title', 'author', 'updatetime', 'addtime', 'status');
			$list = $total ? $db->field($field)->where($where)->order($order)->limit($limit)->select() : array();
			if($list){
				foreach($list as &$item){
					$item['addtime']    = $item['addtime'] ? date('Y-m-d H:i:s', $item['addtime']) : '-';
					$item['updatetime'] = $item['updatetime'] ? date('Y-m-d H:i:s', $item['updatetime']) : '-';
				}
			}
			$data = array('total'=>$total, 'rows'=>$list);
			$this->ajaxReturn($data);
		}else{
			$datagrid = array(
				'options'     => array(
					'url'          => U('Content/article', array('grid'=>'datagrid', 'catid'=>$catid)),
					'toolbar'      => '#content-article-datagrid-toolbar',
					'singleSelect' => false,
				),
				'fields' => array(
					'选中'    => array('field'=>'ck', 'checkbox'=>true),
					'ID'      => array('field'=>'id','width'=>10,'sortable'=>true),
					'标题'     => array('field'=>'title','width'=>50,'sortable'=>true),
					'作者'   => array('field'=>'author','width'=>20,'sortable'=>true),
					'添加时间' => array('field'=>'addtime','width'=>20,'sortable'=>true),
					'更新时间' => array('field'=>'updatetime','width'=>20,'sortable'=>true),
					'状态'    => array('field'=>'status','width'=>10,'sortable'=>true,'formatter'=>'contentArticleModule.status'),
					'管理操作' => array('field'=>'operate','width'=>15,'formatter'=>'contentArticleModule.operate')
				)
			);
			$this->assign('datagrid', $datagrid);
			$this->assign('catid', $catid);
			$this->display('article_list');
		}
	}

	/**
	 * 添加文章
	 */
	public function add_article($catid){
		if(IS_POST){
			$model = D('Category')->where(array('catid'=>$catid))->getField('model');

			if(I('get.dosubmit')){
				$data = I('post.info', array(), 'trim');
				if(!$data['title'] || !$data['content']) $this->error('请填写必填字段');

				$data['catid']   = $catid;
				$data['uuid']    = uuid();
				$data['status']  = (isset($data['status']) && $data['status'] == '发布') ? '1' : '0';
				$data['addtime'] = preg_match("/^\d{4}(\-\d{2}){2}\ \d{2}(\:\d{2}){2}$/", $data['addtime']) ? strtotime($data['addtime']) : time();

				//转向链接判断
				if(isset($data['islink']) && $data['islink'] == '开启'){
					$data['islink'] = '1';
				}else{
					$data['islink'] = '0';
					unset($data['url']);
				}

				$mapList = dict('map', 'Category'); //模型映射
				$db      = M($mapList[$model]);
				$res     = $db->add($data);
				$res ? $this->success('操作成功') : $this->error('操作失败');
			}else{
				$data = array();

				$fieldList = dict('field', 'Category'); //获取当前配置选项列表
				foreach ($fieldList[$model] as $key=>$fieldInfo){
					$fieldInfo['name']  = isset($fieldInfo['required']) && $fieldInfo['required'] ? "*{$fieldInfo['name']}" : $fieldInfo['name'];
					$fieldInfo['value'] = isset($info[$key]) ? $info[$key] : (isset($fieldInfo['default']) ? $fieldInfo['default'] : '');
					$fieldInfo['key']   = $key;

					array_push($data, $fieldInfo);
				}
				$this->ajaxReturn($data);
			}
		}else{
			$this->assign('catid', $catid);
			$this->display('article_add');
		}
	}

	/**
	 * 编辑文章
	 */
	public function edit_article($catid, $id){
		$model   = D('Category')->where(array('catid'=>$catid))->getField('model');
		$mapList = dict('map', 'Category'); //模型映射
		$db      = M($mapList[$model]);
		if(IS_POST){
			if(I('get.dosubmit')){
				$data = I('post.info', array(), 'trim');
				if(!$data['title'] || !$data['content']) $this->error('请填写必填字段');

				unset($data['addtime']);
				$data['updatetime'] = time();
				$data['status']     = (isset($data['status']) && $data['status'] == '发布') ? '1' : '0';
				
				//转向链接判断
				if(isset($data['islink']) && $data['islink'] == '开启'){
					$data['islink'] = '1';
				}else{
					$data['islink'] = '0';
					unset($data['url']);
				}

				$res = $db->where(array('catid'=>$catid, 'id'=>$id))->save($data);
				$res ? $this->success('操作成功') : $this->error('操作失败');
				
			}else{
				$data = array();
				$info = $db->where(array('catid'=>$catid, 'id'=>$id))->find();

				$fieldList = dict('field', 'Category'); //获取当前配置选项列表
				foreach ($fieldList[$model] as $key=>$fieldInfo){
					$fieldInfo['name']  = isset($fieldInfo['required']) && $fieldInfo['required'] ? "*{$fieldInfo['name']}" : $fieldInfo['name'];
					$fieldInfo['value'] = isset($info[$key]) ? $info[$key] : (isset($fieldInfo['default']) ? $fieldInfo['default'] : '');
					$fieldInfo['key']   = $key;
					
					switch($key){
						case 'islink':
							$fieldInfo['value'] = $fieldInfo['value'] ? '开启' : '关闭';
							break;
							
						case 'status':
							$fieldInfo['value'] = $fieldInfo['value'] ? '发布' : '不发布';
							break;

						case 'addtime':
							$fieldInfo['value'] = date('Y-m-d H:i:s', $fieldInfo['value']);
							unset($fieldInfo['editor']);
							break;
					}

					array_push($data, $fieldInfo);
				}
				$this->ajaxReturn($data);
			}
		}else{
			$info = $db->field(array('id', 'content'))->where(array('catid'=>$catid, 'id'=>$id))->find();
			$info['content'] = htmlspecialchars_decode($info['content']);
			$this->assign('info', $info);
			$this->assign('catid', $catid);
			$this->display('article_edit');
		}
	}

	/**
	 * 删除文章
	 */
	public function delete_article($catid) {
		if (IS_POST) {
			$model   = D('Category')->where(array('catid'=>$catid))->getField('model');
			$mapList = dict('map', 'Category'); //模型映射
			$db      = M($mapList[$model]);

			$ids   = I('post.ids', array());
			foreach ($ids as $id) {
				$db->where(array('id' => $id))->delete();
			}
			$this->success('操作成功');
		} else {
			$this->error('操作失败');
		}
	}
}