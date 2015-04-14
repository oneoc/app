/**
 * 公共方法
 * 依赖:
 *   1.jquery.json.js
 *   2.jquery.easyui.js
 *   3.jquery.croppic.js
 */
(function($){
	$.extend({ app: { method: {}, module: {} } });

	/* 消息提示 */
	$.extend($.app.method, { tip:
		function (title, msg, icon, timeout, showType) {
			var text = []
			text.push('<div class="messager-icon messager-');
			text.push(icon || 'info');
			text.push('"></div>');
			text.push('<div>' + msg + '</div>');
			$.messager.show({
				title: title || '提示信息',
				msg: text.join(''),
				timeout: timeout || 3000,
				showType: showType || 'slide'
				//style: { right:'', bottom:''}
			});
		}
	});

	/* 点击上传 */
	$.extend($.app.method, {'uploadOption': {}}, { upload:
		function(action, callback, check){
			var option = {
				action   : action,
				id       : 'app-upload-when-click-div-' + new Date().getTime(),
				onload   : false,
				dialog   : null,
				callback : callback,
				check    : check,
				method   : {
					callback: function(that){
						if(!$.app.method.uploadOption.onload) return false;
						$.messager.progress('close');

						var text = that.contentWindow.document.body.innerHTML;
						$('#' + $.app.method.uploadOption.id).remove();

						try{
							var obj = $.parseJSON(text);
						}catch(e){}

						if(!obj){
							$.app.method.tip('提示信息', '数据返回格式有误', 'error');
							return false;
						}

						//上次成功后执行回调函数
						if(typeof $.app.method.uploadOption.callback == 'function') return $.app.method.uploadOption.callback(obj);
					},
					submit: function(that){
						//关闭弹出层
						if($.app.method.uploadOption.dialog){
							$($.app.method.uploadOption.dialog).dialog('close');
							$.app.method.uploadOption.dialog = null;
						}

						var check = true;
						//验证上传文件函数
						if(typeof $.app.method.uploadOption.check == 'function'){
							if(!$.app.method.uploadOption.check($('#' + $.app.method.uploadOption.id).find('form input[type="file"]:first').val())){
								check = false;
							}
						}
						//验证通过后直接上传
						if(check){
							$.app.method.uploadOption.onload = true;
							try{
								$(that).parent('form:first').trigger('submit');
								$.messager.progress({text:'正在上传，请稍候...'});
							}catch(e){
								$('#' + $.app.method.uploadOption.id).remove();
								$.app.method.tip('提示信息', e.message, 'warning');
							}

						}
					},
					destroy: function(){
						if($.app.method.uploadOption.dialog) $($.app.method.uploadOption.dialog).dialog('close');
						if($.app.method.uploadOption.id) $('#' + $.app.method.uploadOption.id).remove();
					}
				}
			};

			if(typeof option.action != 'string'){
				$.app.method.tip('提示信息', '未设置上传地址！', 'error');
				return false;
			}
			option.method.destroy();
			$.app.method.uploadOption = option;

			var html = [];
			html.push('<div id="' + $.app.method.uploadOption.id + '" style="display:block;margin:0;padding:0;width:0;height:0;overflow:hidden;">');
			html.push('<iframe onload="$.app.method.uploadOption.method.callback(this)" name="app-upload-when-click-form-submit-target-iframe" style="display:none"></iframe>');
			html.push('<form style="padding:15px 10px;text-align:center" method="post" action="' + $.app.method.uploadOption.action + '" enctype="multipart/form-data" target="app-upload-when-click-form-submit-target-iframe">');
			html.push('<input type="file" name="upload" onchange="$.app.method.uploadOption.method.submit(this)" />');
			html.push('</form>');
			html.push('</div>');

			$(html.join('')).appendTo('body');

			//IE由于安全限制不允许直接用js选择文件并上传
			if ((navigator.userAgent.indexOf('MSIE') >= 0) && (navigator.userAgent.indexOf('Opera') < 0)){
				$.app.method.uploadOption.dialog = $('#' + $.app.method.uploadOption.id).dialog({title: '请选择文件',iconCls: 'icons-application-application_form_add',width: 280,modal: true});
			}else{
				$('#' + $.app.method.uploadOption.id).find('input[type="file"][name="upload"]:first').trigger('click');
			}
			return false;
		}
	});

	/* 图片裁剪 */
	$.extend($.app.method, { crop:
		function(url, img, width, height,callback){
			var id = 'app-image-crop-div-' + new Date().getTime();
			if(typeof width == 'function'){
				callback = width;
				width = null;
			}
			width  = width ? width : 240;
			height = height ? height : 180;
			$('<div id="' + id + '" style="width:' + width + 'px;height:' + height + 'px;display:none"></div>').appendTo('body');
			var croppic = new Croppic(id, {
				modal: true,
				imgEyecandyOpacity: 0.1,
				imgEyecandy: true,
				cropUrl: url,
				loadPicture: img,
				loaderHtml: '<div class="loader bubblingG"><span id="bubblingG_1"></span><span id="bubblingG_2"></span><span id="bubblingG_3"></span></div> ',

				onAfterImgCrop: function(){
					$('#' + id).remove();
					if(callback && croppic.croppedImg[0].src) callback(croppic.croppedImg[0].src);
					croppic.destroy();
				},
				onError: function(err){
					$.app.method.tip('提示信息', err, 'error');
					croppic.reset();
				}
			});

		}
	});
})(jQuery);


/**
 * easyui扩展方法
 */
(function($){
	/**
	 * datagrid扩展
	 */
	$.extend($.fn.datagrid.defaults.editors, {
		image: {
			init: function(container, options){
				if(!options.handler) return null;

				options.width      = options.width  || 240;
				options.height     = options.height || 180;
				options.subfix     = options.subfix || '';
				options.zoom       = typeof options.zoom == 'undefined' ? true : options.zoom;
				options.zoomWidth  = options.zoomWidth  || 160;
				options.zoomHeight = options.zoomHeight || 160;

				var html = ['<input type="image" class="datagrid-editable-input" alt="点击上传图片" title="点击上传图片" style="cursor:pointer"'];
				html.push('onclick=\''+ options.handler + '(' + $.toJSON(options) + ')\'');

				//缩放显示
				if(options.zoom) {
					var width = options.width / options.zoomWidth;
					var height = options.height / options.zoomHeight;
					if (width < 1 && height < 1) {
						html.push('width="' + options.width + '"');
						html.push('height="' + options.height + '"');
					} else {
						if (width >= height) {
							html.push('width="' + options.zoomWidth + '"');
							html.push('height="' + parseInt(options.height / width) + '"');
						} else {
							html.push('width="' + parseInt(options.width / height) + '"');
							html.push('height="' + options.zoomHeight + '"');
						}
					}
				}
				html.push('/>');
				return $(html.join(' ')).appendTo(container);
			},
			getValue: function(target){
				return $(target).attr('src');
			},
			setValue: function(target, value){
				$(target).prop('src', value);
			}
		}
	});

	/**
	 * validatebox扩展
	 */
	$.extend($.fn.validatebox.defaults.rules, {
		equals: {
			validator: function(value,param){
				return value == $(param[0]).val();
			},
			message: '两次密码不一致'
		},
		controller: {
			validator: function(value){
				return /^([A-Z][a-z1-9]*)+$/.test(value);
			},
			message: '必须为首字母大写的驼峰法命名'
		},
		action: {
			validator: function(value){
				return /^[a-z_]*([a-z]+[A-Z]?)+$/.test(value);
			},
			message: '必须为首字母小写的驼峰法命名'
		},
		querystring: {
			validator: function(value){
				return /^([^=&]+=[^=&]+)(&([^=&]+=[^=&]+))*$/.test(value);
			},
			message: '必须为querystring格式'
		},
		zh: {
			validator: function(value){
				return /^[\u4e00-\u9fa5]+$/.test(value);
			},
			message: '必须为中文字符'
		},
		ip: {
			validator: function(value){
				return /^((25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\.){3}(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})$/.test(value);
			},
			message: '必须为IP地址'
		},
		ipv6: {
			validator: function(value){
				return /^\s*((([0-9A-Fa-f]{1,4}:){7}([0-9A-Fa-f]{1,4}|:))|(([0-9A-Fa-f]{1,4}:){6}(:[0-9A-Fa-f]{1,4}|((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){5}(((:[0-9A-Fa-f]{1,4}){1,2})|:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){4}(((:[0-9A-Fa-f]{1,4}){1,3})|((:[0-9A-Fa-f]{1,4})?:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){3}(((:[0-9A-Fa-f]{1,4}){1,4})|((:[0-9A-Fa-f]{1,4}){0,2}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){2}(((:[0-9A-Fa-f]{1,4}){1,5})|((:[0-9A-Fa-f]{1,4}){0,3}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){1}(((:[0-9A-Fa-f]{1,4}){1,6})|((:[0-9A-Fa-f]{1,4}){0,4}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(:(((:[0-9A-Fa-f]{1,4}){1,7})|((:[0-9A-Fa-f]{1,4}){0,5}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:)))(%.+)?\s*$/.test(value);
			},
			message: '必须为IPV6地址'
		},
		idcard: {
			validator: function(value){
				var Wi = [ 7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2, 1 ];// 加权因子;
				var ValideCode = [ 1, 0, 10, 9, 8, 7, 6, 5, 4, 3, 2 ];// 身份证验证位值，10代表X;

				if (value.length == 15) {
					return isValidityBrithBy15IdCard(value);
				}else if (value.length == 18){
					var a_idCard = value.split('');// 得到身份证数组
					if (isValidityBrithBy18IdCard(value)&&isTrueValidateCodeBy18IdCard(a_idCard)) {
						return true;
					}
					return false;
				}
				return false;

				function isTrueValidateCodeBy18IdCard(a_idCard) {
					var sum = 0; // 声明加权求和变量
					if (a_idCard[17].toLowerCase() == 'x') {
						a_idCard[17] = 10;// 将最后位为x的验证码替换为10方便后续操作
					}
					for ( var i = 0; i < 17; i++) {
						sum += Wi[i] * a_idCard[i];// 加权求和
					}
					valCodePosition = sum % 11;// 得到验证码所位置
					if (a_idCard[17] == ValideCode[valCodePosition]) {
						return true;
					}
					return false;
				}
				function isValidityBrithBy18IdCard(idCard18){
					var year = idCard18.substring(6,10);
					var month = idCard18.substring(10,12);
					var day = idCard18.substring(12,14);
					var temp_date = new Date(year,parseFloat(month)-1,parseFloat(day));
					// 这里用getFullYear()获取年份，避免千年虫问题
					if(temp_date.getFullYear()!=parseFloat(year) || temp_date.getMonth()!=parseFloat(month)-1 || temp_date.getDate()!=parseFloat(day)){
						return false;
					}
					return true;
				}
				function isValidityBrithBy15IdCard(idCard15){
					var year =  idCard15.substring(6,8);
					var month = idCard15.substring(8,10);
					var day = idCard15.substring(10,12);
					var temp_date = new Date(year,parseFloat(month)-1,parseFloat(day));
					// 对于老身份证中的你年龄则不需考虑千年虫问题而使用getYear()方法
					if(temp_date.getYear()!=parseFloat(year) || temp_date.getMonth()!=parseFloat(month)-1 || temp_date.getDate()!=parseFloat(day)){
						return false;
					}
					return true;
				}
			},
			message: '必须为身份证号码'
		},
		nothtml: {
			validator: function(value){
				return !/<.*?>/.test(value);
			},
			message: '不能使用HTML标签'
		},
		exts: {
			validator: function(value){
				return /^\w+(,\w+)*$/.test(value);
			},
			message: '必须为文件后缀名格式，多个可用逗号拼接'
		}
	});
})(jQuery);