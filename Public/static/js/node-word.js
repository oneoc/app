var service = {
	http        : require('http'),
	url         : require('url'),
	querystring : require('querystring'),
	fs          : require('fs'),

	config      : {
		timeout : 60000,
		charset : 'utf8',
		port    : 10101,
		host    : '127.0.0.1'
	},

	router : {
		index : function(res, query){
			res.end('Server is running!');
		},
		check : function(res, query){
			var result = {status: 1, info: 'success'};
			result = JSON.stringify(result);
			if(typeof query.callback == 'string'){
				result = query.callback + '(' + result + ')';
			}
			res.end(result);
		},
		word : function(res, query){
			var _this = service;
			var result = {status: 0, info: 'error'};
			if(typeof query.file == 'string'){
				var img = query.file.match(/file:\/\/+(localhost)?(\S+\.(png|jpg|jpeg|gif|bmp))/i);
				console.log(img);
				if(img){
					var base64 = _this.base64_encode(img[2]);
					result.status = 1;
					result.info = 'data:image/' + img[3] + ';base64,' + base64;
				}
			}
			result = JSON.stringify(result);
			if(typeof query.callback == 'string'){
				result = query.callback + '(' + result + ')';
			}
			res.end(result);
		}
	},

	start : function(){
		var _this  = this;

		var Server = _this.http.createServer(function (req, res) {
			var URL = _this.url.parse(req.url);
			var receive = [];

			var router = null;
			switch(URL.pathname){
				case '/word':
					router = _this.router.word;
					break;
				case '/check':
					router = _this.router.check;
					break;
				default:
					router = _this.router.index;
			}

			req.setEncoding(_this.config.charset);
			req.addListener('data', function(data) {
				receive.push(data);
			});
			res.writeHead(200, {'Content-Type': 'text/plain'});

			res.on("close",function(){
				console.log("res closed");
			});
			req.on("close",function(){
				console.log("req closed");
			});
			req.addListener('end', function() {
				router(res, _this.querystring.parse(URL.query));
			});
		});

		Server.listen(_this.config.port, _this.config.host, 1024);

		Server.setTimeout(_this.config.timeout, function(cli){
			cli.end('timeout\n');
		});
		console.log('Server running at http://' + _this.config.host + ':' + _this.config.port);
	},

	//base64
	base64_encode : function(file){
		var bitmap = this.fs.readFileSync(file);
		return new Buffer(bitmap).toString('base64');
	}
};
service.start();