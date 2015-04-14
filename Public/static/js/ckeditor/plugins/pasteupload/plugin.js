CKEDITOR.plugins.add( 'pasteupload', {
	init : function( editor ) {
		var loading = CKEDITOR.getUrl(CKEDITOR.plugins.get('pasteupload').path+'images/loading.gif');

		if (editor.addFeature) {
			editor.addFeature({
				allowedContent: 'img[!src,id];'
			});
		}

		var filrefoxUpload = function(data, id, subfix){
			var url    = editor.config.filebrowserImageUploadUrl;
			if (url.indexOf("?") == -1)
				url += "?";
			else
				url += "&";

			url += 'CKEditor=' + editor.name + '&CKEditorFuncNum=2&langCode=' + editor.langCode;

			var xhr = new XMLHttpRequest();

			xhr.open("POST", url);
			xhr.onload = function() {
				// Upon finish, get the url and update the file
				var imageUrl = xhr.responseText.match(/2,\s*['"](.*?)['"],/)[1];
				var theImage = editor.document.getById( id );
				theImage.data('cke-saved-src', imageUrl);
				theImage.setAttribute('src', imageUrl);
				theImage.removeAttribute( id );
			}

			var BOUNDARY = "---------------------------1966284435497298061834782736";
			var rn = "\r\n";
			var req = "--" + BOUNDARY;
			req += rn + "Content-Disposition: form-data; name=\"upload\"";
			var bin = window.atob( data );
			req += "; filename=\"" + id + "." + subfix + "\"" + rn + "Content-type: image/" + subfix;
			req += rn + rn + bin + rn + "--" + BOUNDARY;
			req += "--";
			xhr.setRequestHeader("Content-Type", "multipart/form-data; boundary=" + BOUNDARY);
			xhr.sendAsBinary(req);
		};

		var upload = function(data, id){
			if(!editor.config.imagePasteUploadEnabled || !editor.config.imageRemoteUploadUrl){
				var theImage = editor.document.getById( id );
				theImage.removeAttribute( id );
				return;
			}

			//firefox直接提交
			if (window.navigator.userAgent.indexOf('Firefox') >= 0){
				var matchs = data.match(/data:image\/(\w+);base64,(.*)$/i);
				if(!matchs) return;
				return filrefoxUpload(matchs[2], id, matchs[1]);
			}

			$.ajax({
				type     : 'post',
				url      : editor.config.imageRemoteUploadUrl,
				data     : {url : data},
				dataType : 'json',
				timeout  : 600000,
				success  : function(res){
					var theImage = editor.document.getById( id );
					if(!res.status){
						theImage.removeAttribute( id );
						alert("图片上传失败");
						return;
					}

					theImage.data('cke-saved-src', res.info);
					theImage.setAttribute('src', res.info);
					theImage.removeAttribute( id );
				},
				error : function(){
					alert("图片上传失败");
					var theImage = editor.document.getById( id );
					theImage.removeAttribute( id );
				}
			});
		};

		//粘贴事件
		editor.on( 'paste', function(e) {
			var data = e.data,
				html = (data.html || ( data.type && data.type=='html' && data.dataValue));

			if (!html) return;

			// 特殊浏览器处理
			if (CKEDITOR.env.webkit && (html.indexOf("webkit-fake-url")>0) ) {
				alert('抱歉，图片贴Safari不可用');
				html = html.replace( /<img.*?src="webkit-fake-url:.*?">/g, '');
			}

			//远程图片上传
			html = html.replace( /<img.*?src=['"](http|https|ftp):\/\/.*?['"].*?>/gi, function( img ) {
				if(!editor.config.imagePasteUploadEnabled) return;
				if(!editor.config.imageRemoteUploadUrl) return;

				if(!img.match( /<img.*?src=['"](http|https|ftp):\/\/.*?['"].*?>/i)) return;
				if(img.indexOf(loading) > -1) return;

				var matchs = img.match(/src=['"](.*?)['"]/i);
				var src = matchs[1];

				//过滤不用是上传的图片地址
				if(editor.config.imageRemoteUploadBaseHref && src.indexOf(editor.config.imageRemoteUploadBaseHref) > -1) return;
				var id  = CKEDITOR.tools.getNextId();

				$.ajax({
					type     : 'post',
					url      : editor.config.imageRemoteUploadUrl,
					data     : {url : src},
					dataType : 'json',
					timeout  : 600000,
					success  : function(res){
						var theImage = editor.document.getById( id );
						if(!res.status){
							theImage.removeAttribute( id );
							alert("远程图片抓取失败");
							return;
						}

						theImage.data('cke-saved-src', res.info);
						theImage.setAttribute('src', res.info);
						theImage.removeAttribute( id );
					},
					error : function(){
						alert("远程图片抓取失败");
						var theImage = editor.document.getById( id );
						theImage.removeAttribute( id );
					}
				});
				setTimeout(function(){
					var theImage = editor.document.getById( id );
					theImage.setAttribute('src',loading);
				}, 1);
				return img.replace(/\/?>/, ' id="' + id + '">');
			});

			//word图片处理
			html = html.replace( /<img.*?src=['"]file:\/\/.*?['"].*?>/gi, function( img ) {
				if(!editor.config.wordImagePasteApiUrl) return;

				if(!img.match(/<img.*?src=['"]file:\/\/.*?['"].*?>/i)) return;
				if(img.indexOf(loading) > -1) return;

				var id  = CKEDITOR.tools.getNextId();
				var api = editor.config.wordImagePasteApiUrl;

				$.ajax({
					type     : 'get',
					url      : api + '/check',
					dataType : 'jsonp',
					jsonp    : 'callback',
					timeout  : 1000,
					success  : function(){
						var matchs = img.match(/"(file:\/\/.*?\.(.*?))"/i);
						var subfix = matchs[2];
						var src    = matchs[1];

						$.getJSON(api + '/word?file=' + src + '&callback=?', function(res){
							var theImage = editor.document.getById( id );
							if(!res.status){
								theImage.removeAttribute( id );
								return;
							}
							if(!editor.config.imagePasteUploadEnabled){
								theImage.data('cke-saved-src', res.info);
								theImage.setAttribute('src', res.info);
								theImage.removeAttribute( id );
								return;
							}
							upload(res.info, id);
						});

					},
					error : function() {
						alert('未开启word图片服务');
						var theImage = editor.document.getById( id );
						theImage.removeAttribute( id );
					}
				});

				setTimeout(function(){
					var theImage = editor.document.getById( id );
					theImage.setAttribute('src',loading);
				}, 1);
				return img.replace(/\/?>/, ' id="' + id + '">');
			});

			//上传base64格式图片
			html = html.replace( /<img.*?src=['"]data:image\/\w+;base64,.*?['"].*?>/gi, function( img ) {
				if(!editor.config.imagePasteUploadEnabled) return;
				if(img.indexOf(loading) > -1) return;

				var matchs = img.match(/"(data:image\/(\w+);base64,.*?)"/i);
				if(!matchs) return;

				var subfix = matchs[2];
				var data   = matchs[1];

				var id     = CKEDITOR.tools.getNextId();

				upload(data, id);

				setTimeout(function(){
					var theImage = editor.document.getById( id );
					theImage.setAttribute('src',loading);
				}, 1);
				return img.replace(/\/?>/, ' id="' + id + '">');
			});

			if (e.data.html)
				e.data.html = html;
			else
				e.data.dataValue = html;
		});

	}
} );
