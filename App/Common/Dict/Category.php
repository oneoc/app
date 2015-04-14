<?php
/**
 * 内容管理中的编辑页面表单元素
 */
return array(
	/* 类型 */
	'type'  => array( 0 => '页面', 1 => '列表'),

	/* 模型 */
	'model' => array(
		'article'  => '文章',
		'news'     => '新闻',
	),

	/* 映射 TODO 模型名称对应表名 */
	'map' => array(
		'article'  => 'article',
		'news'     => 'article',
	),

	/* 字段 TODO 键值与模型对应 */
	'field' => array(
		/* 页面  */
		'page' => array(
			'title'       => array(
				'name'      => '标题',
				'group'     => '基本属性',
				'editor'    => array('type'=>'text','options'=>array('tipPosition'=>'left', 'validType'=>array('length'=>array(3,8)), 'required' => true )),
				'required' => true,
			),
			'keywords'    => array(
				'name'      => '关键字',
				'group'     => '基本属性',
				'editor'    => array('type'=>'validatebox','options'=>array('tipPosition'=>'left', 'validType'=>array('length'=>array(0,255)) )),
			),
			'description' => array(
				'name'      => '描述',
				'group'     => '基本属性',
				'editor'    => array('type'=>'textarea','options'=>array('tipPosition'=>'left', 'validType'=>array('length'=>array(5,255)) )),
			),
			'status'      => array(
				'name'     => '状态',
				'group'    => '发布设置',
				'editor'   => array('type'=>'checkbox','options'=>array('on'=>'发布','off'=>'不发布')),
				'default'  => '发布',
			),
		),

		/* 文章 */
		'article' => array(
			'title'       => array(
				'name'      => '标题',
				'group'     => '基本属性',
				'editor'    => array('type'=>'text','options'=>array('tipPosition'=>'left', 'validType'=>array('length'=>array(3,8)), 'required' => true )),
				'required' => true,
			),
			'keywords'    => array(
				'name'      => '关键字',
				'group'     => '基本属性',
				'editor'    => array('type'=>'validatebox','options'=>array('tipPosition'=>'left', 'validType'=>array('length'=>array(0,255)) )),
			),
			'description' => array(
				'name'      => '描述',
				'group'     => '基本属性',
				'editor'    => array('type'=>'textarea','options'=>array('tipPosition'=>'left', 'validType'=>array('length'=>array(5,255)) )),
			),
			'thumb'       => array(
				'name'      => '缩略图',
				'group'     => '基本属性',
				'editor'    => array('type'=>'image','options'=>array( 'handler'=>'contentArticleModule.thumb', 'width'=>240, 'height'=>180, 'subfix'=>'_4x3')),
			),
			'author'    => array(
				'name'      => '作者',
				'group'     => '基本属性',
				'editor'    => array('type'=>'validatebox','options'=>array('tipPosition'=>'left', 'validType'=>array('length'=>array(0,50)) )),
				'default'   => cookie('username'),
			),
			'islink'        => array(
				'name'      => '状态',
				'group'     => '转向链接',
				'editor'    => array('type'=>'checkbox','options'=>array('on'=>'开启','off'=>'关闭')),
				'default'   => '关闭',
			),
			'url'         => array(
				'name'      => '链接',
				'group'     => '转向链接',
				'editor'    => array('type'=>'validatebox','options'=>array('tipPosition'=>'left', 'validType'=>array('url','length[0,255]'))),
			),
			'addtime'      => array(
				'name'      => '添加时间',
				'group'     => '发布设置',
				'editor'    => array('type'=>'datetimebox','options'=>array('tipPosition'=>'left', 'required'=>true)),
				'default'   => date('Y-m-d H:i:s'),
				'required'  => true,
			),
			'status'      => array(
				'name'      => '状态',
				'group'     => '发布设置',
				'editor'    => array('type'=>'checkbox','options'=>array('on'=>'发布','off'=>'不发布')),
				'default'   => '发布',
			),
		),

		/* 新闻 */
		'news' => array(
			'title'       => array(
				'name'      => '标题',
				'group'     => '基本属性',
				'editor'    => array('type'=>'text','options'=>array('tipPosition'=>'left', 'validType'=>array('length'=>array(3,8)), 'required' => true )),
				'required' => true,
			),
			'keywords'    => array(
				'name'      => '关键字',
				'group'     => '基本属性',
				'editor'    => array('type'=>'validatebox','options'=>array('tipPosition'=>'left', 'validType'=>array('length'=>array(0,255)) )),
			),
			'description' => array(
				'name'      => '描述',
				'group'     => '基本属性',
				'editor'    => array('type'=>'textarea','options'=>array('tipPosition'=>'left', 'validType'=>array('length'=>array(5,255)) )),
			),
			'thumb'       => array(
				'name'      => '缩略图',
				'group'     => '基本属性',
				'editor'    => array('type'=>'image','options'=>array( 'handler'=>'contentArticleModule.thumb', 'width'=>240, 'height'=>240, 'subfix'=>'_1x1')),
				'required' => true,
			),
			'author'    => array(
				'name'      => '作者',
				'group'     => '基本属性',
				'editor'    => array('type'=>'validatebox','options'=>array('tipPosition'=>'left', 'validType'=>array('length'=>array(0,50)) )),
				'default'   => cookie('username'),
			),
			'islink'        => array(
				'name'      => '状态',
				'group'     => '转向链接',
				'editor'    => array('type'=>'checkbox','options'=>array('on'=>'开启','off'=>'关闭')),
				'default'   => '关闭',
			),
			'url'         => array(
				'name'      => '链接',
				'group'     => '转向链接',
				'editor'    => array('type'=>'validatebox','options'=>array('tipPosition'=>'left', 'validType'=>array('url','length[0,255]'))),
			),
			'addtime'      => array(
				'name'      => '添加时间',
				'group'     => '发布设置',
				'editor'    => array('type'=>'datetimebox','options'=>array('tipPosition'=>'left', 'required'=>true)),
				'default'   => date('Y-m-d H:i:s'),
				'required'  => true,
			),
			'status'      => array(
				'name'      => '状态',
				'group'     => '发布设置',
				'editor'    => array('type'=>'checkbox','options'=>array('on'=>'发布','off'=>'不发布')),
				'default'   =>  '发布',
			),
		),
	)
);