//Вспомогательный метод myFormat
Object.defineProperty(Object.prototype, 'myFormat', {writable: true, value:
	function() {
		var str = '' + JSON.stringify(this, null, 4);
		//TABS
		str = str.replace(/((?!\r\n)[\s\S]+?)($|(?:\r\n))/g, function (s, STR, CRLN, POS) {
			return STR.replace(/([^\t]*?)\t/g, function (s, STR, POS) {
				return STR + (new Array(4 - (STR.length + 4 ) % 4 + 1)).join(' ');
			}) + CRLN;
		});
		//LN
		str = str.replace(/\n/g, '<br/>');
		//SPACES
		return str.replace(/ +/g, function (s) {
			return (s.length==1) ? (' ') : ((new Array(s.length)).join('&nbsp;') + ' ');
		});
	}
});

//Устанавливаем конфигурацию
myConfig = {};
//Конфигурация пользователя (глобальная)
myConfig.server = {
	port		: 2020,
	isDebug		: true,		//Сообшения сервера
};
//Конфигурация модуля "input-formdata"
myConfig.input = {
	//Папка временных (загружаемых) файлов
	tmpDir 		: __dirname + '/_tmp/',
	//Ограничение данных, байт (0-без ограничения)			
	maxSize		: 1024*1024,
	//Срок хранения временных файлов, сек					
	storageTime	: 5*60, 					
	//Режим отладки
	isDebug		: false,						
};
//Конфигурация модуля "output-view"
myConfig.output = {
	//Папка отображений (Абсолюьный адрес)
	dir 		: __dirname + '/views/',
	//Очищать код		
	clear 		: false,
	//Режим отладки
	isDebug		: false,						
};

//Модуль получения данных
var input = require('input-formdata')(myConfig.input);
//Модуль вывода шаблонов
var output = require('output-view')(myConfig.output);

//Формируем задачу
var app = function(req, res) {
	
	//Игнорируем запрос favicon.ico
	var url = req.url.split('/');
	if (url[1]=='favicon.ico') return;

	if (myConfig.server.isDebug) {
		console.log('\nПолучен запрос req.url', req.url);
		console.time('app');	//Установим метку времени
	}
	//Подключаем и запускаем модуль input -->req.input
	input.start (req, res, function () {
		if (myConfig.server.isDebug) console.log('Получены данные ' + req.method + ':', req.input.toString());
		
		
		var url = req.url.split('?').shift().split('/');
		if (url[1]=='test') {
			if (req.method=='POST') {
				//Очищаем папку uploads
				require('fs').readdirSync(__dirname + '/uploads/').forEach (function (filename) {
					require('fs').unlinkSync(__dirname + '/uploads/' + filename);
				});
				//Перемещаем один загруженный файл
				if (req.input.parse.userfile) {
					req.input.parse.userfile.replaceTo('/uploads/' + req.input.parse.userfile['name'] + req.input.parse.userfile['ext']);
				};
				//Перемещаем несколько загруженных файлов
				if (req.input.parse.userfiles) {
					for (var key in req.input.parse.userfiles) {
						if (req.input.parse.userfiles[key]) req.input.parse.userfiles[key].replaceTo('/uploads/' + req.input.parse.userfiles[key]['name'] + req.input.parse.userfiles[key]['ext']);
					}
				};
				if (req.headers['x-requested-with']=='XmlHttpRequest') {
					//Если контроллер вызван через XMLHttpRequest
					res.writeHead(200, {'Content-Type': 'text/plain; charset=utf-8'});
					res.write(req.input.parse.myFormat());
					res.end();
					return
				}
			};
			//Выводим файл с результататом
			res.writeHead(200, {'Content-Type': 'text/html; charset=utf-8'});
			res.write(
				output.view({
					file : 'result.php',
					data : {
						$result : req.input.parse.myFormat()
					}
				})
			);
			res.end();
		} else {
			//Выводим файл с тестами
			res.writeHead(200, {'Content-Type': 'text/html; charset=utf-8'});
			res.write(
				output.view({
					file : 'home.php',
					data : {
						$test 		: Number(url[1] || 0),
						$subtest 	: Number(url[2] || 0)
					}
				})
			);
			res.end();
		}
	});
};
//Создаем и запускаем сервер для задачи
var server = require('http').createServer(app);
server.listen(myConfig.server.port);
//Отображаем информацию о старте сервера
if (myConfig.server.isDebug) console.log('Server start on port ' + myConfig.server.port + ' ...');
