<?php
return array(
	/* 全站设置  */
	'SITE_TITLE' => array(
		'name'    => '站点标题',
		'group'   => '前台设置',
		'editor'  => array('type'=>'validatebox','options'=>array('tipPosition'=>'left', 'validType'=>array('nothtml','length[0,255]'))),
		'default' => '',
	),
	'SITE_KEYWORDS' => array(
		'name'    => '关键字',
		'group'   => '前台设置',
		'editor'  => array('type'=>'validatebox','options'=>array('tipPosition'=>'left', 'validType'=>array('nothtml','length[0,255]'))),
		'default' => '',
	),
	'SITE_DESCRIPTION' => array(
		'name'    => '描述',
		'group'   => '前台设置',
		'editor'  => array('type'=>'validatebox','options'=>array('tipPosition'=>'left', 'validType'=>array('nothtml','length[0,255]'))),
		'default' => '',
	),
	'SITE_ICP' => array(
		'name'    => '备案号',
		'group'   => '前台设置',
		'editor'  => array('type'=>'validatebox','options'=>array('tipPosition'=>'left', 'validType'=>array('nothtml','length[0,255]'))),
		'default' => ''
	),
	'SITE_COPYRIGHT' => array(
		'name'    => '版权信息',
		'group'   => '前台设置',
		'editor'  => array('type'=>'validatebox','options'=>array('tipPosition'=>'left', 'validType'=>array('nothtml','length[0,255]'))),
		'default' => '&copy;2013-2015',
	),
	
	/* 后台设置  */
	'SAVE_LOG_OPEN' => array(
		'name'    => '开启后台日志记录',
		'group'   => '后台设置',
		'editor'  => array('type'=>'checkbox','options'=>array('on'=>'开启','off'=>'关闭')),
		'default' => C('SAVE_LOG_OPEN') ? '开启' : '关闭',
	),
	'LOGIN_ONLY_ONE' => array(
		'name'    => '开启单点登录',
		'group'   => '后台设置',
		'editor'  => array('type'=>'checkbox','options'=>array('on'=>'开启','off'=>'关闭')),
		'default' => C('LOGIN_ONLY_ONE'),
	),
	'MAX_LOGIN_TIMES' => array(
		'name'    => '登录失败后允许最大次数',
		'group'   => '后台设置',
		'editor'  => 'numberbox',
		'default' => C('MAX_LOGIN_TIMES'),
	),
	'LOGIN_WAIT_TIME' => array(
		'name'    => '错误等待时间(分钟)',
		'group'   => '后台设置',
		'editor'  => 'numberbox',
		'default' => C('LOGIN_WAIT_TIME'),
	),
	'DATAGRID_PAGE_SIZE' => array(
		'name'    => '列表默认分页数',
		'group'   => '后台设置',
		'editor'  => 'numberbox',
		'default' => C('DATAGRID_PAGE_SIZE'),
	),
	
	/* 上传设置  */
	'FILE_UPLOAD_CONFIG.exts' => array(
		'name'    => '允许上传扩展(全局)',
		'group'   => '上传设置',
		'editor'  => 'text',
		'default' => C('FILE_UPLOAD_CONFIG.exts'),
	),
	'FILE_UPLOAD_CONFIG.maxSize' => array(
		'name'    => '允许上传大小(全局)',
		'group'   => '上传设置',
		'editor'  => 'numberbox',
		'default' => C('FILE_UPLOAD_CONFIG.maxSize'),
	),
	'FILE_UPLOAD_LINK_CONFIG.exts' => array(
		'name'    => '允许上传扩展(附件)',
		'group'   => '上传设置',
		'editor'  => array('type'=>'validatebox','options'=>array('tipPosition'=>'left', 'validType'=>array('exts','length[0,255]'))),
		'default' => C('FILE_UPLOAD_LINK_CONFIG.exts'),
	),
	'FILE_UPLOAD_IMG_CONFIG.exts' => array(
		'name'    => '允许上传扩展(图片)',
		'group'   => '上传设置',
		'editor'  => array('type'=>'validatebox','options'=>array('tipPosition'=>'left', 'validType'=>array('exts','length[0,255]'))),
		'default' => C('FILE_UPLOAD_IMG_CONFIG.exts'),
	),
	'FILE_UPLOAD_FLASH_CONFIG.exts' => array(
		'name'    => '允许上传扩展(动画)',
		'group'   => '上传设置',
		'editor'  => array('type'=>'validatebox','options'=>array('tipPosition'=>'left', 'validType'=>array('exts','length[0,255]'))),
		'default' => C('FILE_UPLOAD_FLASH_CONFIG.exts'),
	),
	'FILE_UPLOAD_MEDIA_CONFIG.exts' => array(
		'name'    => '允许上传扩展(媒体)',
		'group'   => '上传设置',
		'editor'  => array('type'=>'validatebox','options'=>array('tipPosition'=>'left', 'validType'=>array('exts','length[0,255]'))),
		'default' => C('FILE_UPLOAD_MEDIA_CONFIG.exts'),
	),

	/* 水印设置 */
	'IMAGE_WATER_CONFIG.status' => array(
		'name'    => '开启水印',
		'group'   => '水印设置',
		'editor'  => array('type'=>'checkbox','options'=>array('on'=>'开启','off'=>'关闭')),
		'default' => '关闭',
	),
	'IMAGE_WATER_CONFIG.image' => array(
		'name'    => '水印图片',
		'group'   => '水印设置',
		'editor'  => array('type'=>'image','options'=>array( 'handler'=>'systemSettingModule.image', 'zoom'=>false)),
		'default' => '',
	),
	'IMAGE_WATER_CONFIG.position' => array(
		'name'    => '水印位置(1-9九宫格)',
		'group'   => '水印设置',
		'editor'  => array('type'=>'numberbox','options'=>array('tipPosition'=>'left', 'min'=>1, 'max'=>9, 'precision'=>0)),
		'default' => C('IMAGE_WATER_CONFIG.position'),
	),
	'IMAGE_WATER_CONFIG.minWidth' => array(
		'name'    => '水印最小宽度',
		'group'   => '水印设置',
		'editor'  => 'numberbox',
		'default' => C('IMAGE_WATER_CONFIG.minWidth'),
	),
	'IMAGE_WATER_CONFIG.minHeight' => array(
		'name'    => '水印最小高度',
		'group'   => '水印设置',
		'editor'  => 'numberbox',
		'default' => C('IMAGE_WATER_CONFIG.minHeight'),
	),
	
	/* 邮箱设置  */
	'EMAIL_SMTP' => array(
		'name'    => 'SMTP',
		'group'   => '邮箱设置',
		'editor'  => array('type'=>'validatebox','options'=>array('tipPosition'=>'left', 'validType'=>array('nothtml','length[0,255]'))),
		'default' => '',
	),
	'EMAIL_PORT' => array(
		'name'    => '端口',
		'group'   => '邮箱设置',
		'editor'  => 'numberbox',
		'default' => 25,
	),
	'EMAIL_USER' => array(
		'name'    => '用户名',
		'group'   => '邮箱设置',
		'editor'  => array('type'=>'validatebox','options'=>array('tipPosition'=>'left', 'validType'=>array('nothtml','length[0,255]'))),
		'default' => '',
	),
	'EMAIL_PWD' => array(
		'name'    => '密码',
		'group'   => '邮箱设置',
		'editor'  => array('type'=>'validatebox','options'=>array('tipPosition'=>'left', 'validType'=>array('nothtml','length[0,255]'))),
		'default' => '',
	),
	'EMAIL_FROM' => array(
		'name'    => '发件人',
		'group'   => '邮箱设置',
		'editor'  => array('type'=>'validatebox','options'=>array('tipPosition'=>'left', 'validType'=>array('email','length[0,255]'))),
		'default' => '',
	),
);