<?php
namespace Admin\Controller;
use Admin\Controller\CommonController;

/**
 * 前台会员模块
 * @author wangdong
 */
class MemberController extends CommonController {
	/**
	 * 会员管理
	 */
	public function memberList($page = 1, $rows = 10, $sort = 'memberid', $order = 'asc'){
		if(IS_POST){
			$member_db      = M('member');
			$member_type_db = M('member_type');
			$typelist       = $member_type_db->where(array('disabled'=>'0'))->order('listorder asc')->getField('typeid,typename', true);

			$total = $member_db->count();
			$order = $sort.' '.$order;
			$limit = ($page - 1) * $rows . "," . $rows;
			$list = $total ? $member_db->field('memberid,username,nick,gender,status,lastloginip,lastlogintime,regtime,typeid')->order($order)->limit($limit)->select() : array();
			foreach($list as &$info){
				$info['typename']      = isset($typelist[$info['typeid']]) ? $typelist[$info['typeid']] : '-';
				$info['lastlogintime'] = $info['lastlogintime'] ? date('Y-m-d H:i:s', $info['lastlogintime']) : '-';
				$info['regtime']       = $info['regtime'] ? date('Y-m-d H:i:s', $info['regtime']) : '-';
			}
			$data = array('total'=>$total, 'rows'=>$list);
			$this->ajaxReturn($data);
		}else{
			$menu_db = D('Menu');
			$currentpos = $menu_db->currentPos(I('get.menuid'));  //栏目位置
			$datagrid = array(
				'options'     => array(
					'title'   => $currentpos,
					'url'     => U('Member/memberList', array('grid'=>'datagrid')),
					'toolbar' => 'memberMemberModule.toolbar',
				),
				'fields' => array(
					'会员名'      => array('field'=>'username','width'=>15,'sortable'=>true),
					'昵称'        => array('field'=>'nick','width'=>15,'sortable'=>true),
					'性别'        => array('field'=>'gender','width'=>5,'sortable'=>true,'formatter'=>'memberMemberModule.gender'),
					'会员类型'    => array('field'=>'typename','width'=>15,'sortable'=>false),
					'最后登录IP'  => array('field'=>'lastloginip','width'=>15,'sortable'=>true),
					'最后登录时间' => array('field'=>'lastlogintime','width'=>15,'sortable'=>true,'formatter'=>'memberMemberModule.time'),
					'状态'        => array('field'=>'status','width'=>10,'sortable'=>true,'formatter'=>'memberMemberModule.status'),
					'注册时间'    => array('field'=>'regtime','width'=>15,'sortable'=>true,'formatter'=>'memberMemberModule.time'),
					'管理操作'    => array('field'=>'memberid','width'=>30,'formatter'=>'memberMemberModule.operate'),
				)
			);
			$this->assign('datagrid', $datagrid);
			$this->display('member_list');
		}
	}
	
	/**
	 * 添加会员
	 */
	public function memberAdd(){
		if(IS_POST){
			$member_db = M('member');
			$data = I('post.info');
			if($member_db->where(array('username'=>$data['username']))->field('username')->find()){
				$this->error('会员名称已经存在');
			}
			$passwordinfo = password($data['password']);
			$data['password'] = $passwordinfo['password'];
			$data['encrypt']  = $passwordinfo['encrypt'];
			$data['regtime']  = time();

			$id = $member_db->add($data);
			if($id){
				$this->success('添加成功');
			}else {
				$this->error('添加失败');
			}
		}else{
			$member_type_db = M('member_type');
			$typelist = $member_type_db->where(array('disabled'=>'0'))->getField('typeid,typename', true);
			$this->assign('typelist', $typelist);
			$this->display('member_add');
		}
	}
	
	/**
	 * 编辑会员
	 */
	public function memberEdit($id){
		$member_db = M('member');
		if(IS_POST){
			$data = I('post.info');
			if(isset($data['password'])) unset($data['password']);
			$result = $member_db->where(array('memberid'=>$id))->save($data);
			if($result){
				$this->success('修改成功');
			}else {
				$this->error('修改失败');
			}
		}else{
			$member_type_db = M('member_type');
			$info = $member_db->field('password, encrypt', true)->where(array('memberid'=>$id))->find();
			$typelist = $member_type_db->where(array('disabled'=>'0'))->getField('typeid,typename', true);
			$this->assign('info', $info);
			$this->assign('typelist', $typelist);
			$this->display('member_edit');
		}
	}
	
	/**
	 * 删除会员
	 */
	public function memberDelete($id){
		$member_oauth_db = M('member_oauth');
		$member_oauth_db->where(array('memberid'=>$id))->delete();

		$member_db = M('member');
		$result = $member_db->where(array('memberid'=>$id))->delete();

		if ($result){
			$this->success('删除成功');
		}else {
			$this->error('删除失败');
		}
	}
	
	/**
	 * 重置密码
	 */
	public function memberResetPassword($id){
		$member_db = M('member');
		$password = rand(100000, 999999);
		$info = password($password);
		$data = array(
			'password' => $info['password'],
			'encrypt'  => $info['encrypt']
		);
		$result = $member_db->where(array('memberid'=>$id))->save($data);
		
		if ($result){
			$this->ajaxReturn(array('status'=>1, 'info'=>'重置成功', 'password'=>$password));
		}else {
			$this->error('重置失败');
		}
	}
	
	/**
	 * 查看会员
	 */
	public function memberView($id){
		if(IS_POST){
			$data = array();
			
			//基本信息
			$member_db       = M('member');
			$field = array(
				'username'      => '用户名',
				'nick'          => '昵称',
				'gender'        => '性别',
				'status'        => '状态',
				'regtime'       => '注册时间',
				'lastloginip'   => '上次登录IP',
				'lastlogintime' => '上次登录时间',
				'remark'        => '备注',
				'typeid'        => '会员类型',
			);
			$info = $member_db->field('memberid,password,encrypt', true)->where(array('memberid'=>$id))->find();
			foreach ($info as $key=>$value){
				switch ($key){
					case 'typeid':
						$member_type_db = M('member_type');
						$typeInfo = $member_type_db->where(array('typeid'=>$value))->getField('typeid,typename', true);
						$value = $typeInfo ? $typeInfo[$value] : '-';
						break;
						
					case 'regtime':
					case 'lastlogintime':
						$value = $value ? date('Y-m-d H:i:s', $value) : '-';
						break;
						
					case 'lastloginip':
						$value = $value ? $value : '-';
						break;
						
					case 'gender':
						$dict = array(0=>'女', 1=>'男', 2=>'保密');
						$value = isset($dict[$value]) ? $dict[$value] : '-';
						break;
						
					case 'status':
						$dict = array(0=>'<font color="red">未验证</font>', 1=>'已验证');
						$value = isset($dict[$value]) ? $dict[$value] : '-';
						break;
				}
					
				$data[] = array(
					'name'    => $field[$key],
					'group'   => '基本信息',
					'value'   => $value,
				);
			}
			
			//授权信息
			$member_oauth_db = M('member_oauth');
			$oauthField = array(
				'nick'    => '昵称',
				'head'    => '头像',
				'gender'  => '性别',
				'type'    => '来源',
				'addtime' => '首次授权时间'
			);
			$list = $member_oauth_db->field(array('nick', 'head', 'gender', 'type', 'addtime'))->where(array('memberid'=>$id))->select();
			foreach ($list as $info){
				foreach ($info as $key=>$value){
					switch ($key){
						case 'head':
							$value = '<img src="'. $value .'" height="50" />';
							break;
							
						case 'addtime':
							$value = date('Y-m-d H:i:s', $value);
							break;
					}
					
					$data[] = array(
						'name'    => $oauthField[$key],
						'group'   => strtoupper($info['type']) . '授权信息',
						'value'   => $value,
					);
				}
			}
			
			$this->ajaxReturn($data);
		}else {
			$propertygrid = array(
				'options'     => array(
					'url'     => U('Member/memberView', array('id'=>$id, 'grid'=>'propertygrid')),
				)
			);
			$this->assign('propertygrid', $propertygrid);
			$this->display('member_view');
		}
	}

	/**
	 * 会员类型
	 */
	public function typeList($page = 1, $rows = 10, $sort = 'listorder', $order = 'asc'){
		if(IS_POST){
			$member_type_db = M('member_type');
			$total = $member_type_db->count();
			$order = $sort.' '.$order;
			$limit = ($page - 1) * $rows . "," . $rows;
			$list = $member_type_db->field('*,typeid as id')->order($order)->limit($limit)->select();
			if(!$list) $list = array();
			$data = array('total'=>$total, 'rows'=>$list);
			$this->ajaxReturn($data);
		}else{
			$menu_db = D('Menu');
			$currentpos = $menu_db->currentPos(I('get.menuid'));  //栏目位置
			$datagrid = array(
				'options'     => array(
					'title'   => $currentpos,
					'url'     => U('Member/typeList', array('grid'=>'datagrid')),
					'toolbar' => 'memberTypeModule.toolbar',
				),
				'fields' => array(
					'排序'     => array('field'=>'listorder','width'=>5,'align'=>'center','formatter'=>'memberTypeModule.sort'),
					'ID'       => array('field'=>'typeid','width'=>5,'align'=>'center','sortable'=>true),
					'分类名称'  => array('field'=>'typename','width'=>15,'sortable'=>true),
					'分类描述'  => array('field'=>'description','width'=>25),
					'状态'     => array('field'=>'disabled','width'=>15,'sortable'=>true,'formatter'=>'memberTypeModule.state'),
					'管理操作'  => array('field'=>'id','width'=>15,'formatter'=>'memberTypeModule.operate'),
				)
			);
			$this->assign('datagrid', $datagrid);
			$this->display('type_list');
		}
	}

	/**
	 * 添加分类
	 */
	public function typeAdd(){
		if(IS_POST){
			$member_type_db = M('member_type');
			$data = I('post.info');
			if($member_type_db->where(array('typename'=>$data['typename']))->field('typename')->find()){
				$this->error('分类名称已存在');
			}
			$id = $member_type_db->add($data);
			if($id){
				$this->success('添加成功');
			}else {
				$this->error('添加失败');
			}
		}else{
			$this->display('type_add');
		}
	}

	/**
	 * 编辑分类
	 */
	public function typeEdit($id){
		if($id == '1') $this->error('该分类不能被修改');
		$member_type_db = M('member_type');
		if(IS_POST){
			$data = I('post.info');
			$id = $member_type_db->where(array('typeid'=>$id))->save($data);
			if($id){
				$this->success('修改成功');
			}else {
				$this->error('修改失败');
			}
		}else{
			$info = $member_type_db->where(array('typeid'=>$id))->find();
			$this->assign('info', $info);
			$this->display('type_edit');
		}
	}

	/**
	 * 删除分类
	 */
	public function typeDelete($id) {
		if($id == '1') $this->error('该分类不能被删除');
		$member_type_db = M('member_type');
		$result = $member_type_db->where(array('typeid'=>$id))->delete();
		if ($result){
			$this->success('删除成功');
		}else {
			$this->error('删除失败');
		}
	}

	/**
	 * 分类排序
	 */
	public function typeOrder(){
		if(IS_POST) {
			$member_type_db = M('member_type');
			foreach(I('post.order') as $typeid=>$listorder) {
				$member_type_db->where(array('typeid'=>$typeid))->save(array('listorder'=>$listorder));
			}
			$this->success('操作成功');
		} else {
			$this->error('操作失败');
		}
	}

	/**
	 * 验证会员名
	 */
	public function public_checkName($name){
		if (I('get.default') == $name) {
			exit('true');
		}
		$member_db = M('member');
		$exists = $member_db->where(array('username'=>$name))->field('username')->find();
		if ($exists) {
			exit('false');
		}else{
			exit('true');
		}
	}
	
	/**
	 * 验证昵称
	 */
	public function public_checkNick($nick){
		if (I('get.default') == $nick) {
			exit('true');
		}
		$member_db = M('member');
		$exists = $member_db->where(array('nick'=>$nick))->field('nick')->find();
		if ($exists) {
			exit('false');
		}else{
			exit('true');
		}
	}

	/**
	 * 验证分类名称是否存在
	 */
	public function public_checkTypeName($typename){
		if (I('get.default') == $typename) {
			exit('true');
		}
		$member_type_db = M('member_type');
		$exists = $member_type_db->where(array('typename'=>$typename))->field('typename')->find();
		if ($exists) {
			exit('false');
		}else{
			exit('true');
		}
	}

}